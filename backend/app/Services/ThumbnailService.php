<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ThumbnailService
{
    private const COLORS = [
        '#E74C3C', '#3498DB', '#2ECC71', '#F39C12', '#9B59B6',
        '#1ABC9C', '#E67E22', '#2980B9', '#27AE60', '#D35400',
        '#8E44AD', '#16A085', '#C0392B', '#2C3E50', '#7F8C8D',
    ];

    public function generateSvgThumbnail(string $channelName): string
    {
        $hash = md5($channelName);
        $colorIndex = hexdec(substr($hash, 0, 8)) % count(self::COLORS);
        $bgColor = self::COLORS[$colorIndex];

        $initials = $this->getInitials($channelName);
        $initialsEscaped = htmlspecialchars($initials, ENT_QUOTES | ENT_XML1);

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="320" height="180" viewBox="0 0 320 180">
  <defs>
    <linearGradient id="g" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" style="stop-color:{$bgColor};stop-opacity:1" />
      <stop offset="100%" style="stop-color:{$this->darken($bgColor)};stop-opacity:1" />
    </linearGradient>
  </defs>
  <rect width="320" height="180" rx="8" fill="url(#g)" />
  <circle cx="160" cy="90" r="40" fill="rgba(255,255,255,0.15)" />
  <text x="160" y="102" font-family="Arial, Helvetica, sans-serif" font-size="36" font-weight="bold" fill="white" text-anchor="middle" dominant-baseline="middle">{$initialsEscaped}</text>
</svg>
SVG;

        return $svg;
    }

    public function getThumbnailUrl(\App\Models\Channel $channel): string
    {
        if ($channel->logo_url) {
            return $channel->logo_url;
        }

        $name = $channel->name ?: 'TV';
        $cachePath = "thumbnails/{$channel->id}.svg";
        $disk = Storage::disk('public');

        if ($disk->exists($cachePath)) {
            return asset("storage/{$cachePath}");
        }

        $svg = $this->generateSvgThumbnail($name);
        $disk->put($cachePath, $svg);

        return asset("storage/{$cachePath}");
    }

    public function getUiAvatarUrl(string $name): string
    {
        $encoded = urlencode($name);
        return "https://ui-avatars.com/api/?name={$encoded}&size=320&background=random&color=fff&bold=true";
    }

    private function getInitials(string $name): string
    {
        $parts = preg_split('/[\s\-_]+/', $name, 2);
        $initials = '';
        foreach ($parts as $part) {
            $clean = trim($part);
            if ($clean !== '') {
                $initials .= mb_strtoupper(mb_substr($clean, 0, 1));
            }
        }
        return $initials ?: 'TV';
    }

    private function darken(string $hex): string
    {
        $hex = ltrim($hex, '#');
        $r = max(0, hexdec(substr($hex, 0, 2)) - 40);
        $g = max(0, hexdec(substr($hex, 2, 2)) - 40);
        $b = max(0, hexdec(substr($hex, 4, 2)) - 40);
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }
}
