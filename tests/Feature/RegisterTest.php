<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function payload(array $o = []): array
    {
        return array_merge([
            'role' => 'pendonor', 'name' => 'Sari', 'email' => 'sari@test.id', 'instansi' => 'Kampus',
            'password' => 'rahasia123', 'password_confirmation' => 'rahasia123',
        ], $o);
    }

    public function test_halaman_register_tampil(): void
    {
        $this->get('/register')->assertOk()->assertSee('Registrasi Akun');
    }

    public function test_registrasi_pendonor_tersimpan_dan_redirect_sesuai_peran(): void
    {
        $this->post('/register', $this->payload())->assertRedirect('/pendonor/dashboard');

        $this->assertDatabaseHas('users', ['email' => 'sari@test.id', 'instansi' => 'Kampus']);
        $u = User::where('email', 'sari@test.id')->first();
        $this->assertTrue($u->hasRole('pendonor'));
        $this->assertTrue(Hash::check('rahasia123', $u->password));
        $this->assertAuthenticatedAs($u);
    }

    public function test_registrasi_petugas_rs_redirect_ke_dashboard_petugas(): void
    {
        $this->post('/register', $this->payload(['role' => 'petugas_rs', 'instansi' => 'RSUD Jakarta']))
            ->assertRedirect('/petugas/dashboard');

        $this->assertTrue(User::first()->hasRole('petugas_rs'));
    }

    public function test_petugas_rs_wajib_mengisi_instansi(): void
    {
        $this->post('/register', $this->payload(['role' => 'petugas_rs', 'instansi' => '']))
            ->assertSessionHasErrors('instansi');
        $this->assertEquals(0, User::count());
    }

    public function test_admin_pmi_bisa_daftar_dengan_kode_yang_benar(): void
    {
        config(['bloodconnect.admin_register_code' => 'KODE-RAHASIA']);

        $this->post('/register', $this->payload([
            'role' => 'admin_pmi', 'instansi' => 'PMI Pusat', 'kode_admin' => 'KODE-RAHASIA',
        ]))->assertRedirect('/admin/dashboard');

        $this->assertTrue(User::first()->hasRole('admin_pmi'));
    }

    public function test_admin_pmi_ditolak_jika_kode_salah_atau_kosong(): void
    {
        config(['bloodconnect.admin_register_code' => 'KODE-RAHASIA']);

        $this->post('/register', $this->payload(['role' => 'admin_pmi', 'instansi' => 'PMI', 'kode_admin' => 'salah']))
            ->assertSessionHasErrors('kode_admin');
        $this->post('/register', $this->payload(['role' => 'admin_pmi', 'instansi' => 'PMI']))
            ->assertSessionHasErrors('kode_admin');

        $this->assertEquals(0, User::count());
    }

    public function test_registrasi_admin_nonaktif_jika_kode_belum_diatur(): void
    {
        config(['bloodconnect.admin_register_code' => null]);

        $this->post('/register', $this->payload(['role' => 'admin_pmi', 'instansi' => 'PMI', 'kode_admin' => '']))
            ->assertSessionHasErrors('kode_admin');
        $this->assertEquals(0, User::count());
    }

    public function test_peran_tidak_dikenal_ditolak(): void
    {
        $this->post('/register', $this->payload(['role' => 'superadmin']))->assertSessionHasErrors('role');
        $this->assertEquals(0, User::count());
    }

    public function test_email_harus_unik(): void
    {
        $this->post('/register', $this->payload());
        auth()->logout();
        $this->post('/register', $this->payload())->assertSessionHasErrors('email');
        $this->assertEquals(1, User::count());
    }

    public function test_validasi_registrasi(): void
    {
        $this->post('/register', [])->assertSessionHasErrors(['role', 'name', 'email', 'password']);
        $this->post('/register', $this->payload(['password' => '123', 'password_confirmation' => '123']))
            ->assertSessionHasErrors('password');
        $this->post('/register', $this->payload(['password_confirmation' => 'beda12345']))
            ->assertSessionHasErrors('password');
    }

    public function test_login_sesuai_peran_hasil_registrasi(): void
    {
        $this->post('/register', $this->payload(['role' => 'petugas_rs', 'instansi' => 'RSUD']));
        $this->post('/logout');

        // Tab peran yang salah ditolak
        $this->post('/login', ['email' => 'sari@test.id', 'password' => 'rahasia123', 'role' => 'pendonor'])
            ->assertSessionHasErrors('email');

        // Tab peran yang benar diterima
        $this->post('/login', ['email' => 'sari@test.id', 'password' => 'rahasia123', 'role' => 'petugas_rs'])
            ->assertRedirect('/petugas/dashboard');
    }
}
