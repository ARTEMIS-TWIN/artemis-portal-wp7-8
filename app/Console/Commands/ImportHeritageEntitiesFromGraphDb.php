<?php

namespace App\Console\Commands;

use App\Services\GraphDbHeritageEntityImportService;
use Illuminate\Console\Command;
use Throwable;

class ImportHeritageEntitiesFromGraphDb extends Command
{
    protected $signature = 'heritage-entities:import-graphdb
        {graph : Named graph URI}
        {entityUri?* : Optional one or more entity URIs to import from the graph}';

    protected $description = 'Import heritage entities from ARTEMIS GraphDB into the local OpenSearch heritage index.';

    public function handle(GraphDbHeritageEntityImportService $importer): int
    {
        $graph = (string) $this->argument('graph');
        $entityUris = array_values(array_unique((array) $this->argument('entityUri')));

        try {
            $results = $importer->importGraph($graph, $entityUris);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($results === []) {
            $this->warn('No heritage entities found for the provided graph.');

            return self::SUCCESS;
        }

        $this->table(['id', 'uri', 'result', 'entityType', 'label'], $results);

        return self::SUCCESS;
    }
}
