<?php

namespace App\Console\Commands;

use App\Models\Channel;
use App\Models\Country;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CleanupSources extends Command
{
    protected $signature = 'iptv:cleanup-sources {--dry-run : Show what would be removed without deleting}';
    protected $description = 'Remove channels from unreliable sources and deduplicate';

    private array $knownUgandaNames = [
        '3abn tv uganda', 'acw ug tv', 'akaboozi', 'alpha digital',
        'ark tv', 'baba tv', 'bbs tv', 'be tv', 'bethany tv', 'bm tv africa',
        'btm tv', 'btv', 'bukalango tv', 'bukedde tv', 'bunyoro tv',
        'ccco aspire tv', 'chamuka tv', 'channel 44 uganda', 'channel u',
        'church of uganda family television', 'delta tv tukole',
        'doxa tv', 'dream tv', 'eternal life tv', 'excel tv', 'face tv',
        'faraja television', 'focus of god tv', 'fort tv',
        'freedom experience tv', 'freedom love zone tv', 'freedom movie sphere',
        'fresh tv', 'fufa tv', 'galaxy tv', 'gbn tv', 'glorious times tv',
        'gmtv', 'gntv', 'ground tv', 'gtv', 'gugudde tv', 'hgtv uganda',
        'hope channel uganda', 'host tv', 'janan schools tv', 'kbs tv',
        'king tv', 'kitara tv', 'krc tv', 'kstv uganda', 'ktv',
        'lighthouse television', 'lit tv', 'magic1 tv', 'makula kika',
        'makula tv', 'mama tv', 'manifest television', 'moon tv',
        'nbs plus', 'nbs sport', 'nbs star', 'nbs tv',
        'ntv uganda', 'nyce tv',
        'pearl magic', 'pearl magic prime',
        'praise jesus tower tv', 'rest tv', 'revival tv', 'rite tv',
        'rwenzori tv', 'salam tv', 'salt tv', 'sanyuka prime', 'sanyuka tv',
        'see tv', 'sky television', 'smart24 tv', 'spark tv',
        'spirit of glory tv', 'spirit tv', 'star tv', 'stv', 'tagy tv',
        'tayari west tv', 'tbs tv', 'top tv', 'trumpet of faith tv',
        'trust tv', 'turn tv', 'tv east', 'tv one uganda', 'tv west',
        'u24 television',         'ubc tv', 'uctv', 'urban tv', 'wan luo tv',
        'wbs tv', 'westnile tv', 'worship tv',
        'ndejje university tv', 'nrg radio visual',
    ];

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $uganda = Country::where('code', 'ug')->first();
        if (!$uganda) {
            $this->error('Uganda country not found');
            return Command::FAILURE;
        }

        $removed = 0;
        $deduped = 0;

        $channels = Channel::where('country_id', $uganda->id)->get();
        foreach ($channels as $ch) {
            $nameLower = strtolower(trim(preg_replace('/\s*\([^)]*\)\s*(\[.*?\])?\s*$/i', '', $ch->name)));

            $isKnown = false;
            foreach ($this->knownUgandaNames as $known) {
                if ($nameLower === $known || Str::startsWith($nameLower, $known)) {
                    $isKnown = true;
                    break;
                }
            }

            if (!$isKnown) {
                if ($dryRun) {
                    $this->line("  [DRY RUN] Would remove: {$ch->name} ({$ch->source})");
                } else {
                    $ch->delete();
                    $this->line("  Removed: {$ch->name} ({$ch->source})");
                }
                $removed++;
            }
        }

            if (!$dryRun) {
            $channels = Channel::where('country_id', $uganda->id)->get();
            $seen = [];

            foreach ($channels as $ch) {
                $baseName = strtolower(trim(preg_replace('/\s*\([^)]*\)\s*(\[.*?\])?\s*$/i', '', $ch->name)));
                $key = $baseName;
                if (isset($seen[$key])) {
                    $existing = $seen[$key];
                    if (!$existing->stream_url && $ch->stream_url) {
                        $ch->delete();
                    } elseif ($existing->stream_url && !$ch->stream_url) {
                        continue;
                    } elseif ($ch->stream_url && $existing->stream_url) {
                        if ($ch->is_online && !$existing->is_online) {
                            $existing->delete();
                            $seen[$key] = $ch;
                        } elseif ($existing->is_online && !$ch->is_online) {
                            $ch->delete();
                        } elseif (strlen($ch->stream_url) < strlen($existing->stream_url)) {
                            $existing->delete();
                            $seen[$key] = $ch;
                        } else {
                            $ch->delete();
                        }
                    }
                    $deduped++;
                } else {
                    $seen[$key] = $ch;
                }
            }
        }

        $remaining = Channel::where('country_id', $uganda->id)->count();
        $this->newLine();
        $this->info("Removed {$removed} non-Uganda channels, {$deduped} duplicates");
        $this->info("Remaining Uganda channels: {$remaining}");

        return Command::SUCCESS;
    }
}
