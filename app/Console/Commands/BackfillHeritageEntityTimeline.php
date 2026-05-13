<?php

namespace App\Console\Commands;

use App\Services\GraphDbHeritageEntityImportService;
use Illuminate\Console\Command;
use Throwable;

class BackfillHeritageEntityTimeline extends Command
{
    protected $signature = 'heritage-entities:backfill-timeline
        {--dry-run : Show how many documents would be updated without writing changes}';

    protected $description = 'Backfill Heritage Entities timeline fields using Dating years first, then Chronology bounds.';

    public function handle(GraphDbHeritageEntityImportService $importer): int
    {
        $dryRun = (bool) $this->option('dry-run');

        try {
            $result = $importer->backfillTimelineBounds($dryRun);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->table(['Total scanned', 'Documents '.($dryRun ? 'that would be updated' : 'updated')], [[
            (string) ($result['total'] ?? 0),
            (string) ($result['updated'] ?? 0),
        ]]);

        if ($dryRun) {
            $this->info('Dry run completed. Re-run without --dry-run to apply updates.');
        }

        return self::SUCCESS;
    }
}

