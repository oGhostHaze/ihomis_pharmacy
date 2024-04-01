<?php

namespace Database\Seeders;

use App\Models\Pharmacy\PharmLocation;
use Illuminate\Database\Seeder;

class LocationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        PharmLocation::create([
           'description' => 'Warehouse',
        ]);
        PharmLocation::create([
           'description' => 'OPD',
        ]);
        PharmLocation::create([
           'description' => 'Satellite',
        ]);
    }
}
