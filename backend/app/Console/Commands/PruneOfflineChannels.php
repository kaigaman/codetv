<?php

namespace App\Console\Commands;

use App\Models\Channel;
use App\Models\Country;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneOfflineChannels extends Command
{
    protected $signature = 'iptv:prune-offline
                            {--dry-run : Preview only, no deletions}
                            {--days=7 : Only delete channels offline for more than N days}
                            {--source= : Filter by source (e.g., iptv-org, m3u)}
                            {--country= : Filter by country code}
                            {--force : Skip confirmation prompt}';

    protected $description = 'Remove channels that have been verified offline';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $days = (int) $this->option('days');
        $source = $this->option('source');
        $countryCode = $this->option('country');
        $force = $this->option('force');

        $query = Channel::where('is_online', false)
            ->where('last_checked_at', '<', now()->subDays($days));

        if ($source) {
            $query->where('source', $source);
        }

        if ($countryCode) {
            $country = Country::where('code', $countryCode)->first();
            if (!$country) {
                $this->error("Country '{$countryCode}' not found");
                return Command::FAILURE;
            }
            $query->where('country_id', $country->id);
        }

        $total = $query->count();
        if ($total === 0) {
            $this->info("No offline channels found matching the criteria");
            return Command::SUCCESS;
        }

        $summary = (clone $query)
            ->select('source', DB::raw('count(*) as count'))
            ->groupBy('source')
            ->pluck('count', 'source')
            ->toArray();

        $this->line("Found <fg=yellow>{$total}</> channels offline for >{$days} days");
        $this->newLine();
        $this->table(['Source', 'Count'], array_map(fn($c, $s) => [$s, $c], $summary, array_keys($summary)));

        if (!$force && !$dryRun) {
            if (!$this->confirm("Delete {$total} offline channels?", true)) {
                $this->info('Cancelled');
                return Command::SUCCESS;
            }
        }

        if ($dryRun) {
            $this->newLine();
            $this->line("<fg=yellow>[DRY RUN]</> Would delete {$total} channels. No changes made.");
            return Command::SUCCESS;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $deleted = 0;
        $chunkSize = 100;
        $query->chunk($chunkSize, function ($channels) use ($bar, &$deleted) {
            foreach ($channels as $ch) {
                $ch->delete();
                $deleted++;
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info("Deleted {$deleted} offline channels");

        $remaining = Channel::count();
        $online = Channel::where('is_online', true)->count();
        $this->line("Remaining: {$remaining} total, {$online} online");

        return Command::SUCCESS;
    }
}
