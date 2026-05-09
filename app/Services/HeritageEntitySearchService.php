<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use stdClass;

class HeritageEntitySearchService
{
    public function __construct(
        private readonly ?string $baseUrl = null,
        private readonly ?string $index = null,
    ) {
    }

    public function search(Request $request): array
    {
        return $this->resultToFrontend(
            $this->searchIndex($this->buildSearchPayload($request->query()))
        );
    }

    public function getSearchAggregationData(Request $request): array
    {
        $payload = $this->buildSearchPayload($request->query());
        $payload['size'] = 0;
        $payload['aggregations'] = $this->aggregations();

        unset($payload['_source'], $payload['from'], $payload['sort']);

        return $this->resultToFrontend($this->searchIndex($payload));
    }

    public function getTotalCount(): int
    {
        return (int) ($this->request('GET', '/'.$this->index().'/_count')['count'] ?? 0);
    }

    public function getEntity(string $id): ?array
    {
        $result = $this->request('GET', '/'.$this->index().'/_doc/'.rawurlencode($id), allow404: true);

        if (($result['found'] ?? false) !== true) {
            return null;
        }

        return [
            'id' => $result['_id'] ?? $id,
            'data' => $result['_source'] ?? [],
        ];
    }

    private function buildSearchPayload(array $params): array
    {
        $payload = [
            'size' => $this->size($params),
            'from' => $this->from($params),
            'sort' => $this->sort($params),
            '_source' => [
                'uri',
                'label',
                'description',
                'entityType',
                'classification',
                'classificationLabels',
                'materials',
                'periods',
                'periodLabels',
                'countryLabel',
                'placeLabel',
                'ownerLabel',
                'hasImage',
                'visualRepresentationStatus',
                'hasRelatedDataResources',
                'relatedDataResourceStatus',
                'relatedDataResources',
                'visualRepresentations',
                'location',
                'sameAs',
                'sourceGraph',
                'hasWikidata',
                'minPeriodFrom',
                'maxPeriodUntil',
                'timelineFrom',
                'timelineUntil',
                'timelineSource',
            ],
        ];

        $query = trim((string) ($params['q'] ?? ''));
        $must = [];
        $filter = [];

        if ($query === '') {
            $must[] = ['match_all' => new stdClass()];
        } else {
            $must[] = [
                'simple_query_string' => [
                    'query' => sprintf('%1$s | "%1$s" | %1$s*', $query),
                    'fields' => [
                        'label^4',
                        'description^2',
                        'entityType^2',
                        'classification.label^3',
                        'classificationLabels^3',
                        'materials^2',
                        'periods.label^2',
                        'periodLabels^2',
                        'placeLabel^2',
                        'countryLabel',
                        'owner.label^2',
                        'ownerLabel^2',
                        'encounterEvent.label',
                        'encounterEvent.actorLabel',
                        'relatedDataResources.title^2',
                    ],
                    'default_operator' => 'and',
                ],
            ];
        }

        foreach ($this->filters($params) as $field => $values) {
            if ($values === []) {
                continue;
            }

            $filter[] = [
                'terms' => [
                    $field => $values,
                ],
            ];
        }

        $payload['query'] = [
            'bool' => [
                'must' => $must,
                'filter' => $filter,
            ],
        ];

        return $payload;
    }

    /**
     * @return array<string, list<string>>
     */
    private function filters(array $params): array
    {
        return [
            'entityType' => $this->explodeFilter($params['entityType'] ?? null),
            'classificationLabels' => $this->explodeFilter($params['classification'] ?? null),
            'materials' => $this->explodeFilter($params['material'] ?? null),
            'periodLabels' => $this->explodeFilter($params['period'] ?? null),
            'countryLabel' => $this->explodeFilter($params['country'] ?? null),
            'placeLabel' => $this->explodeFilter($params['place'] ?? null),
            'ownerLabel' => $this->explodeFilter($params['owner'] ?? null),
            'visualRepresentationStatus' => $this->explodeFilter($params['visualRepresentation'] ?? null),
            'relatedDataResourceStatus' => $this->explodeFilter($params['relatedDataResource'] ?? null),
        ];
    }

