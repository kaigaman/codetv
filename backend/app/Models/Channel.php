<?php

namespace App\Models;

use App\Services\ThumbnailService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Channel extends Model
{
    protected $appends = ['thumbnail_url'];
    protected $fillable = [
        'name', 'slug', 'stream_url', 'stream_type',
        'country_id', 'category_id', 'language_id',
        'logo_url', 'website', 'description', 'resolution',
        'is_hd', 'is_geoblocked', 'is_online', 'is_active',
        'source', 'tvg_id', 'tvg_name', 'tvg_url',
        'latency_ms', 'last_checked_at', 'last_online_at',
    ];

    protected $casts = [
        'is_hd' => 'boolean',
        'is_geoblocked' => 'boolean',
        'is_online' => 'boolean',
        'is_active' => 'boolean',
        'last_checked_at' => 'datetime',
        'last_online_at' => 'datetime',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function languages(): BelongsToMany
    {
        return $this->belongsToMany(Language::class);
    }

    public function scopeOnline($query)
    {
        return $query->where('is_online', true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCountry($query, string $code)
    {
        return $query->whereHas('country', fn($q) => $q->where('code', $code));
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where('name', 'like', "%{$term}%");
    }

    public function scopeHd($query)
    {
        return $query->where('is_hd', true);
    }

    public function getThumbnailUrlAttribute(): string
    {
        if ($this->logo_url) {
            return $this->logo_url;
        }
        $name = $this->name ?: 'TV';
        if (!$this->id) {
            $service = app(ThumbnailService::class);
            return $service->getUiAvatarUrl($name);
        }
        $cachePath = "thumbnails/{$this->id}.svg";
        $disk = \Illuminate\Support\Facades\Storage::disk('public');
        if ($disk->exists($cachePath)) {
            return asset("storage/{$cachePath}");
        }
        $service = app(ThumbnailService::class);
        $svg = $service->generateSvgThumbnail($name);
        $disk->put($cachePath, $svg);
        return asset("storage/{$cachePath}");
    }
}
