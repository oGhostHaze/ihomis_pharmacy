<?php

namespace App\Http\Livewire\Pharmacy\Deliveries;

use App\Models\Pharmacy\DeliveryDetail;
use App\Models\References\ChargeCode;
use App\Models\References\Supplier;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class DeliveryListDonations extends Component
{
    use WithPagination;
    use LivewireAlert;

    protected $listeners = ['add_delivery'];

    public $po_no, $si_no, $pharm_location_id, $user_id, $delivery_date, $suppcode, $delivery_type, $charge_code;

    public $search, $supplier_id = '00688';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {

        $deliveries = DeliveryDetail::with(['supplier', 'items', 'charge'])
            ->when($this->supplier_id, function ($query, $supplier_id) {
                $query->where('suppcode', $supplier_id);
            })
            ->latest()
            ->paginate(15);
        $suppliers = Supplier::where('suppcode', $this->supplier_id)->get();
        $charges = ChargeCode::where('bentypcod', 'DRUME')
            ->where('chrgstat', 'A')
            ->whereIn('chrgcode', array('DRUMAK'))
            ->get();

        return view('livewire.pharmacy.deliveries.delivery-list-donations', [
            'deliveries' => $deliveries,
            'suppliers' => $suppliers,
            'charges' => $charges,

        ]);
    }

    public function add_delivery()
    {
        $this->validate([
            'suppcode' => 'required',
            'charge_code' => 'required',
        ]);

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
