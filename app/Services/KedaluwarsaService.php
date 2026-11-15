<?php

namespace App\Services;

use App\Models\KantongDarah;
use App\Models\LogTransaksi;
use Illuminate\Support\Facades\DB;

class KedaluwarsaService
{
    /** Ubah kantong yang lewat tanggal kedaluwarsa menjadi Kedaluwarsa. Mengembalikan jumlahnya. */
    public function tandai(): int
    {
        $jumlah = 0;

        KantongDarah::whereIn('status', ['karantina', 'ready', 'ditahan'])
            ->whereDate('tanggal_kedaluwarsa', '<', today())
            ->chunkById(100, function ($kantongs) use (&$jumlah) {
                foreach ($kantongs as $kantong) {
                    DB::transaction(function () use ($kantong) {
                        $lama = $kantong->status;
                        $kantong->update(['status' => 'kedaluwarsa']);

                        LogTransaksi::catat(
                            'kantong_kedaluwarsa', 'kantong', $kantong->id,
                            "Kantong {$kantong->kode_batch} melewati tanggal kedaluwarsa",
                            [
                                'lama' => ['status' => $lama],
                                'baru' => ['status' => 'kedaluwarsa'],
                                'user' => null, // dijalankan sistem
                            ]
                        );
                    });
                    $jumlah++;
                }
            });

        return $jumlah;
    }
}