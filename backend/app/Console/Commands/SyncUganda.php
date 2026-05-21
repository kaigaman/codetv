<?php

namespace App\Console\Commands;

use App\Services\PythonBridgeService;
use Illuminate\Console\Command;

class SyncUganda extends Command
{
    protected $signature = 'iptv:sync-uganda';
    protected $description = 'Scrape and sync Ugandan channels via Python service';

    public function handle(PythonBridgeService $bridge): int
    {
        $this->info('Scraping Ugandan channels...');
        $result = $bridge->scrapeUganda();

        $this->info('Fetching Uganda M3U...');
        $m3u = $bridge->generateUgandaM3U();

        if ($m3u) {
            $path = public_path('m3u/uganda.m3u8');
            file_put_contents($path, $m3u);
            $this->info("Uganda playlist saved to: {$path}");
        }

        $this->info(json_encode($result));
        return Command::SUCCESS;
    }
}
