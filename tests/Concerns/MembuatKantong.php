<?php

namespace Tests\Concerns;

use App\Models\KantongDarah;
use App\Models\User;
use Spatie\Permission\Models\Role;

trait MembuatKantong
{
    protected int $urutKantong = 0;

    protected function buatKantong(array $o = []): KantongDarah
    {
        $this->urutKantong++;

        return KantongDarah::create(array_merge([
            'kode_batch'          => 'BATCH-TEST-' . $this->urutKantong,
            'tanggal_donor'       => today()->subDays(10),
            'volume_ml'           => 350,
            'komponen'            => 'prc',
            'golongan'            => 'A',
            'rhesus'              => 'pos',
            'tanggal_kedaluwarsa' => today()->addDays(20),
            'status'              => 'ready',
        ], $o));
    }

    protected function kantongKedaluwarsa(string $status = 'ready', int $lewat = 3): KantongDarah
    {
        return $this->buatKantong([
            'tanggal_kedaluwarsa' => today()->subDays($lewat),
            'status'              => $status,
        ]);
    }

    protected function masukSebagaiAdminPmi(): User
    {
        Role::findOrCreate('admin_pmi', 'web');
        $user = User::factory()->create();
        $user->assignRole('admin_pmi');
        $this->actingAs($user);

        return $user;
    }
}