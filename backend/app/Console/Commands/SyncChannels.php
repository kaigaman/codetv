<?php

namespace App\Console\Commands;

use App\Services\IPTVService;
use Illuminate\Console\Command;

class SyncChannels extends Command
{
    protected $signature = 'iptv:sync {--source=iptv-org : Source to sync from}';
    protected $description = 'Sync channels from IPTV data sources';

    public function handle(IPTVService $service): int
    {
        $source = $this->option('source');
        $this->info("Syncing channels from: {$source}");

        if ($source === 'iptv-org') {
            $result = $service->syncFromIptvOrg();
            $this->info("Synced {$result['count']} channels from iptv-org");
        } else {
            $this->error("Unknown source: {$source}");
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
