<?php

namespace App\Console\Commands;

use App\Models\Pharmacy\DrugPrice;
use App\Models\Pharmacy\Drugs\ConsumptionLogDetail;
use App\Models\Pharmacy\Drugs\DrugStock;
use App\Models\Pharmacy\Drugs\DrugStockCard;
use App\Models\Pharmacy\Drugs\DrugStockLog;
use App\Models\Pharmacy\Drugs\StockReclassification;
use App\Models\Pharmacy\PharmLocation;
use App\Models\References\ChargeCode;
use App\Models\StockAdjustment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Throwable;

class ReclassifyInventoryChargeCode extends Command
{
    protected $signature = 'pharmacy:reclassify-charge-code
                            {source : Existing charge code to remove inventory from}
                            {destination : Charge code to receive inventory}
                            {--user-id= : User responsible for this correction}
                            {--commit : Apply the reclassification; without this option only a preview is shown}';

    protected $description = 'Preview or execute a system-wide inventory charge-code reclassification';

    private $priceTimestampOffset = 0;

    public function handle()
    {
        $sourceCode = strtoupper(trim((string) $this->argument('source')));
        $destinationCode = strtoupper(trim((string) $this->argument('destination')));
        $userId = $this->option('user-id');

        try {
            $this->validateRequest($sourceCode, $destinationCode, $userId);
            $preview = $this->sourceSnapshot($sourceCode);
            $this->displaySnapshot($preview, $sourceCode, $destinationCode);

            if (!$this->option('commit')) {
                $this->info('PREVIEW ONLY. No inventory records were changed.');
                $this->comment('Run the same command with --commit after reviewing this snapshot.');

                return 0;
            }

            $this->validateOperationalState($preview);
            $reference = $this->makeReference();
            $result = DB::connection('hospital')->transaction(function () use (
                $sourceCode,
                $destinationCode,
                $userId,
                $preview,
                $reference
            ) {
                $locked = DrugStock::where('chrgcode', $sourceCode)
                    ->where('stock_bal', '>', 0)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                $lockedSnapshot = $this->snapshotFromStocks($locked);
                if ($this->snapshotHash($preview) !== $this->snapshotHash($lockedSnapshot)) {
                    throw new RuntimeException(
                        'Source inventory changed after preview. Nothing was transferred; run the preview again.'
                    );
                }

                $locationConsumptions = $this->activeConsumptionIds($locked->pluck('loc_code')->unique());
                $rows = [];

                foreach ($locked as $sourceStock) {
                    $rows[] = $this->transferStock(
                        $sourceStock,
                        $sourceCode,
                        $destinationCode,
                        (int) $userId,
                        $locationConsumptions[(string) $sourceStock->loc_code],
                        $reference
                    );
                }

                $remaining = DrugStock::where('chrgcode', $sourceCode)
                    ->where('stock_bal', '>', 0)
                    ->sum('stock_bal');
                $debited = collect($rows)->sum('quantity');
                $credited = collect($rows)->sum('credited_quantity');

                if ((float) $remaining !== 0.0 || !$this->sameDecimal($debited, $credited)) {
                    throw new RuntimeException('Post-transfer reconciliation failed. The transaction was rolled back.');
                }

                return [
                    'reference' => $reference,
                    'rows' => $rows,
                    'debited' => $debited,
                    'credited' => $credited,
                ];
            }, 3);

            $reportPath = $this->saveReport($result, $sourceCode, $destinationCode);

            $this->newLine();
            $this->info("Reclassification {$reference} completed successfully.");
            $this->table(
                ['Batches', 'Quantity debited', 'Quantity credited', 'Remaining source inventory'],
                [[count($result['rows']), number_format($result['debited'], 2), number_format($result['credited'], 2), '0.00']]
            );
            $this->info("Reconciliation report: {$reportPath}");

            return 0;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return 1;
        }
    }

