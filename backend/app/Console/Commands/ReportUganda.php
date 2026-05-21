<?php

namespace App\Console\Commands;

use App\Models\Channel;
use App\Models\Country;
use Illuminate\Console\Command;

class ReportUganda extends Command
{
    protected $signature = 'iptv:report-uganda';
    protected $description = 'Generate a comprehensive report on Uganda channels';

    public function handle(): int
    {
        $country = Country::where('code', 'ug')->first();
        if (!$country) {
            $this->error('Uganda not found in database');
            return Command::FAILURE;
        }

        $total = Channel::where('country_id', $country->id)->count();
        $withUrl = Channel::where('country_id', $country->id)
            ->whereNotNull('stream_url')
            ->where('stream_url', '!=', '')
            ->count();
        $online = Channel::where('country_id', $country->id)
            ->where('is_online', true)
            ->count();
        $offline = Channel::where('country_id', $country->id)
            ->where('is_online', false)
            ->whereNotNull('stream_url')
            ->where('stream_url', '!=', '')
            ->count();
        $noUrl = Channel::where('country_id', $country->id)
            ->where(function ($q) {
                $q->whereNull('stream_url')->orWhere('stream_url', '');
            })->count();
        $hd = Channel::where('country_id', $country->id)->where('is_hd', true)->count();
        $checked = Channel::where('country_id', $country->id)
            ->whereNotNull('last_checked_at')
            ->count();
        $avgLatency = Channel::where('country_id', $country->id)
            ->whereNotNull('latency_ms')
            ->avg('latency_ms');

        $categories = Channel::where('country_id', $country->id)
            ->where('is_online', true)
            ->with('category')
            ->get()
            ->groupBy(fn($c) => $c->category?->name ?? 'Other')
            ->map(fn($group) => $group->count())
            ->sortDesc();

        $working = Channel::where('country_id', $country->id)
            ->where('is_online', true)
            ->whereNotNull('stream_url')
            ->where('stream_url', '!=', '')
            ->orderBy('latency_ms', 'asc')
            ->get();

        $dead = Channel::where('country_id', $country->id)
            ->where('is_online', false)
            ->whereNotNull('stream_url')
            ->where('stream_url', '!=', '')
            ->orderBy('name')
            ->get();

        $this->line('');
        $this->line('  ┌─────────────────────────────────────────────┐');
        $this->line('  │  Uganda Channel Report                      │');
        $this->line('  ├─────────────────────────────────────────────┤');

        $rows = [
            ['Total channels', $total],
            ['With stream URL', $withUrl],
            ['No stream URL', $noUrl],
            ['Verified working', $online],
            ['Dead/offline', $offline],
            ['HD channels', $hd],
            ['Last checked', $checked],
            ['Avg latency', $avgLatency ? round($avgLatency, 2) . ' ms' : 'N/A'],
        ];

        foreach ($rows as $row) {
            $label = str_pad((string)$row[0], 20, ' ', STR_PAD_RIGHT);
            $this->line("  │  {$label} {$row[1]}");
        }

        $this->line('  ├─────────────────────────────────────────────┤');
        $this->line('  │  Categories (verified):                     │');

        foreach ($categories as $catName => $count) {
            $label = str_pad($catName, 20, ' ', STR_PAD_RIGHT);
            $this->line("  │    {$label} {$count}");
        }

        $this->line('  └─────────────────────────────────────────────┘');
        $this->line('');

        if ($working->isNotEmpty()) {
            $this->line('  <fg=green>✓ Working channels:</>');
            foreach ($working as $ch) {
                $latency = $ch->latency_ms ? round($ch->latency_ms) . 'ms' : '?';
                $this->line("    ✓ {$ch->name} ({$latency})");
            }
            $this->line('');
        }

        if ($dead->isNotEmpty()) {
            $this->line('  <fg=red>✗ Dead channels:</>');
            foreach ($dead as $ch) {
                $this->line("    ✗ {$ch->name}");
            }
            $this->line('');
        }

        $percentage = $total > 0 ? round(($online / $total) * 100, 1) : 0;
        $this->line("  Summary: <fg=green>{$online} working</> / {$total} total ({$percentage}%)");

        return Command::SUCCESS;
    }
}
