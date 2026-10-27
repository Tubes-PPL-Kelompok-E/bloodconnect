<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function makeUser(): User
    {
        $u = User::create(['name' => 'Sari', 'email' => 'sari@test.id', 'instansi' => 'Umum', 'password' => 'lama12345']);
        $u->assignRole('pendonor');
        return $u;
    }

    public function test_halaman_lupa_password_tampil(): void
    {
        $this->get('/forgot-password')->assertOk()->assertSee('Lupa Kata Sandi');
    }

    public function test_tautan_dikirim_untuk_email_terdaftar(): void
    {
        Notification::fake();
        $u = $this->makeUser();

        $this->post('/forgot-password', ['email' => $u->email])->assertSessionHas('status');
        Notification::assertSentTo($u, ResetPassword::class);
    }

    public function test_email_tidak_terdaftar_tetap_dijawab_sama_tanpa_kirim(): void
    {
        Notification::fake();

        $this->post('/forgot-password', ['email' => 'tidakada@test.id'])->assertSessionHas('status');
        Notification::assertNothingSent();
    }

    public function test_akun_nonaktif_tidak_dikirimi_tautan(): void
    {
        Notification::fake();
        $u = $this->makeUser();
        $u->delete();

        $this->post('/forgot-password', ['email' => $u->email]);
        Notification::assertNothingSent();
    }

    public function test_reset_password_dengan_token_valid(): void
    {
        $u = $this->makeUser();
        $token = Password::createToken($u);

        $this->post('/reset-password', [
            'token' => $token, 'email' => $u->email,
            'password' => 'baru12345', 'password_confirmation' => 'baru12345',
        ])->assertRedirect('/login');

        $this->assertTrue(Hash::check('baru12345', $u->fresh()->password));
        $this->assertFalse(Hash::check('lama12345', $u->fresh()->password));
    }

    public function test_token_salah_ditolak(): void
    {
        $u = $this->makeUser();

        $this->post('/reset-password', [
            'token' => 'token-palsu', 'email' => $u->email,
            'password' => 'baru12345', 'password_confirmation' => 'baru12345',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('lama12345', $u->fresh()->password));
    }

    public function test_password_lemah_ditolak(): void
    {
        $u = $this->makeUser();

        $this->post('/reset-password', [
            'token' => Password::createToken($u), 'email' => $u->email,
            'password' => '123', 'password_confirmation' => '123',
        ])->assertSessionHasErrors('password');
    }

    public function test_login_dengan_password_baru_setelah_reset(): void
    {
        $u = $this->makeUser();
        $this->post('/reset-password', [
            'token' => Password::createToken($u), 'email' => $u->email,
            'password' => 'baru12345', 'password_confirmation' => 'baru12345',
        ]);

        $this->post('/login', ['email' => $u->email, 'password' => 'baru12345', 'role' => 'pendonor'])
            ->assertRedirect('/pendonor/dashboard');
    }
}
