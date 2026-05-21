<?php

namespace App\Console\Commands;

use App\Services\M3UAggregatorService;
use App\Services\IPTVService;
use Illuminate\Console\Command;

class SyncM3U extends Command
{
    protected $signature = 'iptv:sync-m3u
        {--sources=uganda,news,entertainment,movies,music : Comma-separated sources to sync}
        {--list : List all available M3U sources}';

    protected $description = 'Sync channels from specific country/category M3U sources (fast, no large JSON downloads)';

    public function handle(M3UAggregatorService $m3u, IPTVService $iptv): int
    {
        $available = [
            // Categories
            'sports' => 'iptv-org-sports',
            'news' => 'iptv-org-news',
            'entertainment' => 'iptv-org-entertainment',
            'movies' => 'iptv-org-movies',
            'music' => 'iptv-org-music',
            'documentary' => 'iptv-org-documentary',
            'kids' => 'iptv-org-kids',
            'education' => 'iptv-org-education',
            'religious' => 'iptv-org-religious',
            'business' => 'iptv-org-business',
            'lifestyle' => 'iptv-org-lifestyle',
            'culture' => 'iptv-org-culture',
            'comedy' => 'iptv-org-comedy',
            'drama' => 'iptv-org-drama',
            'animation' => 'iptv-org-animation',
            'series' => 'iptv-org-series',
            'science' => 'iptv-org-science',
            'travel' => 'iptv-org-travel',
            'cooking' => 'iptv-org-cooking',
            // Countries
            'uganda' => 'iptv-org-uganda',
            'uk' => 'iptv-org-uk',
            'usa' => 'iptv-org-usa',
            'kenya' => 'iptv-org-kenya',
            'nigeria' => 'iptv-org-nigeria',
            'tanzania' => 'iptv-org-tanzania',
            // Legacy aggregators
            'global' => 'iptv-org-global',
            'free-tv' => 'free-tv',
            'world-ip-tv' => 'world-ip-tv',
            'herbert-he' => 'herbert-he',
        ];

        if ($this->option('list')) {
            $this->info('Available M3U sources:');
            $this->newLine();
            $rows = [];
            foreach ($available as $short => $internal) {
                $rows[] = [$short, $internal];
            }
            $this->table(['Short Name', 'Internal Key'], $rows);
            $this->newLine();
            $this->line('Usage: php artisan iptv:sync-m3u --sources=uganda,news,entertainment');
            return Command::SUCCESS;
        }

        $sourceNames = explode(',', $this->option('sources'));
        $internalNames = [];
        foreach ($sourceNames as $s) {
            $s = trim($s);
            if (isset($available[$s])) {
                $internalNames[] = $available[$s];
            } else {
                $this->warn("Unknown source: {$s}, skipping");
            }
        }

        if (empty($internalNames)) {
            $this->error('No valid sources specified. Use --list to see available sources.');
            return Command::FAILURE;
        }

        $this->info('M3U Channel Sync');
        $this->newLine();

        foreach ($internalNames as $internal) {
            $this->line("Syncing {$internal}...");

            if ($internal === 'iptv-org-uganda') {
                $result = $iptv->syncUganda();
                $this->info("   {$result['count']} Uganda channels");
            } else {
                $result = $m3u->syncSelected([$internal]);
                $count = $result['sources'][$internal]['count'] ?? 0;
                $error = $result['sources'][$internal]['error'] ?? null;
                if ($error) {
                    $this->warn("   {$error}");
                } else {
                    $this->info("   {$count} channels");
                }
            }
        }

        $this->newLine();
        $this->info('Sync complete.');

        return Command::SUCCESS;
    }
}
