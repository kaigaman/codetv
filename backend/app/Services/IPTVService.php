<?php

namespace App\Services;

use App\Models\Channel;
use App\Models\Country;
use App\Models\Category;
use App\Models\Language;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class IPTVService
{
    public function syncFromIptvOrg(): array
    {
        $channelsResp = Http::withOptions([
            'timeout' => 120,
            'connect_timeout' => 30,
            'force_ip_resolve' => 'v4',
        ])->get(config('services.iptv_org.channels'));

        if (!$channelsResp->successful()) {
            return ['error' => 'Failed to fetch channels from iptv-org', 'count' => 0];
        }

        $streamsResp = Http::withOptions([
            'timeout' => 120,
            'connect_timeout' => 30,
            'force_ip_resolve' => 'v4',
        ])->get(config('services.iptv_org.streams'));

        if (!$streamsResp->successful()) {
            return ['error' => 'Failed to fetch streams from iptv-org', 'count' => 0];
        }

        $logosResp = Http::withOptions([
            'timeout' => 60,
            'connect_timeout' => 30,
        ])->get(config('services.logos.url'));

        $logosByChannel = [];
        if ($logosResp->successful()) {
            foreach ($logosResp->json() as $logoEntry) {
                if (!empty($logoEntry['channel'])) {
                    $logosByChannel[$logoEntry['channel']] = $logoEntry['url'] ?? null;
                }
            }
        }

        $channels = $channelsResp->json();
        $streams = $streamsResp->json();

        $streamsByChannel = [];
        foreach ($streams as $s) {
            if (!empty($s['channel'])) {
                $streamsByChannel[$s['channel']] = $s;
            }
        }

        $synced = 0;
        $streamCount = 0;

        foreach ($channels as $data) {
            try {
                $channelId = $data['id'] ?? Str::slug($data['name']);

                $country = null;
                if (!empty($data['country'])) {
                    $country = Country::firstOrCreate(
                        ['code' => strtolower($data['country'])],
                        [
                            'name' => $data['country_name'] ?? strtoupper($data['country']),
                            'is_active' => true,
                        ]
                    );
                }

                $category = null;
                if (!empty($data['categories']) && is_array($data['categories'])) {
                    $catName = $data['categories'][0];
                    $category = $this->findOrCreateCategory($catName);
                }

                $stream = $streamsByChannel[$channelId] ?? null;
                $streamUrl = $stream['url'] ?? '';
                $isHls = str_contains($streamUrl, '.m3u8');
                $resolution = $stream['quality'] ?? null;
                $isHd = $resolution && in_array($resolution, ['720p', '1080p', '4k', '2160p']);

                $logoUrl = $logosByChannel[$channelId] ?? $data['logo'] ?? null;

                Channel::updateOrCreate(
                    ['slug' => $channelId],
                    [
                        'name' => $data['name'],
                        'stream_url' => $streamUrl,
                        'stream_type' => $isHls ? 'hls' : ($streamUrl ? 'other' : 'unknown'),
                        'country_id' => $country?->id,
                        'category_id' => $category?->id,
                        'resolution' => $resolution,
                        'is_hd' => $isHd,
                        'logo_url' => $logoUrl,
                        'website' => $data['website'] ?? null,
                        'source' => 'iptv-org',
                        'is_online' => (bool) $streamUrl,
                        'is_active' => true,
                    ]
                );

                if ($streamUrl) {
                    $streamCount++;
                }

                $synced++;
            } catch (\Exception $e) {
                continue;
            }
        }

        return ['count' => $synced, 'with_streams' => $streamCount];
    }

    public function syncUganda(): array
    {
        $m3uUrl = config('services.uganda.m3u');
        $response = Http::withOptions([
            'timeout' => 60,
            'connect_timeout' => 15,
            'force_ip_resolve' => 'v4',
        ])->get($m3uUrl);

        if (!$response->successful()) {
            return ['error' => "Failed to fetch: $m3uUrl", 'count' => 0];
        }

        $lines = explode("\n", $response->body());
        $uganda = Country::firstOrCreate(
            ['code' => 'ug'],
            ['name' => 'Uganda', 'is_active' => true]
        );

        $synced = 0;
        $current = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (str_starts_with($line, '#EXTINF:')) {
                preg_match('/tvg-id="([^"]*)"/', $line, $tvgMatch);
                preg_match('/tvg-name="([^"]*)"/', $line, $nameMatch);
                preg_match('/tvg-logo="([^"]*)"/', $line, $logoMatch);
                preg_match('/group-title="([^"]*)"/', $line, $groupMatch);

                $current = [
                    'tvg_id' => $tvgMatch[1] ?? null,
                    'name' => $nameMatch[1] ?? null,
                    'logo' => $logoMatch[1] ?? null,
                    'group' => $groupMatch[1] ?? 'General',
                ];

                $parts = explode(',', $line);
                if (!$current['name'] && count($parts) > 1) {
                    $current['name'] = trim(end($parts));
                }
            } elseif ($line && !str_starts_with($line, '#') && $current) {
                $category = $this->findOrCreateCategory($current['group']);

                $slug = $current['tvg_id'] ?: Str::slug($current['name']);

                Channel::updateOrCreate(
                    ['slug' => 'ug-' . $slug],
                    [
                        'name' => $current['name'],
                        'stream_url' => $line,
                        'stream_type' => str_contains($line, '.m3u8') ? 'hls' : 'other',
                        'country_id' => $uganda->id,
                        'category_id' => $category?->id,
                        'tvg_id' => $current['tvg_id'],
                        'tvg_name' => $current['name'],
                        'logo_url' => $current['logo'],
                        'source' => 'iptv-org-m3u',
                        'is_online' => true,
                        'is_active' => true,
                    ]
                );
                $synced++;
                $current = [];
            }
        }

        return ['count' => $synced, 'source' => 'iptv-org-m3u'];
    }

    public function syncAllSources(): array
    {
        $results = [];

        $results['iptv-org'] = $this->syncFromIptvOrg();

        $total = array_sum(array_column($results, 'count'));
        return ['total' => $total, 'sources' => $results];
    }

    private function findOrCreateCountry(?string $code, string $name): ?Country
    {
        if (!$code) return null;
        return Country::firstOrCreate(
            ['code' => strtolower($code)],
            ['name' => $name]
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
