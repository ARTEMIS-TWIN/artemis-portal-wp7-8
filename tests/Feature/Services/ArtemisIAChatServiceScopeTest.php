<?php

namespace Tests\Feature\Services;

use App\Services\ArtemisIA\ArtemisIAChatService;
use Tests\TestCase;

class ArtemisIAChatServiceScopeTest extends TestCase
{
    public function test_scope_defaults_to_selected_only_when_records_are_selected(): void
    {
        $scope = ArtemisIAChatService::resolveScopeDecision(
            hasSelectedRecords: true,
            explicitGlobal: false,
            explicitSelectedOnly: false,
            scopeOverride: null,
        );

        $this->assertSame('selected_only', $scope);
    }

    public function test_scope_switches_to_global_on_explicit_global_signal(): void
    {
        $scope = ArtemisIAChatService::resolveScopeDecision(
            hasSelectedRecords: true,
            explicitGlobal: true,
            explicitSelectedOnly: false,
            scopeOverride: null,
        );

        $this->assertSame('global', $scope);
    }

    public function test_scope_uses_global_when_nothing_is_selected(): void
    {
        $scope = ArtemisIAChatService::resolveScopeDecision(
            hasSelectedRecords: false,
            explicitGlobal: false,
            explicitSelectedOnly: false,
            scopeOverride: null,
        );

        $this->assertSame('global', $scope);
    }

    public function test_explicit_scope_override_takes_precedence(): void
    {
        $scope = ArtemisIAChatService::resolveScopeDecision(
            hasSelectedRecords: true,
            explicitGlobal: false,
            explicitSelectedOnly: false,
            scopeOverride: 'global',
        );

        $this->assertSame('global', $scope);
    }
}

