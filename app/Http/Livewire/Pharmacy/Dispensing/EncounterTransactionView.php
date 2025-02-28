<?php

namespace App\Http\Livewire\Pharmacy\Dispensing;

use Exception;
use Carbon\Carbon;
use Livewire\Component;
use App\Models\Pharmacy\Drug;
use App\Jobs\LogDrugStockIssue;
use Illuminate\Support\Facades\DB;
use App\Models\Hospital\Department;
use Illuminate\Support\Facades\Log;
use App\Models\References\ChargeCode;
use Illuminate\Support\Facades\Crypt;
use App\Models\Pharmacy\Drugs\DrugStock;
use App\Models\Pharmacy\Drugs\DrugStockLog;
use App\Models\Pharmacy\Drugs\DrugStockCard;
use App\Models\Pharmacy\Dispensing\DrugOrder;
use App\Models\Pharmacy\Drugs\DrugStockIssue;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use App\Models\Record\Encounters\EncounterLog;
use App\Models\Record\Prescriptions\Prescription;
use App\Models\Pharmacy\Dispensing\OrderChargeCode;
use App\Models\Record\Prescriptions\PrescriptionData;
use App\Models\Record\Prescriptions\PrescriptionDataIssued;

class EncounterTransactionView extends Component
{
    use LivewireAlert;

    protected $listeners = ['charge_items', 'issue_order', 'add_item', 'return_issued', 'add_prescribed_item', 'delete_item', 'deactivate_rx', 'update_qty'];

    public $generic, $charge_code = [];
    public $enccode, $location_id, $hpercode, $toecode, $mssikey;

    public $order_qty, $unit_price, $return_qty, $docointkey;
    public $item_id;
    public $ems, $maip, $wholesale, $caf, $type, $konsulta, $pcso, $phic, $pay, $service, $doh_free, $bnb = false;

    public $is_ris = false;
    public $remarks;

    public $charges;
    protected $encounter = [];

    public $selected_items = [];
    public $marked_items = false;
    public $selected_remarks, $new_remarks;

    public $patient;
    public $active_prescription = [], $extra_prescriptions = [];
    public $active_prescription_all = [], $extra_prescriptions_all = [];
    public $adm;
    public $rx_charge_code;

    public $patient_room, $wardname, $rmname;
    public $code;
    public $encdate;
    public $diagtext;
    public $patlast;
    public $patfirst;
    public $patmiddle, $billstat = null;
    public $adttl_remarks;
    public $rx_id, $rx_dmdcomb, $rx_dmdctr, $empid, $mss, $deptcode;

    public $stock_changes = false;

    // Cache complex data to avoid multiple DB calls
    private $cachedStocks = [];

