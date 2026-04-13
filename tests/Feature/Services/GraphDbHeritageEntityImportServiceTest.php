<?php

namespace Tests\Feature\Services;

use App\Services\GraphDbHeritageEntityImportService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GraphDbHeritageEntityImportServiceTest extends TestCase
{
    public function test_it_imports_a_graphdb_heritage_entity_into_opensearch(): void
    {
        $relatedResourceUri = 'https://ariadne-infrastructure.eu/aocat/Resource/14EBE8A4-42D0-3FD2-AD07-4CFC6033E772';
        $relatedResourceId = hash('sha256', $relatedResourceUri);

        Http::fake(function (Request $request) use ($relatedResourceId, $relatedResourceUri) {
            if (str_starts_with($request->url(), 'https://graphdb.ino.cnr.it/repositories/artemis-kb01')) {
                $query = (string) ($request['query'] ?? '');

                if (str_contains($query, 'SELECT DISTINCT ?entityUri')) {
                    return Http::response([
                        'head' => ['vars' => ['entityUri']],
                        'results' => [
                            'bindings' => [[
                                'entityUri' => [
                                    'type' => 'uri',
                                    'value' => 'https://artemis-twin.eu/entity/Stonehenge',
                                ],
                            ]],
                        ],
                    ]);
                }

                return Http::response([
                    'head' => ['vars' => ['typeUri', 'label', 'title', 'sameAs']],
                    'results' => [
                        'bindings' => [
                            [
                                'typeUri' => ['type' => 'uri', 'value' => 'https://www.artemis-twin.eu/ontology/rhdto/HC3_Tangible_Heritage'],
                                'label' => ['type' => 'literal', 'value' => 'Stonehenge - Neolithic henge monument in Amesbury, Wiltshire, England, UK'],
                                'title' => ['type' => 'literal', 'value' => 'Stonehenge'],
                                'description' => ['type' => 'literal', 'value' => 'Stonehenge is one of the most famous prehistoric monuments in the world.'],
                                'sameAs' => ['type' => 'uri', 'value' => 'http://www.wikidata.org/entity/Q39671'],
                                'classification' => ['type' => 'uri', 'value' => 'http://vocab.getty.edu/aat/300006983'],
                                'classificationLabel' => ['type' => 'literal', 'value' => 'megalithic monuments'],
                                'material' => ['type' => 'uri', 'value' => 'http://vocab.getty.edu/aat/300011672'],
                                'materialLabel' => ['type' => 'literal', 'value' => 'sarsen'],
                                'period' => ['type' => 'uri', 'value' => 'https://artemis-twin.eu/entity/Period_Neolithic_England'],
                                'periodAuthority' => ['type' => 'uri', 'value' => 'http://n2t.net/ark:/99152/p0kh9dsbz2g'],
                                'periodLabel' => ['type' => 'literal', 'value' => 'Neolithic - England'],
                                'from' => ['type' => 'literal', 'value' => '-4000'],
                                'until' => ['type' => 'literal', 'value' => '-2200'],
                                'place' => ['type' => 'uri', 'value' => 'https://artemis-twin.eu/entity/Place_Stonehenge'],
                                'placeLabel' => ['type' => 'literal', 'value' => 'Stonehenge, Amesbury, United Kingdom'],
                                'placeName' => ['type' => 'literal', 'value' => 'Stonehenge, Amesbury, Wiltshire, UK'],
                                'placeIri' => ['type' => 'uri', 'value' => 'https://www.geonames.org/2636812'],
                                'placeIriLabel' => ['type' => 'literal', 'value' => 'Stonehenge'],
                                'country' => ['type' => 'uri', 'value' => 'https://www.geonames.org/2635167'],
                                'countryLabel' => ['type' => 'literal', 'value' => 'United Kingdom'],
                                'lat' => ['type' => 'literal', 'value' => '51.17889'],
                                'lon' => ['type' => 'literal', 'value' => '-1.82622'],
                                'identifierNode' => ['type' => 'uri', 'value' => 'https://artemis-twin.eu/entity/Stonehenge_Identifier'],
                                'identifierType' => ['type' => 'uri', 'value' => 'http://www.cidoc-crm.org/cidoc-crm/E42_Identifier'],
                                'identifierLabel' => ['type' => 'literal', 'value' => 'artemis_he_id_001'],
                                'identifierValue' => ['type' => 'literal', 'value' => 'artemis_he_id_001'],
                                'owner' => ['type' => 'uri', 'value' => 'http://vocab.getty.edu/ulan/500216503'],
                                'ownerLabel' => ['type' => 'literal', 'value' => 'English Heritage'],
                                'ownerHomepage' => ['type' => 'literal', 'value' => 'https://www.english-heritage.org.uk/'],
                                'ownerIdentifier' => ['type' => 'literal', 'value' => 'https://www.english-heritage.org.uk/'],
                                'encounter' => ['type' => 'uri', 'value' => 'https://artemis-twin.eu/entity/Stonehenge_Riverside_Project'],
                                'encounterLabel' => ['type' => 'literal', 'value' => 'Stonehenge Riverside Project (2003-2009)'],
                                'encounterActor' => ['type' => 'uri', 'value' => 'http://viaf.org/viaf/7479619'],
                                'encounterActorLabel' => ['type' => 'literal', 'value' => 'Michael Parker Pearson'],
                                'encounterTimeSpanLabel' => ['type' => 'literal', 'value' => '2003-2009'],
                                'relatedDataResource' => ['type' => 'uri', 'value' => $relatedResourceUri],
                                'visualRepresentation' => ['type' => 'uri', 'value' => 'https://upload.wikimedia.org/wikipedia/commons/b/b0/Stonehenge_plan.jpg'],
                                'visualRepresentationLabel' => ['type' => 'literal', 'value' => 'Aerial view of Stonehenge monument'],
                            ],
                            [
                                'typeUri' => ['type' => 'uri', 'value' => 'https://www.artemis-twin.eu/ontology/rhdto/HC3_Tangible_Heritage'],
                                'label' => ['type' => 'literal', 'value' => 'Stonehenge - Neolithic henge monument in Amesbury, Wiltshire, England, UK'],
                                'title' => ['type' => 'literal', 'value' => 'Stonehenge'],
                                'description' => ['type' => 'literal', 'value' => 'Stonehenge is one of the most famous prehistoric monuments in the world.'],
                                'sameAs' => ['type' => 'uri', 'value' => 'http://www.wikidata.org/entity/Q39671'],
                                'classification' => ['type' => 'uri', 'value' => 'http://vocab.getty.edu/aat/300006983'],
                                'classificationLabel' => ['type' => 'literal', 'value' => 'megalithic monuments'],
                                'material' => ['type' => 'uri', 'value' => 'http://vocab.getty.edu/aat/300011162'],
                                'materialLabel' => ['type' => 'literal', 'value' => 'bluestone'],
                                'period' => ['type' => 'uri', 'value' => 'https://artemis-twin.eu/entity/Period_BronzeAge_England'],
                                'periodAuthority' => ['type' => 'uri', 'value' => 'http://n2t.net/ark:/99152/p0kh9ds7q8m'],
                                'periodLabel' => ['type' => 'literal', 'value' => 'Bronze Age - England'],
                                'from' => ['type' => 'literal', 'value' => '-2600'],
                                'until' => ['type' => 'literal', 'value' => '-700'],
                                'place' => ['type' => 'uri', 'value' => 'https://artemis-twin.eu/entity/Place_Stonehenge'],
                                'placeLabel' => ['type' => 'literal', 'value' => 'Stonehenge, Amesbury, United Kingdom'],
                                'placeName' => ['type' => 'literal', 'value' => 'Stonehenge, Amesbury, Wiltshire, UK'],
                                'placeIri' => ['type' => 'uri', 'value' => 'https://www.geonames.org/2636812'],
                                'placeIriLabel' => ['type' => 'literal', 'value' => 'Stonehenge'],
                                'country' => ['type' => 'uri', 'value' => 'https://www.geonames.org/2635167'],
                                'countryLabel' => ['type' => 'literal', 'value' => 'United Kingdom'],
                                'lat' => ['type' => 'literal', 'value' => '51.17889'],
                                'lon' => ['type' => 'literal', 'value' => '-1.82622'],
                                'identifierNode' => ['type' => 'uri', 'value' => 'https://artemis-twin.eu/entity/Stonehenge_Appellation'],
                                'identifierType' => ['type' => 'uri', 'value' => 'http://www.cidoc-crm.org/cidoc-crm/E41_Appellation'],
                                'identifierLabel' => ['type' => 'literal', 'value' => 'Stonehenge'],
                                'identifierValue' => ['type' => 'literal', 'value' => 'Stonehenge'],
                                'owner' => ['type' => 'uri', 'value' => 'http://vocab.getty.edu/ulan/500216503'],
                                'ownerLabel' => ['type' => 'literal', 'value' => 'English Heritage'],
                                'ownerHomepage' => ['type' => 'literal', 'value' => 'https://www.english-heritage.org.uk/'],
                                'ownerIdentifier' => ['type' => 'literal', 'value' => 'https://www.english-heritage.org.uk/'],
                                'encounter' => ['type' => 'uri', 'value' => 'https://artemis-twin.eu/entity/Stonehenge_Riverside_Project'],
                                'encounterLabel' => ['type' => 'literal', 'value' => 'Stonehenge Riverside Project (2003-2009)'],
                                'encounterActor' => ['type' => 'uri', 'value' => 'http://viaf.org/viaf/7479619'],
                                'encounterActorLabel' => ['type' => 'literal', 'value' => 'Michael Parker Pearson'],
                                'encounterTimeSpanLabel' => ['type' => 'literal', 'value' => '2003-2009'],
                                'relatedDataResource' => ['type' => 'uri', 'value' => $relatedResourceUri],
                                'visualRepresentation' => ['type' => 'uri', 'value' => 'https://upload.wikimedia.org/wikipedia/commons/b/b0/Stonehenge_plan.jpg'],
                                'visualRepresentationLabel' => ['type' => 'literal', 'value' => 'Aerial view of Stonehenge monument'],
                            ],
                        ],
                    ],
                ]);
            }

            if (str_starts_with($request->url(), 'https://portal.ariadne-infrastructure.eu/api/getRecord/'.$relatedResourceId)) {
                return Http::response([
                    'identifier' => $relatedResourceUri,
                    'resourceType' => 'dataset',
                    'title' => ['text' => 'Stonehenge Riverside Project report'],
                ]);
            }

            if (str_starts_with($request->url(), 'http://127.0.0.1:9200/ariadne_portal/_doc/'.$relatedResourceId)) {
                return Http::response(['result' => 'created']);
            }

            if (str_starts_with($request->url(), 'http://127.0.0.1:9200/ariadne_portal/_search')) {
                $payload = json_decode($request->body(), true);

                if (! empty($payload['search_after'])) {
                    return Http::response([
                        'hits' => [
                            'hits' => [],
                        ],
                    ]);
                }

                return Http::response([
                    'hits' => [
                        'hits' => [
                            [
                                '_id' => $relatedResourceId,
                                '_source' => [
                                    'identifier' => $relatedResourceUri,
                                    'resourceType' => 'dataset',
                                    'title' => ['text' => 'Stonehenge Riverside Project report'],
                                    'importSource' => 'ariadne-portal-api',
                                    'sourcePortal' => 'https://portal.ariadne-infrastructure.eu',
                                ],
                                'sort' => [$relatedResourceId],
                            ],
                        ],
                    ],
                ]);
            }

            if (str_starts_with($request->url(), 'http://127.0.0.1:9200/artemis_heritage_entities/_doc/')) {
                return Http::response(['result' => 'created']);
            }

            return Http::response(status: 500);
        });

        $results = app(GraphDbHeritageEntityImportService::class)->importGraph(
            'https://artemis-twin.eu/digitaltwins/stonehenge'
        );

        $this->assertCount(1, $results);
        $this->assertSame('created', $results[0]['result']);
        $this->assertSame('Stonehenge', $results[0]['label']);
        $this->assertSame('Tangible Heritage', $results[0]['entityType']);

        Http::assertSent(function (Request $request) use ($relatedResourceId) {
            if (! str_starts_with($request->url(), 'http://127.0.0.1:9200/artemis_heritage_entities/_doc/')) {
                return false;
            }

            $payload = json_decode($request->body(), true);

            return $payload['uri'] === 'https://artemis-twin.eu/entity/Stonehenge'
                && $payload['placeLabel'] === 'Stonehenge'
                && $payload['countryLabel'] === 'United Kingdom'
                && $payload['entityType'] === 'Tangible Heritage'
                && $payload['hasWikidata'] === true
                && $payload['ownerLabel'] === 'English Heritage'
                && $payload['hasImage'] === true
                && $payload['hasRelatedDataResources'] === true
                && $payload['minPeriodFrom'] === -4000
                && $payload['maxPeriodUntil'] === -700
                && $payload['periodLabels'] === ['Neolithic - England', 'Bronze Age - England']
                && $payload['materials'] === ['sarsen', 'bluestone']
                && count($payload['identifiers']) === 2
                && $payload['visualRepresentations'][0]['uri'] === 'https://upload.wikimedia.org/wikipedia/commons/b/b0/Stonehenge_plan.jpg'
                && $payload['relatedDataResources'][0]['id'] === $relatedResourceId;
        });
    }
}