    private function validateRequest($sourceCode, $destinationCode, $userId)
    {
        if ($sourceCode === '' || $destinationCode === '') {
            throw new RuntimeException('Source and destination charge codes are required.');
        }

        if ($sourceCode === $destinationCode) {
            throw new RuntimeException('Source and destination charge codes must be different.');
        }

        foreach ([$sourceCode, $destinationCode] as $code) {
            $valid = ChargeCode::where('chrgcode', $code)
                ->where('bentypcod', 'DRUME')
                ->where('chrgstat', 'A')
                ->exists();

            if (!$valid) {
                throw new RuntimeException("Charge code {$code} does not exist or is not an active drug charge code.");
            }
        }

        if (!$userId || !ctype_digit((string) $userId) || !User::whereKey($userId)->exists()) {
            throw new RuntimeException('A valid --user-id is required.');
        }
    }

    private function validateOperationalState(array $snapshot)
    {
        $locationIds = collect($snapshot)->pluck('loc_code')->unique()->values();
        $locations = PharmLocation::withTrashed()->whereIn('id', $locationIds)->get()->keyBy('id');

        foreach ($locationIds as $locationId) {
            $location = $locations->get($locationId);
            if (!$location) {
                throw new RuntimeException("Pharmacy location {$locationId} does not exist.");
            }
            if (!(bool) $location->under_maintenance) {
                throw new RuntimeException(
                    "Location {$locationId} ({$location->description}) must be under maintenance before committing."
                );
            }
        }

        $this->activeConsumptionIds($locationIds);
    }

    private function activeConsumptionIds(Collection $locationIds)
    {
        $result = [];
        foreach ($locationIds as $locationId) {
            $active = ConsumptionLogDetail::where('loc_code', $locationId)
                ->where('status', 'A')
                ->get();

            if ($active->count() !== 1) {
                throw new RuntimeException(
                    "Location {$locationId} must have exactly one active consumption period; found {$active->count()}."
                );
            }
            $result[(string) $locationId] = $active->first()->id;
        }

        return $result;
    }

    private function sourceSnapshot($sourceCode)
    {
        $stocks = DrugStock::where('chrgcode', $sourceCode)
            ->where('stock_bal', '>', 0)
            ->orderBy('loc_code')
            ->orderBy('dmdcomb')
            ->orderBy('dmdctr')
            ->orderBy('exp_date')
            ->orderBy('lot_no')
            ->orderBy('id')
            ->get();

        if ($stocks->isEmpty()) {
            throw new RuntimeException("No positive inventory exists under charge code {$sourceCode}.");
        }

        return $this->snapshotFromStocks($stocks);
    }

    private function snapshotFromStocks(Collection $stocks)
    {
        return $stocks->map(function (DrugStock $stock) {
            $price = $this->priceForStock($stock, $stock->chrgcode);

            return [
                'stock_id' => (int) $stock->id,
                'loc_code' => (int) $stock->loc_code,
                'dmdcomb' => (string) $stock->dmdcomb,
                'dmdctr' => (string) $stock->dmdctr,
                'drug' => (string) $stock->drug_concat,
                'lot_no' => (string) $stock->lot_no,
                'exp_date' => Carbon::parse($stock->exp_date)->format('Y-m-d'),
                'quantity' => $this->decimal($stock->stock_bal),
                'unit_cost' => $this->decimal($price->acquisition_cost ?: $price->dmduprice, 4),
                'value' => $this->decimal(
                    (float) $stock->stock_bal * (float) ($price->acquisition_cost ?: $price->dmduprice),
                    4
                ),
                'dmdprdte' => Carbon::parse($stock->dmdprdte)->format('Y-m-d H:i:s.u'),
            ];
        })->all();
    }

    private function displaySnapshot(array $snapshot, $sourceCode, $destinationCode)
    {
        $this->info("Inventory reclassification preview: {$sourceCode} -> {$destinationCode}");
        $this->table(
            ['Stock ID', 'Location', 'Drug', 'Lot', 'Expiry', 'Quantity', 'Unit cost', 'Value'],
            collect($snapshot)->map(function ($row) {
                return [
                    $row['stock_id'],
                    $row['loc_code'],
                    $row['drug'] ?: "{$row['dmdcomb']}/{$row['dmdctr']}",
                    $row['lot_no'] ?: '-',
                    $row['exp_date'],
                    number_format($row['quantity'], 2),
                    number_format($row['unit_cost'], 4),
                    number_format($row['value'], 4),
                ];
            })->all()
        );

        $this->table(
            ['Batches', 'Locations', 'Total quantity', 'Total inventory value'],
            [[
                count($snapshot),
                collect($snapshot)->pluck('loc_code')->unique()->count(),
                number_format(collect($snapshot)->sum('quantity'), 2),
                number_format(collect($snapshot)->sum('value'), 4),
            ]]
        );
    }