    public function render()
    {
        $enccode = str_replace('--', ' ', Crypt::decrypt($this->enccode));

        // Keeping these as raw queries for performance
        $rxos = DB::select("SELECT docointkey, pcchrgcod, dodate, pchrgqty, estatus, qtyissued, pchrgup, pcchrgamt, drug_concat, chrgdesc, remarks, mssikey, tx_type, prescription_data_id
                            FROM hospital.dbo.hrxo
                            INNER JOIN hdmhdr ON hdmhdr.dmdcomb = hrxo.dmdcomb AND hdmhdr.dmdctr = hrxo.dmdctr
                            INNER JOIN hcharge ON orderfrom = chrgcode
                            LEFT JOIN hpatmss ON hrxo.enccode = hpatmss.enccode
                            WHERE hrxo.enccode = '" . $enccode . "'
                            ORDER BY dodate DESC");

        // Keep as raw query for performance
        $stocks = DB::select("SELECT pharm_drug_stocks.dmdcomb, pharm_drug_stocks.dmdctr, drug_concat, hcharge.chrgdesc, pharm_drug_stocks.chrgcode, hdmhdrprice.retail_price, dmselprice, pharm_drug_stocks.loc_code, pharm_drug_stocks.dmdprdte as dmdprdte, SUM(stock_bal) as stock_bal, MAX(id) as id, MIN(exp_date) as exp_date
                                FROM hospital.dbo.pharm_drug_stocks
                                INNER JOIN hcharge on hcharge.chrgcode = pharm_drug_stocks.chrgcode
                                INNER JOIN hdmhdrprice on hdmhdrprice.dmdprdte = pharm_drug_stocks.dmdprdte
                                WHERE loc_code = '" . $this->location_id . "'
                                AND drug_concat LIKE '%" . implode("''", explode("'", $this->generic)) . "%'
                                AND stock_bal > 0
                                GROUP BY pharm_drug_stocks.dmdcomb, pharm_drug_stocks.dmdctr, pharm_drug_stocks.chrgcode, hdmhdrprice.retail_price, dmselprice, drug_concat, hcharge.chrgdesc, pharm_drug_stocks.loc_code, pharm_drug_stocks.dmdprdte
                                ORDER BY drug_concat");

        // Store in cache for later use
        $this->cachedStocks = collect($stocks)->keyBy('id');

        // Keep as raw query for performance
        $summaries = DB::select("
            SELECT drug_concat, SUM(pchrgqty) qty_issued, MAX(dodtepost) last_issue
                FROM hrxo
            JOIN hdmhdr ON hrxo.dmdcomb = hdmhdr.dmdcomb AND hrxo.dmdctr = hdmhdr.dmdctr
                WHERE enccode = '" . $enccode . "' AND estatus = 'S'
            GROUP BY drug_concat
        ");

        $departments = DB::select("SELECT * FROM hdept WHERE deptstat = 'A'");

        $this->dispatchBrowserEvent('issued');
        $encounter = $this->encounter;

        return view('livewire.pharmacy.dispensing.encounter-transaction-view', compact(
            'rxos',
            'stocks',
            'encounter',
            'departments',
            'summaries',
        ));
    }

    public function mount($enccode)
    {
        $this->enccode = $enccode;
        $this->location_id = session('pharm_location_id');

        $enccode = str_replace('--', ' ', Crypt::decrypt($this->enccode));

        // Keeping this as raw query for performance
        $encounter = collect(DB::select("SELECT TOP 1 enctr.hpercode, enctr.toecode, enctr.enccode, enctr.encdate, diag.diagtext, pat.patlast, pat.patfirst, pat.patmiddle,
                                                mss.mssikey, ward.wardname, room.rmname, track.billstat
                                FROM henctr as enctr
                                LEFT JOIN hactrack as track ON enctr.enccode = track.enccode
                                LEFT JOIN hencdiag as diag ON enctr.enccode = diag.enccode
                                INNER JOIN hperson as pat ON enctr.hpercode = pat.hpercode
                                LEFT JOIN hpatmss as mss ON enctr.enccode = mss.enccode
                                LEFT JOIN hpatroom as patroom ON enctr.enccode = patroom.enccode
                                LEFT JOIN hward as ward ON patroom.wardcode = ward.wardcode
                                LEFT JOIN hroom as room ON patroom.rmintkey = room.rmintkey
                                WHERE enctr.enccode = '" . $enccode . "'
                                ORDER BY patroom.hprdate DESC
                                "))->first();

        // Active prescriptions
        $this->active_prescription = Prescription::where('enccode', $enccode)->with('data_active')->has('data_active')->get();
        $this->active_prescription_all = Prescription::where('enccode', $enccode)->with('data')->get();

        $past_log = null;
        switch ($encounter->toecode) {
            case 'ADM':
                $past_log = EncounterLog::where('hpercode', $encounter->hpercode)
                    ->where(function ($query) {
                        $query->where('toecode', 'ERADM')
                            ->orWhere('toecode', 'OPDAD');
                    })
                    ->latest('encdate')
                    ->first();
                break;

            case 'OPDAD':
                $past_log = EncounterLog::where('hpercode', $encounter->hpercode)
                    ->where(function ($query) {
                        $query->where('toecode', 'OPD');
                    })
                    ->latest('encdate')
                    ->first();
                break;

            case 'ERADM':
                $past_log = EncounterLog::where('hpercode', $encounter->hpercode)
                    ->where(function ($query) {
                        $query->where('toecode', 'ER');
                    })
                    ->latest('encdate')
                    ->first();
                break;
        }

        if ($past_log) {
            $this->extra_prescriptions = Prescription::where('enccode', $past_log->enccode)->with('data_active')->has('data_active')->get();
            $this->extra_prescriptions_all = Prescription::where('enccode', $past_log->enccode)->with('data')->get();
        }

        if (!$this->hpercode) {
            $this->hpercode = $encounter->hpercode;
            $this->toecode = $encounter->toecode;
        }

        $this->mssikey = $encounter->mssikey;
        $this->encounter = $encounter;
        $this->code = $encounter->enccode;
        $this->encdate = $encounter->encdate;
        $this->diagtext = $encounter->diagtext;
        $this->patlast = $encounter->patlast;
        $this->patfirst = $encounter->patfirst;
        $this->patmiddle = $encounter->patmiddle;
        $this->wardname = $encounter->wardname;
        $this->rmname = $encounter->rmname;
        $this->billstat = $encounter->billstat;

        if (!$this->charges) {
            $this->charges = ChargeCode::where('bentypcod', 'DRUME')
                ->where('chrgstat', 'A')
                ->whereIn('chrgcode', app('chargetable'))
                ->get();
        }
    }

    /**
     * Determine the transaction type based on patient context and selected options
     */
    private function determineTransactionType()
    {
        if ($this->toecode == 'ADM' || $this->toecode == 'OPDAD' || $this->toecode == 'ERADM') {
            switch ($this->mssikey) {
                case 'MSSA11111999':
                case 'MSSB11111999':
                    $this->type = 'pay';
                    break;
                case 'MSSC111111999':
                    $this->type = 'pay';
                    break;
                case 'MSSC211111999':
                case 'MSSC311111999':
                case 'MSSD11111999':
                default:
                    $this->type = 'service';
            }

            // Override based on BNB flag
            $this->type = $this->bnb ? 'pay' : 'service';
        } else {
            // For other encounter types, determine by checkbox selections
            if ($this->ems) {
                $this->type = 'ems';
            } else if ($this->maip) {
                $this->type = 'maip';
            } else if ($this->wholesale) {
                $this->type = 'wholesale';
            } else if ($this->service) {
                $this->type = 'service';
            } else if ($this->caf) {
                $this->type = 'caf';
            } else if ($this->is_ris) {
                $this->type = 'ris';
            } else if ($this->pcso) {
                $this->type = 'pcso';
            } else if ($this->phic) {
                $this->type = 'phic';
            } else if ($this->konsulta) {
                $this->type = 'konsulta';
            } else if ($this->doh_free) {
                $this->type = 'doh_free';
            } else {
                $this->type = 'opdpay';
            }

            if ($this->toecode != 'ER' and !empty($this->selected_items)) {
                $this->validate(['deptcode' => 'required'], ['deptcode.required' => 'Please select department.']);
            }
        }
    }

    public function charge_items()
    {
        // Create the charge code entry
        $charge_code = OrderChargeCode::create([
            'charge_desc' => 'a',
        ]);

        $pcchrgcod = 'P' . date('y') . '-' . sprintf('%07d', $charge_code->id);

        if (empty($this->selected_items)) {
            $this->alert('error', 'No items selected.');
            return;
        }

        $cnt = 0;
        foreach ($this->selected_items as $docointkey) {
            // Using raw query as it's more efficient for this use case
            $cnt += DB::update(
                "UPDATE hospital.dbo.hrxo SET pcchrgcod = '" . $pcchrgcod . "', estatus = 'P' WHERE docointkey = " . $docointkey . " AND ((estatus = 'U' OR orderfrom = 'DRUMK' OR pchrgup = 0) AND pcchrgcod IS NULL)"
            );
        }

        if ($cnt > 0) {
            $this->dispatchBrowserEvent('charged', ['pcchrgcod' => $pcchrgcod]);
        } else {
            $this->alert('error', 'No item to charge.');
        }
    }

    public function issue_order()
    {
        // Start a database transaction to ensure data integrity
        DB::beginTransaction();

        try {
            $enccode = str_replace('--', ' ', Crypt::decrypt($this->enccode));
            $cnt = 0;

            // Validate selected items
            if (empty($this->selected_items)) {
                $this->alert('error', 'No items selected.');
                return;
            }

            // Get transaction type based on patient context
            $this->determineTransactionType();


            $selected_items = implode(',', $this->selected_items);

            // Add a lock to prevent concurrent updates on the same records
            // Using FOR UPDATE ensures other transactions won't interfere
            $rxos = collect(DB::select("SELECT * FROM hrxo WITH (ROWLOCK, XLOCK)
                            WHERE docointkey IN (" . $selected_items . ")
                            AND (estatus = 'P' OR orderfrom = 'DRUMK' OR pchrgup = 0)
                            ORDER BY dodate ASC"))->all();
            if (empty($rxos)) {
                DB::rollBack();
                $this->alert('error', 'No items ready for issuing.');
                return;
            }

            $temp_type = $this->type;
            $allItemsProcessed = true;

            foreach ($rxos as $rxo) {
                // Verify item hasn't already been issued (prevent double-click issues)
                $currentStatus = DB::select("SELECT estatus FROM hrxo WHERE docointkey = '" . $rxo->docointkey . "'");
                if (!empty($currentStatus) && $currentStatus[0]->estatus === 'S') {
                    continue; // Skip already issued items
                }

                // Temporarily override type for special cases
                $this->type = ($rxo->orderfrom == 'DRUMK') ? 'service' : $temp_type;

                // Fetch available stock items - Ensure FEFO (First Expiry First Out)
                $stocks = DB::select(
                    "SELECT pharm_drug_stocks.*, hdmhdrprice.dmduprice
                        FROM pharm_drug_stocks WITH (ROWLOCK, XLOCK)
                        JOIN hdmhdrprice ON pharm_drug_stocks.dmdprdte = hdmhdrprice.dmdprdte
                    WHERE pharm_drug_stocks.dmdcomb = '" . $rxo->dmdcomb . "'
                        AND pharm_drug_stocks.dmdctr = '" . $rxo->dmdctr . "'
                        AND pharm_drug_stocks.chrgcode = '" . $rxo->orderfrom . "'
                        AND pharm_drug_stocks.loc_code = '" . session('pharm_location_id') . "'
                        AND pharm_drug_stocks.exp_date > '" . date('Y-m-d') . "'
                        AND pharm_drug_stocks.stock_bal > 0
                    ORDER BY pharm_drug_stocks.exp_date ASC" // FEFO ordering is explicit here
                );

                // Verify sufficient stock is available
                $totalAvailable = array_sum(array_column($stocks, 'stock_bal'));
                if (empty($stocks) || (!$rxo->ris && $totalAvailable < $rxo->pchrgqty)) {
                    $insuf = Drug::select('drug_concat')->where('dmdcomb', $rxo->dmdcomb)->where('dmdctr', $rxo->dmdctr)->first();
                    $allItemsProcessed = false;
                    DB::rollBack();
                    return $this->alert('error', 'Insufficient Stock Balance. ' . $insuf->drug_concat);
                }

                $total_deduct = $rxo->pchrgqty;
                $dmdcomb = $rxo->dmdcomb;
                $dmdctr = $rxo->dmdctr;
                $docointkey = $rxo->docointkey;
                $loc_code = $rxo->loc_code;
                $chrgcode = $rxo->orderfrom;
                $unit_price = $rxo->pchrgup;
                $pcchrgamt = $rxo->pcchrgamt;
                $pcchrgcod = $rxo->pcchrgcod;
                $tag = $this->type;
                $stockUpdateSuccess = true;

                foreach ($stocks as $stock) {
                    if ($total_deduct <= 0) {
                        break;
                    }

                    $trans_qty = 0;

                    if (!$rxo->ris) {
                        // Recheck current stock balance to ensure it hasn't changed
                        $currentStock = DB::select("SELECT stock_bal FROM pharm_drug_stocks WHERE id = '" . $stock->id . "'");
                        if (empty($currentStock) || $currentStock[0]->stock_bal < $stock->stock_bal) {
                            // Stock has changed, use current value
                            $stock->stock_bal = empty($currentStock) ? 0 : $currentStock[0]->stock_bal;
                        }

                        if ($total_deduct > $stock->stock_bal) {
                            $trans_qty = $stock->stock_bal;
                            $total_deduct -= $stock->stock_bal;
                            $stock_bal = 0;
                        } else {
                            $trans_qty = $total_deduct;
                            $stock_bal = $stock->stock_bal - $total_deduct;
                            $total_deduct = 0;
                        }

                        // Update stock balance with optimistic concurrency control
                        $updateResult = DB::update(
                            "UPDATE hospital.dbo.pharm_drug_stocks
                            SET stock_bal = '" . $stock_bal . "'
                            WHERE id = '" . $stock->id . "'
                            AND stock_bal = '" . $stock->stock_bal . "'"
                        );

                        if ($updateResult != 1) {
                            $stockUpdateSuccess = false;
                            break; // Stock was updated by another process, rollback
                        }

                        $cnt = $updateResult;
                    } else {
                        $total_deduct = 0;
                    }

                    if ($trans_qty > 0) {
                        $drug_concat = implode("", explode('_', $stock->drug_concat));

                        // Log stock issue
                        $this->log_stock_issue(
                            $stock->id,
                            $docointkey,
                            $dmdcomb,
                            $dmdctr,
                            $loc_code,
                            $chrgcode,
                            $stock->exp_date,
                            $trans_qty,
                            $unit_price,
                            $pcchrgamt,
                            session('user_id'),
                            $rxo->hpercode,
                            $rxo->enccode,
                            $this->toecode,
                            $pcchrgcod,
                            $tag,
                            $rxo->ris,
                            $stock->dmdprdte,
                            $stock->retail_price,
                            $drug_concat,
                            date('Y-m-d'),
                            now(),
                            session('active_consumption'),
                            $stock->dmduprice
                        );
                    }
                }

                // If stock update failed or insufficient stock, rollback
                if (!$stockUpdateSuccess || $total_deduct > 0) {
                    $allItemsProcessed = false;
                    DB::rollBack();
                    return $this->alert('error', 'Stock was updated by another process or insufficient stock. Please try again.');
                }

                if ($cnt == 1) {
                    // Update order status with a timestamp to prevent duplicates
                    $processTime = now();
                    $cnt = DB::update(
                        "UPDATE hospital.dbo.hrxo
                        SET estatus = 'S',
                            qtyissued = '" . $rxo->pchrgqty . "',
                            tx_type = '" . $this->type . "',
                            dodtepost = '" . $processTime . "',
                            dotmepost = '" . $processTime . "',
                            deptcode = '" . $this->deptcode . "'
                        WHERE docointkey = '" . $rxo->docointkey . "'
                        AND (estatus = 'P' OR orderfrom = 'DRUMK' OR pchrgup = 0)"
                    );

                    // Log issue
                    if ($cnt > 0) {
                        $this->log_hrxoissue($rxo->docointkey, $rxo->enccode, $rxo->hpercode, $rxo->dmdcomb, $rxo->dmdctr, $rxo->pchrgqty, session('employeeid'), $rxo->orderfrom, $rxo->pcchrgcod, $rxo->pchrgup, $rxo->ris, $rxo->prescription_data_id, $processTime, $rxo->dmdprdte);
                    }
                }
            }

            // Commit transaction if successful
            if ($allItemsProcessed) {
                DB::commit();
                $this->alert('success', 'Order issued successfully.');
                // Clear selected items to prevent resubmission
                $this->selected_items = [];
            } else {
                DB::rollBack();
                $this->alert('error', 'No item to issue or issue failed.');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $this->alert('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    public function log_hrxoissue($docointkey, $enccode, $hpercode, $dmdcomb, $dmdctr, $pchrgqty, $employeeid, $orderfrom, $pcchrgcod, $pchrgup, $ris, $prescription_data_id, $date, $dmdprdte)
    {
        if ($prescription_data_id) {
            // Log prescription data issued
            PrescriptionDataIssued::create([
                'presc_data_id' => $prescription_data_id,
                'docointkey' => $docointkey,
                'qtyissued' => $pchrgqty,
            ]);
        } else {
            // Try to match with active prescription data
            $rx_header = Prescription::where('enccode', $enccode)
                ->with('data_active')
                ->get();

            if ($rx_header) {
                foreach ($rx_header as $rxh) {
                    $rx_data = $rxh->data_active()
                        ->where('dmdcomb', $dmdcomb)
                        ->where('dmdctr', $dmdctr)
                        ->first();

                    if ($rx_data) {
                        // Create prescription data issued record
                        PrescriptionDataIssued::create([
                            'presc_data_id' => $rx_data->id,
                            'docointkey' => $docointkey,
                            'qtyissued' => $pchrgqty,
                        ]);

                        // Update drug order with prescription data
                        DB::update(
                            "UPDATE hospital.dbo.hrxo SET prescription_data_id = ?, prescribed_by = ? WHERE docointkey = ?",
                            [$rx_data->id, $rx_data->entry_by, $docointkey]
                        );

                        // Mark prescription as issued
                        $rx_data->stat = 'I';
                        $rx_data->save();
                    }
                }
            }
        }
    }

    public function log_stock_issue($stock_id, $docointkey, $dmdcomb, $dmdctr, $loc_code, $chrgcode, $exp_date, $trans_qty, $unit_price, $pcchrgamt, $user_id, $hpercode, $enccode, $toecode, $pcchrgcod, $tag, $ris, $dmdprdte, $retail_price, $concat, $stock_date, $date, $active_consumption = null, $unit_cost)
    {
        // Create drug stock issue record
        $issued_drug = DrugStockIssue::create([
            'stock_id' => $stock_id,
            'docointkey' => $docointkey,
            'dmdcomb' => $dmdcomb,
            'dmdctr' => $dmdctr,
            'loc_code' => $loc_code,
            'chrgcode' => $chrgcode,
            'exp_date' => $exp_date,
            'qty' =>  $trans_qty,
            'pchrgup' =>  $unit_price,
            'pcchrgamt' =>  $pcchrgamt,
            'status' => 'Issued',
            'user_id' => $user_id,
            'hpercode' => $hpercode,
            'enccode' => $enccode,
            'toecode' => $toecode,
            'pcchrgcod' => $pcchrgcod,

            'ems' => $tag == 'ems' ? $trans_qty : false,
            'maip' => $tag == 'maip' ? $trans_qty : false,
            'wholesale' => $tag == 'wholesale' ? $trans_qty : false,
            'pay' => $tag == 'pay' ? $trans_qty : false,
            'opdpay' => $tag == 'opdpay' ? $trans_qty : false,
            'service' => $tag == 'service' ? $trans_qty : false,
            'caf' => $tag == 'caf' ? $trans_qty : false,
            'ris' =>  $ris ? true : false,

            'konsulta' => $tag == 'konsulta' ? $trans_qty : false,
            'pcso' => $tag == 'pcso' ? $trans_qty : false,
            'phic' => $tag == 'phic' ? $trans_qty : false,
            'doh_free' => $tag == 'doh_free' ? $trans_qty : false,

            'dmdprdte' => $dmdprdte,
        ]);

        // Update stock log
        $log = DrugStockLog::firstOrNew([
            'loc_code' => $loc_code,
            'dmdcomb' => $dmdcomb,
            'dmdctr' => $dmdctr,
            'chrgcode' => $chrgcode,
            'unit_cost' => $unit_cost,
            'unit_price' => $retail_price,
            'consumption_id' => $active_consumption,
        ]);

        $log->issue_qty += $trans_qty;
        $log->wholesale += $issued_drug->wholesale;
        $log->ems += $issued_drug->ems;
        $log->maip += $issued_drug->maip;
        $log->caf += $issued_drug->caf;
        $log->ris += $issued_drug->ris ? 1 : 0;
        $log->pay += $issued_drug->pay;
        $log->service += $issued_drug->service;
        $log->konsulta += $issued_drug->konsulta;
        $log->pcso += $issued_drug->pcso;
        $log->phic += $issued_drug->phic;
        $log->opdpay += $issued_drug->opdpay;
        $log->doh_free += $issued_drug->doh_free;
        $log->save();

        // Update stock card
        $card = DrugStockCard::firstOrNew([
            'chrgcode' => $chrgcode,
            'loc_code' => $loc_code,
            'dmdcomb' => $dmdcomb,
            'dmdctr' => $dmdctr,
            'exp_date' => $exp_date,
            'stock_date' => $stock_date,
            'drug_concat' => $concat,
        ]);

        $card->iss += $trans_qty;
        $card->bal -= $trans_qty;
        $card->save();
    }

    public function add_item($dmdcomb, $dmdctr, $chrgcode, $loc_code, $dmdprdte, $id, $available, $exp_date)
    {
        // Check if associated with prescription
        $with_rx = false;
        if ($dmdcomb == $this->rx_dmdcomb && $dmdctr == $this->rx_dmdctr) {
            $with_rx = true;
            $rx_id = $this->rx_id;
            $empid = $this->empid;
        }

        // Validate order quantity
        if (!is_numeric($this->order_qty) || $this->order_qty <= 0) {
            return $this->alert('error', 'Please enter a valid quantity.');
        }

        // Determine transaction type
        $this->determineTransactionType();

        // Check if stock is available or RIS
        if ($this->is_ris || $available >= $this->order_qty) {
            $enccode = str_replace('--', ' ', Crypt::decrypt($this->enccode));
            $docointkey = '0000040' . $this->hpercode . date('m/d/Yh:i:s', strtotime(now())) . $chrgcode . $dmdcomb . $dmdctr;

            // Insert new order
            DB::insert("INSERT INTO hospital.dbo.hrxo(docointkey, enccode, hpercode, rxooccid, rxoref, dmdcomb, repdayno1, rxostatus,
                            rxolock, rxoupsw, rxoconfd, dmdctr, estatus, entryby, ordcon, orderupd, locacode, orderfrom, issuetype,
                            has_tag, tx_type, ris, pchrgqty, pchrgup, pcchrgamt, dodate, dotime, dodtepost, dotmepost, dmdprdte, exp_date, loc_code, item_id, remarks, prescription_data_id, prescribed_by )
                        VALUES ( '" . $docointkey . "', '" . $enccode . "', '" . $this->hpercode . "', '1', '1', '" . $dmdcomb . "', '1', 'A',
                            'N', 'N', 'N', '" . $dmdctr . "', 'U', '" . session('employeeid') . "', 'NEWOR', 'ACTIV', 'PHARM', '" . $chrgcode . "', 'c',
                            '" . ($this->type ? true : false) . "', '" . $this->type . "', '" . ($this->is_ris ? true : false) . "', '" . $this->order_qty . "', '" . $this->unit_price . "',
                            '" . $this->order_qty * $this->unit_price . "', '" . now() . "', '" . now() . "', '" . now() . "', '" . now() . "', '" . $dmdprdte . "', '" . $exp_date . "',
                            '" . $loc_code . "', '" . $id . "', '" . ($this->remarks ?? '') . "', '" . ($with_rx ? $rx_id : null) . "', '" . ($with_rx ? $empid : null) . "' )");

            // Update prescription if needed
            if ($with_rx) {
                DB::connection('webapp')->table('webapp.dbo.prescription_data')
                    ->where('id', $rx_id)
                    ->update(['stat' => 'I']);
            }

            // Reset form fields but keep needed context
            $this->resetExcept('generic', 'rx_dmdcomb', 'rx_dmdctr', 'rx_id', 'empid', 'stocks', 'enccode', 'location_id', 'encounter', 'charges', 'hpercode', 'toecode', 'selected_items', 'patient', 'active_prescription', 'adm', 'wardname', 'rmname', 'mss', 'summaries');

            $this->alert('success', 'Item added.');
        } else {
            $this->alert('error', 'Insufficient stock!');
        }
    }

    public function delete_item()
    {
        if (empty($this->selected_items)) {
            $this->alert('error', 'No items selected.');
            return;
        }

        $selectedItems = implode(',', $this->selected_items);
        DB::delete("DELETE FROM hrxo WHERE docointkey IN(" . $selectedItems . ") AND (estatus = 'U' OR pcchrgcod IS NULL)");

        $this->reset('selected_items');
        $this->alert('success', 'Selected item/s deleted!');
    }

    public function return_issued(DrugOrder $item)
    {
        // Start a database transaction to ensure data integrity
        DB::beginTransaction();

        try {
            $this->validate([
                'return_qty' => ['required', 'numeric', 'min:1', 'max:' . $item->qtyissued],
                'unit_price' => 'required',
                'docointkey' => 'required',
            ]);
            // Check if return already exists to prevent double-processing
            $existingReturn = DB::select("SELECT COUNT(*) as count FROM hospital.dbo.hrxoreturn
                                        WHERE docointkey = '" . $item->docointkey . "'
                                        AND qty = '" . $this->return_qty . "'
                                        AND returndate >= '" . now()->subMinutes(5)->format('Y-m-d H:i:s') . "'");

            if (!empty($existingReturn) && $existingReturn[0]->count > 0) {
                DB::rollBack();
                $this->alert('info', 'This return was already processed.');
                return;
            }

            // Lock the drug order record for update
            $lockedItem = DB::select("SELECT * FROM hrxo WITH (ROWLOCK, XLOCK)
                                    WHERE docointkey = '" . $item->docointkey . "'
                                    AND qtyissued >= '" . $this->return_qty . "'");

            if (empty($lockedItem)) {
                DB::rollBack();
                $this->alert('error', 'Item not found or quantity already returned by another user.');
                return;
            }

            $processTime = now();

            // Record return item to hrxoreturn table with timestamp
            DB::insert("INSERT INTO hospital.dbo.hrxoreturn(
                    docointkey, enccode, hpercode, dmdcomb, returndate, returntime, qty, returnby,
                    status, rxolock, updsw, confdl, entryby, locacode, dmdctr, dmdprdte, remarks,
                    returnfrom, chrgcode, pcchrgcod, rcode, unitprice, pchrgup, loc_code)
                VALUES(
                '" . $item->docointkey . "',
                '" . $item->enccode . "',
                '" . $item->hpercode . "',
                '" . $item->dmdcomb . "',
                '" . $processTime . "',
                '" . $processTime . "',
                '" . $this->return_qty . "',
                '" . session('employeeid') . "',
                'A',
                'N',
                'N',
                'N',
                '" . session('employeeid') . "',
                '" . $item->locacode . "',
                '" . $item->dmdctr . "',
                '" . $item->dmdprdte . "',
                '" . $item->remarks . "',
                '" . $item->orderfrom . "',
                '" . $item->orderfrom . "',
                '" . $item->pcchrgcod . "',
                '',
                '" . $item->pchrgup . "',
                '" . $item->pchrgup . "',
                '" . $this->location_id . "'
                )
            ");

            // Deduct quantity issued from hrxo with optimistic concurrency control
            $newQtyIssued = $item->qtyissued - $this->return_qty;
            $newPcchrgamt = $item->pchrgup * $newQtyIssued;

            $updateResult = DB::update(
                "UPDATE hospital.dbo.hrxo
                SET qtyissued = '" . $newQtyIssued . "',
                    pcchrgamt = '" . $newPcchrgamt . "'
                WHERE docointkey = '" . $item->docointkey . "'
                AND qtyissued = '" . $item->qtyissued . "'"
            );

            if ($updateResult != 1) {
                DB::rollBack();
                $this->alert('error', 'Item was updated by another user. Please refresh and try again.');
                return;
            }

            // Process stock returns - lock issued items to prevent concurrent updates
            $issued_items = DB::select(
                "SELECT dsi.*, ps.*
                FROM pharm_drug_stock_issues AS dsi WITH (ROWLOCK, XLOCK)
                JOIN pharm_drug_stocks AS ps ON dsi.stock_id = ps.id
                WHERE dsi.docointkey = '" . $this->docointkey . "'
                AND dsi.qty > dsi.returned_qty
                ORDER BY dsi.created_at DESC"
            );

            if (empty($issued_items)) {
                DB::rollBack();
                $this->alert('error', 'No issued items found for return.');
                return;
            }

            $qty_to_return = $this->return_qty;
            $allReturnsProcessed = true;

            foreach ($issued_items as $stock_issued) {
                if ($qty_to_return <= 0) {
                    break;
                }

                $available_to_return = $stock_issued->qty - $stock_issued->returned_qty;

                if ($qty_to_return > $available_to_return) {
                    $returned_qty = $available_to_return;
                    $qty_to_return -= $available_to_return;
                    $new_returned_qty = $stock_issued->returned_qty + $available_to_return;
                } else {
                    $returned_qty = $qty_to_return;
                    $new_returned_qty = $stock_issued->returned_qty + $qty_to_return;
                    $qty_to_return = 0;
                }

                // Update the returned quantity with optimistic locking
                $updateResult = DB::update(
                    "UPDATE pharm_drug_stock_issues
                    SET returned_qty = '" . $new_returned_qty . "'
                    WHERE docointkey = '" . $stock_issued->docointkey . "'
                    AND returned_qty = '" . $stock_issued->returned_qty . "'"
                );

                if ($updateResult != 1) {
                    $allReturnsProcessed = false;
                    break;
                }

                // Return quantity to stock with update verification
                $currentStock = DB::select(
                    "SELECT stock_bal FROM pharm_drug_stocks WITH (ROWLOCK, XLOCK)
                    WHERE id = '" . $stock_issued->stock_id . "'"
                );

                if (empty($currentStock)) {
                    $allReturnsProcessed = false;
                    break;
                }

                $new_stock_bal = $currentStock[0]->stock_bal + $returned_qty;

                $updateResult = DB::update(
                    "UPDATE pharm_drug_stocks
                    SET stock_bal = '" . $new_stock_bal . "'
                    WHERE id = '" . $stock_issued->stock_id . "'
                    AND stock_bal = '" . $currentStock[0]->stock_bal . "'"
                );

                if ($updateResult != 1) {
                    $allReturnsProcessed = false;
                    break;
                }

                // Update stock log
                $log = DrugStockLog::firstOrNew([
                    'loc_code' => $this->location_id,
                    'dmdcomb' => $stock_issued->dmdcomb,
                    'dmdctr' => $stock_issued->dmdctr,
                    'chrgcode' => $stock_issued->chrgcode,
                    'unit_cost' => $stock_issued->dmduprice ?? 0,
                    'unit_price' => $stock_issued->retail_price,
                    'consumption_id' => session('active_consumption'),
                ]);

                $log->return_qty += $returned_qty;
                $log->save();
            }

            if (!$allReturnsProcessed || $qty_to_return > 0) {
                DB::rollBack();
                $this->alert('error', 'Return processing failed. Please try again.');
                return;
            }

            // All operations successful, commit the transaction
            DB::commit();

            // Reset return quantity to prevent accidental resubmission
            $this->return_qty = null;
            $this->alert('success', 'Item returned successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->alert('error', 'An error occurred: ' . $e->getMessage());
        }
    }

    public function add_prescribed_item($dmdcomb, $dmdctr)
    {
        $rx_id = $this->rx_id;
        $empid = $this->empid;

        // Determine transaction type based on checkboxes
        if ($this->ems) {
            $this->type = 'ems';
        } else if ($this->maip) {
            $this->type = 'maip';
        } else if ($this->wholesale) {
            $this->type = 'wholesale';
        } else if ($this->service) {
            $this->type = 'service';
        } else if ($this->caf) {
            $this->type = 'caf';
        } else if ($this->is_ris) {
            $this->type = 'ris';
        } else if ($this->pcso) {
            $this->type = 'pcso';
        } else if ($this->phic) {
            $this->type = 'phic';
        } else if ($this->konsulta) {
            $this->type = 'konsulta';
        } else {
            $this->type = 'pay';
        }

        // Get drug stock with available quantity
        $dm = DrugStock::where('dmdcomb', $dmdcomb)
            ->where('dmdctr', $dmdctr)
            ->where('chrgcode', $this->rx_charge_code)
            ->where('loc_code', $this->location_id)
            ->where('stock_bal', '>', '0')
            ->orderBy('exp_date', 'ASC')
            ->first();

        if ($dm) {
            $enccode = str_replace('--', ' ', Crypt::decrypt($this->enccode));

            // Create drug order
            DrugOrder::create([
                'docointkey' => '0000040' . $this->hpercode . date('m/d/Yh:i:s', strtotime(now())) . $dm->chrgcode . $dm->dmdcomb . $dm->dmdctr,
                'enccode' => $enccode,
                'hpercode' => $this->hpercode,
                'rxooccid' => '1',
                'rxoref' => '1',
                'dmdcomb' => $dm->dmdcomb,
                'repdayno1' => '1',
                'rxostatus' => 'A',
                'rxolock' => 'N',
                'rxoupsw' => 'N',
                'rxoconfd' => 'N',
                'dmdctr' => $dm->dmdctr,
                'estatus' => 'U',
                'entryby' => session('employeeid'),
                'ordcon' => 'NEWOR',
                'orderupd' => 'ACTIV',
                'locacode' => 'PHARM',
                'orderfrom' => $dm->chrgcode,
                'issuetype' => 'c',
                'has_tag' => $this->type ? true : false,
                'tx_type' => $this->type,
                'ris' => $this->is_ris ? true : false,
                'pchrgqty' => $this->order_qty,
                'pchrgup' => $dm->current_price->dmselprice,
                'pcchrgamt' => $this->order_qty * $dm->current_price->dmselprice,
                'dodate' => now(),
                'dotime' => now(),
                'dodtepost' => now(),
                'dotmepost' => now(),
                'dmdprdte' => $dm->dmdprdte,
                'exp_date' => $dm->exp_date,
                'loc_code' => $dm->loc_code,
                'item_id' => $dm->id,
                'remarks' => $this->remarks,
                'prescription_data_id' => $rx_id,
                'prescribed_by' => $empid,
            ]);

            // Update prescription data status
            DB::connection('webapp')->table('webapp.dbo.prescription_data')
                ->where('id', $rx_id)
                ->update(['stat' => 'I']);

            $this->resetExcept('generic', 'stocks', 'enccode', 'location_id', 'encounter', 'charges', 'hpercode', 'toecode', 'selected_items', 'patient', 'active_prescription', 'adm', 'wardname', 'rmname', 'summaries');

            $this->alert('success', 'Item added.');
        } else {
            $this->alert('error', 'Insufficient stock!');
        }
    }

    public function update_remarks()
    {
        $this->validate(['selected_remarks' => ['required'], 'new_remarks' => ['nullable', 'string', 'max:255']]);

        $rxo = DrugOrder::find($this->selected_remarks);
        if (!$rxo) {
            return $this->alert('error', 'Order not found.');
        }

        $rxo->remarks = $this->new_remarks;
        $rxo->save();

        $this->alert('success', 'Remarks updated');
        return redirect(route('dispensing.view.enctr', $this->enccode));
    }

    public function deactivate_rx($rx_id)
    {
        $data = PrescriptionData::find($rx_id);
        if (!$data) {
            return $this->alert('error', 'Prescription not found.');
        }
        $data->adttl_remarks = $this->adttl_remarks;
        $data->stat = 'I';
        $data->save();

        $this->alert('success', 'Prescription updated!');
    }

    public function update_qty($docointkey)
    {
        $this->validate([
            'order_qty' => ['required', 'numeric', 'min:1'],
        ]);

        DrugOrder::where('docointkey', $docointkey)
            ->update([
                'pchrgqty' => $this->order_qty,
                'pchrgup' => $this->unit_price,
                'pcchrgamt' => $this->order_qty * $this->unit_price
            ]);

        $this->alert('success', 'Order updated!');
    }
}
