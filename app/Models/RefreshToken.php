<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefreshToken extends Model
{
    protected $fillable = [
        'user_id',
        'token_hash',
        'user_agent',
        'ip',
        'expires_at',
        'revoked_at'
    ];

    protected $dates = ['expires_at', 'revoked_at'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired()
    {
        return $this->expires_at && now()->gt($this->expires_at);
    }

    public function isRevoked()
    {
        return !is_null($this->revoked_at);
    }
}
