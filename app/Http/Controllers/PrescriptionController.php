<?php

namespace App\Http\Controllers;

use App\Models\Record\Encounters\EncounterLog;
use App\Models\Record\Prescriptions\Prescription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrescriptionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $encounter = EncounterLog::where('enccode', $id)
            ->select('henctr.enccode', 'hperson.hpercode', 'hperson.patfirst', 'hperson.patmiddle', 'hperson.patlast', 'hperson.patsuffix', 'hperson.patbdate', DB::raw("(SELECT dbo.sfn_les_get_patient_address(henctr.hpercode)) AS address"))
            ->join('hperson', 'henctr.hpercode', '=', 'hperson.hpercode')
            ->first();

        $prescription = Prescription::where('enccode', $id)
            ->with(['data' => function ($query) {
                $query->with('dm');
            }])
            ->first();

        $prescriber = DB::table('hpersonal')
            ->select('hpersonal.employeeid', 'lastname', 'middlename', 'firstname', 'licno', 'empalias', 'empdegree')
            ->join('hprovider', 'hprovider.employeeid', '=', 'hpersonal.employeeid')
            ->where('hpersonal.employeeid', $prescription->empid)
            ->first();

        return view('prescriptions.rx-layout', [
            'encounter' => $encounter,
            'prescription' => $prescription,
            'prescriber' => $prescriber,
            'patient' => $encounter, // Assuming patient data is part of the encounter
            'pharmacy' => [
                'name' => 'MARIANO MARCOS MEM HOSP. MED CTR',
                'address' => 'Brgy. 6 San Julian, City of Batac, Ilocos Norte 2906',
                'phone' => '(077) 792-1234',
                'license' => 'PH-12345'
            ]
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
