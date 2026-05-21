<?php

namespace App\Console\Commands;

use App\Services\CuratedChannelService;
use App\Services\IPTVApiService;
use App\Services\StreamValidatorService;
use App\Models\Channel;
use Illuminate\Console\Command;

class SyncSoccer extends Command
{
    protected $signature = 'iptv:sync-soccer
        {--sources=all : Sources: curated, iptv-api, sports-m3u, all}
        {--validate : Run stream validation after sync}';

    protected $description = 'Sync curated soccer/sports channels from multiple sources (curated list, iptv-api, sports M3U) and validate streams';

    public function handle(
        CuratedChannelService $curatedService,
        IPTVApiService $iptvApiService,
        StreamValidatorService $validator
    ): int {
        $sources = $this->option('sources');
        $sourceList = $sources === 'all' ? ['curated', 'iptv-api', 'sports-m3u'] : explode(',', $sources);

        $this->info('⚽ Soccer & Sports Channel Sync');
        $this->newLine();

        $total = 0;

        if (in_array('curated', $sourceList)) {
            $this->line('⭐ Syncing curated channels...');
            $result = $curatedService->sync();
            $count = $result['count'] ?? 0;
            $withStreams = $result['with_streams'] ?? 0;
            $this->info("   → {$count} channels matched, {$withStreams} with streams");
            $total += $count;
            $this->newLine();
        }

        if (in_array('iptv-api', $sourceList)) {
            $this->line('📡 Syncing from iptv-api (Guovin - validated streams)...');
            $result = $iptvApiService->sync();
            $count = $result['count'] ?? 0;
            if (!empty($result['error'])) {
                $this->warn("   ⚠ {$result['error']}");
                if (isset($result['fallback'])) {
                    $this->line('   → Falling back to iptv-org sports.m3u...');
                    $fallback = $iptvApiService->syncFromIptvOrgSportsM3U();
                    $count = $fallback['count'] ?? 0;
                    $this->info("   → {$count} sports channels from fallback");
                }
            } else {
                $this->info("   → {$count} channels synced");
            }
            $total += $count;
            $this->newLine();
        }

        if (in_array('sports-m3u', $sourceList)) {
            $this->line('📺 Syncing from iptv-org sports.m3u...');
            $result = $iptvApiService->syncFromIptvOrgSportsM3U();
            $count = $result['count'] ?? 0;
            $this->info("   → {$count} sports channels");
            $total += $count;
            $this->newLine();
        }

        if ($this->option('validate')) {
            $this->line('🔍 Validating channels...');
            $validated = 0;
            $online = 0;
            $channels = Channel::where('source', 'curated')
                ->orWhere('source', 'iptv-api')
                ->orWhere('source', 'iptv-org-sports')
                ->orderBy('last_checked_at', 'asc')
                ->limit(200)
                ->get();

            foreach ($channels as $channel) {
                $result = $validator->validateChannel($channel);
                if ($result['is_online']) $online++;
                $validated++;
            }

            $this->info("   → Validated {$validated} channels, {$online} working");
            $this->newLine();
        }

        $this->line('━━━━━━━━━━━━━━━━━━━━━━');
        $this->info("⚽ Total: {$total} soccer/sports channels synced");

        return Command::SUCCESS;
    }
}
