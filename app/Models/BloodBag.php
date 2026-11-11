<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BloodBag extends Model
{
    use HasFactory;

    protected $table = 'blood_bags';

    /**
     * Daftar status kantong darah
     */
    public const STATUS_AVAILABLE   = 'available';
    public const STATUS_RESERVED    = 'reserved';
    public const STATUS_DISTRIBUTED = 'distributed';
    public const STATUS_EXPIRED     = 'expired';
    public const STATUS_DISCARDED   = 'discarded';

    public const STATUSES = [
        self::STATUS_AVAILABLE,
        self::STATUS_RESERVED,
        self::STATUS_DISTRIBUTED,
        self::STATUS_EXPIRED,
        self::STATUS_DISCARDED,
    ];

    /**
     * Atribut yang dapat diisi secara massal
     */
    protected $fillable = [
        'bag_code',
        'blood_type',
        'rhesus',
        'component_type',
        'volume_ml',
        'donor_id',
        'collection_date',
        'expiry_date',
        'storage_location',
        'storage_temperature',
        'status',
    ];

    /**
     * Type casting atribut
     */
    protected function casts(): array
    {
        return [
            'collection_date'     => 'date',
            'expiry_date'         => 'date',
            'volume_ml'           => 'integer',
            'storage_temperature' => 'float',
        ];
    }

    /**
     * Relasi opsional ke data Pendonor jika donor_id berupa foreign key/id
     */
    public function donor(): BelongsTo
    {
        return $this->belongsTo(Pendonor::class, 'donor_id');
    }

    /* --------------------------------------------------------------------------
     * LOCAL SCOPES (FIFO & FILTERING)
     * -------------------------------------------------------------------------- */

    /**
     * Scope FIFO (First In, First Out / First Expired, First Out):
     * Mengambil kantong darah berstatus 'available' dan mengurutkannya berdasarkan
     * tanggal kedaluwarsa terdekat (paling awal) sehingga dipakai terlebih dahulu.
     */
    public function scopeFifo(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_AVAILABLE)
                     ->orderBy('expiry_date', 'asc');
    }

    /**
     * Scope untuk kantong darah berstatus siap pakai (available)
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_AVAILABLE);
    }

    /**
     * Scope untuk filter golongan darah dan rhesus
     */
    public function scopeBloodGroup(Builder $query, string $bloodType, ?string $rhesus = null): Builder
    {
        $query->where('blood_type', strtoupper($bloodType));

        if ($rhesus !== null) {
            $query->where('rhesus', $rhesus);
        }

        return $query;
    }

    /**
     * Scope peringatan dini untuk kantong available yang mendekati kedaluwarsa
     */
    public function scopeExpiringSoon(Builder $query, int $days = 7): Builder
    {
        $today = Carbon::today();
        $threshold = Carbon::today()->addDays($days);

        return $query->where('status', self::STATUS_AVAILABLE)
                     ->whereBetween('expiry_date', [$today, $threshold])
                     ->orderBy('expiry_date', 'asc');
    }

    /* --------------------------------------------------------------------------
     * ACCESSORS & HELPER METHODS
     * -------------------------------------------------------------------------- */

    /**
     * Sisa masa simpan dalam hari.
     * Bernilai positif jika masih berlaku, 0 jika hari ini kedaluwarsa, negatif jika sudah lewat.
     */
    public function getRemainingDaysAttribute(): int
    {
        if (! $this->expiry_date) {
            return 0;
        }

        return (int) Carbon::today()->diffInDays($this->expiry_date, false);
    }

    /**
     * Cek apakah kantong darah telah melewati tanggal kedaluwarsa
     */
    public function isExpired(): bool
    {
        if (! $this->expiry_date) {
            return false;
        }

        return Carbon::today()->greaterThan($this->expiry_date);
    }

    /**
     * Format gabungan golongan darah dan rhesus, contoh: "A+" atau "O-"
     */
    public function getFullBloodTypeAttribute(): string
    {
        return "{$this->blood_type}{$this->rhesus}";
    }
}