    private function transferStock(
        DrugStock $sourceStock,
        $sourceCode,
        $destinationCode,
        $userId,
        $consumptionId,
        $reference
    ) {
        $sourcePrice = $this->priceForStock($sourceStock, $sourceCode);
        $quantity = (float) $sourceStock->stock_bal;
        $sourceBefore = $quantity;

        $destinationStock = $this->findCompatibleDestination($sourceStock, $sourcePrice, $destinationCode);
        if (!$destinationStock) {
            $destinationStock = $this->createDestinationStock($sourceStock, $sourcePrice, $destinationCode);
        }

        $destinationStock = DrugStock::whereKey($destinationStock->id)->lockForUpdate()->firstOrFail();
        $destinationPrice = $this->priceForStock($destinationStock, $destinationCode);
        if (!$this->pricesMatch($sourcePrice, $destinationPrice)) {
            throw new RuntimeException("Destination stock {$destinationStock->id} changed or has incompatible pricing.");
        }

        $destinationBefore = (float) $destinationStock->stock_bal;
        $sourceStock->stock_bal = 0;
        $sourceStock->save();

        $destinationStock->stock_bal = $destinationBefore + $quantity;
        $destinationStock->save();

        $this->recordLogs(
            $sourceStock,
            $destinationStock,
            $sourcePrice,
            $destinationPrice,
            $quantity,
            $consumptionId,
            $reference
        );

        StockAdjustment::create([
            'stock_id' => $sourceStock->id,
            'user_id' => $userId,
            'from_qty' => $sourceBefore,
            'to_qty' => 0,
        ]);
        StockAdjustment::create([
            'stock_id' => $destinationStock->id,
            'user_id' => $userId,
            'from_qty' => $destinationBefore,
            'to_qty' => $destinationBefore + $quantity,
        ]);

        StockReclassification::create([
            'reference_no' => $reference,
            'source_stock_id' => $sourceStock->id,
            'destination_stock_id' => $destinationStock->id,
            'user_id' => $userId,
            'loc_code' => $sourceStock->loc_code,
            'dmdcomb' => $sourceStock->dmdcomb,
            'dmdctr' => $sourceStock->dmdctr,
            'source_chrgcode' => $sourceCode,
            'destination_chrgcode' => $destinationCode,
            'quantity' => $quantity,
            'unit_cost' => $sourcePrice->acquisition_cost ?: $sourcePrice->dmduprice,
            'source_before' => $sourceBefore,
            'source_after' => 0,
            'destination_before' => $destinationBefore,
            'destination_after' => $destinationBefore + $quantity,
            'executed_at' => now(),
        ]);

        return [
            'source_stock_id' => $sourceStock->id,
            'destination_stock_id' => $destinationStock->id,
            'loc_code' => $sourceStock->loc_code,
            'dmdcomb' => $sourceStock->dmdcomb,
            'dmdctr' => $sourceStock->dmdctr,
            'drug' => $sourceStock->drug_concat,
            'lot_no' => $sourceStock->lot_no,
            'exp_date' => Carbon::parse($sourceStock->exp_date)->format('Y-m-d'),
            'quantity' => $quantity,
            'credited_quantity' => $quantity,
            'unit_cost' => (float) ($sourcePrice->acquisition_cost ?: $sourcePrice->dmduprice),
            'source_before' => $sourceBefore,
            'source_after' => 0,
            'destination_before' => $destinationBefore,
            'destination_after' => $destinationBefore + $quantity,
        ];
    }

