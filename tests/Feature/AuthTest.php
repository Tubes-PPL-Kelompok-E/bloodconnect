<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function makeUser(string $role): User
    {
        $u = User::create([
            'name' => ucfirst($role), 'email' => "$role@test.id",
            'instansi' => 'Test', 'password' => 'password123',
        ]);
        $u->assignRole($role);
        return $u;
    }

    public function test_halaman_login_tampil(): void
    {
        $this->get('/login')->assertOk()->assertSee('BloodConnect');
    }

    public function test_redirect_sesuai_peran(): void
    {
        foreach ([
            'admin_pmi'  => '/admin/dashboard',
            'petugas_rs' => '/petugas/dashboard',
            'pendonor'   => '/pendonor/dashboard',
        ] as $role => $url) {
            $this->makeUser($role);
            $this->post('/login', ['email' => "$role@test.id", 'password' => 'password123', 'role' => $role])
                ->assertRedirect($url);
            $this->post('/logout');
        }
    }

    public function test_password_salah_ditolak(): void
    {
        $this->makeUser('admin_pmi');
        $this->post('/login', ['email' => 'admin_pmi@test.id', 'password' => 'salah', 'role' => 'admin_pmi'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_peran_tab_tidak_sesuai_ditolak(): void
    {
        $this->makeUser('pendonor');
        $this->post('/login', ['email' => 'pendonor@test.id', 'password' => 'password123', 'role' => 'admin_pmi'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_rate_limiting_setelah_5_kali_gagal(): void
    {
        $this->makeUser('admin_pmi');
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'admin_pmi@test.id', 'password' => 'salah', 'role' => 'admin_pmi']);
        }
        $this->post('/login', ['email' => 'admin_pmi@test.id', 'password' => 'password123', 'role' => 'admin_pmi'])
            ->assertSessionHasErrors('email');

        $this->assertStringContainsString('Terlalu banyak', session('errors')->first('email'));
        $this->assertGuest();
    }

    public function test_akun_nonaktif_tidak_bisa_login(): void
    {
        $this->makeUser('pendonor')->delete(); // soft delete
        $this->post('/login', ['email' => 'pendonor@test.id', 'password' => 'password123', 'role' => 'pendonor'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_peran_lain_tidak_bisa_akses_halaman_admin(): void
    {
        $this->actingAs($this->makeUser('pendonor'))->get('/admin/users')->assertForbidden();
    }
}
