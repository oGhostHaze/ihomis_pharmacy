<?php

namespace App\Http\Livewire;

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

    public function render()
    {
        return view('livewire.dashboard');
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

                $users = User::where('pharm_location_id', $pharm_location_id)->get();
                foreach ($users as $user) {
                    $sessions = UserSession::where('user_id', '<>', '1')->where('user_id', $user->id)->get();
                    foreach ($sessions as $session) {
                        $session->delete();
                    }
                }

                $date = Carbon::parse(now())->format('Y-m-d');
                $stocks = DrugStock::select('id', 'stock_bal', 'dmdcomb', 'dmdctr', 'exp_date', 'drug_concat', 'chrgcode', 'loc_code', 'dmdprdte', 'retail_price')->with('current_price')->where('loc_code', $pharm_location_id)->where('stock_bal', '>', 0)->get();
                foreach ($stocks as $stock) {
                    $log = DrugStockLog::firstOrNew([
                        'loc_code' => $stock->loc_code,
                        'dmdcomb' => $stock->dmdcomb,
                        'dmdctr' => $stock->dmdctr,
                        'chrgcode' => $stock->chrgcode,
                        'date_logged' => $date,
                        'unit_cost' => $stock->current_price ? $stock->current_price->acquisition_cost : 0,
                        'unit_price' => $stock->retail_price,
                        'beg_bal' => $stock->stock_bal,
                        'consumption_id' => $active_consumption->id,
                    ]);
                    $log->time_logged = now();
                    $log->save();
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
