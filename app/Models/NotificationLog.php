<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationLog extends Model
{
    protected $fillable = [
        'calon_santri_id',
        'channel',
        'recipient',
        'recipient_role',
        'status',
        'http_status',
        'message',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function calonSantri()
    {
        return $this->belongsTo(CalonSantri::class);
    }
}
