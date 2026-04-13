<?php

namespace Tests\Feature\Services;

use App\Services\PortalResourceImportService;
use Tests\TestCase;

class PortalResourceImportServiceTest extends TestCase
{
    public function test_it_derives_portal_record_id_from_aocat_uri(): void
    {
        $service = app(PortalResourceImportService::class);

        $recordId = $service->extractRecordId(
            'https://ariadne-infrastructure.eu/aocat/Collection/AMCR/4999D660-D1DB-360B-819F-DC15E3C79867'
        );

        $this->assertSame(
            '6eb9e1118dccd2ab9fbce5124ea475b36f338814a7ae1167a505b3b558d6529f',
            $recordId
        );
    }
}
