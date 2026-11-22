<?php

namespace App\Console\Commands;

use App\Services\DocumentService;
use Illuminate\Console\Command;

class PruneDocumentsCommand extends Command
{
    protected $signature = 'documents:prune';

    protected $description = 'Delete uploaded documents that have passed the configured retention period';

    public function handle(DocumentService $documents): int
    {
        $count = $documents->pruneExpired();
        $this->components->info("Removed {$count} expired document(s).");

        return self::SUCCESS;
    }
}
