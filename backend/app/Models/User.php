<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    protected $fillable = [
        'name', 'email', 'password', 'country_code', 'role',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function watchHistory(): HasMany
    {
        return $this->hasMany(WatchHistory::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function favoriteChannelIds(): array
    {
        return $this->favorites()->pluck('channel_id')->toArray();
    }
}
