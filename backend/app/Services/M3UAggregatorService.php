<?php

namespace App\Services;

use App\Models\Channel;
use App\Models\Country;
use App\Models\Category;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class M3UAggregatorService
{
    private array $sources = [];

    public function __construct()
    {
        $this->sources = [
            'iptv-org-global' => env('IPTV_ORG_GLOBAL_M3U', 'https://iptv-org.github.io/iptv/index.m3u'),
            'free-tv' => config('services.free_tv.m3u'),
            'world-ip-tv' => config('services.world_ip_tv.playlist'),
            'herbert-he' => env('HERBERT_HE_M3U', 'https://raw.githubusercontent.com/HerbertHe/iptv-sources/main/iptv.m3u'),
        ];
    }

    public function syncAll(): array
    {
        $results = [];
        foreach ($this->sources as $name => $url) {
            $results[$name] = $this->syncFromM3U($url, $name);
        }
        $total = array_sum(array_column($results, 'count'));
        return ['total' => $total, 'sources' => $results];
    }

    public function syncFromM3U(string $m3uUrl, string $source): array
    {
        $response = Http::withOptions([
            'timeout' => 120,
            'connect_timeout' => 30,
            'force_ip_resolve' => 'v4',
        ])->get($m3uUrl);

        if (!$response->successful()) {
            return ['error' => "Failed to fetch: $m3uUrl", 'count' => 0];
        }

        $lines = explode("\n", $response->body());
        $synced = 0;
        $current = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (str_starts_with($line, '#EXTINF:')) {
                preg_match('/tvg-id="([^"]*)"/', $line, $tvgMatch);
                preg_match('/tvg-name="([^"]*)"/', $line, $nameMatch);
                preg_match('/tvg-logo="([^"]*)"/', $line, $logoMatch);
                preg_match('/group-title="([^"]*)"/', $line, $groupMatch);
                preg_match('/tvg-country="([^"]*)"/', $line, $countryMatch);

                $current = [
                    'tvg_id' => $tvgMatch[1] ?? null,
                    'name' => $nameMatch[1] ?? null,
                    'logo' => $logoMatch[1] ?? null,
                    'group' => $groupMatch[1] ?? 'General',
                    'country' => $countryMatch[1] ?? '',
                ];

                $parts = explode(',', $line);
                if (!$current['name'] && count($parts) > 1) {
                    $current['name'] = trim(end($parts));
                }
            } elseif ($line && !str_starts_with($line, '#') && $current) {
                $this->saveChannel($current, $line, $source);
                $synced++;
                $current = [];
            }
        }

        return ['count' => $synced, 'source' => $source];
    }

    private function saveChannel(array $meta, string $streamUrl, string $source): void
    {
        $name = $meta['name'] ?? 'Unknown';
        if (!$name || $name === 'Unknown') return;

        $countryCode = $meta['country'] ?? '';
        $country = null;
        if ($countryCode && strlen($countryCode) === 2) {
            $country = Country::firstOrCreate(
                ['code' => strtolower($countryCode)],
                ['name' => strtoupper($countryCode), 'is_active' => true]
            );
        }

        $group = $meta['group'] ?? 'General';
        $category = $this->findOrCreateCategory($group);

        $baseSlug = $meta['tvg_id'] ?: Str::slug($name);
        $slug = $baseSlug . '-' . substr(md5($streamUrl), 0, 8);

        Channel::updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'stream_url' => $streamUrl,
                'stream_type' => str_contains($streamUrl, '.m3u8') ? 'hls' : 'other',
                'country_id' => $country?->id,
                'category_id' => $category?->id,
                'tvg_id' => $meta['tvg_id'],
                'tvg_name' => $meta['name'],
                'logo_url' => $meta['logo'],
                'source' => $source,
                'is_online' => true,
                'is_active' => true,
            ]
        );
    }

    private function findOrCreateCategory(?string $name): ?Category
    {
        if (!$name) return null;
        $slug = Str::slug($name);
        return Category::firstOrCreate(
            ['slug' => $slug],
            ['name' => $name]
        );
    }
}
