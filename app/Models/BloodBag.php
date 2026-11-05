<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BloodBag extends Model
{
    use HasFactory;

    public const KARANTINA = 'karantina';
    public const READY = 'ready';
    public const DITAHAN = 'ditahan';

    protected $fillable = [
        'bag_number', 'blood_type', 'rhesus', 'component',
        'collected_at', 'expired_at', 'status',
    ];

    protected $casts = [
        'collected_at' => 'date',
        'expired_at' => 'date',
    ];

    public function screening(): HasOne
    {
        return $this->hasOne(ScreeningResult::class);
    }

    public function getGroupLabelAttribute(): string
    {
        return $this->blood_type . ' (' . $this->rhesus . ')';
    }
}