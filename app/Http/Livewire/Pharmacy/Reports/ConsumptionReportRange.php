<?php

namespace App\Http\Livewire\Pharmacy\Reports;

use Exception;
use Carbon\Carbon;
use Livewire\Component;
use App\Models\DrugManualLogItem;
use Illuminate\Support\Facades\DB;
use App\Models\DrugManualLogHeader;
use Illuminate\Support\Facades\Log;
use App\Models\References\ChargeCode;
use App\Models\Pharmacy\PharmLocation;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use App\Models\Pharmacy\Drugs\PharmConsumptionGenerated;

class ConsumptionReportRange extends Component
{
    use LivewireAlert;

    public $month, $filter_charge = 'DRUME,Drugs and Medicines (Regular)';
    public $date_from, $date_to;
    public $location_id;
    public $report_id;
    public $ended = NULL;
    public $active_report;
    public $active_consumption = [];
    public $processing = false;

    // For editable fields
    public $editableFields = [];
    public $editMode = false;

    public function updatedReportId()
    {
        $cons = DrugManualLogHeader::find($this->report_id);
        $this->ended = $cons ? $cons->consumption_to : NULL;
    }

    public function render()
    {
        $charge_codes = ChargeCode::where('bentypcod', 'DRUME')
            ->where('chrgstat', 'A')
            ->whereIn('chrgcode', app('chargetable'))
            ->get();

        $filter_charge = explode(',', $this->filter_charge);

        $cons = DrugManualLogHeader::where('loc_code', auth()->user()->pharm_location_id)
            ->where('is_custom', true)
            ->latest()
            ->get();

        // Now directly pull from the pharm_consumption_generated table
        $drugs_issued = [];
        if ($this->report_id) {
            $drugs_issued = PharmConsumptionGenerated::where('consumption_id', $this->report_id)
                ->where('chrgcode', $filter_charge[0])
                ->where('loc_code', session('pharm_location_id'))
                ->orderBy('drug_concat')
                ->get();
        }

        $locations = PharmLocation::all();

        return view('livewire.pharmacy.reports.consumption-report-range', [
            'charge_codes' => $charge_codes,
            'current_charge' => $filter_charge[1],
            'drugs_issued' => $drugs_issued,
            'locations' => $locations,
            'cons' => $cons,
        ]);
    }

    public function mount()
    {
        $this->date_from = date('Y-m-d', strtotime(now()));
        $this->date_to = date('Y-m-d', strtotime(now()));
        $this->location_id = session('pharm_location_id');
    }

    /**
     * Toggle edit mode
     */
    public function toggleEditMode()
    {
        $this->editMode = !$this->editMode;
        $this->editableFields = [];
    }

    /**
     * Initialize editable field
     */
    public function initializeEditField($recordId, $fieldName, $value)
    {
        $this->editableFields[$recordId][$fieldName] = $value;
    }

    /**
     * Save edited field
     */
    public function saveField($recordId, $fieldName)
    {
        try {
            if (!isset($this->editableFields[$recordId][$fieldName])) {
                return;
            }

            $record = PharmConsumptionGenerated::find($recordId);
            if (!$record) {
                $this->alert('error', 'Record not found.');
                return;
            }

            $value = $this->editableFields[$recordId][$fieldName];

            // Validate value is numeric
            if (!is_numeric($value)) {
                $this->alert('error', 'Value must be a number.');
                return;
            }

            // Update the field
            $record->{$fieldName} = $value;
            $record->save();

            // Update original table for backward compatibility
            DB::table('pharm_drug_stock_logs_copy')
                ->where('consumption_id', $record->consumption_id)
                ->where('loc_code', $record->loc_code)
                ->where('dmdcomb', $record->dmdcomb)
                ->where('dmdctr', $record->dmdctr)
                ->where('chrgcode', $record->chrgcode)
                ->update([
                    $fieldName === 'received_iotrans' ? 'received' : $fieldName => $value,
                    'updated_at' => now()
                ]);

            $this->alert('success', 'Value updated successfully.');
        } catch (Exception $e) {
            $this->alert('error', 'Error updating value: ' . $e->getMessage());
        }
    }

