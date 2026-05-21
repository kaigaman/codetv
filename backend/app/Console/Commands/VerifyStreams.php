<?php

namespace App\Console\Commands;

use App\Services\StreamValidatorService;
use Illuminate\Console\Command;

class VerifyStreams extends Command
{
    protected $signature = 'iptv:verify {--limit=100 : Number of streams to check}';
    protected $description = 'Verify stream health for channels';

    public function handle(StreamValidatorService $service): int
    {
        $limit = (int) $this->option('limit');
        $this->info("Verifying {$limit} streams...");

        $results = $service->validateBatch($limit);
        $online = count(array_filter($results, fn($r) => $r['is_online'] ?? false));

        $this->info("Checked: " . count($results));
        $this->info("Online: {$online}");
        $this->info("Offline: " . (count($results) - $online));

        return Command::SUCCESS;
    }
}
