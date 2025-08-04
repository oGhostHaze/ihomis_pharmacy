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
use App\Models\Pharmacy\Drugs\DrugStockCard;
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
        $this->cleanse();
        $active_consumption = $this->generateConsumptionHeader();

        $from_date = $active_consumption->consumption_from;
        $to_date = $active_consumption->consumption_to;
        $location_id = auth()->user()->pharm_location_id;
        $filter_charge = explode(',', $this->filter_charge);

        // Get beginning balances
        $beginningBalances = $this->getBeginningBalances($from_date, $location_id);

        // Get issuances
        $issuances = $this->getIssuances($from_date, $to_date, $location_id);

        // Get returns
        $returns = $this->getReturns($from_date, $to_date, $location_id);

        // Get IO transactions
        $incomingTransfers = $this->getIncomingTransfers($from_date, $to_date, $location_id);
        $outgoingTransfers = $this->getOutgoingTransfers($from_date, $to_date, $location_id);

        // Get deliveries
        $deliveries = $this->getDeliveries($from_date, $to_date, $location_id);

        // Get pullouts
        $pullouts = $this->getPullouts($from_date, $to_date, $location_id);

        // Combine all data and save directly to PharmConsumptionGenerated
        $this->aggregateAndSaveData(
            $active_consumption->id,
            $location_id,
            $filter_charge[0],
            $beginningBalances,
            $issuances,
            $returns,
            $incomingTransfers,
            $outgoingTransfers,
            $deliveries,
            $pullouts
        );

        $this->alert('success', 'Consumption report has been generated successfully on ' . now());
    }

    /**
     * Get beginning balances for drugs (first day reference only)
     */
    private function getBeginningBalances($date_from, $location_id)
    {
        return DB::select("
            SELECT
                first_day_reference as beg_bal,
                dmdcomb,
                dmdctr,
                dmdprdte,
                chrgcode,
                drug_concat,
                acquisition_cost,
                dmselprice
            FROM (
                SELECT
                    dsc.reference as first_day_reference,
                    dsc.dmdcomb,
                    dsc.dmdctr,
                    dsc.dmdprdte,
                    dsc.chrgcode,
                    drug.drug_concat,
                    price.acquisition_cost,
                    price.dmselprice,
                    ROW_NUMBER() OVER (
                        PARTITION BY dsc.dmdcomb, dsc.dmdctr, dsc.chrgcode, dsc.dmdprdte,
                                     price.acquisition_cost, price.dmselprice
                        ORDER BY dsc.stock_date ASC
                    ) as rn
                FROM pharm_drug_stock_cards dsc
                    JOIN hdmhdr drug ON dsc.dmdcomb = drug.dmdcomb AND dsc.dmdctr = drug.dmdctr
                    JOIN hdmhdrprice price ON dsc.dmdprdte = price.dmdprdte
                WHERE dsc.stock_date BETWEEN ? AND ?
                    AND dsc.loc_code = ?
                    AND dsc.dmdprdte IS NOT NULL
            ) ranked
            WHERE rn = 1
        ", [$date_from, Carbon::parse($date_from)->endOfDay(), $location_id]);
    }

    /**
     * Get issuances data
     */
    private function getIssuances($from_date, $to_date, $location_id)
    {
        return DB::select("
            SELECT
                hrxo.loc_code,
                hrxo.dmdcomb,
                hrxo.dmdctr,
                hrxo.orderfrom as chrgcode,
                drug.drug_concat,
                COUNT(*) as LineItem,
                SUM(pchrgqty) as qty_issued,
                pri.acquisition_cost as unit_cost,
                pri.dmselprice as retail_price,
                tx_type
            FROM hrxo
                JOIN hdmhdr drug ON hrxo.dmdcomb = drug.dmdcomb AND hrxo.dmdctr = drug.dmdctr
                JOIN hdmhdrprice pri ON hrxo.dmdprdte = pri.dmdprdte
            WHERE dodtepost BETWEEN ? AND ?
                AND loc_code = ?
                AND hrxo.estatus = 'S'
            GROUP BY
                hrxo.dmdcomb,
                hrxo.dmdctr,
                hrxo.orderfrom,
                hrxo.loc_code,
                drug.drug_concat,
                pri.acquisition_cost,
                pri.dmselprice,
                tx_type
        ", [$from_date, $to_date, $location_id]);
    }

    /**
     * Get returns data
     */
    private function getReturns($from_date, $to_date, $location_id)
    {
        return DB::select("
            SELECT
                hrxo.loc_code,
                hrxo.dmdcomb,
                hrxo.dmdctr,
                hrxo.chrgcode as chrgcode,
                drug.drug_concat,
                COUNT(*) as LineItem,
                SUM(qty) as qty_returned,
                pri.acquisition_cost as unit_cost,
                pri.dmselprice as retail_price
            FROM hrxoreturn hrxo
                JOIN hdmhdr drug ON hrxo.dmdcomb = drug.dmdcomb AND hrxo.dmdctr = drug.dmdctr
                JOIN hdmhdrprice pri ON hrxo.dmdprdte = pri.dmdprdte
            WHERE returndate BETWEEN ? AND ?
                AND loc_code = ?
            GROUP BY
                hrxo.dmdcomb,
                hrxo.dmdctr,
                hrxo.chrgcode,
                hrxo.loc_code,
                drug.drug_concat,
                pri.acquisition_cost,
                pri.dmselprice
        ", [$from_date, $to_date, $location_id]);
    }

    /**
     * Get incoming transfers
     */
    private function getIncomingTransfers($from_date, $to_date, $location_id)
    {
        return DB::select("
            SELECT
                pit.dmdcomb,
                pit.dmdctr,
                drug.drug_concat,
                pit.chrgcode,
                pri.acquisition_cost,
                pri.dmselprice,
                SUM(qty) as qty,
                [to]
            FROM pharm_io_trans_items pit
                JOIN hdmhdr drug ON pit.dmdcomb = drug.dmdcomb AND pit.dmdctr = drug.dmdctr
                JOIN hdmhdrprice pri ON pit.dmdprdte = pri.dmdprdte
            WHERE [to] = ?
                AND status = 'Received'
                AND pit.updated_at BETWEEN ? AND ?
            GROUP BY
                pit.dmdcomb,
                pit.dmdctr,
                drug.drug_concat,
                pit.chrgcode,
                [to],
                pri.acquisition_cost,
                pri.dmselprice
        ", [$location_id, $from_date, $to_date]);
    }

    /**
     * Get outgoing transfers
     */
    private function getOutgoingTransfers($from_date, $to_date, $location_id)
    {
        return DB::select("
            SELECT
                pit.dmdcomb,
                pit.dmdctr,
                drug.drug_concat,
                pit.chrgcode,
                pri.acquisition_cost,
                pri.dmselprice,
                SUM(qty) as qty,
                [from]
            FROM pharm_io_trans_items pit
                JOIN hdmhdr drug ON pit.dmdcomb = drug.dmdcomb AND pit.dmdctr = drug.dmdctr
                JOIN hdmhdrprice pri ON pit.dmdprdte = pri.dmdprdte
            WHERE [from] = ?
                AND status = 'Received'
                AND pit.updated_at BETWEEN ? AND ?
            GROUP BY
                pit.dmdcomb,
                pit.dmdctr,
                drug.drug_concat,
                pit.chrgcode,
                [from],
                pri.acquisition_cost,
                pri.dmselprice
        ", [$location_id, $from_date, $to_date]);
    }

    /**
     * Get deliveries
     */
    private function getDeliveries($from_date, $to_date, $location_id)
    {
        return DB::select("
            SELECT
                di.pharm_location_id,
                di.dmdcomb,
                di.dmdctr,
                di.charge_code as chrgcode,
                drug.drug_concat,
                COUNT(*) as LineItem,
                SUM(qty) as qty_delivered,
                pri.acquisition_cost as unit_cost,
                pri.dmselprice as retail_price
            FROM pharm_delivery_items di
                JOIN hdmhdr drug ON di.dmdcomb = drug.dmdcomb AND di.dmdctr = drug.dmdctr
                JOIN hdmhdrprice pri ON di.dmdprdte = pri.dmdprdte
            WHERE di.updated_at BETWEEN ? AND ?
                AND di.pharm_location_id = ?
            GROUP BY
                di.dmdcomb,
                di.dmdctr,
                di.charge_code,
                di.pharm_location_id,
                drug.drug_concat,
                pri.acquisition_cost,
                pri.dmselprice
        ", [$from_date, $to_date, $location_id]);
    }

    /**
     * Get pullouts
     */
    private function getPullouts($from_date, $to_date, $location_id)
    {
        return DB::select("
            SELECT
                SUM(i.pullout_qty) as pullout_qty,
                p.pharm_location_id,
                s.dmdcomb,
                s.dmdctr,
                s.chrgcode,
                drug.drug_concat,
                price.acquisition_cost as unit_cost,
                price.dmselprice as retail_price
            FROM pharm_pull_out_items i
                JOIN pharm_pull_outs p ON i.detail_id = p.id
                JOIN pharm_drug_stocks s ON i.stock_id = s.id
                JOIN hdmhdr drug ON s.dmdcomb = drug.dmdcomb AND s.dmdctr = drug.dmdctr
                JOIN hdmhdrprice price ON s.dmdprdte = price.dmdprdte
            WHERE i.updated_at BETWEEN ? AND ?
                AND p.pharm_location_id = ?
            GROUP BY
                p.pharm_location_id,
                s.dmdcomb,
                s.dmdctr,
                s.chrgcode,
                drug.drug_concat,
                price.acquisition_cost,
                price.dmselprice
        ", [$from_date, $to_date, $location_id]);
    }

    /**
     * Aggregate and save all the data directly to PharmConsumptionGenerated
     */
    private function aggregateAndSaveData(
        $consumption_id,
        $location_id,
        $charge_code,
        $beginningBalances,
        $issuances,
        $returns,
        $incomingTransfers,
        $outgoingTransfers,
        $deliveries,
        $pullouts
    ) {
        // Create a collection for aggregation
        $aggregatedData = collect();

        // Process beginning balances
        foreach ($beginningBalances as $item) {
            if ($item->chrgcode != $charge_code) continue;

            $key = $item->dmdcomb . '_' . $item->dmdctr . '_' . $item->acquisition_cost . '_' . $item->dmselprice;
            if (!$aggregatedData->has($key)) {
                $aggregatedData->put($key, [
                    'dmdcomb' => $item->dmdcomb,
                    'dmdctr' => $item->dmdctr,
                    'loc_code' => $location_id,
                    'chrgcode' => $item->chrgcode,
                    'drug_concat' => $item->drug_concat,
                    'acquisition_cost' => $item->acquisition_cost,
                    'dmselprice' => $item->dmselprice,
                    'beg_bal' => $item->beg_bal > 0 ? $item->beg_bal : 0,
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
                    'pullout_qty' => 0,
                    'consumption_id' => $consumption_id
                ]);
            } else {
                $existingData = $aggregatedData->get($key);
                $existingData['beg_bal'] += $item->beg_bal;
                $aggregatedData->put($key, $existingData);
            }
        }

        // Process issuances
        foreach ($issuances as $item) {
            if ($item->chrgcode != $charge_code) continue;

            $key = $item->dmdcomb . '_' . $item->dmdctr . '_' . $item->unit_cost . '_' . $item->retail_price;
            if (!$aggregatedData->has($key)) {
                $aggregatedData->put($key, [
                    'dmdcomb' => $item->dmdcomb,
                    'dmdctr' => $item->dmdctr,
                    'loc_code' => $location_id,
                    'chrgcode' => $item->chrgcode,
                    'drug_concat' => $item->drug_concat,
                    'acquisition_cost' => $item->unit_cost,
                    'dmselprice' => $item->retail_price,
                    'beg_bal' => 0,
                    'purchased' => 0,
                    'received_iotrans' => 0,
                    'transferred_iotrans' => 0,
                    'return_qty' => 0,
                    'ems' => $item->tx_type == 'ems' ? $item->qty_issued : 0,
                    'maip' => $item->tx_type == 'maip' ? $item->qty_issued : 0,
                    'wholesale' => $item->tx_type == 'wholesale' ? $item->qty_issued : 0,
                    'opdpay' => $item->tx_type == 'opdpay' ? $item->qty_issued : 0,
                    'pay' => $item->tx_type == 'pay' ? $item->qty_issued : 0,
                    'service' => $item->tx_type == 'service' ? $item->qty_issued : 0,
                    'konsulta' => $item->tx_type == 'konsulta' ? $item->qty_issued : 0,
                    'pcso' => $item->tx_type == 'pcso' ? $item->qty_issued : 0,
                    'phic' => $item->tx_type == 'phic' ? $item->qty_issued : 0,
                    'caf' => $item->tx_type == 'caf' ? $item->qty_issued : 0,
                    'issue_qty' => $item->qty_issued,
                    'pullout_qty' => 0,
                    'consumption_id' => $consumption_id
                ]);
            } else {
                $existingData = $aggregatedData->get($key);
                if ($item->tx_type == 'ems') $existingData['ems'] += $item->qty_issued;
                if ($item->tx_type == 'maip') $existingData['maip'] += $item->qty_issued;
                if ($item->tx_type == 'wholesale') $existingData['wholesale'] += $item->qty_issued;
                if ($item->tx_type == 'opdpay') $existingData['opdpay'] += $item->qty_issued;
                if ($item->tx_type == 'pay') $existingData['pay'] += $item->qty_issued;
                if ($item->tx_type == 'service') $existingData['service'] += $item->qty_issued;
                if ($item->tx_type == 'konsulta') $existingData['konsulta'] += $item->qty_issued;
                if ($item->tx_type == 'pcso') $existingData['pcso'] += $item->qty_issued;
                if ($item->tx_type == 'phic') $existingData['phic'] += $item->qty_issued;
                if ($item->tx_type == 'caf') $existingData['caf'] += $item->qty_issued;
                $existingData['issue_qty'] += $item->qty_issued;
                $aggregatedData->put($key, $existingData);
            }
        }

        // Process returns
        foreach ($returns as $item) {
            if ($item->chrgcode != $charge_code) continue;

            $key = $item->dmdcomb . '_' . $item->dmdctr . '_' . $item->unit_cost . '_' . $item->retail_price;
            if (!$aggregatedData->has($key)) {
                $aggregatedData->put($key, [
                    'dmdcomb' => $item->dmdcomb,
                    'dmdctr' => $item->dmdctr,
                    'loc_code' => $location_id,
                    'chrgcode' => $item->chrgcode,
                    'drug_concat' => $item->drug_concat,
                    'acquisition_cost' => $item->unit_cost,
                    'dmselprice' => $item->retail_price,
                    'beg_bal' => 0,
                    'purchased' => 0,
                    'received_iotrans' => 0,
                    'transferred_iotrans' => 0,
                    'return_qty' => $item->qty_returned,
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
                    'pullout_qty' => 0,
                    'consumption_id' => $consumption_id
                ]);
            } else {
                $existingData = $aggregatedData->get($key);
                $existingData['return_qty'] += $item->qty_returned;
                $aggregatedData->put($key, $existingData);
            }
        }

        // Process incoming transfers
        foreach ($incomingTransfers as $item) {
            if ($item->chrgcode != $charge_code) continue;

            $key = $item->dmdcomb . '_' . $item->dmdctr . '_' . $item->acquisition_cost . '_' . $item->dmselprice;
            if (!$aggregatedData->has($key)) {
                $aggregatedData->put($key, [
                    'dmdcomb' => $item->dmdcomb,
                    'dmdctr' => $item->dmdctr,
                    'loc_code' => $location_id,
                    'chrgcode' => $item->chrgcode,
                    'drug_concat' => $item->drug_concat,
                    'acquisition_cost' => $item->acquisition_cost,
                    'dmselprice' => $item->dmselprice,
                    'beg_bal' => 0,
                    'purchased' => 0,
                    'received_iotrans' => $item->qty,
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
                    'pullout_qty' => 0,
                    'consumption_id' => $consumption_id
                ]);
            } else {
                $existingData = $aggregatedData->get($key);
                $existingData['received_iotrans'] += $item->qty;
                $aggregatedData->put($key, $existingData);
            }
        }

        // Process outgoing transfers
        foreach ($outgoingTransfers as $item) {
            if ($item->chrgcode != $charge_code) continue;

            $key = $item->dmdcomb . '_' . $item->dmdctr . '_' . $item->acquisition_cost . '_' . $item->dmselprice;
            if (!$aggregatedData->has($key)) {
                $aggregatedData->put($key, [
                    'dmdcomb' => $item->dmdcomb,
                    'dmdctr' => $item->dmdctr,
                    'loc_code' => $location_id,
                    'chrgcode' => $item->chrgcode,
                    'drug_concat' => $item->drug_concat,
                    'acquisition_cost' => $item->acquisition_cost,
                    'dmselprice' => $item->dmselprice,
                    'beg_bal' => 0,
                    'purchased' => 0,
                    'received_iotrans' => 0,
                    'transferred_iotrans' => $item->qty,
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
                    'pullout_qty' => 0,
                    'consumption_id' => $consumption_id
                ]);
            } else {
                $existingData = $aggregatedData->get($key);
                $existingData['transferred_iotrans'] += $item->qty;
                $aggregatedData->put($key, $existingData);
            }
        }

        // Process deliveries
        foreach ($deliveries as $item) {
            if ($item->chrgcode != $charge_code) continue;

            $key = $item->dmdcomb . '_' . $item->dmdctr . '_' . $item->unit_cost . '_' . $item->retail_price;
            if (!$aggregatedData->has($key)) {
                $aggregatedData->put($key, [
                    'dmdcomb' => $item->dmdcomb,
                    'dmdctr' => $item->dmdctr,
                    'loc_code' => $location_id,
                    'chrgcode' => $item->chrgcode,
                    'drug_concat' => $item->drug_concat,
                    'acquisition_cost' => $item->unit_cost,
                    'dmselprice' => $item->retail_price,
                    'beg_bal' => 0,
                    'purchased' => $item->qty_delivered,
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
                    'pullout_qty' => 0,
                    'consumption_id' => $consumption_id
                ]);
            } else {
                $existingData = $aggregatedData->get($key);
                $existingData['purchased'] += $item->qty_delivered;
                $aggregatedData->put($key, $existingData);
            }
        }

        // Process pullouts
        foreach ($pullouts as $item) {
            if ($item->chrgcode != $charge_code) continue;

            $key = $item->dmdcomb . '_' . $item->dmdctr . '_' . $item->unit_cost . '_' . $item->retail_price;
            if (!$aggregatedData->has($key)) {
                $aggregatedData->put($key, [
                    'dmdcomb' => $item->dmdcomb,
                    'dmdctr' => $item->dmdctr,
                    'loc_code' => $location_id,
                    'chrgcode' => $item->chrgcode,
                    'drug_concat' => $item->drug_concat,
                    'acquisition_cost' => $item->unit_cost,
                    'dmselprice' => $item->retail_price,
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
                    'pullout_qty' => $item->pullout_qty,
                    'consumption_id' => $consumption_id
                ]);
            } else {
                $existingData = $aggregatedData->get($key);
                $existingData['pullout_qty'] += $item->pullout_qty;
                $aggregatedData->put($key, $existingData);
            }
        }

        // Now save all the aggregated data directly to PharmConsumptionGenerated
        foreach ($aggregatedData as $data) {
            PharmConsumptionGenerated::updateOrCreate(
                [
                    'dmdcomb' => $data['dmdcomb'],
                    'dmdctr' => $data['dmdctr'],
                    'chrgcode' => $data['chrgcode'],
                    'consumption_id' => $data['consumption_id'],
                    'loc_code' => $data['loc_code'],
                    'acquisition_cost' => $data['acquisition_cost'],
                    'dmselprice' => $data['dmselprice']
                ],
                $data
            );
        }

        // For backward compatibility - still need to populate original table
        $this->populateOriginalTable($consumption_id, $location_id, $aggregatedData);
    }

    /**
     * Populate original table for backward compatibility
     */
    private function populateOriginalTable($consumption_id, $location_id, $aggregatedData)
    {
        // Clear existing data
        DB::table('pharm_drug_stock_logs_copy')
            ->where('consumption_id', $consumption_id)
            ->where('loc_code', $location_id)
            ->delete();

        // Insert aggregated data into original table
        foreach ($aggregatedData as $data) {
            DB::table('pharm_drug_stock_logs_copy')->insert([
                'dmdcomb' => $data['dmdcomb'],
                'dmdctr' => $data['dmdctr'],
                'loc_code' => $data['loc_code'],
                'chrgcode' => $data['chrgcode'],
                'unit_cost' => $data['acquisition_cost'],
                'unit_price' => $data['dmselprice'],
                'beg_bal' => $data['beg_bal'],
                'purchased' => $data['purchased'],
                'received' => $data['received_iotrans'],
                'transferred' => $data['transferred_iotrans'],
                'return_qty' => $data['return_qty'],
                'ems' => $data['ems'],
                'maip' => $data['maip'],
                'wholesale' => $data['wholesale'],
                'opdpay' => $data['opdpay'],
                'pay' => $data['pay'],
                'service' => $data['service'],
                'konsulta' => $data['konsulta'],
                'pcso' => $data['pcso'],
                'phic' => $data['phic'],
                'caf' => $data['caf'],
                'issue_qty' => $data['issue_qty'],
                'pullout_qty' => $data['pullout_qty'],
                'consumption_id' => $data['consumption_id'],
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}
