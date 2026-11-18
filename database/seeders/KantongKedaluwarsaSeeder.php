<?php

namespace Database\Seeders;

use App\Models\KantongDarah;
use Illuminate\Database\Seeder;

class KantongKedaluwarsaSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['EXP-01', 'ready',     -3],
            ['EXP-02', 'ready',     -1],
            ['EXP-03', 'ditahan',   -5],
            ['EXP-04', 'karantina', -2],
            ['OK-01',  'ready',     20],
            ['OK-02',  'ready',      7],
        ];

        foreach ($data as [$kode, $status, $selisihHari]) {
            KantongDarah::firstOrCreate(
                ['kode_batch' => 'BATCH-DEMO-' . $kode],
                [
                    'tanggal_donor'       => today()->subDays(30),
                    'volume_ml'           => 350,
                    'komponen'            => 'prc',
                    'golongan'            => 'O',
                    'rhesus'              => 'pos',
                    'tanggal_kedaluwarsa' => today()->addDays($selisihHari),
                    'status'              => $status,
                ]
            );
        }
    }
}