    private function findCompatibleDestination(DrugStock $source, DrugPrice $sourcePrice, $destinationCode)
    {
        $candidates = DrugStock::where('dmdcomb', $source->dmdcomb)
            ->where('dmdctr', $source->dmdctr)
            ->where('loc_code', $source->loc_code)
            ->where('chrgcode', $destinationCode)
            ->whereDate('exp_date', Carbon::parse($source->exp_date)->format('Y-m-d'))
            ->where('lot_no', $source->lot_no)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $compatible = $candidates->filter(function (DrugStock $candidate) use (
            $source,
            $sourcePrice,
            $destinationCode
        ) {
            $candidatePrice = $this->priceForStock($candidate, $destinationCode);

            return $this->sameDecimal($source->retail_price, $candidate->retail_price, 4)
                && $this->pricesMatch($sourcePrice, $candidatePrice);
        })->values();

        if ($compatible->count() > 1) {
            throw new RuntimeException(
                "Ambiguous destination for source stock {$source->id}: {$compatible->count()} compatible rows found."
            );
        }

        return $compatible->first();
    }

    private function createDestinationStock(DrugStock $source, DrugPrice $sourcePrice, $destinationCode)
    {
        $destination = $source->replicate([
            'id',
            'chrgcode',
            'stock_bal',
            'beg_bal',
            'dmdprdte',
            'created_at',
            'updated_at',
        ]);
        $destination->chrgcode = $destinationCode;
        $destination->stock_bal = 0;
        $destination->beg_bal = 0;
        $destination->dmdprdte = null;
        $destination->save();

        $price = $sourcePrice->replicate();
        $price->dmdcomb = $sourcePrice->dmdcomb;
        $price->dmhdrsub = $destinationCode;
        $price->dmdprdte = $this->nextPriceTimestamp();
        $price->stock_id = $destination->id;
        $price->save();

        $destination->dmdprdte = $price->dmdprdte;
        $destination->save();

        return $destination;
    }

    private function priceForStock(DrugStock $stock, $chargeCode)
    {
        $price = DrugPrice::where('dmdcomb', $stock->dmdcomb)
            ->where('dmdctr', $stock->dmdctr)
            ->where('dmhdrsub', $chargeCode)
            ->where('dmdprdte', $stock->dmdprdte)
            ->first();

        if (!$price) {
            throw new RuntimeException("Stock {$stock->id} has no consistent price record.");
        }

        return $price;
    }

    private function pricesMatch(DrugPrice $left, DrugPrice $right)
    {
        $fields = [
            'dmduprice',
            'dmselprice',
            'acquisition_cost',
            'mark_up',
            'compounding_fee',
            'retail_price',
        ];

        foreach ($fields as $field) {
            if (!$this->sameDecimal($left->{$field}, $right->{$field}, 4)) {
                return false;
            }
        }

        return (bool) $left->has_compounding === (bool) $right->has_compounding;
    }

