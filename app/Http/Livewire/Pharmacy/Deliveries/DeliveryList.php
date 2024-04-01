<?php

namespace App\Http\Livewire\Pharmacy\Deliveries;

use App\Models\Pharmacy\DeliveryDetail;
use App\Models\Pharmacy\Drugs\ConsumptionLogDetail;
use App\Models\References\ChargeCode;
use App\Models\References\Supplier;
use Illuminate\Support\Facades\Auth;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class DeliveryList extends Component
{
    use WithPagination;
    use LivewireAlert;

    protected $listeners = ['add_delivery'];

    public $po_no, $si_no, $pharm_location_id, $user_id, $delivery_date, $suppcode, $delivery_type, $charge_code;

    public $search;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {

        $deliveries = DeliveryDetail::with('supplier')->with('items')->with('charge')->latest()->paginate(15);
        $suppliers = Supplier::all();
        $charges = ChargeCode::where('bentypcod', 'DRUME')
            ->where('chrgstat', 'A')
            ->whereIn('chrgcode', array('DRUMA', 'DRUMB', 'DRUMC', 'DRUME', 'DRUMK', 'DRUMAA', 'DRUMAB', 'DRUMR', 'DRUMS', 'DRUMAD', 'DRUMAE', 'DRUMAF', 'DRUMAG', 'DRUMAH'))
            ->get();

        return view('livewire.pharmacy.deliveries.delivery-list', [
            'deliveries' => $deliveries,
            'suppliers' => $suppliers,
            'charges' => $charges,

        ]);
    }

    public function add_delivery()
    {
        $delivery = new DeliveryDetail();
        $delivery->po_no = $this->po_no;
        $delivery->si_no = $this->si_no;
        $delivery->pharm_location_id = session('pharm_location_id');
        $delivery->user_id = session('user_id');
        $delivery->delivery_date = $this->delivery_date;
        $delivery->suppcode = $this->suppcode;
        $delivery->delivery_type = $this->delivery_type;
        $delivery->charge_code = $this->charge_code;
        $delivery->save();

        $this->redirect(route('delivery.view', [$delivery->id, true]));
        $this->alert('success', 'Delivery details saved!');
    }
}
