<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpecialEvent extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'date',
        'opd_id',
        'special_event_category_id',
    ];

    protected $casts = [
        'date' => 'datetime',
    ];
    
    protected $hidden = ['created_at', 'updated_at'];


    public function opd()
    {
        return $this->belongsTo(Opd::class);
    }

    public function category()
    {
        return $this->belongsTo(SpecialEventCategory::class, 'special_event_category_id');
    }

    public function qrcodes()
    {
        return $this->hasMany(Qrcode::class, 'event_id');
    }
}
