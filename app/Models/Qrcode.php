<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Qrcode extends Model
{
    use HasFactory, HasTimestamps;

    protected $fillable = [
        'opd_id',
        'name',
        'value',
        'url',
        'latitude',
        'longitude',
        'radius',
        'waktu_awal',
        'waktu_akhir',
        'type',
        'event_id',
    ];

    protected $casts = [
        'waktu_awal' => 'datetime',
        'waktu_akhir' => 'datetime',
    ];

    public function opd()
    {
        return $this->belongsTo(Opd::class);
    }

    public function getLatLngAttribute(): array|null
    {
        if (is_null($this->latitude) || is_null($this->longitude)) {
            return null;
        }
        return [
            'lat' => $this->latitude,
            'lng' => $this->longitude,
        ];
    }
}
