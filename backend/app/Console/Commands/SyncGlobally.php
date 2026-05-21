<?php

namespace App\Console\Commands;

use App\Services\IPTVService;
use App\Services\FASTService;
use App\Services\M3UAggregatorService;
use App\Services\CuratedChannelService;
use App\Services\IPTVApiService;
use Illuminate\Console\Command;

class SyncGlobally extends Command
{
    protected $signature = 'iptv:sync-global
        {--sources=all : Comma-separated sources: iptv-org, fast, m3u, curated, soccer, all}
        {--no-country-auto : Skip automatic country creation}';

    protected $description = 'Sync international channels from all global sources (iptv-org, kptv-fast, M3U aggregators, curated)';

    public function handle(
        IPTVService $iptvService,
        FASTService $fastService,
        M3UAggregatorService $m3uService,
        CuratedChannelService $curatedService,
        IPTVApiService $iptvApiService
    ): int {
        $sources = $this->option('sources');
        $sourceList = $sources === 'all' ? ['iptv-org', 'fast', 'm3u', 'curated', 'soccer'] : explode(',', $sources);

        $this->info('🌍 Global IPTV Sync');
        $this->newLine();

        $allResults = [];
        $total = 0;

        if (in_array('iptv-org', $sourceList)) {
            $this->line('📡 Syncing from iptv-org (channels + streams + logos)...');
            $result = $iptvService->syncFromIptvOrg();
            $allResults['iptv-org'] = $result;
            $this->info("   → {$result['count']} channels, {$result['with_streams']} with streams");
            $total += $result['count'];
            $this->newLine();
        }

        if (in_array('fast', $sourceList)) {
            $this->line('⚡ Syncing from kptv-fast (FAST channels)...');
            $result = $fastService->sync();
            $allResults['kptv-fast'] = $result;
            $this->info("   → {$result['count']} channels");
            $total += $result['count'];
            $this->newLine();
        }

        if (in_array('m3u', $sourceList)) {
            $this->line('📺 Syncing from M3U aggregators...');
            $result = $m3uService->syncAll();
            $allResults['m3u'] = $result;
            $this->info("   → {$result['total']} channels");
            $total += $result['total'];
            $this->newLine();
        }

        if (in_array('curated', $sourceList)) {
            $this->line('⭐ Syncing curated channels...');
            $result = $curatedService->sync();
            $count = $result['count'] ?? 0;
            $withStreams = $result['with_streams'] ?? 0;
            $this->info("   → {$count} channels, {$withStreams} with streams");
            $total += $count;
            $this->newLine();
        }

        if (in_array('soccer', $sourceList)) {
            $this->line('⚽ Syncing soccer/sports channels from iptv-api...');
            $result = $iptvApiService->sync();
            $count = $result['count'] ?? 0;
            if (!empty($result['error'])) {
                $this->warn("   ⚠ {$result['error']}, trying sports.m3u...");
                $fallback = $iptvApiService->syncFromIptvOrgSportsM3U();
                $count = $fallback['count'] ?? 0;
            }
            $this->info("   → {$count} sports channels");
            $total += $count;
            $this->newLine();
        }

        $this->line('━━━━━━━━━━━━━━━━━━━━━━');
        $this->info("🏁 Total: {$total} channels synced globally");

        return Command::SUCCESS;
    }
}
