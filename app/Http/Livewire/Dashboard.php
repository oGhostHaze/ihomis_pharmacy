<?php

namespace App\Http\Livewire;

use App\Models\DrugManualLogHeader;
use App\Models\DrugManualLogItem;
use App\Models\Pharmacy\Drugs\ConsumptionLogDetail;
use App\Models\Pharmacy\Drugs\DrugStock;
use App\Models\Pharmacy\Drugs\DrugStockLog;
use App\Models\User;
use App\Models\UserSession;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class Dashboard extends Component
{
    use LivewireAlert;

    protected $listeners = ['start_log', 'stop_log'];
    public $password;
    public $location_id, $below_date;

    public function render()
    {
        $near_expiry = DrugStock::where('loc_code', $this->location_id)
            ->where('exp_date', '<', $this->below_date)
            ->where('exp_date', '>', now())
            ->where('stock_bal', '>', 0)
            ->count();

        $expired = DrugStock::where('loc_code', $this->location_id)
            ->where('exp_date', '<=', now())
            ->where('stock_bal', '>', 0)
            ->count();

        $near_reorder = count(DB::select("SELECT pds.drug_concat, SUM(pds.stock_bal) as stock_bal,
                                (SELECT reorder_point
                                    FROM pharm_drug_stock_reorder_levels as level
                                    WHERE pds.dmdcomb = level.dmdcomb AND pds.dmdctr = level.dmdctr AND pds.loc_code = level.loc_code) as reorder_point,
                                    pds.dmdcomb, pds.dmdctr
                                FROM pharm_drug_stocks as pds
                                WHERE pds.loc_code = " . $this->location_id . "
                                    AND EXISTS (SELECT id FROM pharm_drug_stock_reorder_levels level WHERE pds.dmdcomb = level.dmdcomb
                                                AND pds.dmdctr = level.dmdctr AND pds.loc_code = level.loc_code
                                                AND reorder_point > 0
                                                AND level.reorder_point < stock_bal
                                                AND level.reorder_point < (stock_bal - (stock_bal * 0.3)))
                                GROUP BY pds.drug_concat, pds.loc_code, pds.dmdcomb, pds.dmdctr
                        "));

        $critical = count(DB::select("SELECT pds.drug_concat, SUM(pds.stock_bal) as stock_bal,
                                (SELECT reorder_point
                                    FROM pharm_drug_stock_reorder_levels as level
                                    WHERE pds.dmdcomb = level.dmdcomb AND pds.dmdctr = level.dmdctr AND pds.loc_code = level.loc_code) as reorder_point,
                                    pds.dmdcomb, pds.dmdctr
                                FROM pharm_drug_stocks as pds
                                WHERE pds.loc_code = " . $this->location_id . "
                                    AND EXISTS (SELECT id FROM pharm_drug_stock_reorder_levels level WHERE pds.dmdcomb = level.dmdcomb
                                                AND pds.dmdctr = level.dmdctr AND pds.loc_code = level.loc_code
                                                AND reorder_point > 0
                                                AND level.reorder_point >= stock_bal)
                                GROUP BY pds.drug_concat, pds.loc_code, pds.dmdcomb, pds.dmdctr
                                        "));

        $date_from = Carbon::parse(now())->startOfDay()->format('Y-m-d H:i:s');
        $date_to = Carbon::parse(now())->endOfDay()->format('Y-m-d H:i:s');

        $pending_order = count(DB::select("SELECT rxo.pcchrgcod
                                            FROM hrxo rxo
                                            WHERE   (dodate BETWEEN '" . $date_from . "' and '" . $date_to . "')
                                            AND ((rxo.estatus = 'U' OR rxo.estatus = 'P')
                                                OR (rxo.estatus = 'S' AND (rxo.pcchrgcod IS NULL OR rxo.pcchrgcod = ''))
                                            AND rxo.loc_code = '" . $this->location_id . "')
                                            GROUP BY rxo.pcchrgcod
                                            "));

        return view('livewire.dashboard', compact(
            'near_expiry',
            'expired',
            'near_reorder',
            'critical',
            'pending_order',
        ));
    }

    public function mount()
    {
        $this->location_id = Auth::user()->pharm_location_id;
        $this->below_date = Carbon::parse(now())->addMonths(6)->format('Y-m-d');
    }

    public function start_log()
    {
        if (session('active_consumption')) {
            $this->alert('warning', 'Logger currently active');
        } else {
            if (Hash::check($this->password, Auth::user()->password)) {
                $pharm_location_id = session('pharm_location_id');
                $active_consumption = ConsumptionLogDetail::create([
                    'consumption_from' => now(),
                    'status' => 'A',
                    'entry_by' => session('user_id'),
                    'loc_code' => $pharm_location_id,
                ]);

                $active_manual_consumption = DrugManualLogHeader::create([
                    'consumption_from' => now(),
                    'status' => 'A',
                    'entry_by' => session('user_id'),
                    'loc_code' => $pharm_location_id,
                ]);

                $users = User::where('pharm_location_id', $pharm_location_id)->get();
                foreach ($users as $user) {
                    $sessions = UserSession::where('user_id', '<>', '1')->where('user_id', $user->id)->get();
                    foreach ($sessions as $session) {
                        $session->delete();
                    }
                }

                $date = Carbon::parse(now())->format('Y-m-d');
                $stocks = DrugStock::select('id', 'stock_bal', 'dmdcomb', 'dmdctr', 'exp_date', 'drug_concat', 'chrgcode', 'loc_code', 'dmdprdte', 'retail_price')
                ->with('current_price')
                ->where('loc_code', $pharm_location_id)
                ->where('stock_bal', '>', 0)
                ->get();
                foreach ($stocks as $stock) {
                    $log = DrugStockLog::create([
                        'loc_code' => $stock->loc_code,
                        'dmdcomb' => $stock->dmdcomb,
                        'dmdctr' => $stock->dmdctr,
                        'chrgcode' => $stock->chrgcode,
                        'unit_cost' => $stock->current_price ? $stock->current_price->acquisition_cost : 0,
                        'unit_price' => $stock->retail_price,
                        'beg_bal' => $stock->stock_bal,
                        'consumption_id' => $active_consumption->id,
                    ]);

                    DrugManualLogItem::create([
                        'loc_code' => $stock->loc_code,
                        'dmdcomb' => $stock->dmdcomb,
                        'dmdctr' => $stock->dmdctr,
                        'chrgcode' => $stock->chrgcode,
                        'unit_cost' => $stock->current_price ? $stock->current_price->acquisition_cost : 0,
                        'unit_price' => $stock->retail_price,
                        'beg_bal' => $stock->stock_bal,
                        'detail_id' => $active_manual_consumption->id,
                    ]);
                }

                session(['active_consumption' => $active_consumption->id]);

                $this->alert('success', 'Drug Consumption Logger has been initialized successfully on ' . now());
            } else {
                $this->alert('error', 'Wrong password!');
            }
        }
    }

    public function stop_log()
    {
        if (session('active_consumption')) {
            if (Hash::check($this->password, Auth::user()->password)) {
                $active_consumption = ConsumptionLogDetail::find(session('active_consumption'));
                $active_consumption->consumption_to = now();
                $active_consumption->status = 'I';
                $active_consumption->closed_by = session('user_id');
                $active_consumption->save();

                session(['active_consumption' => null]);
                $pharm_location_id = session('pharm_location_id');
                //
                $users = User::where('pharm_location_id', $pharm_location_id)->get();
                foreach ($users as $user) {
                    $sessions = UserSession::where('user_id', '<>', '1')->where('user_id', $user->id)->get();
                    foreach ($sessions as $session) {
                        $session->delete();
                    }
                }
                //
                $this->alert('success', 'Drug Consumption Logger has been successfully stopped on ' . now());
            } else {
                $this->alert('error', 'Wrong password!');
            }
        } else {
            $this->alert('warning', 'Logger currently inactive');
        }
    }
}
