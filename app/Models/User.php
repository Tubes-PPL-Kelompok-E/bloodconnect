<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use Notifiable, HasRoles, SoftDeletes;

    protected $fillable = ['name', 'email', 'instansi', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed', // otomatis di-hash (bcrypt)
        ];
    }

    /** Nama route dashboard sesuai peran */
    public function homeRoute(): string
    {
        return match (true) {
            $this->hasRole('admin_pmi')  => 'admin.dashboard',
            $this->hasRole('petugas_rs') => 'petugas.dashboard',
            default                      => 'pendonor.dashboard',
        };
    }
}
