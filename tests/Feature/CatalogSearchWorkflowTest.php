<?php

namespace Tests\Feature;

use App\Http\Middleware\ResolveSiteContext;
use App\Services\CatalogSearchService;
use Mockery;
use Tests\TestCase;

class CatalogSearchWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ResolveSiteContext::class);
    }

    public function test_search_page_opens_without_a_keyword(): void
    {
        $this->bindEmptyCatalog();

        $response = $this->get('/search');

        $response->assertOk();
        $response->assertSee('name="query"', false);
        $response->assertSee('Commencez votre recherche');
    }

    public function test_legacy_qurey_parameter_is_still_supported(): void
    {
        $catalog = Mockery::mock(CatalogSearchService::class);
        $catalog->shouldReceive('recommendationProfile')->once()->andReturn([]);
        $catalog->shouldReceive('searchRestaurants')->once()->withArgs(fn ($query) => $query === 'pondu')->andReturn(collect());
        $catalog->shouldReceive('searchProducts')->once()->withArgs(fn ($query) => $query === 'pondu')->andReturn(collect());
        $catalog->shouldReceive('recommendRestaurants')->once()->andReturn(collect());
        $catalog->shouldReceive('recommendProducts')->once()->andReturn(collect());
        $this->app->instance(CatalogSearchService::class, $catalog);

        $response = $this->get('/search?qurey=pondu');

        $response->assertOk();
        $response->assertSee('value="pondu"', false);
    }

    public function test_home_restaurant_search_is_redirected_to_the_global_search(): void
    {
        $response = $this->withoutMiddleware()->get('/restaurants?search=pondu');

        $response->assertRedirect('/search?query=pondu');
    }

    public function test_location_search_keeps_coordinates_separate_from_keyword(): void
    {
        $response = $this->withoutMiddleware()->get('/restaurants?search=Bacongo&lat=-4.271&lng=15.251');

        $location = $response->headers->get('Location');
        $this->assertNotNull($location);
        $this->assertStringContainsString('/search?', $location);
        $this->assertStringContainsString('latitude=-4.271', $location);
        $this->assertStringContainsString('longitude=15.251', $location);
        $this->assertStringContainsString('location_label=Bacongo', $location);
        $this->assertStringNotContainsString('query=Bacongo', $location);
    }

    public function test_empty_api_search_returns_a_stable_payload(): void
    {
        $this->bindEmptyCatalog(false);

        $response = $this->getJson('/search/api');

        $response->assertOk()
            ->assertJson([
                'status' => true,
                'query' => '',
                'restaurants' => [],
                'products' => [],
            ]);
    }

    private function bindEmptyCatalog(bool $withRecommendations = true): void
    {
        $catalog = Mockery::mock(CatalogSearchService::class);

        if ($withRecommendations) {
            $catalog->shouldReceive('recommendationProfile')->once()->andReturn([]);
            $catalog->shouldReceive('recommendRestaurants')->once()->andReturn(collect());
            $catalog->shouldReceive('recommendProducts')->once()->andReturn(collect());
        }

        $this->app->instance(CatalogSearchService::class, $catalog);
    }
}
