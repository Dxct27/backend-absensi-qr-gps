<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;
    use HasTimestamps;

    protected $fillable = [
        'user_id',
        'opd_id',
        'qrcode_id',
        'date',
        'timestamp',
        'latitude',
        'longitude',
        'status',
        'notes',
        'attachment',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'datetime:Y-m-d',
            'timestamp' => 'datetime:H:i:s',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function opd()
    {
        return $this->belongsTo(Opd::class);
    }

    public function qrcode()
    {
        return $this->belongsTo(Qrcode::class);
    }

    function getLatLngAttribute(): array|null
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
