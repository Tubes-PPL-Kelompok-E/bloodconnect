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
            'name' => 'Sari', 'email' => 'sari@test.id', 'instansi' => 'Kampus',
            'password' => 'rahasia123', 'password_confirmation' => 'rahasia123',
        ], $o);
    }

    public function test_halaman_register_tampil(): void
    {
        $this->get('/register')->assertOk()->assertSee('Daftar Pendonor');
    }

    public function test_registrasi_tersimpan_di_database_dan_otomatis_pendonor(): void
    {
        $this->post('/register', $this->payload())->assertRedirect('/pendonor/dashboard');

        $this->assertDatabaseHas('users', ['email' => 'sari@test.id', 'instansi' => 'Kampus']);
        $u = User::where('email', 'sari@test.id')->first();
        $this->assertTrue($u->hasRole('pendonor'));
        $this->assertTrue(Hash::check('rahasia123', $u->password));
        $this->assertAuthenticatedAs($u);
    }

    public function test_peran_tidak_bisa_dipaksa_lewat_input(): void
    {
        $this->post('/register', $this->payload(['role' => 'admin_pmi']));
        $this->assertTrue(User::first()->hasRole('pendonor'));
        $this->assertFalse(User::first()->hasRole('admin_pmi'));
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
        $this->post('/register', [])->assertSessionHasErrors(['name', 'email', 'password']);
        $this->post('/register', $this->payload(['password' => '123', 'password_confirmation' => '123']))
            ->assertSessionHasErrors('password');
        $this->post('/register', $this->payload(['password_confirmation' => 'beda12345']))
            ->assertSessionHasErrors('password');
    }

    public function test_akun_hasil_registrasi_bisa_login(): void
    {
        $this->post('/register', $this->payload());
        $this->post('/logout');
        $this->post('/login', ['email' => 'sari@test.id', 'password' => 'rahasia123', 'role' => 'pendonor'])
            ->assertRedirect('/pendonor/dashboard');
    }
}
