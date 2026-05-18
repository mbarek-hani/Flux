<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MarketplaceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test guest is redirected to login when trying to access marketplace.
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/marketplace');
        $response->assertRedirect('/login');
    }

    /**
     * Test authenticated user can access marketplace index.
     */
    public function test_authenticated_user_can_access_marketplace_index(): void
    {
        $user = User::factory()->create();

        // Mock the registry API call
        Http::fake([
            '*/v1/marketplace/plugins*' => Http::response([
                'data' => [
                    [
                        'id' => '76f62f3a-97ab-43e3-82be-92ea1295b9c1',
                        'name' => 'ai-analytics',
                        'current_version' => '1.0.0',
                        'author' => 'Flux Team',
                        'description' => 'AI Analytics Plugin',
                        'total_downloads' => 125,
                        'repo_url' => 'https://github.com/flux/ai-analytics',
                    ],
                ],
                'meta' => [
                    'total' => 1,
                    'page' => 1,
                    'page_size' => 10,
                    'total_pages' => 1,
                ],
            ], 200),
        ]);

        $response = $this->actingAs($user)->get('/marketplace');

        $response->assertStatus(200);
        $response->assertViewHas('plugins');
        $response->assertSee('ai-analytics');
        $response->assertSee('v1.0.0');
    }

    /**
     * Test error handling when registry is down.
     */
    public function test_marketplace_displays_error_alert_when_registry_is_down(): void
    {
        $user = User::factory()->create();

        // Simulate connection timeout/failure
        Http::fake([
            '*/v1/marketplace/plugins*' => Http::response(null, 500),
        ]);

        $response = $this->actingAs($user)->get('/marketplace');

        $response->assertStatus(200);
        $response->assertViewHas('error');
        $response->assertSee('Impossible de se connecter au registre de plugins');
    }

    /**
     * Test details page can be rendered.
     */
    public function test_plugin_details_page_can_be_rendered(): void
    {
        $user = User::factory()->create();
        $pluginId = '76f62f3a-97ab-43e3-82be-92ea1295b9c1';

        Http::fake([
            '*/v1/marketplace/plugins/*' => Http::response([
                'data' => [
                    'id' => $pluginId,
                    'name' => 'ai-analytics',
                    'current_version' => '1.0.0',
                    'author' => 'Flux Team',
                    'description' => 'AI Analytics description detailed',
                    'total_downloads' => 125,
                    'repo_url' => 'https://github.com/flux/ai-analytics',
                    'licence' => 'MIT',
                ],
            ], 200),
        ]);

        $response = $this->actingAs($user)->get("/marketplace/{$pluginId}");

        $response->assertStatus(200);
        $response->assertSee('ai-analytics');
        $response->assertSee('AI Analytics description detailed');
        $response->assertSee('Consulter le dépôt');
    }
}
