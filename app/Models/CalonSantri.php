<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalonSantri extends Model
{
    protected $guarded = [];
    
    protected $casts = [
        'siblings_data' => 'array',
        'is_ayah_tahsin' => 'boolean',
        'is_ibu_tahsin' => 'boolean',
        'punya_saudara_di_sini' => 'boolean',
    ];
}
