<?php

namespace App\Services;

use App\Models\Channel;
use App\Models\Country;
use App\Models\Category;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class FASTService
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.kptv_fast.url', 'http://kptv-fast:8080');
    }

    public function sync(): array
    {
        $resp = Http::timeout(120)->get("{$this->baseUrl}/channels");
        if (!$resp->successful()) {
            return ['error' => 'Failed to fetch from kptv-fast', 'count' => 0];
        }

        $data = $resp->json();
        $synced = 0;

        foreach ($data as $ch) {
            try {
                $name = $ch['name'] ?? $ch['title'] ?? '';
                if (!$name) continue;

                $streamUrl = $ch['stream_url'] ?? $ch['url'] ?? '';
                if (!$streamUrl) continue;

                $countryCode = $ch['country'] ?? $ch['country_code'] ?? null;
                $country = null;
                if ($countryCode && strlen($countryCode) === 2) {
                    $country = Country::firstOrCreate(
                        ['code' => strtolower($countryCode)],
                        ['name' => strtoupper($countryCode), 'is_active' => true]
                    );
                }

                $group = $ch['group'] ?? $ch['group_title'] ?? 'General';
                $category = $this->findOrCreateCategory($group);

                $slug = Str::slug($name) . '-' . Str::random(6);
                $logoUrl = $ch['logo'] ?? $ch['logo_url'] ?? null;
                $isHd = $ch['hd'] ?? $ch['is_hd'] ?? false;

                Channel::updateOrCreate(
                    ['slug' => 'fast-' . $slug],
                    [
                        'name' => $name,
                        'stream_url' => $streamUrl,
                        'stream_type' => str_contains($streamUrl, '.m3u8') ? 'hls' : 'other',
                        'country_id' => $country?->id,
                        'category_id' => $category?->id,
                        'logo_url' => $logoUrl,
                        'is_hd' => filter_var($isHd, FILTER_VALIDATE_BOOLEAN),
                        'source' => 'kptv-fast',
                        'is_online' => true,
                        'is_active' => true,
                    ]
                );
                $synced++;
            } catch (\Exception $e) {
                continue;
            }
        }

        return ['count' => $synced, 'source' => 'kptv-fast'];
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
