<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['Admin PMI',  'admin.pmi@bloodconnect.id',  'PMI Pusat',    'admin_pmi'],
            ['Petugas RS', 'petugas.rs@bloodconnect.id', 'RSUD Jakarta', 'petugas_rs'],
            ['Pendonor',   'pendonor@bloodconnect.id',   'Umum',         'pendonor'],
        ];

        foreach ($data as [$name, $email, $instansi, $role]) {
            $user = User::firstOrCreate(
                ['email' => $email],
                ['name' => $name, 'instansi' => $instansi, 'password' => 'password123']
            );
            $user->syncRoles([$role]);
        }
    }
}
