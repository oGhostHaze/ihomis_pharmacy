<?php

namespace App\Http\Livewire;

use Carbon\Carbon;
use Livewire\Component;
use App\Models\Pharmacy\Drugs\DrugStock;
use App\Models\Pharmacy\PharmLocation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class DashboardExecutive extends Component
{
    use LivewireAlert;

    // Properties
    public $password;
    public $location_id;
    public $below_date;
    public $date_range = 'today';
    public $custom_date_from;
    public $custom_date_to;
    public $stats = [];
    public $transactions = [];
    public $emergency_purchases = [];
    public $top_drugs = [];
    public $recent_activities = [];
    public $locations = [];
    // Listeners
    protected $listeners = [
        'start_log',
        'stop_log',
        'refreshDashboard' => '$refresh'
    ];

    // Initialize component
    public function mount()
    {
        $this->location_id = Auth::user()->pharm_location_id;
        $this->below_date = Carbon::parse(now())->addMonths(6)->format('Y-m-d');
        $this->custom_date_from = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->custom_date_to = Carbon::now()->format('Y-m-d');

        $this->loadDashboardData();
    }

    // Render component
    public function render()
    {
        return view('livewire.dashboard-executive', [
            'stats' => $this->stats,
            'transactions' => $this->transactions,
            'emergency_purchases' => $this->emergency_purchases,
            'top_drugs' => $this->top_drugs,
            'recent_activities' => $this->recent_activities,
        ]);
    }

    // Update date range and refresh data
    public function updatedDateRange()
    {
        $this->loadDashboardData();
    }

    // Update custom date range and refresh data
    public function updatedCustomDateFrom()
    {
        if ($this->date_range === 'custom') {
            $this->loadDashboardData();
        }
    }

    public function updatedCustomDateTo()
    {
        if ($this->date_range === 'custom') {
            $this->loadDashboardData();
        }
    }

    // Load dashboard data based on selected date range
    public function loadDashboardData()
    {
        // Set date range based on selection
        $date_from = Carbon::now()->startOfDay();
        $date_to = Carbon::now()->endOfDay();

        switch ($this->date_range) {
            case 'today':
                $date_from = Carbon::now()->startOfDay();
                $date_to = Carbon::now()->endOfDay();
                break;
            case 'yesterday':
                $date_from = Carbon::now()->subDay()->startOfDay();
                $date_to = Carbon::now()->subDay()->endOfDay();
                break;
            case 'this_week':
                $date_from = Carbon::now()->startOfWeek();
                $date_to = Carbon::now()->endOfWeek();
                break;
            case 'last_week':
                $date_from = Carbon::now()->subWeek()->startOfWeek();
                $date_to = Carbon::now()->subWeek()->endOfWeek();
                break;
            case 'this_month':
                $date_from = Carbon::now()->startOfMonth();
                $date_to = Carbon::now()->endOfMonth();
                break;
            case 'last_month':
                $date_from = Carbon::now()->subMonth()->startOfMonth();
                $date_to = Carbon::now()->subMonth()->endOfMonth();
                break;
            case 'custom':
                $date_from = Carbon::parse($this->custom_date_from)->startOfDay();
                $date_to = Carbon::parse($this->custom_date_to)->endOfDay();
                break;
        }

        // Format dates for queries
        $date_from_formatted = $date_from->format('Y-m-d H:i:s');
        $date_to_formatted = $date_to->format('Y-m-d H:i:s');

        // Load dashboard statistics
        $this->loadDashboardStats($date_from_formatted, $date_to_formatted);
        $this->loadLocations();
    }

    // Load dashboard statistics
    private function loadDashboardStats($date_from, $date_to)
    {
        // Near expiry stock count
        $near_expiry = DrugStock::where('exp_date', '<', $this->below_date)
            ->where('exp_date', '>', now())
            ->where('stock_bal', '>', 0)
            ->count();

        // Expired stock count
        $expired = DrugStock::where('exp_date', '<=', now())
            ->where('stock_bal', '>', 0)
            ->count();

        // Near reorder level count
        $near_reorder = count(DB::select("SELECT pds.drug_concat, SUM(pds.stock_bal) as stock_bal,
                                (SELECT reorder_point
                                    FROM pharm_drug_stock_reorder_levels as level
                                    WHERE pds.dmdcomb = level.dmdcomb AND pds.dmdctr = level.dmdctr AND pds.loc_code = level.loc_code) as reorder_point,
                                    pds.dmdcomb, pds.dmdctr
                                FROM pharm_drug_stocks as pds
                                WHERE EXISTS (SELECT id FROM pharm_drug_stock_reorder_levels level WHERE pds.dmdcomb = level.dmdcomb
                                                AND pds.dmdctr = level.dmdctr AND pds.loc_code = level.loc_code
                                                AND reorder_point > 0
                                                AND level.reorder_point < stock_bal
                                                AND level.reorder_point < (stock_bal - (stock_bal * 0.3)))
                                GROUP BY pds.drug_concat, pds.loc_code, pds.dmdcomb, pds.dmdctr
                        "));

        // Critical stock count
        $critical = count(DB::select("SELECT pds.drug_concat, SUM(pds.stock_bal) as stock_bal,
                                (SELECT reorder_point
                                    FROM pharm_drug_stock_reorder_levels as level
                                    WHERE pds.dmdcomb = level.dmdcomb AND pds.dmdctr = level.dmdctr AND pds.loc_code = level.loc_code) as reorder_point,
                                    pds.dmdcomb, pds.dmdctr
                                FROM pharm_drug_stocks as pds
                                WHERE EXISTS (SELECT id FROM pharm_drug_stock_reorder_levels level WHERE pds.dmdcomb = level.dmdcomb
                                                AND pds.dmdctr = level.dmdctr AND pds.loc_code = level.loc_code
                                                AND reorder_point > 0
                                                AND level.reorder_point >= stock_bal)
                                GROUP BY pds.drug_concat, pds.loc_code, pds.dmdcomb, pds.dmdctr
                                "));

        // Set data for the view
        $this->stats = [
            'near_expiry' => $near_expiry,
            'expired' => $expired,
            'near_reorder' => $near_reorder,
            'critical' => $critical,
        ];
    }

    private function loadLocations()
    {
        $this->locations = PharmLocation::where('non_pharma', false)
            ->orderBy('description')
            ->get();
    }
}
