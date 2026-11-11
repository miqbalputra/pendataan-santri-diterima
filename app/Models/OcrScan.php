<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OcrScan extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id', 'token_hash', 'session_hash', 'target', 'status', 'stage', 'progress',
        'file_path', 'mime_type', 'result', 'expires_at', 'used_at',
    ];

    protected function casts(): array
    {
        return [
            'result' => 'array',
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }
}
