<?php

namespace App\Console\Commands;

use App\Models\Channel;
use App\Models\Country;
use App\Models\Category;
use App\Services\PythonBridgeService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SyncUganda extends Command
{
    protected $signature = 'iptv:sync-uganda';
    protected $description = 'Scrape and sync Ugandan channels via Python service';

    public function handle(PythonBridgeService $bridge): int
    {
        $this->info('Scraping Ugandan channels via Python...');
        $scrape = $bridge->scrapeUganda();
        $this->info('Scrape result: ' . json_encode($scrape));

        $this->info('Waiting 3s for scrape to complete...');
        sleep(3);

        $this->info('Fetching Uganda channels from Python API...');
        $data = $bridge->getUgandaChannels();
        $channels = $data['channels'] ?? [];

        if (empty($channels)) {
            $this->warn('No channels returned from Python API. Trying M3U generation...');
            $m3u = $bridge->generateUgandaM3U();
            if ($m3u) {
                $path = public_path('m3u/uganda.m3u8');
                file_put_contents($path, $m3u);
                $this->info("Uganda playlist saved to: {$path}");
            }
            return Command::FAILURE;
        }

        // Fetch Uganda country
        $country = Country::firstOrCreate(
            ['code' => 'ug'],
            ['name' => 'Uganda', 'is_active' => true]
        );

        // Find or create a "General" category for channels
        $generalCategory = Category::firstOrCreate(
            ['slug' => 'general'],
            ['name' => 'General']
        );

        $synced = 0;

        foreach ($channels as $ch) {
            $name = $ch['name'] ?? $ch['channel'] ?? 'Unknown';
            $streamUrl = $ch['url'] ?? $ch['stream_url'] ?? '';
            $logo = $ch['logo'] ?? $ch['logo_url'] ?? null;

            if (!$name || $name === 'Unknown' || !$streamUrl) {
                continue;
            }

            $slug = Str::slug($name) . '-' . substr(md5($streamUrl), 0, 8);

            // Map category if available
            $category = null;
            $catName = $ch['category'] ?? $ch['group'] ?? null;
            if ($catName) {
                $catSlug = Str::slug($catName);
                $category = Category::firstOrCreate(
                    ['slug' => $catSlug],
                    ['name' => $catName]
                );
            }

            Channel::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'stream_url' => $streamUrl,
                    'stream_type' => str_contains($streamUrl, '.m3u8') ? 'hls' : 'other',
                    'country_id' => $country->id,
                    'category_id' => $category?->id ?? $generalCategory->id,
                    'logo_url' => $logo ? (strlen($logo) > 1000 ? substr($logo, 0, 1000) : $logo) : null,
                    'website' => $ch['website'] ?? null,
                    'source' => 'python-uganda',
                    'is_online' => true,
                    'is_active' => true,
                ]
            );

            $synced++;
        }

        // Also save M3U playlist
        $this->info('Fetching Uganda M3U...');
        $m3u = $bridge->generateUgandaM3U();
        if ($m3u) {
            $path = public_path('m3u/uganda.m3u8');
            file_put_contents($path, $m3u);
            $this->info("Uganda playlist saved to: {$path}");
        }

        $this->info("Synced {$synced} Uganda channels to database.");
        return Command::SUCCESS;
    }
}