    /**
     * @return list<string>
     */
    private function explodeFilter(mixed $value): array
    {
        if (!is_string($value) || trim($value) === '') {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (string $item): string => trim($item),
            explode('|', $value),
        )));
    }

    /**
     * @return array<string, mixed>
     */
    private function aggregations(): array
    {
        return [
            'entityType' => $this->termsAggregation('entityType'),
            'classification' => $this->termsAggregation('classificationLabels'),
            'material' => $this->termsAggregation('materials'),
            'period' => $this->termsAggregation('periodLabels'),
            'country' => $this->termsAggregation('countryLabel'),
            'place' => $this->termsAggregation('placeLabel'),
            'owner' => $this->termsAggregation('ownerLabel'),
            'visualRepresentation' => $this->termsAggregation('visualRepresentationStatus'),
            'relatedDataResource' => $this->termsAggregation('relatedDataResourceStatus'),
            'range_buckets' => $this->timelineBucketsAggregation(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function timelineBucketsAggregation(): array
    {
        $currentYear = (int) date('Y');
        $range = [-1000000, -100000, -10000, -1000, 0, 1000, 1250, 1500, 1750, $currentYear];

        $intervalCount = count($range) - 1;
        $defaultBucketCount = 50;
        $bucketsPerInterval = $intervalCount > 1
            ? (int) floor($defaultBucketCount / $intervalCount)
            : $defaultBucketCount;

        $filters = [];

        for ($intervalIndex = 0; $intervalIndex < $intervalCount; $intervalIndex++) {
            if ($intervalIndex === $intervalCount - 1) {
                $bucketsPerInterval += $defaultBucketCount % $intervalCount;
            }

            $startYear = $range[$intervalIndex];
            $endYear = $range[$intervalIndex + 1];
            $delta = ($endYear - $startYear) / $bucketsPerInterval;
            $currentStartYear = $startYear;

            for ($bucketIndex = 0; $bucketIndex < $bucketsPerInterval; $bucketIndex++) {
                $rangeStartYear = (int) round($currentStartYear);
                $rangeEndYear = (int) round($currentStartYear + $delta);

                $filters[$rangeStartYear.':'.$rangeEndYear] = [
                    'bool' => [
                        'should' => [
                            // Full chronology overlap (range intersects timeline bucket).
                            [
                                'bool' => [
                                    'must' => [
                                        ['range' => ['timelineUntil' => ['gte' => $rangeStartYear]]],
                                        ['range' => ['timelineFrom' => ['lte' => $rangeEndYear]]],
                                    ],
                                ],
                            ],
                            // Preferred timeline source with only lower bound available.
                            [
                                'range' => [
                                    'timelineFrom' => [
                                        'gte' => $rangeStartYear,
                                        'lte' => $rangeEndYear,
                                    ],
                                ],
                            ],
                            // Preferred timeline source with only upper bound available.
                            [
                                'range' => [
                                    'timelineUntil' => [
                                        'gte' => $rangeStartYear,
                                        'lte' => $rangeEndYear,
                                    ],
                                ],
                            ],
                            // Legacy chronology overlap (range intersects timeline bucket).
                            [
                                'bool' => [
                                    'must' => [
                                        ['range' => ['maxPeriodUntil' => ['gte' => $rangeStartYear]]],
                                        ['range' => ['minPeriodFrom' => ['lte' => $rangeEndYear]]],
                                    ],
                                ],
                            ],
                            // Fallback when only "from" is available.
                            [
                                'range' => [
                                    'minPeriodFrom' => [
                                        'gte' => $rangeStartYear,
                                        'lte' => $rangeEndYear,
                                    ],
                                ],
                            ],
                            // Fallback when only "until" is available.
                            [
                                'range' => [
                                    'maxPeriodUntil' => [
                                        'gte' => $rangeStartYear,
                                        'lte' => $rangeEndYear,
                                    ],
                                ],
                            ],
                        ],
                        'minimum_should_match' => 1,
                    ],
                ];

                $currentStartYear += $delta;
            }
        }

        return [
            'filters' => [
                'filters' => $filters,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function termsAggregation(string $field): array
    {
        return [
            'terms' => [
                'field' => $field,
                'size' => 30,
                'order' => ['_count' => 'desc'],
            ],
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function sort(array $params): array
    {
        $sort = (string) ($params['sort'] ?? '');
        $order = (string) ($params['order'] ?? '');

        return match ($sort) {
            'label' => ['label.keyword' => ['order' => $order === 'desc' ? 'desc' : 'asc']],
            'periodFrom' => ['minPeriodFrom' => ['order' => $order === 'desc' ? 'desc' : 'asc']],
            default => [($this->hasQuery($params) ? '_score' : 'label.keyword') => ['order' => $this->hasQuery($params) ? 'desc' : 'asc']],
        };
    }

    private function from(array $params): int
    {
        $page = (int) ($params['page'] ?? 0);

        if ($page < 2) {
            return 0;
        }

        return ($page - 1) * $this->size($params);
    }

    private function size(array $params): int
    {
        return min(max((int) ($params['size'] ?? 20), 0), 50);
    }

    private function hasQuery(array $params): bool
    {
        return trim((string) ($params['q'] ?? '')) !== '';
    }

    /**
     * @return array{total: mixed, hits: list<array{id: string, data: array<string, mixed>}>, aggregations: array<string, mixed>}
     */
    private function resultToFrontend(array $result): array
    {
        $hits = [];

        foreach (Arr::get($result, 'hits.hits', []) as $hit) {
            $hits[] = [
                'id' => (string) ($hit['_id'] ?? ''),
                'data' => $hit['_source'] ?? [],
            ];
        }

        return [
            'total' => $result['hits']['total'] ?? ['value' => 0, 'relation' => 'eq'],
            'hits' => $hits,
            'aggregations' => $result['aggregations'] ?? [],
        ];
    }

    private function searchIndex(array $payload): array
    {
        return $this->request('POST', '/'.$this->index().'/_search', $payload);
    }

    private function request(string $method, string $path, array $payload = [], bool $allow404 = false): array
    {
        try {
            $response = Http::timeout(15)
                ->acceptJson()
                ->withBody($payload ? json_encode($payload, JSON_UNESCAPED_SLASHES) : '', 'application/json')
                ->send($method, $this->baseUrl().$path);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('OpenSearch is unreachable: '.$exception->getMessage(), previous: $exception);
        }

        if ($allow404 && $response->status() === 404) {
            return ['found' => false];
        }

        if ($response->failed()) {
            throw new RuntimeException("OpenSearch request failed for {$path}: ".$response->body());
        }

        return $response->json() ?? [];
    }

    private function baseUrl(): string
    {
        return rtrim((string) ($this->baseUrl ?? env('OPENSEARCH_URL', 'http://127.0.0.1:9200')), '/');
    }

    private function index(): string
    {
        return (string) ($this->index ?? env('OPENSEARCH_HERITAGE_ENTITIES_INDEX', 'artemis_heritage_entities'));
    }
}
