<?php

namespace App\Console\Commands;

use App\Services\IPTVService;
use Illuminate\Console\Command;

class SyncAllSources extends Command
{
    protected $signature = 'iptv:sync-all-sources';
    protected $description = 'Sync Uganda channels from all configured M3U sources (Free-TV, iptv-org)';

    public function handle(IPTVService $service): int
    {
        $this->info('Syncing Uganda channels from all M3U sources...');
        $this->newLine();

        $results = $service->syncAllSources();

        if (empty($results)) {
            $this->error('Sync failed.');
            return Command::FAILURE;
        }

        $this->table(
            ['Source', 'Channels Synced'],
            collect($results['sources'])->map(fn($r, $name) => [
                $name,
                $r['count'] ?? $r['error'] ?? 0,
            ])->toArray()
        );

        $this->newLine();
        $this->info("Total: {$results['total']} channels synced from all sources");

        return Command::SUCCESS;
    }
}
