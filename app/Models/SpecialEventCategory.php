<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpecialEventCategory extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
    ];
    protected $hidden = ['created_at', 'updated_at'];
}
