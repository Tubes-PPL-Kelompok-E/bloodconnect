<?php

namespace Tests\Feature;

use App\Models\PemusnahanKantong;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatKantong;
use Tests\TestCase;

class PemusnahanKantongTest extends TestCase
{
    use RefreshDatabase, MembuatKantong;

    protected function setUp(): void
    {
        parent::setUp();
        $this->masukSebagaiAdminPmi();
    }

    private function payload($kantong, array $o = []): array
    {
        return array_merge([
            'kantong_id'         => $kantong->id,
            'tanggal_pemusnahan' => today()->toDateString(),
            'alasan'             => 'Melewati tanggal kedaluwarsa',
        ], $o);
    }

    public function test_tambah_pemusnahan_kantong_kedaluwarsa(): void
    {
        $kantong = $this->kantongKedaluwarsa('kedaluwarsa');

        $this->post(route('admin.pemusnahan.store'), $this->payload($kantong))->assertRedirect();

        $p = PemusnahanKantong::first();
        $this->assertSame('dimusnahkan', $kantong->fresh()->status);
        $this->assertSame('kedaluwarsa', $p->status_sebelumnya);
        $this->assertFalse($p->sebelum_kedaluwarsa);
        $this->assertNotEmpty($p->nama_petugas);
        $this->assertDatabaseHas('log_transaksi', ['aksi' => 'kantong_dimusnahkan', 'entitas_id' => $kantong->id]);
    }

    public function test_kantong_belum_kedaluwarsa_wajib_alasan_khusus(): void
    {
        $kantong = $this->buatKantong(['status' => 'ready']);

        $this->post(route('admin.pemusnahan.store'), $this->payload($kantong))
            ->assertSessionHasErrors('alasan_khusus');
        $this->assertSame('ready', $kantong->fresh()->status);

        $this->post(route('admin.pemusnahan.store'),
            $this->payload($kantong, ['alasan_khusus' => 'Kantong bocor saat penyimpanan']))
            ->assertRedirect();

        $this->assertTrue(PemusnahanKantong::first()->sebelum_kedaluwarsa);
        $this->assertSame('dimusnahkan', $kantong->fresh()->status);
    }

    public function test_validasi_gagal_tanpa_isian_wajib(): void
    {
        $this->post(route('admin.pemusnahan.store'), [])
            ->assertSessionHasErrors(['kantong_id', 'tanggal_pemusnahan', 'alasan']);
    }

    public function test_kantong_tidak_bisa_dimusnahkan_dua_kali(): void
    {
        $kantong = $this->kantongKedaluwarsa();
        $this->post(route('admin.pemusnahan.store'), $this->payload($kantong));

        $this->post(route('admin.pemusnahan.store'), $this->payload($kantong))
            ->assertSessionHasErrors('kantong_id');
    }

    public function test_baca_daftar_kedaluwarsa_riwayat_dan_detail(): void
    {
        $kedaluwarsa = $this->kantongKedaluwarsa('kedaluwarsa');
        $layak = $this->buatKantong(['status' => 'ready']);

        $this->get(route('admin.kedaluwarsa'))
            ->assertOk()
            ->assertSee($kedaluwarsa->kode_batch)
            ->assertDontSee($layak->kode_batch);

        $this->post(route('admin.pemusnahan.store'), $this->payload($kedaluwarsa));
        $p = PemusnahanKantong::first();

        $this->get(route('admin.pemusnahan'))->assertOk()->assertSee($kedaluwarsa->kode_batch);
        $this->get(route('admin.pemusnahan.show', $p))->assertOk()->assertSee($kedaluwarsa->kode_batch);
    }

    public function test_cek_kedaluwarsa_manual(): void
    {
        $kantong = $this->kantongKedaluwarsa('ready');

        $this->post(route('admin.kedaluwarsa.periksa'))->assertRedirect(route('admin.kedaluwarsa'));

        $this->assertSame('kedaluwarsa', $kantong->fresh()->status);
    }

    public function test_ubah_catatan_pemusnahan(): void
    {
        $kantong = $this->kantongKedaluwarsa();
        $this->post(route('admin.pemusnahan.store'), $this->payload($kantong));
        $p = PemusnahanKantong::first();

        $this->put(route('admin.pemusnahan.update', $p), [
            'tanggal_pemusnahan' => today()->toDateString(),
            'alasan'             => 'Alasan diperbaiki oleh petugas',
        ])->assertRedirect(route('admin.pemusnahan.show', $p));

        $this->assertSame('Alasan diperbaiki oleh petugas', $p->fresh()->alasan);
        $this->assertSame('dimusnahkan', $kantong->fresh()->status);
    }

    public function test_ubah_catatan_kantong_belum_kedaluwarsa_tetap_wajib_alasan_khusus(): void
    {
        $kantong = $this->buatKantong(['status' => 'ready']);
        $this->post(route('admin.pemusnahan.store'),
            $this->payload($kantong, ['alasan_khusus' => 'Kantong rusak parah']));
        $p = PemusnahanKantong::first();

        $this->put(route('admin.pemusnahan.update', $p), [
            'tanggal_pemusnahan' => today()->toDateString(),
            'alasan'             => 'Alasan umum',
            'alasan_khusus'      => '',
        ])->assertSessionHasErrors('alasan_khusus');
    }

    public function test_batalkan_mengembalikan_status_kantong_yang_masih_berlaku(): void
    {
        $kantong = $this->buatKantong(['status' => 'ready']);
        $this->post(route('admin.pemusnahan.store'),
            $this->payload($kantong, ['alasan_khusus' => 'Kantong rusak parah']));
        $p = PemusnahanKantong::first();

        $this->delete(route('admin.pemusnahan.destroy', $p))->assertRedirect(route('admin.pemusnahan'));

        $this->assertDatabaseMissing('pemusnahan_kantong', ['id' => $p->id]);
        $this->assertSame('ready', $kantong->fresh()->status);
    }

    public function test_batalkan_kantong_yang_sudah_lewat_tanggal_kembali_ke_kedaluwarsa(): void
    {
        $kantong = $this->kantongKedaluwarsa('ready');
        $this->post(route('admin.pemusnahan.store'), $this->payload($kantong));
        $p = PemusnahanKantong::first();

        $this->delete(route('admin.pemusnahan.destroy', $p));

        $this->assertSame('kedaluwarsa', $kantong->fresh()->status);
    }

    public function test_pengguna_tanpa_role_admin_pmi_ditolak(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('admin.pemusnahan'))->assertForbidden();
    }
}