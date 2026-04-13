<?php

namespace Tests\Feature\Filament;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeritageEntitiesPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_heritage_entities_page(): void
    {
        $user = User::factory()->create([
            'is_admin' => true,
        ]);

        $this->actingAs($user)
            ->get('/admin/heritage-entities')
            ->assertOk();
    }
}