    private function recordLogs(
        DrugStock $source,
        DrugStock $destination,
        DrugPrice $sourcePrice,
        DrugPrice $destinationPrice,
        $quantity,
        $consumptionId,
        $reference
    ) {
        $sourceLog = DrugStockLog::firstOrNew([
            'loc_code' => $source->loc_code,
            'dmdcomb' => $source->dmdcomb,
            'dmdctr' => $source->dmdctr,
            'chrgcode' => $source->chrgcode,
            'unit_cost' => $sourcePrice->acquisition_cost ?: $sourcePrice->dmduprice,
            'unit_price' => $sourcePrice->dmselprice,
            'consumption_id' => $consumptionId,
        ]);
        $sourceLog->dmdprdte = $source->dmdprdte;
        $sourceLog->transferred = (float) $sourceLog->transferred + $quantity;
        $sourceLog->save();

        $destinationLog = DrugStockLog::firstOrNew([
            'loc_code' => $destination->loc_code,
            'dmdcomb' => $destination->dmdcomb,
            'dmdctr' => $destination->dmdctr,
            'chrgcode' => $destination->chrgcode,
            'unit_cost' => $destinationPrice->acquisition_cost ?: $destinationPrice->dmduprice,
            'unit_price' => $destinationPrice->dmselprice,
            'consumption_id' => $consumptionId,
        ]);
        $destinationLog->dmdprdte = $destination->dmdprdte;
        $destinationLog->received = (float) $destinationLog->received + $quantity;
        $destinationLog->save();

        $date = now()->format('Y-m-d');
        $sourceCard = DrugStockCard::firstOrNew([
            'chrgcode' => $source->chrgcode,
            'loc_code' => $source->loc_code,
            'dmdcomb' => $source->dmdcomb,
            'dmdctr' => $source->dmdctr,
            'exp_date' => $source->exp_date,
            'stock_date' => $date,
            'drug_concat' => $source->drug_concat,
            'dmdprdte' => $source->dmdprdte,
            'io_trans_ref_no' => $reference,
        ]);
        if (!$sourceCard->exists) {
            $sourceCard->reference = $quantity;
            $sourceCard->bal = $quantity;
        }
        $sourceCard->iss = (float) $sourceCard->iss + $quantity;
        $sourceCard->bal = (float) $sourceCard->bal - $quantity;
        $sourceCard->save();

        $destinationCard = DrugStockCard::firstOrNew([
            'chrgcode' => $destination->chrgcode,
            'loc_code' => $destination->loc_code,
            'dmdcomb' => $destination->dmdcomb,
            'dmdctr' => $destination->dmdctr,
            'exp_date' => $destination->exp_date,
            'stock_date' => $date,
            'drug_concat' => $destination->drug_concat,
            'dmdprdte' => $destination->dmdprdte,
            'io_trans_ref_no' => $reference,
        ]);
        if (!$destinationCard->exists) {
            $destinationBefore = (float) $destination->stock_bal - $quantity;
            $destinationCard->reference = $destinationBefore;
            $destinationCard->bal = $destinationBefore;
        }
        $destinationCard->rec = (float) $destinationCard->rec + $quantity;
        $destinationCard->bal = (float) $destinationCard->bal + $quantity;
        $destinationCard->save();
    }

    private function saveReport(array $result, $sourceCode, $destinationCode)
    {
        $directory = storage_path('app/stock-reclassifications');
        File::ensureDirectoryExists($directory);
        $path = $directory . DIRECTORY_SEPARATOR . $result['reference'] . '.csv';
        $handle = fopen($path, 'w');

        if ($handle === false) {
            throw new RuntimeException("Unable to create reconciliation report at {$path}.");
        }

        fputcsv($handle, [
            'reference_no',
            'source_chrgcode',
            'destination_chrgcode',
            'source_stock_id',
            'destination_stock_id',
            'loc_code',
            'dmdcomb',
            'dmdctr',
            'drug',
            'lot_no',
            'exp_date',
            'quantity',
            'unit_cost',
            'source_before',
            'source_after',
            'destination_before',
            'destination_after',
        ]);

        foreach ($result['rows'] as $row) {
            fputcsv($handle, [
                $result['reference'],
                $sourceCode,
                $destinationCode,
                $row['source_stock_id'],
                $row['destination_stock_id'],
                $row['loc_code'],
                $row['dmdcomb'],
                $row['dmdctr'],
                $row['drug'],
                $row['lot_no'],
                $row['exp_date'],
                $row['quantity'],
                $row['unit_cost'],
                $row['source_before'],
                $row['source_after'],
                $row['destination_before'],
                $row['destination_after'],
            ]);
        }
        fclose($handle);

        return $path;
    }

    private function snapshotHash(array $snapshot)
    {
        $snapshot = collect($snapshot)->sortBy('stock_id')->values()->all();

        return hash('sha256', json_encode($snapshot));
    }

    private function nextPriceTimestamp()
    {
        $this->priceTimestampOffset++;

        return now()->addSeconds($this->priceTimestampOffset);
    }

    private function makeReference()
    {
        return 'RECLASS-' . now()->format('Ymd-His') . '-' . strtoupper(substr(uniqid(), -6));
    }

    private function decimal($value, $precision = 2)
    {
        return round((float) $value, $precision);
    }

    private function sameDecimal($left, $right, $precision = 2)
    {
        return $this->decimal($left, $precision) === $this->decimal($right, $precision);
    }
}
