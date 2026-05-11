<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupJoinLink extends Model
{
    protected $fillable = [
        'calon_santri_id',
        'token',
        'role',
        'channel',
        'group_type',
        'target_url',
        'clicked_at',
        'click_count',
        'last_clicked_ip',
        'last_clicked_user_agent',
        'expires_at',
    ];

    protected $casts = [
        'clicked_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function calonSantri()
    {
        return $this->belongsTo(CalonSantri::class);
    }
}
