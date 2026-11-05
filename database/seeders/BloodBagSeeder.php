<?php

namespace Database\Seeders;

use App\Models\BloodBag;
use Illuminate\Database\Seeder;

class BloodBagSeeder extends Seeder
{
    public function run(): void
    {
        BloodBag::factory()->count(10)->create();
    }
}