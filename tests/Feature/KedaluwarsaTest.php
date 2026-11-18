<?php

namespace Tests\Feature;

use App\Models\KantongDarah;
use App\Models\LogTransaksi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\Concerns\MembuatKantong;
use Tests\TestCase;

class KedaluwarsaTest extends TestCase
{
    use RefreshDatabase, MembuatKantong;

    public function test_kantong_lewat_tanggal_menjadi_kedaluwarsa_dan_keluar_dari_stok_siap(): void
    {
        $ready = $this->kantongKedaluwarsa('ready');
        $karantina = $this->kantongKedaluwarsa('karantina');
        $ditahan = $this->kantongKedaluwarsa('ditahan');
        $layak = $this->buatKantong(['status' => 'ready']);

        $this->artisan('kantong:tandai-kedaluwarsa')->assertSuccessful();

        $this->assertSame('kedaluwarsa', $ready->fresh()->status);
        $this->assertSame('kedaluwarsa', $karantina->fresh()->status);
        $this->assertSame('kedaluwarsa', $ditahan->fresh()->status);
        $this->assertSame('ready', $layak->fresh()->status);
        $this->assertSame(1, KantongDarah::siapPakai()->count());
    }

    public function test_kantong_yang_kedaluwarsa_hari_ini_masih_berlaku(): void
    {
        $kantong = $this->buatKantong(['status' => 'ready', 'tanggal_kedaluwarsa' => today()]);

        $this->artisan('kantong:tandai-kedaluwarsa');

        $this->assertSame('ready', $kantong->fresh()->status);
    }

    public function test_kantong_dimusnahkan_tidak_diubah(): void
    {
        $kantong = $this->kantongKedaluwarsa('dimusnahkan');

        $this->artisan('kantong:tandai-kedaluwarsa');

        $this->assertSame('dimusnahkan', $kantong->fresh()->status);
    }

    public function test_command_aman_dijalankan_berulang(): void
    {
        $kantong = $this->kantongKedaluwarsa();

        $this->artisan('kantong:tandai-kedaluwarsa');
        $this->artisan('kantong:tandai-kedaluwarsa');

        $this->assertSame('kedaluwarsa', $kantong->fresh()->status);
        $this->assertSame(1, LogTransaksi::where('aksi', 'kantong_kedaluwarsa')->count());
    }

    public function test_perubahan_tercatat_di_log_transaksi(): void
    {
        $kantong = $this->kantongKedaluwarsa();

        $this->artisan('kantong:tandai-kedaluwarsa');

        $this->assertDatabaseHas('log_transaksi', [
            'aksi'       => 'kantong_kedaluwarsa',
            'entitas_id' => $kantong->id,
        ]);
    }

    public function test_command_terdaftar_di_scheduler(): void
    {
        Artisan::call('schedule:list');

        $this->assertStringContainsString('kantong:tandai-kedaluwarsa', Artisan::output());
    }
}