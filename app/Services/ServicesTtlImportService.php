<?php

namespace App\Services;

use App\Models\ImportedService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class ServicesTtlImportService
{
    private const RDF_TYPE = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type';

    private const RDFS_LABEL = 'http://www.w3.org/2000/01/rdf-schema#label';

    private const CRM_TITLE = 'http://www.cidoc-crm.org/cidoc-crm/P102_has_title';

    private const CRM_DESCRIPTION = 'http://www.cidoc-crm.org/cidoc-crm/P3_has_note';

    private const CRM_TYPE = 'http://www.cidoc-crm.org/cidoc-crm/P2_has_type';

    private const CRM_USED_FOR = 'http://www.cidoc-crm.org/cidoc-crm/P16i_was_used_for';

    private const CRM_INTENDED_FOR = 'http://www.cidoc-crm.org/cidoc-crm/P103_was_intended_for';

    private const CRM_COMPOSED_OF = 'http://www.cidoc-crm.org/cidoc-crm/P106_is_composed_of';

    private const CRM_DOCUMENTED_IN = 'http://www.cidoc-crm.org/cidoc-crm/P70i_is_documented_in';

    private const RHDTO_DIGITAL_SERVICE_HTTP = 'http://vast-lab.org/rhdto/HC21_Digital_Service';

    private const RHDTO_DIGITAL_SERVICE_HTTPS = 'https://www.artemis-twin.eu/ontology/rhdto/HC21_Digital_Service';

    private const RHDTO_PROVIDED_BY_HTTP = 'http://vast-lab.org/rhdto/HP30_provided_by';

    private const RHDTO_PROVIDED_BY_HTTPS = 'https://www.artemis-twin.eu/ontology/rhdto/HP30_provided_by';

    private const AOCAT_HAS_FUNCTIONALITY = 'https://www.ariadne-infrastructure.eu/resource/ao/cat/1.1/has_functionality';

    private const AOCAT_HAS_TECH_SUPPORT = 'https://www.ariadne-infrastructure.eu/resource/ao/cat/1.1/has_technical_support';

    private const AOCAT_HAS_SUPPORTED_LANGUAGE = 'https://www.ariadne-infrastructure.eu/resource/ao/cat/1.1/has_supported_language';

    private const AOCAT_HAS_CONSUMED_MEDIA = 'https://www.ariadne-infrastructure.eu/resource/ao/cat/1.1/has_consumed_media';

    private const AOCAT_HAS_PRODUCED_MEDIA = 'https://www.ariadne-infrastructure.eu/resource/ao/cat/1.1/has_produced_media';

    private const AOCAT_HAS_CONSUMED_FORMAT = 'https://www.ariadne-infrastructure.eu/resource/ao/cat/1.1/has_consumed_format';

    private const AOCAT_HAS_PRODUCED_FORMAT = 'https://www.ariadne-infrastructure.eu/resource/ao/cat/1.1/has_produced_format';

    private const AOCAT_HAS_HOMEPAGE = 'https://www.ariadne-infrastructure.eu/resource/ao/cat/1.1/has_homepage';

    private const AOCAT_HAS_EMAIL = 'https://www.ariadne-infrastructure.eu/resource/ao/cat/1.1/has_email';

    private const AOCAT_HAS_NAME = 'https://www.ariadne-infrastructure.eu/resource/ao/cat/1.1/has_name';

    /**
     * @param  list<string>  $serviceUris
     * @return list<array{id: string, uri: string|null, result: string, title: string|null, topic: string|null}>
     */
    public function import(string $sourcePath, array $serviceUris = []): array
    {
        $sourcePath = trim($sourcePath);
        $normalizedFilter = array_values(array_unique(array_filter(array_map('trim', $serviceUris))));
        $filter = $normalizedFilter !== [] ? array_flip($normalizedFilter) : null;
        $documents = [];

        if ($sourcePath !== '' && filter_var($sourcePath, FILTER_VALIDATE_URL)) {
            $documents = $this->collectGraphServiceDocuments($sourcePath, $filter);
        } else {
            $ttlSources = $this->loadTtlSources($sourcePath);

            foreach ($ttlSources as $filePath => $ttlContent) {
                foreach ($this->parseServiceDocuments($ttlContent, $filePath) as $document) {
                    $uri = (string) ($document['uri'] ?? '');
                    if ($filter !== null && !isset($filter[$uri])) {
                        continue;
                    }

                    $documents[$this->documentId($uri !== '' ? $uri : $filePath)] = $document;
                }
            }
        }

        $results = [];

        foreach ($documents as $documentId => $document) {
            $response = $this->putDocument($documentId, $document);
            $results[] = [
                'id' => $documentId,
                'uri' => $document['uri'] ?? null,
                'result' => (string) ($response['result'] ?? 'unknown'),
                'title' => $document['title'] ?? null,
                'topic' => $document['topic'] ?? null,
            ];
        }

        return $results;
    }

    /**
     * @return array<string, string>
     */
    private function loadTtlSources(string $sourcePath): array
    {
        $sourcePath = $this->resolveSourcePath($sourcePath);

        return $this->readTtlFiles($sourcePath);
    }

    /**
     * @param  array<string, true>|null  $filter
     * @return array<string, array<string, mixed>>
     */
    private function collectGraphServiceDocuments(string $graphUri, ?array $filter): array
    {
        $graphUri = $this->assertAbsoluteUri($graphUri, 'graph');
        $serviceUris = $filter !== null ? array_keys($filter) : $this->discoverServiceUris($graphUri);
        $documents = [];

        foreach ($serviceUris as $serviceUri) {
            $ttl = $this->readTtlForServiceFromGraphUri($graphUri, $serviceUri);

            foreach ($this->parseServiceDocuments($ttl, 'graphdb::'.$graphUri.'::'.$serviceUri) as $document) {
                $uri = (string) ($document['uri'] ?? '');

                if ($uri === '' || $uri !== $serviceUri) {
                    continue;
                }

                $documents[$this->documentId($uri)] = $document;
                break;
            }
        }

        return $documents;
    }

    public function remove(string $reference): void
    {
        $documentId = $this->extractDocumentId($reference);

        try {
            $response = Http::timeout(30)
                ->acceptJson()
                ->send('DELETE', $this->opensearchBaseUrl().'/'.$this->servicesIndex().'/_doc/'.rawurlencode($documentId).'?refresh=wait_for');
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
        if (!Schema::hasTable('imported_services')) {
            return 0;
        }

        $now = Carbon::now();
        $existingRecords = ImportedService::query()
            ->get(['record_id', 'imported_by', 'imported_at', 'created_at'])
            ->filter(fn (ImportedService $record): bool => filled($record->record_id))
            ->keyBy('record_id');

        $records = [];

        foreach ($this->fetchImportedDocuments() as $document) {
            $recordId = (string) ($document['id'] ?? '');
            if ($recordId === '') {
                continue;
            }

            $existingRecord = $existingRecords->get($recordId);
            $hasImportOverride = array_key_exists($recordId, $importedByOverrides);

            $records[] = [
                'source_path' => $document['sourcePath'] ?? null,
                'service_uri' => $document['uri'] ?? null,
                'record_id' => $recordId,
                'status' => 'linked',
                'service_type' => $document['topic'] ?? null,
                'title' => $document['title'] ?? null,
                'provider_name' => $document['provider']['name'] ?? null,
                'url' => $document['url'] ?? null,
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

        ImportedService::query()->delete();

        if ($records !== []) {
            ImportedService::query()->insert($records);
        }

        return count($records);
    }

    /**
     * @return array<string, string>
     */
    private function readTtlFiles(string $sourcePath): array
    {
        if (is_dir($sourcePath)) {
            return $this->readTtlFilesFromDirectory($sourcePath);
        }

        if (is_file($sourcePath) && Str::endsWith(strtolower($sourcePath), '.zip')) {
            return $this->readTtlFilesFromZip($sourcePath);
        }

        if (is_file($sourcePath) && Str::endsWith(strtolower($sourcePath), '.ttl')) {
            $content = file_get_contents($sourcePath);

            if ($content === false) {
                throw new RuntimeException("Unable to read TTL file at {$sourcePath}.");
            }

            return [$sourcePath => $content];
        }

        throw new RuntimeException("Unsupported source path {$sourcePath}. Provide a .ttl file, a folder, or a .zip archive.");
    }

    /**
     * @return array<string, string>
     */
    private function readTtlFilesFromDirectory(string $directory): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory));

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $path = $file->getPathname();
            $lower = strtolower($path);

            if (!Str::endsWith($lower, '.ttl') || str_contains($path, '/__MACOSX/') || str_contains($path, '/.DS_Store')) {
                continue;
            }

            $content = file_get_contents($path);
            if ($content === false) {
                continue;
            }

            $files[$path] = $content;
        }

        if ($files === []) {
            throw new RuntimeException("No .ttl files found in {$directory}.");
        }

        return $files;
    }

    /**
     * @return array<string, string>
     */
    private function readTtlFilesFromZip(string $zipPath): array
    {
        $zip = new ZipArchive();
        $status = $zip->open($zipPath);

        if ($status !== true) {
            throw new RuntimeException("Unable to open zip archive {$zipPath}.");
        }

        $files = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string) $zip->getNameIndex($i);
            $lower = strtolower($name);

            if (!Str::endsWith($lower, '.ttl') || str_starts_with($name, '__MACOSX/') || str_contains($name, '/.DS_Store')) {
                continue;
            }

            $content = $zip->getFromIndex($i);
            if (!is_string($content)) {
                continue;
            }

            $files[$zipPath.'::'.$name] = $content;
        }

        $zip->close();

        if ($files === []) {
            throw new RuntimeException("No .ttl files found in archive {$zipPath}.");
        }

        return $files;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function parseServiceDocuments(string $ttl, string $sourcePath): array
    {
        [$prefixes, $triples] = $this->parseTriples($ttl);
        $graph = $this->buildGraph($triples);

        $serviceUris = [];

        foreach ($graph as $subject => $predicates) {
            $types = $this->uriValues($predicates[self::RDF_TYPE] ?? []);
            if (in_array(self::RHDTO_DIGITAL_SERVICE_HTTP, $types, true) || in_array(self::RHDTO_DIGITAL_SERVICE_HTTPS, $types, true)) {
                $serviceUris[] = $subject;
            }
        }

        $documents = [];

        foreach ($serviceUris as $serviceUri) {
            $predicates = $graph[$serviceUri] ?? [];
            $providerUri = $this->firstUri($predicates[self::RHDTO_PROVIDED_BY_HTTP] ?? [])
                ?? $this->firstUri($predicates[self::RHDTO_PROVIDED_BY_HTTPS] ?? []);

            $title = $this->resolveTitle($graph, $serviceUri, $predicates);
            $description = $this->resolveDescription($predicates);
            $topic = $this->resolveTopic($graph, $predicates);
            $providerName = $providerUri ? $this->nodeLabel($graph, $providerUri) : null;
            $providerHomepage = $providerUri ? $this->firstLiteral($graph[$providerUri][self::AOCAT_HAS_HOMEPAGE] ?? []) : null;
            $providerEmail = $providerUri ? $this->firstLiteral($graph[$providerUri][self::AOCAT_HAS_EMAIL] ?? []) : null;
            $url = $this->resolveUrl($serviceUri, $providerHomepage);

            $documents[] = array_filter([
                'id' => $this->sortableId($serviceUri),
                'uri' => $serviceUri,
                'title' => $title,
                'topic' => $topic,
                'description' => $description,
                'url' => $url,
                'img' => null,
                'provider' => $providerUri ? array_filter([
                    'uri' => $providerUri,
                    'name' => $providerName,
                    'homepage' => $providerHomepage,
                    'email' => $providerEmail,
                ], static fn (mixed $value): bool => $value !== null && $value !== '') : null,
                'functionalities' => $this->labelsFromUris($graph, $predicates[self::AOCAT_HAS_FUNCTIONALITY] ?? []),
                'usedForActivities' => $this->labelsFromUris($graph, $predicates[self::CRM_USED_FOR] ?? []),
                'intendedFor' => $this->labelsFromUris($graph, $predicates[self::CRM_INTENDED_FOR] ?? []),
                'technicalSupport' => $this->labelsFromUris($graph, $predicates[self::AOCAT_HAS_TECH_SUPPORT] ?? []),
                'languages' => $this->labelsFromUris($graph, $predicates[self::AOCAT_HAS_SUPPORTED_LANGUAGE] ?? []),
                'composedOf' => $this->literalValues($predicates[self::CRM_COMPOSED_OF] ?? []),
                'consumedMedia' => $this->literalValues($predicates[self::AOCAT_HAS_CONSUMED_MEDIA] ?? []),
                'producedMedia' => $this->literalValues($predicates[self::AOCAT_HAS_PRODUCED_MEDIA] ?? []),
                'consumedFormats' => $this->literalValues($predicates[self::AOCAT_HAS_CONSUMED_FORMAT] ?? []),
                'producedFormats' => $this->literalValues($predicates[self::AOCAT_HAS_PRODUCED_FORMAT] ?? []),
                'documents' => $this->documentList($graph, $predicates[self::CRM_DOCUMENTED_IN] ?? []),
                'importSource' => 'services-ttl',
                'sourcePath' => $sourcePath,
                'rdfPrefixes' => $prefixes,
            ], static fn (mixed $value): bool => $value !== null);
        }

        return $documents;
    }

    /**
     * @return array{array<string,string>, list<array{subject: string, predicate: string, object: array{type: string, value: string}}>}
     */
    private function parseTriples(string $ttl): array
    {
        $prefixes = [
            'rdf' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
        ];

        $blocks = [];
        $buffer = '';
        $inString = false;
        $lines = preg_split('/\R/', $ttl) ?: [];

        foreach ($lines as $line) {
            $trimmed = trim((string) $line);

            if ($trimmed === '') {
                continue;
            }

            if (preg_match('/^@prefix\s+([A-Za-z0-9_-]*):\s*<([^>]+)>\s*\.$/', $trimmed, $matches) === 1) {
                $prefixes[$matches[1]] = $matches[2];
                continue;
            }

            $buffer .= ($buffer === '' ? '' : ' ').$trimmed;
            $inString = $this->toggleStringState($trimmed, $inString);

            if (!$inString && str_ends_with($trimmed, '.')) {
                $blocks[] = trim($buffer);
                $buffer = '';
            }
        }

        $triples = [];

        foreach ($blocks as $block) {
            $block = preg_replace('/\s+\.$/', '', $block) ?? $block;
            $subject = $this->nextToken($block);
            if ($subject === null) {
                continue;
            }

            $subjectUri = $this->resolveToken($subject, $prefixes);
            $rest = ltrim(substr($block, strlen($subject)));
            $predObjectParts = $this->splitTopLevel($rest, ';');

            foreach ($predObjectParts as $part) {
                $part = trim($part);
                if ($part === '') {
                    continue;
                }

                $predicateToken = $this->nextToken($part);
                if ($predicateToken === null) {
                    continue;
                }

                $predicateUri = $this->resolveToken($predicateToken, $prefixes, true);
                $objectsPart = trim(substr($part, strlen($predicateToken)));
                $objects = $this->splitTopLevel($objectsPart, ',');

                foreach ($objects as $objectToken) {
                    $parsedObject = $this->parseObjectToken(trim($objectToken), $prefixes);
                    if ($parsedObject === null) {
                        continue;
                    }

                    $triples[] = [
                        'subject' => $subjectUri,
                        'predicate' => $predicateUri,
                        'object' => $parsedObject,
                    ];
                }
            }
        }

        return [$prefixes, $triples];
    }

    private function toggleStringState(string $line, bool $inString): bool
    {
        $escaped = false;

        for ($i = 0; $i < strlen($line); $i++) {
            $char = $line[$i];

            if ($char === '\\' && !$escaped) {
                $escaped = true;
                continue;
            }

            if ($char === '"' && !$escaped) {
                $inString = !$inString;
            }

            $escaped = false;
        }

        return $inString;
    }

    /**
     * @param  list<array{subject: string, predicate: string, object: array{type: string, value: string}}>  $triples
     * @return array<string, array<string, list<array{type: string, value: string}>>>
     */
    private function buildGraph(array $triples): array
    {
        $graph = [];

        foreach ($triples as $triple) {
            $graph[$triple['subject']][$triple['predicate']][] = $triple['object'];
        }

        return $graph;
    }

    private function resolveToken(string $token, array $prefixes, bool $predicate = false): string
    {
        $token = trim($token);

        if ($token === 'a') {
            return self::RDF_TYPE;
        }

        if (str_starts_with($token, '<') && str_ends_with($token, '>')) {
            return substr($token, 1, -1);
        }

        if (str_contains($token, ':')) {
            [$prefix, $local] = explode(':', $token, 2);
            if (array_key_exists($prefix, $prefixes)) {
                return $prefixes[$prefix].$local;
            }
        }

        return $predicate ? $token : $token;
    }

    private function parseObjectToken(string $token, array $prefixes): ?array
    {
        if ($token === '') {
            return null;
        }

        if (preg_match('/^"((?:[^"\\\\]|\\\\.)*)"(?:@[A-Za-z0-9_-]+|\^\^[^ ]+)?$/', $token, $matches) === 1) {
            return [
                'type' => 'literal',
                'value' => stripcslashes($matches[1]),
            ];
        }

        return [
            'type' => 'uri',
            'value' => $this->resolveToken($token, $prefixes),
        ];
    }

    private function nextToken(string $text): ?string
    {
        $text = ltrim($text);
        if ($text === '') {
            return null;
        }

        if ($text[0] === '<') {
            $end = strpos($text, '>');
            return $end === false ? null : substr($text, 0, $end + 1);
        }

        if ($text[0] === '"') {
            $length = strlen($text);
            $escaped = false;

            for ($i = 1; $i < $length; $i++) {
                $char = $text[$i];

                if ($char === '\\' && !$escaped) {
                    $escaped = true;
                    continue;
                }

                if ($char === '"' && !$escaped) {
                    return substr($text, 0, $i + 1);
                }

                $escaped = false;
            }

            return null;
        }

        if (preg_match('/^[^\s;,]+/', $text, $matches) === 1) {
            return $matches[0];
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function splitTopLevel(string $text, string $separator): array
    {
        $parts = [];
        $buffer = '';
        $inString = false;
        $angleDepth = 0;
        $length = strlen($text);
        $escaped = false;

        for ($i = 0; $i < $length; $i++) {
            $char = $text[$i];

            if ($char === '\\' && !$escaped) {
                $escaped = true;
                $buffer .= $char;
                continue;
            }

            if ($char === '"' && !$escaped) {
                $inString = !$inString;
                $buffer .= $char;
                continue;
            }

            if (!$inString) {
                if ($char === '<') {
                    $angleDepth++;
                } elseif ($char === '>' && $angleDepth > 0) {
                    $angleDepth--;
                } elseif ($char === $separator && $angleDepth === 0) {
                    $parts[] = trim($buffer);
                    $buffer = '';
                    continue;
                }
            }

            $buffer .= $char;
            $escaped = false;
        }

        if (trim($buffer) !== '') {
            $parts[] = trim($buffer);
        }

        return $parts;
    }

    private function resolveTitle(array $graph, string $serviceUri, array $predicates): string
    {
        foreach ($predicates[self::CRM_TITLE] ?? [] as $titleObject) {
            if (($titleObject['type'] ?? '') !== 'uri') {
                continue;
            }

            $label = $this->nodeLabel($graph, $titleObject['value']);
            if ($label !== null) {
                return $label;
            }
        }

        $direct = $this->firstLiteral($predicates[self::RDFS_LABEL] ?? []);
        if ($direct !== null) {
            return $direct;
        }

        return $this->fallbackLabel($serviceUri);
    }

    private function resolveDescription(array $predicates): ?string
    {
        $parts = $this->literalValues($predicates[self::CRM_DESCRIPTION] ?? []);
        if ($parts === []) {
            return null;
        }

        return implode("\n\n", $parts);
    }

    private function resolveTopic(array $graph, array $predicates): string
    {
        $labels = $this->labelsFromUris($graph, $predicates[self::CRM_TYPE] ?? []);

        if ($labels !== []) {
            return $labels[0];
        }

        return 'Digital Services';
    }

    private function resolveUrl(string $serviceUri, ?string $providerHomepage): ?string
    {
        if (filter_var($serviceUri, FILTER_VALIDATE_URL)) {
            return $serviceUri;
        }

        if (is_string($providerHomepage) && filter_var($providerHomepage, FILTER_VALIDATE_URL)) {
            return $providerHomepage;
        }

        return null;
    }

    private function nodeLabel(array $graph, string $uri): ?string
    {
        $predicates = $graph[$uri] ?? null;
        if (!is_array($predicates)) {
            return null;
        }

        $label = $this->firstLiteral($predicates[self::RDFS_LABEL] ?? []);
        if ($label !== null) {
            return $label;
        }

        $name = $this->firstLiteral($predicates[self::AOCAT_HAS_NAME] ?? []);
        if ($name !== null) {
            return $name;
        }

        return null;
    }

    /**
     * @param  list<array{type: string, value: string}>  $values
     */
    private function firstLiteral(array $values): ?string
    {
        foreach ($values as $value) {
            if (($value['type'] ?? '') === 'literal' && trim((string) ($value['value'] ?? '')) !== '') {
                return trim((string) $value['value']);
            }
        }

        return null;
    }

    /**
     * @param  list<array{type: string, value: string}>  $values
     */
    private function firstUri(array $values): ?string
    {
        foreach ($values as $value) {
            if (($value['type'] ?? '') === 'uri' && trim((string) ($value['value'] ?? '')) !== '') {
                return trim((string) $value['value']);
            }
        }

        return null;
    }

    /**
     * @param  list<array{type: string, value: string}>  $values
     * @return list<string>
     */
    private function uriValues(array $values): array
    {
        $uris = [];

        foreach ($values as $value) {
            if (($value['type'] ?? '') !== 'uri') {
                continue;
            }

            $uri = trim((string) ($value['value'] ?? ''));
            if ($uri !== '') {
                $uris[] = $uri;
            }
        }

        return array_values(array_unique($uris));
    }

    /**
     * @param  list<array{type: string, value: string}>  $values
     * @return list<string>
     */
    private function literalValues(array $values): array
    {
        $literals = [];

        foreach ($values as $value) {
            if (($value['type'] ?? '') !== 'literal') {
                continue;
            }

            $literal = trim((string) ($value['value'] ?? ''));
            if ($literal !== '') {
                $literals[] = $literal;
            }
        }

        return array_values(array_unique($literals));
    }

    /**
     * @param  list<array{type: string, value: string}>  $values
     * @return list<string>
     */
    private function labelsFromUris(array $graph, array $values): array
    {
        $labels = [];

        foreach ($values as $value) {
            if (($value['type'] ?? '') !== 'uri') {
                continue;
            }

            $uri = trim((string) ($value['value'] ?? ''));
            if ($uri === '') {
                continue;
            }

            $labels[] = $this->nodeLabel($graph, $uri) ?? $this->fallbackLabel($uri);
        }

        return array_values(array_unique(array_filter($labels)));
    }

    /**
     * @param  list<array{type: string, value: string}>  $values
     * @return list<array{uri: string, label: string}>
     */
    private function documentList(array $graph, array $values): array
    {
        $items = [];

        foreach ($values as $value) {
            if (($value['type'] ?? '') !== 'uri') {
                continue;
            }

            $uri = trim((string) ($value['value'] ?? ''));
            if ($uri === '') {
                continue;
            }

            $items[] = [
                'uri' => $uri,
                'label' => $this->nodeLabel($graph, $uri) ?? $this->fallbackLabel($uri),
            ];
        }

        return array_values(array_unique($items, SORT_REGULAR));
    }

    private function putDocument(string $documentId, array $document): array
    {
        try {
            $response = Http::timeout(30)
                ->acceptJson()
                ->withBody(json_encode($document, JSON_UNESCAPED_SLASHES), 'application/json')
                ->send('PUT', $this->opensearchBaseUrl().'/'.$this->servicesIndex().'/_doc/'.rawurlencode($documentId).'?refresh=wait_for');
        } catch (ConnectionException $exception) {
            throw new RuntimeException('OpenSearch is unreachable: '.$exception->getMessage(), previous: $exception);
        }

        if ($response->failed()) {
            throw new RuntimeException("OpenSearch write failed for {$documentId}: ".$response->body());
        }

        return $response->json() ?? [];
    }

    private function documentId(string $value): string
    {
        return hash('sha256', $value);
    }

    private function extractDocumentId(string $reference): string
    {
        $reference = trim($reference);

        if ($reference === '') {
            throw new RuntimeException('Empty service reference.');
        }

        if (filter_var($reference, FILTER_VALIDATE_URL)) {
            return $this->documentId($reference);
        }

        return $reference;
    }

    private function sortableId(string $uri): int
    {
        return (int) sprintf('%u', crc32($uri));
    }

    private function fallbackLabel(string $uri): string
    {
        $tail = Str::afterLast(rtrim($uri, '/'), '/');
        $tail = Str::afterLast($tail, '#');

        return Str::of($tail)
            ->replace('_', ' ')
            ->replace('-', ' ')
            ->headline()
            ->value();
    }

    private function resolveSourcePath(string $sourcePath): string
    {
        $sourcePath = trim($sourcePath);

        if ($sourcePath === '') {
            if (is_dir(base_path('services_ttl'))) {
                return base_path('services_ttl');
            }

            if (is_file(base_path('services_ttl.zip'))) {
                return base_path('services_ttl.zip');
            }
        }

        if (!str_starts_with($sourcePath, '/') && !preg_match('/^[A-Za-z]:\\\\/', $sourcePath)) {
            $sourcePath = base_path($sourcePath);
        }

        if (is_dir($sourcePath) || is_file($sourcePath)) {
            return $sourcePath;
        }

        if (is_file($sourcePath.'.zip')) {
            return $sourcePath.'.zip';
        }

        throw new RuntimeException("Source path {$sourcePath} does not exist.");
    }

    /**
     * @return list<string>
     */
    private function discoverServiceUris(string $graphUri): array
    {
        $rows = $this->querySelect($this->serviceDiscoverySelectQuery($graphUri));

        return array_values(array_unique(array_filter(array_map(
            static fn (array $row): ?string => $row['service']['value'] ?? null,
            $rows,
        ))));
    }

    private function serviceDiscoverySelectQuery(string $graphUri): string
    {
        return <<<SPARQL
SELECT DISTINCT ?service
WHERE {
  GRAPH <{$graphUri}> {
    VALUES ?serviceType {
      <http://vast-lab.org/rhdto/HC21_Digital_Service>
      <https://www.artemis-twin.eu/ontology/rhdto/HC21_Digital_Service>
    }

    ?service a ?serviceType .
  }
}
ORDER BY ?service
SPARQL;
    }

    private function readTtlForServiceFromGraphUri(string $graphUri, string $serviceUri): string
    {
        $serviceUri = $this->assertAbsoluteUri($serviceUri, 'service');

        try {
            $response = Http::timeout(25)
                ->withHeaders(['Accept' => 'text/turtle'])
                ->asForm()
                ->post($this->sparqlEndpoint(), [
                    'query' => $this->serviceEntityConstructQuery($graphUri, $serviceUri),
                    'infer' => 'false',
                ]);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('GraphDB is unreachable: '.$exception->getMessage(), previous: $exception);
        }

        if ($response->failed()) {
            throw new RuntimeException("GraphDB query failed for {$serviceUri}: ".$response->body());
        }

        $ttl = trim((string) $response->body());

        if ($ttl === '') {
            throw new RuntimeException("Graph {$graphUri} returned no triples for service {$serviceUri}.");
        }

        return $ttl;
    }

    private function serviceEntityConstructQuery(string $graphUri, string $serviceUri): string
    {
        return <<<SPARQL
CONSTRUCT {
  ?service ?p ?o .
  ?o ?op ?ov .
}
WHERE {
  GRAPH <{$graphUri}> {
    BIND(<{$serviceUri}> AS ?service)
    ?service ?p ?o .

    OPTIONAL {
      FILTER(isIRI(?o))
      ?o ?op ?ov .
    }
  }
}
SPARQL;
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

    private function assertAbsoluteUri(string $uri, string $label): string
    {
        $uri = trim($uri);

        if (!filter_var($uri, FILTER_VALIDATE_URL)) {
            throw new RuntimeException("Invalid {$label} URI: {$uri}");
        }

        return $uri;
    }

    private function sparqlEndpoint(): string
    {
        return rtrim((string) env('GRAPHDB_SPARQL_ENDPOINT', 'https://graphdb.ino.cnr.it/repositories/artemis-kb01'), '/');
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchImportedDocuments(): array
    {
        $documents = [];
        $searchAfter = null;

        do {
            $payload = [
                'size' => 250,
                '_source' => ['uri', 'title', 'topic', 'provider', 'sourcePath', 'url', 'importSource'],
                'sort' => [['_id' => 'asc']],
                'query' => ['match_all' => (object) []],
            ];

            if ($searchAfter !== null) {
                $payload['search_after'] = [$searchAfter];
            }

            try {
                $response = Http::timeout(30)
                    ->acceptJson()
                    ->withBody(json_encode($payload, JSON_UNESCAPED_SLASHES), 'application/json')
                    ->send('POST', $this->opensearchBaseUrl().'/'.$this->servicesIndex().'/_search');
            } catch (ConnectionException $exception) {
                throw new RuntimeException('OpenSearch is unreachable: '.$exception->getMessage(), previous: $exception);
            }

            if ($response->failed()) {
                throw new RuntimeException('OpenSearch services projection sync failed: '.$response->body());
            }

            $hits = $response->json('hits.hits') ?? [];

            foreach ($hits as $hit) {
                $source = $hit['_source'] ?? [];
                if (($source['importSource'] ?? null) !== 'services-ttl') {
                    continue;
                }

                $source['id'] = $hit['_id'] ?? null;
                $documents[] = $source;
            }

            $lastHit = end($hits);
            $searchAfter = $lastHit['sort'][0] ?? null;
        } while (!empty($hits));

        return $documents;
    }

    private function opensearchBaseUrl(): string
    {
        return rtrim((string) env('OPENSEARCH_URL', 'http://127.0.0.1:9200'), '/');
    }

    private function servicesIndex(): string
    {
        return (string) env('OPENSEARCH_SERVICES_INDEX', 'ariadne_services');
    }
}
