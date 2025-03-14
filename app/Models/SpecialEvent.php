<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpecialEvent extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'date', 'opd_id'];

    public function opd()
    {
        return $this->belongsTo(Opd::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'event_id');
    }
}