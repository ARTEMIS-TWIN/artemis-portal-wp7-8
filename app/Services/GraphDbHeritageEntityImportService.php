<?php

namespace App\Services;

use App\Models\ImportedHeritageEntity;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class GraphDbHeritageEntityImportService
{
    public function __construct(
        private readonly PortalResourceImportService $portalResources,
    ) {
    }

    /**
     * @param  list<string>  $entityUris
     * @return list<array{id: string, uri: string, result: string, label: string|null, entityType: string|null}>
     */
    public function importGraph(string $graphUri, array $entityUris = []): array
    {
        $graphUri = $this->assertAbsoluteUri($graphUri, 'graph');

        if ($entityUris === []) {
            $entityUris = $this->discoverEntityUris($graphUri);
        }

        $results = [];

        foreach ($entityUris as $entityUri) {
            $results[] = $this->importEntity($graphUri, $entityUri);
        }

        return $results;
    }

    /**
     * @return array{id: string, uri: string, result: string, label: string|null, entityType: string|null}
     */
    public function importEntity(string $graphUri, string $entityUri): array
    {
        $graphUri = $this->assertAbsoluteUri($graphUri, 'graph');
        $entityUri = $this->assertAbsoluteUri($entityUri, 'entity');

        $rows = $this->querySelect($this->entityDetailsQuery($graphUri, $entityUri));

        if ($rows === []) {
            throw new RuntimeException("No heritage entity data found for {$entityUri} in graph {$graphUri}.");
        }

        $document = $this->buildDocument($graphUri, $entityUri, $rows);
        $documentId = $this->documentId($entityUri);
        $result = $this->putDocument($documentId, $document);

        return [
            'id' => $documentId,
            'uri' => $entityUri,
            'result' => (string) ($result['result'] ?? 'unknown'),
            'entityType' => $document['entityType'] ?? null,
            'label' => $document['label'] ?? null,
        ];
    }

    public function remove(string $reference): void
    {
        $documentId = $this->extractDocumentId($reference);

        try {
            $response = Http::timeout(30)
                ->acceptJson()
                ->send('DELETE', $this->opensearchBaseUrl().'/'.$this->heritageIndex().'/_doc/'.rawurlencode($documentId).'?refresh=wait_for');
        } catch (ConnectionException $exception) {
            throw new RuntimeException('OpenSearch is unreachable: '.$exception->getMessage(), previous: $exception);
        }

        if ($response->failed() && $response->status() !== 404) {
            throw new RuntimeException("OpenSearch delete failed for {$documentId}: ".$response->body());
        }
    }

    /**
     * @param  array<string, int|null>  $importedByOverrides
     */
    public function syncProjection(array $importedByOverrides = []): int
    {
        $now = Carbon::now();
        $existingRecords = ImportedHeritageEntity::query()
            ->get(['record_id', 'imported_by', 'imported_at', 'created_at'])
            ->filter(fn (ImportedHeritageEntity $record): bool => filled($record->record_id))
            ->keyBy('record_id');

        $records = [];

        foreach ($this->fetchIndexedDocuments() as $document) {
            $recordId = (string) ($document['id'] ?? '');

            if ($recordId === '') {
                continue;
            }

            $existingRecord = $existingRecords->get($recordId);
            $hasImportOverride = array_key_exists($recordId, $importedByOverrides);

            $records[] = [
                'graph_uri' => $document['sourceGraph'] ?? null,
                'entity_uri' => $document['uri'] ?? null,
                'record_id' => $recordId,
                'status' => 'linked',
                'entity_type' => $document['entityType'] ?? null,
                'label' => $document['label'] ?? null,
                'place_label' => $document['placeLabel'] ?? null,
                'country_label' => $document['countryLabel'] ?? null,
                'error_message' => null,
                'imported_by' => $hasImportOverride
                    ? $importedByOverrides[$recordId]
                    : $existingRecord?->imported_by,
                'imported_at' => $hasImportOverride
                    ? $now
                    : ($existingRecord?->imported_at ?? $now),
                'created_at' => $existingRecord?->created_at ?? $now,
                'updated_at' => $now,
            ];
        }

        ImportedHeritageEntity::query()->delete();

        if ($records !== []) {
            ImportedHeritageEntity::query()->insert($records);
        }

        return count($records);
    }

    /**
     * @return list<string>
     */
    public function discoverEntityUris(string $graphUri): array
    {
        $graphUri = $this->assertAbsoluteUri($graphUri, 'graph');

        $rows = $this->querySelect($this->entityDiscoveryQuery($graphUri));

        return array_values(array_unique(array_filter(array_map(
            static fn (array $row): ?string => $row['entityUri']['value'] ?? null,
            $rows,
        ))));
    }

    private function entityDiscoveryQuery(string $graphUri): string
    {
        $targetTypes = implode(' ', array_map(
            static fn (string $uri): string => "<{$uri}>",
            $this->targetClassUris(),
        ));

        return <<<SPARQL
PREFIX rhdto: <https://www.artemis-twin.eu/ontology/rhdto/>
SELECT DISTINCT ?entityUri
WHERE {
  GRAPH <{$graphUri}> {
    VALUES ?targetType { {$targetTypes} }
    ?entityUri a ?targetType .
  }
}
ORDER BY ?entityUri
SPARQL;
    }

    private function entityDetailsQuery(string $graphUri, string $entityUri): string
    {
        return <<<SPARQL
PREFIX crm: <http://www.cidoc-crm.org/cidoc-crm/>
PREFIX crmsci: <http://www.cidoc-crm.org/extensions/crmsci/>
PREFIX rhdto: <https://www.artemis-twin.eu/ontology/rhdto/>
PREFIX aocat: <https://www.ariadne-infrastructure.eu/resource/ao/cat/1.1/>
PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX owl: <http://www.w3.org/2002/07/owl#>

SELECT ?typeUri ?label ?title ?description ?sameAs
       ?classification ?classificationLabel
       ?material ?materialLabel
       ?period ?periodAuthority ?periodLabel ?from ?until
       ?place ?placeLabel ?placeName ?placeIri ?placeIriLabel
       ?country ?countryLabel ?lat ?lon
       ?identifierNode ?identifierType ?identifierLabel ?identifierValue
       ?owner ?ownerLabel ?ownerName ?ownerHomepage ?ownerIdentifier
       ?encounter ?encounterLabel ?encounterActor ?encounterActorLabel ?encounterActorName ?encounterTimeSpanLabel
       ?relatedDataResource
       ?visualRepresentation ?visualRepresentationLabel
WHERE {
  GRAPH <{$graphUri}> {
    BIND(<{$entityUri}> AS ?entity)

    ?entity a ?typeUri .

    OPTIONAL { ?entity rdfs:label ?label }
    OPTIONAL { ?entity crm:P102_has_title ?title }
    OPTIONAL { ?entity crm:P3_has_note ?description }
    OPTIONAL { ?entity owl:sameAs ?sameAs }

    OPTIONAL {
      ?entity crm:P2_has_type ?classification .
      OPTIONAL { ?classification skos:prefLabel ?classificationLabel }
      OPTIONAL { ?classification rdfs:label ?classificationLabel }
    }

    OPTIONAL {
      ?entity crm:P45_consists_of ?material .
      OPTIONAL { ?material skos:prefLabel ?materialLabel }
      OPTIONAL { ?material rdfs:label ?materialLabel }
    }

    OPTIONAL {
      ?entity rhdto:HP12_was_made_within ?period .
      OPTIONAL { ?period rdfs:label ?periodLabel }
      OPTIONAL { ?period aocat:has_period ?periodAuthority }
      OPTIONAL { ?period aocat:from ?from }
      OPTIONAL { ?period aocat:until ?until }
    }

    OPTIONAL {
      ?entity crm:P55_has_current_location ?place .
      OPTIONAL { ?place rdfs:label ?placeLabel }
      OPTIONAL { ?place aocat:has_place_name ?placeName }
      OPTIONAL {
        ?place aocat:has_place_IRI ?placeIri .
        OPTIONAL { ?placeIri skos:prefLabel ?placeIriLabel }
      }
      OPTIONAL {
        ?place aocat:has_country ?country .
        OPTIONAL { ?country skos:prefLabel ?countryLabel }
        OPTIONAL { ?country rdfs:label ?countryLabel }
      }
      OPTIONAL { ?place aocat:has_latitude ?lat }
      OPTIONAL { ?place aocat:has_longitude ?lon }
    }

    OPTIONAL {
      ?entity crm:P1_is_identified_by ?identifierNode .
      OPTIONAL { ?identifierNode a ?identifierType }
      OPTIONAL { ?identifierNode rdfs:label ?identifierLabel }
      OPTIONAL { ?identifierNode crm:P190_has_symbolic_content ?identifierValue }
    }

    OPTIONAL {
      ?entity crm:P52_has_current_owner ?owner .
      OPTIONAL { ?owner rdfs:label ?ownerLabel }
      OPTIONAL { ?owner aocat:has_name ?ownerName }
      OPTIONAL { ?owner aocat:has_homepage ?ownerHomepage }
      OPTIONAL { ?owner aocat:has_agent_identifier ?ownerIdentifier }
    }

    OPTIONAL {
      ?entity crmsci:O19i_was_object_encountered_through ?encounter .
      OPTIONAL { ?encounter rdfs:label ?encounterLabel }
      OPTIONAL {
        ?encounter crm:P14_carried_out_by ?encounterActor .
        OPTIONAL { ?encounterActor rdfs:label ?encounterActorLabel }
        OPTIONAL { ?encounterActor aocat:has_name ?encounterActorName }
      }
      OPTIONAL {
        ?encounter crm:P4_has_time-span ?encounterTimeSpan .
        OPTIONAL { ?encounterTimeSpan rdfs:label ?encounterTimeSpanLabel }
      }
    }

    OPTIONAL { ?entity crm:P129i_is_subject_of ?relatedDataResource }

    OPTIONAL {
      ?entity rhdto:HP9_has_visual_representation ?visualRepresentation .
      OPTIONAL { ?visualRepresentation rdfs:label ?visualRepresentationLabel }
    }
  }
}
SPARQL;
    }

    /**
     * @param  list<array<string, array{type: string, value: string}>>  $rows
     * @return array<string, mixed>
     */
    private function buildDocument(string $graphUri, string $entityUri, array $rows): array
    {
        $label = $this->firstLiteral($rows, ['title', 'label']) ?? $this->fallbackLabel($entityUri);
        $description = $this->firstLiteral($rows, ['description']);
        $entityType = $this->resolveEntityType($rows);
        $classifications = $this->collectKeyedPairs($rows, 'classification', 'classificationLabel');
        $materialDetails = $this->collectKeyedPairs($rows, 'material', 'materialLabel');
        $materials = $this->collectLabels($rows, 'materialLabel', 'material');
        $periods = $this->collectPeriods($rows);
        $sameAs = $this->collectDistinctValues($rows, 'sameAs');
        $identifiers = $this->collectIdentifiers($rows);
        $owner = $this->collectOwner($rows);
        $encounter = $this->collectEncounter($rows);
        $visualRepresentations = $this->collectKeyedPairs($rows, 'visualRepresentation', 'visualRepresentationLabel');
        $relatedDataResources = $this->collectRelatedDataResources($rows);

        $location = $this->buildLocation($rows);

        return array_filter([
            'uri' => $entityUri,
            'label' => $label,
            'description' => $description,
            'entityType' => $entityType,
            'classificationLabels' => array_values(array_filter(array_map(
                static fn (array $item): ?string => $item['label'] ?? null,
                $classifications,
            ))),
            'classificationUris' => array_values(array_filter(array_map(
                static fn (array $item): ?string => $item['uri'] ?? null,
                $classifications,
            ))),
            'classification' => $classifications,
            'materials' => $materials,
            'materialUris' => array_values(array_filter(array_map(
                static fn (array $item): ?string => $item['uri'] ?? null,
                $materialDetails,
            ))),
            'materialDetails' => $materialDetails,
            'periodLabels' => array_values(array_filter(array_map(
                static fn (array $item): ?string => $item['label'] ?? null,
                $periods,
            ))),
            'periodUris' => array_values(array_filter(array_map(
                static fn (array $item): ?string => $item['uri'] ?? null,
                $periods,
            ))),
            'periods' => $periods,
            'minPeriodFrom' => $this->minPeriodFrom($periods),
            'maxPeriodUntil' => $this->maxPeriodUntil($periods),
            'countryLabel' => $location['countryLabel'] ?? null,
            'countryUri' => $location['countryUri'] ?? null,
            'placeLabel' => $location['placeLabel'] ?? null,
            'placeUri' => $location['placeUri'] ?? null,
            'sameAs' => $sameAs,
            'hasWikidata' => collect($sameAs)->contains(
                static fn (string $uri): bool => str_starts_with($uri, 'http://www.wikidata.org/entity/')
                    || str_starts_with($uri, 'https://www.wikidata.org/entity/')
            ),
            'identifiers' => $identifiers,
            'ownerLabel' => $owner['label'] ?? null,
            'ownerUri' => $owner['uri'] ?? null,
            'owner' => $owner,
            'encounterEvent' => $encounter,
            'visualRepresentations' => $visualRepresentations,
            'hasImage' => $visualRepresentations !== [] ? true : null,
            'visualRepresentationStatus' => $visualRepresentations !== [] ? 'Has visual representation' : null,
            'relatedDataResources' => $relatedDataResources,
            'hasRelatedDataResources' => $relatedDataResources !== [] ? true : null,
            'relatedDataResourceStatus' => $relatedDataResources !== [] ? 'Has related data resources' : null,
            'importSource' => 'artemis-graphdb',
            'graphDbEndpoint' => $this->sparqlEndpoint(),
            'sourceGraph' => $graphUri,
            'location' => $location['location'] ?? null,
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }

    /**
     * @param  list<array<string, array{type: string, value: string}>>  $rows
     * @param  list<string>  $keys
     */
    private function firstLiteral(array $rows, array $keys): ?string
    {
        foreach ($rows as $row) {
            foreach ($keys as $key) {
                $value = trim((string) ($row[$key]['value'] ?? ''));

                if ($value !== '') {
                    return $value;
                }
            }
        }

        return null;
    }

    /**
     * @param  list<array<string, array{type: string, value: string}>>  $rows
     * @return list<string>
     */
    private function collectDistinctValues(array $rows, string $key): array
    {
        return array_values(array_unique(array_filter(array_map(
            static fn (array $row): ?string => filled($row[$key]['value'] ?? null) ? trim((string) $row[$key]['value']) : null,
            $rows,
        ))));
    }

    /**
     * @param  list<array<string, array{type: string, value: string}>>  $rows
     * @return list<string>
     */
    private function collectLabels(array $rows, string $preferredKey, string $fallbackKey): array
    {
        $labels = [];

        foreach ($rows as $row) {
            $value = trim((string) ($row[$preferredKey]['value'] ?? $row[$fallbackKey]['value'] ?? ''));

            if ($value !== '' && !in_array($value, $labels, true)) {
                $labels[] = $value;
            }
        }

        return $labels;
    }

    /**
     * @param  list<array<string, array{type: string, value: string}>>  $rows
     * @return list<array{uri: string, label: string|null}>
     */
    private function collectKeyedPairs(array $rows, string $uriKey, string $labelKey): array
    {
        $items = [];

        foreach ($rows as $row) {
            $uri = trim((string) ($row[$uriKey]['value'] ?? ''));

            if ($uri === '') {
                continue;
            }

            $items[$uri] = [
                'uri' => $uri,
                'label' => trim((string) ($row[$labelKey]['value'] ?? '')) ?: $uri,
            ];
        }

        return array_values($items);
    }

    /**
     * @param  list<array<string, array{type: string, value: string}>>  $rows
     * @return list<array{uri: string, label: string, from: int|null, until: int|null}>
     */
    private function collectPeriods(array $rows): array
    {
        $items = [];

        foreach ($rows as $row) {
            $periodUri = trim((string) ($row['periodAuthority']['value'] ?? $row['period']['value'] ?? ''));

            if ($periodUri === '') {
                continue;
            }

            $items[$periodUri] = [
                'uri' => $periodUri,
                'label' => trim((string) ($row['periodLabel']['value'] ?? $row['period']['value'] ?? $periodUri)) ?: $periodUri,
                'from' => $this->toIntOrNull($row['from']['value'] ?? null),
                'until' => $this->toIntOrNull($row['until']['value'] ?? null),
            ];
        }

        return array_values($items);
    }

    /**
     * @param  list<array<string, array{type: string, value: string}>>  $rows
     * @return list<array{uri: string|null, type: string|null, label: string, value: string}>
     */
    private function collectIdentifiers(array $rows): array
    {
        $items = [];

        foreach ($rows as $row) {
            $value = trim((string) ($row['identifierValue']['value'] ?? ''));

            if ($value === '') {
                continue;
            }

            $uri = trim((string) ($row['identifierNode']['value'] ?? ''));

            $items[$uri !== '' ? $uri : $value] = [
                'uri' => $uri !== '' ? $uri : null,
                'type' => $this->identifierTypeLabel($row['identifierType']['value'] ?? null),
                'label' => trim((string) ($row['identifierLabel']['value'] ?? '')) ?: $value,
                'value' => $value,
            ];
        }

        return array_values($items);
    }

    /**
     * @param  list<array<string, array{type: string, value: string}>>  $rows
     * @return array{uri?: string, label?: string, homepage?: string, identifier?: string}|null
     */
    private function collectOwner(array $rows): ?array
    {
        foreach ($rows as $row) {
            $uri = trim((string) ($row['owner']['value'] ?? ''));
            $label = trim((string) ($row['ownerName']['value'] ?? $row['ownerLabel']['value'] ?? ''));
            $homepage = trim((string) ($row['ownerHomepage']['value'] ?? ''));
            $identifier = trim((string) ($row['ownerIdentifier']['value'] ?? ''));

            if ($uri === '' && $label === '' && $homepage === '' && $identifier === '') {
                continue;
            }

            $owner = array_filter([
                'uri' => $uri !== '' ? $uri : null,
                'label' => $label !== '' ? $label : ($uri !== '' ? $uri : null),
                'homepage' => $homepage !== '' ? $homepage : null,
                'identifier' => $identifier !== '' ? $identifier : null,
            ], static fn (mixed $value): bool => $value !== null && $value !== '');

            return $owner !== [] ? $owner : null;
        }

        return null;
    }

    /**
     * @param  list<array<string, array{type: string, value: string}>>  $rows
     * @return array{uri?: string, label?: string, actorUri?: string, actorLabel?: string, timeSpanLabel?: string}|null
     */
    private function collectEncounter(array $rows): ?array
    {
        foreach ($rows as $row) {
            $uri = trim((string) ($row['encounter']['value'] ?? ''));
            $label = trim((string) ($row['encounterLabel']['value'] ?? ''));
            $actorUri = trim((string) ($row['encounterActor']['value'] ?? ''));
            $actorLabel = trim((string) ($row['encounterActorName']['value'] ?? $row['encounterActorLabel']['value'] ?? ''));
            $timeSpanLabel = trim((string) ($row['encounterTimeSpanLabel']['value'] ?? ''));

            if ($uri === '' && $label === '' && $actorUri === '' && $actorLabel === '' && $timeSpanLabel === '') {
                continue;
            }

            $encounter = array_filter([
                'uri' => $uri !== '' ? $uri : null,
                'label' => $label !== '' ? $label : ($uri !== '' ? $uri : null),
                'actorUri' => $actorUri !== '' ? $actorUri : null,
                'actorLabel' => $actorLabel !== '' ? $actorLabel : ($actorUri !== '' ? $actorUri : null),
                'timeSpanLabel' => $timeSpanLabel !== '' ? $timeSpanLabel : null,
            ], static fn (mixed $value): bool => $value !== null && $value !== '');

            return $encounter !== [] ? $encounter : null;
        }

        return null;
    }

    /**
     * @param  list<array<string, array{type: string, value: string}>>  $rows
     * @return list<array{id: string, uri: string, title: string|null, resourceType: string|null}>
     */
    private function collectRelatedDataResources(array $rows): array
    {
        $uris = $this->collectDistinctValues($rows, 'relatedDataResource');

        if ($uris === []) {
            return [];
        }

        $items = [];

        foreach ($uris as $uri) {
            $imported = $this->portalResources->import($uri);

            $items[] = [
                'id' => (string) $imported['id'],
                'uri' => $uri,
                'title' => $imported['title'] ?? null,
                'resourceType' => $imported['resourceType'] ?? null,
            ];
        }

        if (Schema::hasTable('imported_portal_resources')) {
            $this->portalResources->syncProjection();
        }

        return $items;
    }

    /**
     * @param  list<array{uri: string, label: string, from: int|null, until: int|null}>  $periods
     */
    private function minPeriodFrom(array $periods): ?int
    {
        $values = array_values(array_filter(array_map(
            static fn (array $period): ?int => $period['from'],
            $periods,
        ), static fn (?int $value): bool => $value !== null));

        return $values === [] ? null : min($values);
    }

    /**
     * @param  list<array{uri: string, label: string, from: int|null, until: int|null}>  $periods
     */
    private function maxPeriodUntil(array $periods): ?int
    {
        $values = array_values(array_filter(array_map(
            static fn (array $period): ?int => $period['until'],
            $periods,
        ), static fn (?int $value): bool => $value !== null));

        return $values === [] ? null : max($values);
    }

    /**
     * @param  list<array<string, array{type: string, value: string}>>  $rows
     * @return array{placeLabel?: string, placeUri?: string, countryLabel?: string, countryUri?: string, location?: array<string, mixed>}
     */
    private function buildLocation(array $rows): array
    {
        $placeLabel = $this->firstLiteral($rows, ['placeIriLabel', 'placeName', 'placeLabel']) ?? '';
        $displayLabel = $this->firstLiteral($rows, ['placeLabel', 'placeName', 'placeIriLabel']) ?? '';
        $placeUri = $this->firstLiteral($rows, ['placeIri', 'place']) ?? '';
        $countryLabel = $this->firstLiteral($rows, ['countryLabel']) ?? '';
        $countryUri = $this->firstLiteral($rows, ['country']) ?? '';
        $lat = $this->toFloatOrNull($this->firstLiteral($rows, ['lat']));
        $lon = $this->toFloatOrNull($this->firstLiteral($rows, ['lon']));

        $location = array_filter([
            'label' => $displayLabel !== '' ? $displayLabel : null,
            'countryLabel' => $countryLabel !== '' ? $countryLabel : null,
            'countryUri' => $countryUri !== '' ? $countryUri : null,
            'placeUri' => $placeUri !== '' ? $placeUri : null,
            'lat' => $lat,
            'lon' => $lon,
            'geopoint' => $lat !== null && $lon !== null ? ['lat' => $lat, 'lon' => $lon] : null,
        ], static fn (mixed $value): bool => $value !== null && $value !== []);

        return array_filter([
            'placeLabel' => $placeLabel !== '' ? $placeLabel : null,
            'placeUri' => $placeUri !== '' ? $placeUri : null,
            'countryLabel' => $countryLabel !== '' ? $countryLabel : null,
            'countryUri' => $countryUri !== '' ? $countryUri : null,
            'location' => $location !== [] ? $location : null,
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }

    /**
     * @param  list<array<string, array{type: string, value: string}>>  $rows
     */
    private function resolveEntityType(array $rows): ?string
    {
        $typeUris = $this->collectDistinctValues($rows, 'typeUri');

        if ($typeUris === []) {
            return null;
        }

        $preferred = collect($typeUris)
            ->sortBy(fn (string $uri): int => match (true) {
                str_starts_with($uri, 'https://www.artemis-twin.eu/ontology/rhdto/') => 0,
                str_starts_with($uri, 'http://vast-lab.org/crmhs/') => 1,
                str_starts_with($uri, 'https://www.ariadne-infrastructure.eu/resource/ao/cat/1.1/') => 2,
                default => 99,
            })
            ->first();

        if ($preferred === null) {
            return null;
        }

        $known = [
            'https://www.artemis-twin.eu/ontology/rhdto/HC3_Tangible_Entity' => 'Tangible Entity',
            'https://www.artemis-twin.eu/ontology/rhdto/HC3_Tangible_Heritage' => 'Tangible Heritage',
        ];

        if (isset($known[$preferred])) {
            return $known[$preferred];
        }

        $localName = Str::afterLast($preferred, '/');
        $localName = preg_replace('/^[A-Za-z]+[0-9]+_/', '', $localName) ?? $localName;

        return Str::of($localName)
            ->replace('_', ' ')
            ->headline()
            ->value();
    }

    private function identifierTypeLabel(?string $uri): ?string
    {
        $uri = trim((string) $uri);

        if ($uri === '') {
            return null;
        }

        $localName = Str::afterLast($uri, '/');

        return Str::of($localName)
            ->replace(['E42_', 'E41_'], '')
            ->replace('_', ' ')
            ->headline()
            ->value();
    }

    /**
     * @return list<array<string, array{type: string, value: string}>>
     */
    private function querySelect(string $query): array
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders(['Accept' => 'application/sparql-results+json'])
                ->asForm()
                ->post($this->sparqlEndpoint(), [
                    'query' => $query,
                    'infer' => 'false',
                ]);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('GraphDB is unreachable: '.$exception->getMessage(), previous: $exception);
        }

        if ($response->failed()) {
            throw new RuntimeException('GraphDB query failed: '.$response->body());
        }

        return $response->json('results.bindings') ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    private function putDocument(string $documentId, array $document): array
    {
        try {
            $response = Http::timeout(30)
                ->acceptJson()
                ->withBody(json_encode($document, JSON_UNESCAPED_SLASHES), 'application/json')
                ->send('PUT', $this->opensearchBaseUrl().'/'.$this->heritageIndex().'/_doc/'.rawurlencode($documentId).'?refresh=wait_for');
        } catch (ConnectionException $exception) {
            throw new RuntimeException('OpenSearch is unreachable: '.$exception->getMessage(), previous: $exception);
        }

        if ($response->failed()) {
            throw new RuntimeException("OpenSearch write failed for {$documentId}: ".$response->body());
        }

        return $response->json() ?? [];
    }

    private function documentId(string $entityUri): string
    {
        return hash('sha256', $entityUri);
    }

    private function extractDocumentId(string $reference): string
    {
        $reference = trim($reference);

        if ($reference === '') {
            throw new RuntimeException('Empty heritage entity reference.');
        }

        if (filter_var($reference, FILTER_VALIDATE_URL)) {
            return $this->documentId($reference);
        }

        return $reference;
    }

    private function fallbackLabel(string $entityUri): string
    {
        $tail = Str::afterLast(rtrim($entityUri, '/'), '/');

        return Str::of($tail)
            ->replace('_', ' ')
            ->headline()
            ->value();
    }

    private function assertAbsoluteUri(string $uri, string $label): string
    {
        $uri = trim($uri);

        if (! filter_var($uri, FILTER_VALIDATE_URL)) {
            throw new RuntimeException("Invalid {$label} URI: {$uri}");
        }

        return $uri;
    }

    private function toIntOrNull(?string $value): ?int
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return (int) $value;
    }

    private function toFloatOrNull(?string $value): ?float
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return (float) $value;
    }

    private function sparqlEndpoint(): string
    {
        return rtrim((string) env('GRAPHDB_SPARQL_ENDPOINT', 'https://graphdb.ino.cnr.it/repositories/artemis-kb01'), '/');
    }

    /**
     * @return list<string>
     */
    private function targetClassUris(): array
    {
        $configured = (string) env(
            'GRAPHDB_HERITAGE_ENTITY_CLASS_URI',
            'https://www.artemis-twin.eu/ontology/rhdto/HC3_Tangible_Heritage,https://www.artemis-twin.eu/ontology/rhdto/HC3_Tangible_Entity'
        );

        return array_values(array_filter(array_map(
            static fn (string $item): string => trim($item),
            explode(',', $configured),
        )));
    }

    private function opensearchBaseUrl(): string
    {
        return rtrim((string) env('OPENSEARCH_URL', 'http://127.0.0.1:9200'), '/');
    }

    private function heritageIndex(): string
    {
        return (string) env('OPENSEARCH_HERITAGE_ENTITIES_INDEX', 'artemis_heritage_entities');
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchIndexedDocuments(): array
    {
        $documents = [];
        $searchAfter = null;

        do {
            $payload = [
                'size' => 250,
                '_source' => [
                    'uri',
                    'label',
                    'entityType',
                    'placeLabel',
                    'countryLabel',
                    'sourceGraph',
                ],
                'sort' => [['_id' => 'asc']],
                'query' => [
                    'match_all' => (object) [],
                ],
            ];

            if ($searchAfter !== null) {
                $payload['search_after'] = [$searchAfter];
            }

            try {
                $response = Http::timeout(30)
                    ->acceptJson()
                    ->withBody(json_encode($payload, JSON_UNESCAPED_SLASHES), 'application/json')
                    ->send('POST', $this->opensearchBaseUrl().'/'.$this->heritageIndex().'/_search');
            } catch (ConnectionException $exception) {
                throw new RuntimeException('OpenSearch is unreachable: '.$exception->getMessage(), previous: $exception);
            }

            if ($response->failed()) {
                throw new RuntimeException('OpenSearch heritage projection sync failed: '.$response->body());
            }

            $hits = $response->json('hits.hits') ?? [];

            foreach ($hits as $hit) {
                $source = $hit['_source'] ?? [];
                $source['id'] = $hit['_id'] ?? null;
                $documents[] = $source;
            }

            $lastHit = end($hits);
            $searchAfter = $lastHit['sort'][0] ?? null;
        } while (! empty($hits));

        return $documents;
    }
}
