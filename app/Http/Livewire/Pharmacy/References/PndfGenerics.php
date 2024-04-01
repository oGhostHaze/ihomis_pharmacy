<?php

namespace App\Http\Livewire\Pharmacy\References;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Pharmacy\DrugGroup;
use App\Models\Pharmacy\DrugClass1;
use App\Models\Pharmacy\DrugClass2;
use App\Models\Pharmacy\DrugClass3;
use App\Models\Pharmacy\DrugClass4;
use App\Models\Pharmacy\DrugGeneric;
use App\Models\Pharmacy\DrugClassMajor;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class PndfGenerics extends Component
{
    use LivewireAlert;
    use WithPagination;

    public $gencode, $gendesc, $genstat = 'A', $genlock = 'N', $updsw = 'P', $datemod, $entryby, $rationale, $monitor, $interactions;
    public $major_category, $selected_sub1, $selected_sub2, $selected_sub3, $selected_sub4, $selected_gencode;

    public function render()
    {
        $groups = DrugGroup::paginate(20);
        $majors = DrugClassMajor::all();
        $sub1 = DrugClass1::where('dmcode', $this->major_category)->get();
        $sub2 = DrugClass2::where('dms1key', $this->selected_sub1)->get();
        $sub3 = DrugClass3::where('dms2key', $this->selected_sub2)->get();
        $sub4 = DrugClass4::where('dms3key', $this->selected_sub4)->get();

        return view('livewire.pharmacy.references.pndf-generics', [
            'groups' => $groups,
            'majors' => $majors,
            'sub1' => $sub1,
            'sub2' => $sub2,
            'sub3' => $sub3,
            'sub4' => $sub4,
        ]);
    }

    public function new_generic()
    {
        $this->validate([
            'gencode' => ['required', 'string', 'max:5', 'unique:hospital.dbo.hgen,gencode'],
            'gendesc' => ['required', 'string', 'max:255'],
            'major_category' => ['required', 'string'],
        ]);

        $gen = DrugGeneric::create([
            'gencode' => $this->gencode,
            'gendesc' => $this->gendesc,
            'genstat' => $this->genstat,
            'genlock' => $this->genlock,
            'updsw' => $this->updsw,
            'datemod' => now(),
            'entryby' => session('employeeid'),
            'rationale' => $this->rationale,
            'monitor' => $this->monitor,
            'interactions' => $this->interactions,
        ]);

        $count_grp = DrugGroup::latest('grpdtmd')->first();
        $counter = sprintf("%010d", $count_grp->grpcode + 1);
        dd($counter);
        $group = DrugGroup::create([
            'grpcode' => $counter,
            'grpstat' => 'A',
            'grplock' => 'N',
            'grpupsw' => 'P',
            'grpdtmd' => now(),
            'dmcode' => $this->major_category,
            'dms1key' => $this->selected_sub1,
            'dms2key' => $this->selected_sub2,
            'dms3key' => $this->selected_sub3,
            'dms4key' => $this->selected_sub4,
            'gencode' => $this->gencode,
        ]);

        $this->reset();
        $this->alert('success', 'New generic added!');
    }
}
