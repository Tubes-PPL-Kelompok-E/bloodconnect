<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScreeningResult extends Model
{
    public const TESTS = [
        'hiv' => 'HIV',
        'hbsag' => 'HBsAg',
        'hcv' => 'HCV',
        'sifilis' => 'Sifilis',
    ];

    protected $fillable = [
        'blood_bag_id', 'report_number', 'hiv', 'hbsag', 'hcv', 'sifilis',
        'tested_at', 'file_path', 'notes', 'correction_reason', 'corrected_at',
    ];

    protected $casts = [
        'tested_at' => 'date',
        'corrected_at' => 'datetime',
    ];

    public function bloodBag(): BelongsTo
    {
        return $this->belongsTo(BloodBag::class);
    }

    public function isReactive(): bool
    {
        foreach (array_keys(self::TESTS) as $test) {
            if ($this->{$test} === 'reaktif') {
                return true;
            }
        }
        return false;
    }

    /** Ready jika semua non-reaktif, selain itu ditahan. */
    public function resultingBagStatus(): string
    {
        return $this->isReactive() ? BloodBag::DITAHAN : BloodBag::READY;
    }
}