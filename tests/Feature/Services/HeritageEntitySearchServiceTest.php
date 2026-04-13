<?php

namespace Tests\Feature\Services;

use App\Services\HeritageEntitySearchService;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HeritageEntitySearchServiceTest extends TestCase
{
    public function test_search_payload_includes_combined_filters(): void
    {
        $capturedPayload = null;

        Http::fake(function (ClientRequest $request) use (&$capturedPayload) {
            $this->assertStringEndsWith('/artemis_heritage_entities/_search', $request->url());

            $capturedPayload = json_decode($request->body(), true, flags: JSON_THROW_ON_ERROR);

            return Http::response([
                'hits' => [
                    'total' => ['value' => 1, 'relation' => 'eq'],
                    'hits' => [
                        [
                            '_id' => 'stonehenge',
                            '_source' => [
                                'label' => 'Stonehenge',
                                'entityType' => 'Cultural Object',
                                'countryLabel' => 'United Kingdom',
                            ],
                        ],
                    ],
                ],
                'aggregations' => [],
            ]);
        });

        $result = app(HeritageEntitySearchService::class)->search(
            Request::create('/api/heritage-entities/search', 'GET', [
                'q' => 'stonehenge',
                'classification' => 'megalithic monuments',
                'country' => 'United Kingdom',
                'owner' => 'English Heritage',
                'relatedDataResource' => 'Has related data resources',
            ])
        );

        $this->assertSame(['value' => 1, 'relation' => 'eq'], $result['total']);
        $this->assertCount(1, $result['hits']);
        $this->assertSame('Stonehenge', $result['hits'][0]['data']['label']);
        $this->assertIsArray($capturedPayload);
        $this->assertSame('stonehenge | "stonehenge" | stonehenge*', $capturedPayload['query']['bool']['must'][0]['simple_query_string']['query']);
        $this->assertContainsEquals([
            'terms' => [
                'classificationLabels' => ['megalithic monuments'],
            ],
        ], $capturedPayload['query']['bool']['filter']);
        $this->assertContainsEquals([
            'terms' => [
                'countryLabel' => ['United Kingdom'],
            ],
        ], $capturedPayload['query']['bool']['filter']);
        $this->assertContainsEquals([
            'terms' => [
                'ownerLabel' => ['English Heritage'],
            ],
        ], $capturedPayload['query']['bool']['filter']);
        $this->assertContainsEquals([
            'terms' => [
                'relatedDataResourceStatus' => ['Has related data resources'],
            ],
        ], $capturedPayload['query']['bool']['filter']);
    }
}
