<?php

namespace App\Console\Commands;

use App\Services\StreamValidatorService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ValidateUganda extends Command
{
    protected $signature = 'iptv:validate-uganda
        {--concurrency=50 : Number of concurrent stream checks}
        {--reset : Reset all is_online flags before validation}
        {--async : Run as async Celery task instead of blocking}';

    protected $description = 'Validate all Uganda channel streams against their actual URLs';

    public function handle(): int
    {
        $pythonApi = config('services.python_api.url');
        $concurrency = $this->option('concurrency');
        $reset = $this->option('reset');
        $async = $this->option('async');

        $this->info('CODETV Uganda Stream Validator');
        $this->newLine();

        if ($async) {
            $response = Http::timeout(5)->post("{$pythonApi}/api/v1/stream/validate-uganda", [
                'concurrency' => (int)$concurrency,
                'reset_first' => $reset,
            ]);

            if ($response->successful()) {
                $result = $response->json();
                $this->line("Task dispatched. Summary:");
                $this->table(
                    ['Metric', 'Value'],
                    [
                        ['Checked', $result['checked'] ?? 'pending'],
                        ['Working', $result['working'] ?? 'pending'],
                        ['Dead', $result['dead'] ?? 'pending'],
                        ['Time', ($result['elapsed_seconds'] ?? '?') . 's'],
                    ]
                );
            } else {
                $this->error('Failed to start validation: ' . $response->body());
                return Command::FAILURE;
            }
        } else {
            $this->line("Validating Uganda channels...");

            $queryParams = http_build_query([
                'concurrency' => (int)$concurrency,
                'reset_first' => $reset ? 'true' : 'false',
            ]);

            $response = Http::timeout(300)
                ->withOptions(['stream' => false])
                ->post("{$pythonApi}/api/v1/stream/validate-uganda?{$queryParams}");

            if (!$response->successful()) {
                $this->error('Validation failed: ' . $response->body());
                return Command::FAILURE;
            }

            $result = $response->json();
            $this->renderResults($result);
        }

        return Command::SUCCESS;
    }

    private function renderResults(array $result): void
    {
        $this->newLine();
        $this->line('  ┌─────────────────────────────────────────────┐');
        $this->line('  │  Uganda Channel Report                      │');
        $this->line('  ├─────────────────────────────────────────────┤');

        $rows = [
            ['Total channels', $result['total'] ?? '?'],
            ['With stream URL', $result['with_stream_url'] ?? '?'],
            ['Verified working', $result['working'] ?? '?'],
            ['Dead/offline', $result['dead'] ?? '?'],
            ['HD channels', $result['hd'] ?? '?'],
            ['Avg latency', ($result['avg_latency_ms'] ?? '?') . ' ms'],
            ['Time elapsed', ($result['elapsed_seconds'] ?? '?') . ' s'],
        ];

        foreach ($rows as $row) {
            $label = str_pad($row[0], 20, ' ', STR_PAD_RIGHT);
            $value = $row[1];
            $this->line("  │  {$label} {$value}");
        }

        $this->line('  └─────────────────────────────────────────────┘');
        $this->newLine();

        $checked = $result['checked'] ?? 0;
        $working = $result['working'] ?? 0;
        $this->line("  <fg=green>✓ {$working} working</>  <fg=red>✗ " . ($checked - $working) . " dead</>  of {$checked} checked");
        $this->newLine();
    }
}
