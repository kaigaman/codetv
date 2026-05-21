<?php

namespace App\Console\Commands;

use App\Services\M3UAggregatorService;
use App\Services\IPTVService;
use Illuminate\Console\Command;

class SyncM3U extends Command
{
    protected $signature = 'iptv:sync-m3u
        {--sources=uganda,news,entertainment,movies,music : Comma-separated sources to sync (use "africa" for all 54 countries)}
        {--list : List all available M3U sources}';

    protected $description = 'Sync channels from specific country/category M3U sources (fast, no large JSON downloads)';

    public function handle(M3UAggregatorService $m3u, IPTVService $iptv): int
    {
        // All African country shortcuts
        $africanCountries = [
            'algeria', 'angola', 'benin', 'botswana', 'burkina-faso', 'burundi',
            'cameroon', 'cape-verde', 'central-african-republic', 'chad', 'comoros',
            'congo', 'dr-congo', 'cote-divoire', 'djibouti', 'egypt',
            'equatorial-guinea', 'eritrea', 'eswatini', 'ethiopia', 'gabon', 'gambia',
            'ghana', 'guinea', 'guinea-bissau', 'kenya', 'lesotho', 'liberia', 'libya',
            'madagascar', 'malawi', 'mali', 'mauritania', 'mauritius', 'morocco',
            'mozambique', 'namibia', 'niger', 'nigeria', 'rwanda', 'sao-tome',
            'senegal', 'seychelles', 'sierra-leone', 'somalia', 'south-africa',
            'south-sudan', 'sudan', 'tanzania', 'togo', 'tunisia', 'uganda',
            'western-sahara', 'zambia', 'zimbabwe',
        ];

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
            // African countries
            'uganda' => 'iptv-org-uganda',
            'kenya' => 'iptv-org-kenya',
            'nigeria' => 'iptv-org-nigeria',
            'tanzania' => 'iptv-org-tanzania',
            'ghana' => 'iptv-org-ghana',
            'south-africa' => 'iptv-org-south-africa',
            'ethiopia' => 'iptv-org-ethiopia',
            'egypt' => 'iptv-org-egypt',
            'morocco' => 'iptv-org-morocco',
            'algeria' => 'iptv-org-algeria',
            'angola' => 'iptv-org-angola',
            'cameroon' => 'iptv-org-cameroon',
            'cote-divoire' => 'iptv-org-cote-divoire',
            'zimbabwe' => 'iptv-org-zimbabwe',
            'zambia' => 'iptv-org-zambia',
            'senegal' => 'iptv-org-senegal',
            'rwanda' => 'iptv-org-rwanda',
            'somalia' => 'iptv-org-somalia',
            'sudan' => 'iptv-org-sudan',
            'south-sudan' => 'iptv-org-south-sudan',
            'mozambique' => 'iptv-org-mozambique',
            'madagascar' => 'iptv-org-madagascar',
            'malawi' => 'iptv-org-malawi',
            'mali' => 'iptv-org-mali',
            'mauritius' => 'iptv-org-mauritius',
            'mauritania' => 'iptv-org-mauritania',
            'niger' => 'iptv-org-niger',
            'namibia' => 'iptv-org-namibia',
            'benin' => 'iptv-org-benin',
            'botswana' => 'iptv-org-botswana',
            'burkina-faso' => 'iptv-org-burkina-faso',
            'burundi' => 'iptv-org-burundi',
            'cape-verde' => 'iptv-org-cape-verde',
            'central-african-republic' => 'iptv-org-central-african-republic',
            'chad' => 'iptv-org-chad',
            'comoros' => 'iptv-org-comoros',
            'congo' => 'iptv-org-congo',
            'dr-congo' => 'iptv-org-dr-congo',
            'djibouti' => 'iptv-org-djibouti',
            'equatorial-guinea' => 'iptv-org-equatorial-guinea',
            'eritrea' => 'iptv-org-eritrea',
            'eswatini' => 'iptv-org-eswatini',
            'gabon' => 'iptv-org-gabon',
            'gambia' => 'iptv-org-gambia',
            'guinea' => 'iptv-org-guinea',
            'guinea-bissau' => 'iptv-org-guinea-bissau',
            'lesotho' => 'iptv-org-lesotho',
            'liberia' => 'iptv-org-liberia',
            'libya' => 'iptv-org-libya',
            'sao-tome' => 'iptv-org-sao-tome',
            'seychelles' => 'iptv-org-seychelles',
            'sierra-leone' => 'iptv-org-sierra-leone',
            'togo' => 'iptv-org-togo',
            'tunisia' => 'iptv-org-tunisia',
            'western-sahara' => 'iptv-org-western-sahara',
            // Non-Africa countries
            'uk' => 'iptv-org-uk',
            'usa' => 'iptv-org-usa',
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
            $this->line('Special: --sources=africa (syncs all 54 African countries)');
            return Command::SUCCESS;
        }

        $rawSources = explode(',', $this->option('sources'));
        $sourceNames = [];
        foreach ($rawSources as $s) {
            $s = trim($s);
            if ($s === 'africa') {
                $sourceNames = array_merge($sourceNames, $africanCountries);
            } else {
                $sourceNames[] = $s;
            }
        }

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
