<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PemusnahanKantong extends Model
{
    protected $table = 'pemusnahan_kantong';

    public const BISA_DIMUSNAHKAN = ['karantina', 'ready', 'ditahan', 'kedaluwarsa'];

    protected $fillable = [
        'kantong_id', 'tanggal_pemusnahan', 'alasan', 'sebelum_kedaluwarsa',
        'alasan_khusus', 'petugas_id', 'nama_petugas', 'status_sebelumnya',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_pemusnahan'  => 'date',
            'sebelum_kedaluwarsa' => 'boolean',
        ];
    }

    public function kantong(): BelongsTo
    {
        return $this->belongsTo(KantongDarah::class, 'kantong_id');
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'petugas_id');
    }

    /** Dimusnahkan sebelum kedaluwarsa? (kantong masih berlaku pada tanggal pemusnahan) */
    public static function apakahSebelumKedaluwarsa(KantongDarah $kantong, string $tanggal): bool
    {
        return $kantong->tanggal_kedaluwarsa->gte(Carbon::parse($tanggal)->startOfDay());
    }
}