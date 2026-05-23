<?php

namespace App\Services;

use App\Models\Channel;
use App\Models\Country;
use App\Models\Category;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class CuratedChannelService
{
    private array $channels;

    public function __construct()
    {
        $this->channels = [
            // News
            ['id' => 'BBCWorldNews.uk', 'name' => 'BBC World News', 'country' => 'gb', 'category' => 'news'],
            ['id' => 'BBCNews.uk', 'name' => 'BBC News', 'country' => 'gb', 'category' => 'news'],
            ['id' => 'BBCOne.uk', 'name' => 'BBC One', 'country' => 'gb', 'category' => 'general'],
            ['id' => 'AlJazeeraEnglish.qa', 'name' => 'Al Jazeera English', 'country' => 'qa', 'category' => 'news'],
            ['id' => 'CNN.us', 'name' => 'CNN', 'country' => 'us', 'category' => 'news'],
            ['id' => 'CNNInternational.us', 'name' => 'CNN International', 'country' => 'us', 'category' => 'news'],
            ['id' => 'SkyNews.uk', 'name' => 'Sky News', 'country' => 'gb', 'category' => 'news'],
            ['id' => 'France24.fr', 'name' => 'France 24', 'country' => 'fr', 'category' => 'news'],
            ['id' => 'DWNews.de', 'name' => 'DW News', 'country' => 'de', 'category' => 'news'],
            ['id' => 'RTNews.ru', 'name' => 'RT News', 'country' => 'ru', 'category' => 'news'],
            ['id' => 'TRTWorld.tr', 'name' => 'TRT World', 'country' => 'tr', 'category' => 'news'],

            // English Sports / Soccer
            ['id' => 'SkySportsMainEvent.uk', 'name' => 'Sky Sports Main Event', 'country' => 'gb', 'category' => 'sports'],
            ['id' => 'SkySportsPremierLeague.uk', 'name' => 'Sky Sports Premier League', 'country' => 'gb', 'category' => 'sports'],
            ['id' => 'SkySportsFootball.uk', 'name' => 'Sky Sports Football', 'country' => 'gb', 'category' => 'sports'],
            ['id' => 'SkySportsF1.uk', 'name' => 'Sky Sports F1', 'country' => 'gb', 'category' => 'sports'],
            ['id' => 'SkySportsNews.uk', 'name' => 'Sky Sports News', 'country' => 'gb', 'category' => 'sports'],
            ['id' => 'SkySportsArena.uk', 'name' => 'Sky Sports Arena', 'country' => 'gb', 'category' => 'sports'],
            ['id' => 'SkySportsMix.uk', 'name' => 'Sky Sports Mix', 'country' => 'gb', 'category' => 'sports'],

            // US Sports
            ['id' => 'ESPN.us', 'name' => 'ESPN', 'country' => 'us', 'category' => 'sports'],
            ['id' => 'ESPN2.us', 'name' => 'ESPN 2', 'country' => 'us', 'category' => 'sports'],
            ['id' => 'ESPNDeportes.us', 'name' => 'ESPN Deportes', 'country' => 'us', 'category' => 'sports'],
            ['id' => 'FoxSports1.us', 'name' => 'Fox Sports 1', 'country' => 'us', 'category' => 'sports'],
            ['id' => 'FoxSports2.us', 'name' => 'Fox Sports 2', 'country' => 'us', 'category' => 'sports'],
            ['id' => 'FoxDeportes.us', 'name' => 'Fox Deportes', 'country' => 'us', 'category' => 'sports'],
            ['id' => 'CBSSportsNetwork.us', 'name' => 'CBS Sports Network', 'country' => 'us', 'category' => 'sports'],
            ['id' => 'TUDN.us', 'name' => 'TUDN', 'country' => 'us', 'category' => 'sports'],
            ['id' => 'UnivisionDeportes.us', 'name' => 'Univision Deportes', 'country' => 'us', 'category' => 'sports'],

            // International Sports / Soccer
            ['id' => 'beINSportsEnglish.qa', 'name' => 'beIN Sports English', 'country' => 'qa', 'category' => 'sports'],
            ['id' => 'beINSportsFrance.fr', 'name' => 'beIN Sports France', 'country' => 'fr', 'category' => 'sports'],
            ['id' => 'beINSportsES.es', 'name' => 'beIN Sports España', 'country' => 'es', 'category' => 'sports'],
            ['id' => 'beINSports1.qa', 'name' => 'beIN Sports 1', 'country' => 'qa', 'category' => 'sports'],
            ['id' => 'beINSports2.qa', 'name' => 'beIN Sports 2', 'country' => 'qa', 'category' => 'sports'],
            ['id' => 'beINSports3.qa', 'name' => 'beIN Sports 3', 'country' => 'qa', 'category' => 'sports'],
            ['id' => 'beINSports4.qa', 'name' => 'beIN Sports 4', 'country' => 'qa', 'category' => 'sports'],
            ['id' => 'beINSports5.qa', 'name' => 'beIN Sports 5', 'country' => 'qa', 'category' => 'sports'],
            ['id' => 'beINSports6.qa', 'name' => 'beIN Sports 6', 'country' => 'qa', 'category' => 'sports'],
            ['id' => 'beINSports7.qa', 'name' => 'beIN Sports 7', 'country' => 'qa', 'category' => 'sports'],
            ['id' => 'beINSports8.qa', 'name' => 'beIN Sports 8', 'country' => 'qa', 'category' => 'sports'],
            ['id' => 'beINSports9.qa', 'name' => 'beIN Sports 9', 'country' => 'qa', 'category' => 'sports'],
            ['id' => 'beINSports10.qa', 'name' => 'beIN Sports 10', 'country' => 'qa', 'category' => 'sports'],
            ['id' => 'beINSports11.qa', 'name' => 'beIN Sports 11', 'country' => 'qa', 'category' => 'sports'],
            ['id' => 'beINSports12.qa', 'name' => 'beIN Sports 12', 'country' => 'qa', 'category' => 'sports'],
            ['id' => 'beINSports13.qa', 'name' => 'beIN Sports 13', 'country' => 'qa', 'category' => 'sports'],
            ['id' => 'beINSports14.qa', 'name' => 'beIN Sports 14', 'country' => 'qa', 'category' => 'sports'],
            ['id' => 'beINSports15.qa', 'name' => 'beIN Sports 15', 'country' => 'qa', 'category' => 'sports'],
            ['id' => 'beINSports16.qa', 'name' => 'beIN Sports 16', 'country' => 'qa', 'category' => 'sports'],
            ['id' => 'beINSportsMAX1.qa', 'name' => 'beIN Sports MAX 1', 'country' => 'qa', 'category' => 'sports'],
            ['id' => 'beINSportsMAX2.qa', 'name' => 'beIN Sports MAX 2', 'country' => 'qa', 'category' => 'sports'],
            ['id' => 'beINSports4K.qa', 'name' => 'beIN Sports 4K', 'country' => 'qa', 'category' => 'sports'],
            ['id' => 'beINSportsNews.qa', 'name' => 'beIN Sports News', 'country' => 'qa', 'category' => 'news'],
            ['id' => 'DAZN1.uk', 'name' => 'DAZN 1', 'country' => 'gb', 'category' => 'sports'],
            ['id' => 'DAZN2.uk', 'name' => 'DAZN 2', 'country' => 'gb', 'category' => 'sports'],
            ['id' => 'Eurosport1.us', 'name' => 'Eurosport 1', 'country' => 'us', 'category' => 'sports'],
            ['id' => 'Eurosport2.us', 'name' => 'Eurosport 2', 'country' => 'us', 'category' => 'sports'],
            ['id' => 'TNTSports.uk', 'name' => 'TNT Sports', 'country' => 'gb', 'category' => 'sports'],
            ['id' => 'CanalPlusSport.fr', 'name' => 'Canal+ Sport', 'country' => 'fr', 'category' => 'sports'],
            ['id' => 'SportTV.pt', 'name' => 'Sport TV', 'country' => 'pt', 'category' => 'sports'],
            ['id' => 'PremierSports1.uk', 'name' => 'Premier Sports 1', 'country' => 'gb', 'category' => 'sports'],
            ['id' => 'PremierSports2.uk', 'name' => 'Premier Sports 2', 'country' => 'gb', 'category' => 'sports'],

            // Club Channels
            ['id' => 'MUTV.uk', 'name' => 'MUTV', 'country' => 'gb', 'category' => 'sports'],
            ['id' => 'RealMadridTV.es', 'name' => 'Real Madrid TV', 'country' => 'es', 'category' => 'sports'],
            ['id' => 'BarcaTV.es', 'name' => 'Barça TV', 'country' => 'es', 'category' => 'sports'],
            ['id' => 'LFCTV.uk', 'name' => 'LFCTV', 'country' => 'gb', 'category' => 'sports'],
            ['id' => 'ChelseaTV.uk', 'name' => 'Chelsea TV', 'country' => 'gb', 'category' => 'sports'],

            // Free Soccer
            ['id' => 'GolTV.us', 'name' => 'GolTV', 'country' => 'us', 'category' => 'sports'],
            ['id' => 'LaLigaTV.uk', 'name' => 'LaLiga TV', 'country' => 'gb', 'category' => 'sports'],
            ['id' => 'LEquipe.fr', 'name' => "L'Équipe", 'country' => 'fr', 'category' => 'sports'],
            ['id' => 'PlutoTVSports.us', 'name' => 'Pluto TV Sports', 'country' => 'us', 'category' => 'sports'],
            ['id' => 'ElevenSports.uk', 'name' => 'Eleven Sports', 'country' => 'gb', 'category' => 'sports'],
            ['id' => 'OptusSport.au', 'name' => 'Optus Sport', 'country' => 'au', 'category' => 'sports'],
            ['id' => 'SuperSport.za', 'name' => 'SuperSport', 'country' => 'za', 'category' => 'sports'],
            ['id' => 'CBSGolazoNetwork.us', 'name' => 'CBS Sports Golazo Network', 'country' => 'us', 'category' => 'sports'],
            ['id' => 'FIFAPlus.int', 'name' => 'FIFA+', 'country' => 'ch', 'category' => 'sports'],
        ];
    }

    public function sync(): array
    {
        $sportsCategory = Category::firstOrCreate(
            ['slug' => 'sports'],
            ['name' => 'Sports']
        );
        $newsCategory = Category::firstOrCreate(
            ['slug' => 'news'],
            ['name' => 'News']
        );
        $generalCategory = Category::firstOrCreate(
            ['slug' => 'general'],
            ['name' => 'General']
        );

        $channelsResp = Http::withOptions([
            'timeout' => 120,
            'connect_timeout' => 30,
            'force_ip_resolve' => 'v4',
        ])->get(config('services.iptv_org.channels'));

        if (!$channelsResp->successful()) {
            return ['error' => 'Failed to fetch channels from iptv-org', 'count' => 0];
        }

        $streamsResp = Http::withOptions([
            'timeout' => 120,
            'connect_timeout' => 30,
            'force_ip_resolve' => 'v4',
        ])->get(config('services.iptv_org.streams'));

        if (!$streamsResp->successful()) {
            return ['error' => 'Failed to fetch streams from iptv-org', 'count' => 0];
        }

        $logosResp = Http::withOptions([
            'timeout' => 60,
            'connect_timeout' => 30,
        ])->get(config('services.logos.url'));

        $logosByChannel = [];
        if ($logosResp->successful()) {
            foreach ($logosResp->json() as $logoEntry) {
                if (!empty($logoEntry['channel'])) {
                    $logosByChannel[$logoEntry['channel']] = $logoEntry['url'] ?? null;
                }
            }
        }

        $allChannels = $channelsResp->json();
        $allStreams = $streamsResp->json();

        $streamsByChannel = [];
        foreach ($allStreams as $s) {
            if (!empty($s['channel'])) {
                $streamsByChannel[$s['channel']] = $s;
            }
        }

        $indexedChannels = [];
        foreach ($allChannels as $ch) {
            $indexedChannels[$ch['id']] = $ch;
        }

        $synced = 0;
        $withStreams = 0;

        foreach ($this->channels as $curated) {
            $channelData = $indexedChannels[$curated['id']] ?? null;

            if (!$channelData) {
                foreach ($allChannels as $ch) {
                    if (strtolower($ch['name']) === strtolower($curated['name'])) {
                        $channelData = $ch;
                        break;
                    }
                }
            }

            if (!$channelData) continue;

            $channelId = $channelData['id'];
            $stream = $streamsByChannel[$channelId] ?? null;
            $streamUrl = $stream['url'] ?? '';
            $isHls = str_contains($streamUrl, '.m3u8');
            $resolution = $stream['quality'] ?? null;
            $isHd = $resolution && in_array($resolution, ['720p', '1080p', '4k', '2160p']);

            $logoUrl = $logosByChannel[$channelId] ?? $channelData['logo'] ?? null;

            $country = null;
            if (!empty($curated['country'])) {
                $country = Country::firstOrCreate(
                    ['code' => $curated['country']],
                    ['name' => strtoupper($curated['country']), 'is_active' => true]
                );
            }

            $categoryMap = [
                'news' => $newsCategory,
                'sports' => $sportsCategory,
                'general' => $generalCategory,
            ];
            $category = $categoryMap[$curated['category']] ?? $generalCategory;

            Channel::updateOrCreate(
                ['slug' => 'curated-' . $channelId],
                [
                    'name' => $channelData['name'],
                    'stream_url' => $streamUrl,
                    'stream_type' => $isHls ? 'hls' : ($streamUrl ? 'other' : 'unknown'),
                    'country_id' => $country?->id,
                    'category_id' => $category?->id,
                    'resolution' => $resolution,
                    'is_hd' => $isHd,
                    'logo_url' => $logoUrl,
                    'website' => $channelData['website'] ?? null,
                    'source' => 'curated',
                    'is_online' => (bool) $streamUrl,
                    'is_active' => true,
                ]
            );

            if ($streamUrl) $withStreams++;
            $synced++;
        }

        return ['count' => $synced, 'with_streams' => $withStreams];
    }
}