    /**
     * Clear existing consumption data
     */
    public function cleanse()
    {
        // Delete existing records for this consumption_id from our new table
        if ($this->report_id) {
            PharmConsumptionGenerated::where('consumption_id', $this->report_id)->delete();
        }

        // Still need to clean the original table for compatibility
        $cons = DrugManualLogHeader::where('is_custom', true)
            ->where('loc_code', auth()->user()->pharm_location_id)
            ->latest()
            ->first();
        if ($cons) {
            DB::statement("DELETE FROM pharm_drug_stock_logs_copy
                            WHERE consumption_id = $cons->id");
        }

        return;
    }

    /**
     * Generate consumption report header
     */
    public function generateConsumptionHeader()
    {
        // Create or update the header but still using DrugManualLogHeader for compatibility
        $this->active_consumption = DrugManualLogHeader::updateOrCreate([
            'consumption_from' => Carbon::parse($this->date_from)->startOfDay(),
            'consumption_to' => Carbon::parse($this->date_to)->endOfDay(),
            'status' => 'I',
            'loc_code' => auth()->user()->pharm_location_id,
            'is_custom' => true,
        ], [
            'entry_by' => session('user_id'),
        ]);

        $this->report_id = $this->active_consumption->id;

        return $this->active_consumption;
    }

    /**
     * Main function to generate the ending balance report
     */
    public function generate_ending_balance()
    {
        ini_set('max_execution_time', 300); // Increase timeout to 5 minutes
        $this->processing = true;

        try {
            $this->cleanse();
            $active_consumption = $this->generateConsumptionHeader();

            // Store common variables
            $from_date = $active_consumption->consumption_from;
            $to_date = $active_consumption->consumption_to;
            $location_id = auth()->user()->pharm_location_id;
            $filter_charge = explode(',', $this->filter_charge);

            // Process beginning balances
            $this->processBeginningBalances($from_date, $location_id, $active_consumption->id, $filter_charge[0]);

            // Process remaining data
            $this->processIssuances($from_date, $to_date, $location_id, $active_consumption->id, $filter_charge[0]);
            $this->processReturns($from_date, $to_date, $location_id, $active_consumption->id, $filter_charge[0]);
            $this->processIOTransfers($from_date, $to_date, $location_id, $active_consumption->id, $filter_charge[0]);
            $this->processDeliveries($from_date, $to_date, $location_id, $active_consumption->id, $filter_charge[0]);
            $this->processPullouts($from_date, $to_date, $location_id, $active_consumption->id, $filter_charge[0]);

            $this->alert('success', 'Consumption report has been generated successfully on ' . now());
        } catch (Exception $e) {
            $this->alert('error', 'Error generating report: ' . $e->getMessage());
            Log::error('Error generating report: ' . $e->getMessage());
        }

        $this->processing = false;
    }

    /**
     * Process beginning balances - Using only the first date
     */
    private function processBeginningBalances($date_from, $location_id, $consumption_id, $filter_charge)
    {
        // Get just the exact date for beginning balance
        $beginDate = Carbon::parse($date_from)->startOfDay()->format('Y-m-d');

        // Debug output
        DB::enableQueryLog();

        // Query DrugStockCard directly with a raw query to get beginning balances for the specific date
        $balances = DB::select(
            "
            SELECT
                SUM(reference) as begbal,
                dmdcomb,
                dmdctr,
                dmdprdte,
                chrgcode
            FROM
                pharm_drug_stock_cards
            WHERE
                stock_date = ?
                AND loc_code = ?
                AND dmdprdte IS NOT NULL
                AND chrgcode = ?
            GROUP BY
                dmdcomb, dmdctr, chrgcode, dmdprdte",
            [$beginDate, $location_id, $filter_charge]
        );

        // Debug info
        $query = DB::getQueryLog();
        Log::info("Beginning balance query:", $query);
        Log::info("Records found for beginning balances: " . count($balances));

        // If no records found, try a different approach (get the latest balance before the date)
        if (count($balances) === 0) {
            Log::info("No records found for exact date, trying to get latest balance before the date");

            $balances = DB::select(
                "
                SELECT
                    dmdcomb,
                    dmdctr,
                    chrgcode,
                    dmdprdte,
                    reference as begbal
                FROM
                    pharm_drug_stock_cards a
                WHERE
                    stock_date = (
                        SELECT MAX(stock_date)
                        FROM pharm_drug_stock_cards
                        WHERE dmdcomb = a.dmdcomb
                        AND dmdctr = a.dmdctr
                        AND chrgcode = a.chrgcode
                        AND stock_date <= ?
                    )
                    AND loc_code = ?
                    AND dmdprdte IS NOT NULL
                    AND chrgcode = ?",
                [$beginDate, $location_id, $filter_charge]
            );

            Log::info("Records found with latest balance approach: " . count($balances));
        }

        // Process each record individually
        foreach ($balances as $item) {
            try {
                // Get drug information
                $drug = DB::table('hdmhdr')
                    ->where('dmdcomb', $item->dmdcomb)
                    ->where('dmdctr', $item->dmdctr)
                    ->first(['drug_concat']);

                if (!$drug) {
                    Log::warning("No drug found for dmdcomb: {$item->dmdcomb}, dmdctr: {$item->dmdctr}");
                    continue; // Skip this item if no drug found
                }

                // Get price information
                $price = DB::table('hdmhdrprice')
                    ->where('dmdprdte', $item->dmdprdte)
                    ->first(['acquisition_cost', 'dmselprice']);

                if (!$price) {
                    Log::warning("No price found for dmdprdte: {$item->dmdprdte}");
                    // Use default values if no price found
                    $acq_cost = 0;
                    $dms_price = 0;
                } else {
                    $acq_cost = $price->acquisition_cost;
                    $dms_price = $price->dmselprice;
                }

                // Create or update record
                PharmConsumptionGenerated::updateOrCreate(
                    [
                        'dmdcomb' => $item->dmdcomb,
                        'dmdctr' => $item->dmdctr,
                        'chrgcode' => $item->chrgcode,
                        'consumption_id' => $consumption_id,
                        'loc_code' => $location_id
                    ],
                    [
                        'drug_concat' => $drug ? $drug->drug_concat : 'Unknown Drug',
                        'acquisition_cost' => $acq_cost,
                        'dmselprice' => $dms_price,
                        'beg_bal' => $item->begbal > 0 ? $item->begbal : 0,
                        'purchased' => 0,
                        'received_iotrans' => 0,
                        'transferred_iotrans' => 0,
                        'return_qty' => 0,
                        'ems' => 0,
                        'maip' => 0,
                        'wholesale' => 0,
                        'opdpay' => 0,
                        'pay' => 0,
                        'service' => 0,
                        'konsulta' => 0,
                        'pcso' => 0,
                        'phic' => 0,
                        'caf' => 0,
                        'issue_qty' => 0,
                        'pullout_qty' => 0
                    ]
                );

                Log::info("Added beginning balance for drug: {$drug->drug_concat}, beg_bal: {$item->begbal}");
            } catch (Exception $e) {
                Log::error("Error processing beginning balance for dmdcomb: {$item->dmdcomb}, dmdctr: {$item->dmdctr}. Error: " . $e->getMessage());
            }
        }

        // If still no records, try to get all drugs with zero balances
        $recordCount = PharmConsumptionGenerated::where('consumption_id', $consumption_id)
            ->where('loc_code', $location_id)
            ->count();

        if ($recordCount === 0) {
            Log::info("No records found with either approach, creating zero balances for all drugs in the location");

            // Get all drugs in the inventory for this location
            $allDrugs = DB::select(
                "
                SELECT DISTINCT
                    s.dmdcomb,
                    s.dmdctr,
                    s.chrgcode,
                    s.dmdprdte,
                    drug.drug_concat
                FROM
                    pharm_drug_stocks s
                JOIN
                    hdmhdr drug ON s.dmdcomb = drug.dmdcomb AND s.dmdctr = drug.dmdctr
                WHERE
                    s.loc_code = ?
                    AND s.chrgcode = ?",
                [$location_id, $filter_charge]
            );

            Log::info("Found " . count($allDrugs) . " drugs in inventory for the location");

            foreach ($allDrugs as $drug) {
                try {
                    // Get price information
                    $price = DB::table('hdmhdrprice')
                        ->where('dmdprdte', $drug->dmdprdte)
                        ->first(['acquisition_cost', 'dmselprice']);

                    if (!$price) {
                        Log::warning("No price found for dmdprdte: {$drug->dmdprdte}");
                        // Use default values if no price found
                        $acq_cost = 0;
                        $dms_price = 0;
                    } else {
                        $acq_cost = $price->acquisition_cost;
                        $dms_price = $price->dmselprice;
                    }

                    // Create with zero balance
                    PharmConsumptionGenerated::updateOrCreate(
                        [
                            'dmdcomb' => $drug->dmdcomb,
                            'dmdctr' => $drug->dmdctr,
                            'chrgcode' => $drug->chrgcode,
                            'consumption_id' => $consumption_id,
                            'loc_code' => $location_id
                        ],
                        [
                            'drug_concat' => $drug->drug_concat,
                            'acquisition_cost' => $acq_cost,
                            'dmselprice' => $dms_price,
                            'beg_bal' => 0,
                            'purchased' => 0,
                            'received_iotrans' => 0,
                            'transferred_iotrans' => 0,
                            'return_qty' => 0,
                            'ems' => 0,
                            'maip' => 0,
                            'wholesale' => 0,
                            'opdpay' => 0,
                            'pay' => 0,
                            'service' => 0,
                            'konsulta' => 0,
                            'pcso' => 0,
                            'phic' => 0,
                            'caf' => 0,
                            'issue_qty' => 0,
                            'pullout_qty' => 0
                        ]
                    );
                } catch (Exception $e) {
                    Log::error("Error creating zero balance record: " . $e->getMessage());
                }
            }
        }

        // Final check
        $finalCount = PharmConsumptionGenerated::where('consumption_id', $consumption_id)
            ->where('loc_code', $location_id)
            ->count();

        Log::info("Total records in PharmConsumptionGenerated after beginning balances: $finalCount");
    }

    /**
     * Process issuances
     */
    private function processIssuances($from_date, $to_date, $location_id, $consumption_id, $filter_charge)
    {
        $active_consumption = DrugManualLogHeader::find($this->report_id);
        $from_date = $active_consumption->consumption_from;
        $to_date = $active_consumption->consumption_to;
        $location_id = auth()->user()->pharm_location_id;

        $returns = DB::select("
            SELECT di.pharm_location_id, di.dmdcomb, di.dmdctr, di.charge_code chrgcode, COUNT(*) LineItem, SUM(qty) qty_returned, pri.acquisition_cost unit_cost, pri.dmselprice retail_price
            FROM pharm_delivery_items di
                JOIN hdmhdrprice pri ON di.dmdprdte = pri.dmdprdte
            WHERE di.updated_at BETWEEN '" . $from_date . "' AND '" . $to_date . "'
                AND di.pharm_location_id = '" . $location_id . "'
            GROUP BY pri.acquisition_cost, pri.dmselprice, di.dmdcomb, di.dmdctr, di.charge_code, di.pharm_location_id
        ");

        foreach ($returns as $item) {
            DrugManualLogItem::create([
                'loc_code' => $item->pharm_location_id,
                'dmdcomb' => $item->dmdcomb,
                'dmdctr' => $item->dmdctr,
                'chrgcode' => $item->chrgcode,
                'unit_cost' => $item->unit_cost,
                'unit_price' => $item->retail_price,
                'consumption_id' => $active_consumption->id,

                'purchased' => $item->qty_returned,
            ]);
        }

        $this->alert('success', 'Deliveries recorded successfully ' . now());
    }

    /**
     * Process returns
     */
    private function processReturns($from_date, $to_date, $location_id, $consumption_id, $filter_charge)
    {
        $active_consumption = DrugManualLogHeader::find($this->report_id);
        $from_date = $active_consumption->consumption_from;
        $to_date = $active_consumption->consumption_to;
        $location_id = auth()->user()->pharm_location_id;

        $returns = DB::select("
            SELECT di.pharm_location_id, di.dmdcomb, di.dmdctr, di.charge_code chrgcode, COUNT(*) LineItem, SUM(qty) qty_returned, pri.acquisition_cost unit_cost, pri.dmselprice retail_price
            FROM pharm_delivery_items di
                JOIN hdmhdrprice pri ON di.dmdprdte = pri.dmdprdte
            WHERE di.updated_at BETWEEN '" . $from_date . "' AND '" . $to_date . "'
                AND di.pharm_location_id = '" . $location_id . "'
            GROUP BY pri.acquisition_cost, pri.dmselprice, di.dmdcomb, di.dmdctr, di.charge_code, di.pharm_location_id
        ");

        foreach ($returns as $item) {
            DrugManualLogItem::create([
                'loc_code' => $item->pharm_location_id,
                'dmdcomb' => $item->dmdcomb,
                'dmdctr' => $item->dmdctr,
                'chrgcode' => $item->chrgcode,
                'unit_cost' => $item->unit_cost,
                'unit_price' => $item->retail_price,
                'consumption_id' => $active_consumption->id,

                'purchased' => $item->qty_returned,
            ]);
        }
    }

    /**
     * Process IO Transfers
     */
    private function processIOTransfers($from_date, $to_date, $location_id, $consumption_id, $filter_charge)
    {
        $active_consumption = DrugManualLogHeader::find($this->report_id);
        $from_date = $active_consumption->consumption_from;
        $to_date = $active_consumption->consumption_to;
        $location_id = auth()->user()->pharm_location_id;

        $incoming = DB::select("
                SELECT pit.dmdcomb, pit.dmdctr, drug_concat, pit.chrgcode, pri.acquisition_cost, pri.dmselprice, SUM(qty) qty, [to]
                FROM pharm_io_trans_items pit
                    JOIN hdmhdr as drug ON pit.dmdcomb = drug.dmdcomb AND pit.dmdctr = drug.dmdctr
                    JOIN hdmhdrprice pri ON pit.dmdprdte = pri.dmdprdte
                WHERE [to] = '" . $location_id . "'
                    AND status = 'Received'
                    AND pit.updated_at BETWEEN '" . $from_date . "' AND '" . $to_date . "'
                GROUP BY pri.acquisition_cost, pri.dmselprice, pit.dmdcomb, pit.dmdctr, drug_concat, pit.chrgcode, [to]
            ");

        foreach ($incoming as $item) {
            DrugManualLogItem::create([
                'loc_code' => $item->to,
                'dmdcomb' => $item->dmdcomb,
                'dmdctr' => $item->dmdctr,
                'chrgcode' => $item->chrgcode,
                'unit_cost' => $item->acquisition_cost,
                'unit_price' => $item->dmselprice,
                'consumption_id' => $active_consumption->id,
                'received' => $item->qty,
            ]);
        }

        $outgoing = DB::select("
                SELECT pit.dmdcomb, pit.dmdctr, drug_concat, pit.chrgcode, pri.acquisition_cost, pri.dmselprice, SUM(qty) qty, [to]
                FROM pharm_io_trans_items pit
                    JOIN hdmhdr as drug ON pit.dmdcomb = drug.dmdcomb AND pit.dmdctr = drug.dmdctr
                    JOIN hdmhdrprice pri ON pit.dmdprdte = pri.dmdprdte
                WHERE [from] = '" . $location_id . "'
                    AND status = 'Received'
                    AND pit.updated_at BETWEEN '" . $from_date . "' AND '" . $to_date . "'
                GROUP BY pri.acquisition_cost, pri.dmselprice, pit.dmdcomb, pit.dmdctr, drug_concat, pit.chrgcode, [to]
            ");


        foreach ($outgoing as $item) {
            DrugManualLogItem::create([
                'loc_code' => $item->to,
                'dmdcomb' => $item->dmdcomb,
                'dmdctr' => $item->dmdctr,
                'chrgcode' => $item->chrgcode,
                'unit_cost' => $item->acquisition_cost,
                'unit_price' => $item->dmselprice,
                'consumption_id' => $active_consumption->id,
                'transferred' => $item->qty,
            ]);
        }

        $this->alert('success', 'IO Trans recorded successfully ' . now());
    }

    /**
     * Process deliveries
     */
    private function processDeliveries($from_date, $to_date, $location_id, $consumption_id, $filter_charge)
    {
        $active_consumption = DrugManualLogHeader::find($this->report_id);
        $from_date = $active_consumption->consumption_from;
        $to_date = $active_consumption->consumption_to;
        $location_id = auth()->user()->pharm_location_id;

        $returns = DB::select("
            SELECT i.pullout_qty, p.pharm_location_id, s.dmdcomb, s.dmdctr, s.chrgcode, price.acquisition_cost unit_cost, s.retail_price
            FROM pharm_pull_out_items i
            JOIN pharm_pull_outs p ON i.detail_id = p.id
            JOIN pharm_drug_stocks s ON i.stock_id = s.id
            JOIN hdmhdrprice price ON s.dmdprdte = price.dmdprdte
            WHERE i.updated_at BETWEEN '" . $from_date . "' AND '" . $to_date . "'
                AND p.pharm_location_id = '" . $location_id . "'
        ");

        foreach ($returns as $item) {
            DrugManualLogItem::create([
                'loc_code' => $item->pharm_location_id,
                'dmdcomb' => $item->dmdcomb,
                'dmdctr' => $item->dmdctr,
                'chrgcode' => $item->chrgcode,
                'unit_cost' => $item->unit_cost,
                'unit_price' => $item->retail_price,
                'consumption_id' => $active_consumption->id,

                'pullout_qty' => $item->pullout_qty,
            ]);
        }
        // Implementation left unchanged for brevity
    }

    public function generate_ep()
    {
        $active_consumption = DrugManualLogHeader::find($this->report_id);
        $from_date = $active_consumption->consumption_from;
        $to_date = $active_consumption->consumption_to;
        $location_id = auth()->user()->pharm_location_id;

        $returns = DB::select("
            SELECT di.pharm_location_id, di.dmdcomb, di.dmdctr, di.charge_code chrgcode, COUNT(*) LineItem, SUM(qty) qty_returned, pri.acquisition_cost unit_cost, pri.dmselprice retail_price
            FROM pharm_delivery_items di
                JOIN hdmhdrprice pri ON di.dmdprdte = pri.dmdprdte
            WHERE di.updated_at BETWEEN '" . $from_date . "' AND '" . $to_date . "'
                AND di.pharm_location_id = '" . $location_id . "'
            GROUP BY pri.acquisition_cost, pri.dmselprice, di.dmdcomb, di.dmdctr, di.charge_code, di.pharm_location_id
        ");

        foreach ($returns as $item) {
            DrugManualLogItem::create([
                'loc_code' => $item->pharm_location_id,
                'dmdcomb' => $item->dmdcomb,
                'dmdctr' => $item->dmdctr,
                'chrgcode' => $item->chrgcode,
                'unit_cost' => $item->unit_cost,
                'unit_price' => $item->retail_price,
                'consumption_id' => $active_consumption->id,

                'purchased' => $item->qty_returned,
            ]);
        }

        $this->alert('success', 'Deliveries recorded successfully ' . now());
    }

    public function processPullouts($date_from, $location_id, $consumption_id, $filter_charge)
    {
        $active_consumption = DrugManualLogHeader::find($this->report_id);
        $from_date = $active_consumption->consumption_from;
        $to_date = $active_consumption->consumption_to;
        $location_id = auth()->user()->pharm_location_id;

        $returns = DB::select("
            SELECT i.pullout_qty, p.pharm_location_id, s.dmdcomb, s.dmdctr, s.chrgcode, price.acquisition_cost unit_cost, s.retail_price
            FROM pharm_pull_out_items i
            JOIN pharm_pull_outs p ON i.detail_id = p.id
            JOIN pharm_drug_stocks s ON i.stock_id = s.id
            JOIN hdmhdrprice price ON s.dmdprdte = price.dmdprdte
            WHERE i.updated_at BETWEEN '" . $from_date . "' AND '" . $to_date . "'
                AND p.pharm_location_id = '" . $location_id . "'
        ");

        foreach ($returns as $item) {
            DrugManualLogItem::create([
                'loc_code' => $item->pharm_location_id,
                'dmdcomb' => $item->dmdcomb,
                'dmdctr' => $item->dmdctr,
                'chrgcode' => $item->chrgcode,
                'unit_cost' => $item->unit_cost,
                'unit_price' => $item->retail_price,
                'consumption_id' => $active_consumption->id,

                'pullout_qty' => $item->pullout_qty,
            ]);
        }

        $this->alert('success', 'Pullouts recorded successfully ' . now());
    }
}
