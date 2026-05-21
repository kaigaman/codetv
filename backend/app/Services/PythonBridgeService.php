<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PythonBridgeService
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.python_api.url', 'http://python:8000');
    }

    public function checkStream(string $url): array
    {
        $resp = Http::timeout(15)->post("{$this->baseUrl}/api/v1/stream/check", [
            'url' => $url,
        ]);
        return $resp->json() ?? ['is_online' => false, 'error' => 'no_response'];
    }

    public function checkStreamBatch(string $country = 'ug', int $limit = 50): array
    {
        $resp = Http::timeout(60)->get("{$this->baseUrl}/api/v1/stream/check-batch", [
            'country' => $country,
            'limit' => $limit,
        ]);
        return $resp->json() ?? [];
    }

    public function getUgandaChannels(): array
    {
        $resp = Http::timeout(30)->get("{$this->baseUrl}/api/v1/uganda/channels");
        return $resp->json() ?? [];
    }

    public function generateM3U(string $country = 'ug'): ?string
    {
        $resp = Http::timeout(30)->get("{$this->baseUrl}/api/v1/m3u/generate/{$country}");
        if ($resp->successful()) {
            return $resp->body();
        }
        return null;
    }

    public function generateUgandaM3U(): ?string
    {
        $resp = Http::timeout(30)->get("{$this->baseUrl}/api/v1/m3u/uganda");
        if ($resp->successful()) {
            return $resp->body();
        }
        return null;
    }

    public function scrapeUganda(): array
    {
        $resp = Http::timeout(60)->post("{$this->baseUrl}/api/v1/uganda/scrape");
        return $resp->json() ?? ['status' => 'failed'];
    }

    public function triggerEpgFetch(string $country = 'ug'): array
    {
        $resp = Http::timeout(60)->get("{$this->baseUrl}/api/v1/epg/fetch", [
            'country' => $country,
        ]);
        return $resp->json() ?? [];
    }

    public function health(): array
    {
        try {
            $resp = Http::timeout(5)->get("{$this->baseUrl}/health");
            return $resp->json() ?? ['status' => 'unreachable'];
        } catch (\Exception $e) {
            return ['status' => 'unreachable', 'error' => $e->getMessage()];
        }
    }
}
