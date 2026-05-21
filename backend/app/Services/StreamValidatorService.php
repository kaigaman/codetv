<?php

namespace App\Services;

use App\Models\Channel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class StreamValidatorService
{
    public function validateChannel(Channel $channel): array
    {
        $url = $channel->stream_url;
        if (empty($url)) {
            return ['is_online' => false, 'error' => 'no_url'];
        }

        try {
            $start = microtime(true);

            $response = Http::timeout(10)
                ->withOptions(['stream' => false])
                ->head($url);

            $latency = (microtime(true) - $start) * 1000;

            if ($response->status() >= 400) {
                $channel->update([
                    'is_online' => false,
                    'latency_ms' => round($latency, 2),
                    'last_checked_at' => now(),
                ]);
                return ['is_online' => false, 'status' => $response->status()];
            }

            $channel->update([
                'is_online' => true,
                'latency_ms' => round($latency, 2),
                'last_checked_at' => now(),
                'last_online_at' => now(),
            ]);

            return ['is_online' => true, 'latency_ms' => round($latency, 2)];
        } catch (\Exception $e) {
            $channel->update([
                'is_online' => false,
                'last_checked_at' => now(),
            ]);
            return ['is_online' => false, 'error' => $e->getMessage()];
        }
    }

    public function validateBatch(int $limit = 100): array
    {
        $channels = Channel::where('is_active', true)
            ->orderBy('last_checked_at', 'asc')
            ->limit($limit)
            ->get();

        $results = [];
        foreach ($channels as $channel) {
            $results[$channel->id] = $this->validateChannel($channel);
        }

        return $results;
    }

    public function getOnlineCount(): int
    {
        return Cache::remember('online_count', 300, function () {
            return Channel::where('is_online', true)->count();
        });
    }

    public function getTotalCount(): int
    {
        return Cache::remember('total_count', 300, function () {
            return Channel::where('is_active', true)->count();
        });
    }
}
