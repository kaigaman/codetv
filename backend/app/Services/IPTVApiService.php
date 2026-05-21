<?php

namespace App\Services;

use App\Models\Channel;
use App\Models\Country;
use App\Models\Category;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class IPTVApiService
{
    private array $soccerKeywords = [
        'football', 'soccer', 'premier league', 'laliga',
        'serie a', 'bundesliga', 'ligue 1', 'champions league',
        'europa league', 'uefa', 'fifa', 'world cup',
    ];

    public function sync(): array
    {
        $baseUrl = config('services.iptv_api.url', 'http://iptv-api:8080');

        $paths = [
            "$baseUrl/output.m3u",
            "$baseUrl/output.txt",
            "$baseUrl/output/tv.m3u",
        ];

        $content = null;
        $usedPath = null;
        foreach ($paths as $path) {
            try {
                $resp = Http::timeout(15)->get($path);
                if ($resp->successful() && strlen($resp->body()) > 100) {
                    $content = $resp->body();
                    $usedPath = $path;
                    break;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        if (!$content) {
            return ['error' => 'Could not reach iptv-api service', 'count' => 0, 'fallback' => false];
        }

        $lines = explode("\n", $content);
        $synced = 0;
        $current = [];

        $category = Category::firstOrCreate(
            ['slug' => 'sports'],
            ['name' => 'Sports']
        );

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
                    'group' => $groupMatch[1] ?? '',
                    'country' => $countryMatch[1] ?? '',
                ];

                $parts = explode(',', $line);
                if (!$current['name'] && count($parts) > 1) {
                    $current['name'] = trim(end($parts));
                }

                $groupLower = strtolower($current['group']);
                $nameLower = strtolower($current['name'] ?? '');
                $isSport = str_contains($groupLower, 'sport') || str_contains($groupLower, 'football')
                    || str_contains($groupLower, 'soccer') || str_contains($groupLower, 'espn')
                    || str_contains($groupLower, 'bein') || str_contains($groupLower, 'dazn');

                if ($isSport) {
                    $current['is_sport'] = true;
                } else {
                    foreach ($this->soccerKeywords as $kw) {
                        if (str_contains($nameLower, $kw)) {
                            $current['is_sport'] = true;
                            break;
                        }
                    }
                }

                if (!isset($current['is_sport'])) {
                    $current = [];
                }
            } elseif ($line && !str_starts_with($line, '#') && !empty($current)) {
                $this->saveChannel($current, $line, 'iptv-api', $category);
                $synced++;
                $current = [];
            }
        }

        return ['count' => $synced, 'source' => 'iptv-api', 'path' => $usedPath];
    }

    public function syncFromIptvOrgSportsM3U(): array
    {
        $url = 'https://iptv-org.github.io/iptv/categories/sports.m3u';
        $response = Http::withOptions([
            'timeout' => 60,
            'connect_timeout' => 15,
            'force_ip_resolve' => 'v4',
        ])->get($url);

        if (!$response->successful()) {
            return ['error' => "Failed to fetch sports.m3u", 'count' => 0];
        }

        $lines = explode("\n", $response->body());
        $synced = 0;
        $current = [];

        $category = Category::firstOrCreate(
            ['slug' => 'sports'],
            ['name' => 'Sports']
        );

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
                    'group' => $groupMatch[1] ?? '',
                    'country' => $countryMatch[1] ?? '',
                ];

                $parts = explode(',', $line);
                if (!$current['name'] && count($parts) > 1) {
                    $current['name'] = trim(end($parts));
                }
            } elseif ($line && !str_starts_with($line, '#') && !empty($current)) {
                $this->saveChannel($current, $line, 'iptv-org-sports', $category);
                $synced++;
                $current = [];
            }
        }

        return ['count' => $synced, 'source' => 'iptv-org-sports'];
    }

    private function saveChannel(array $meta, string $streamUrl, string $source, ?Category $category): void
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
}
