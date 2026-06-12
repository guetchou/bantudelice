<?php

namespace Tests\Feature;

use App\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OrderRatingApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_rating_endpoints_require_user_context(): void
    {
        $this->getJson('/api/orders/1/rating/check')
            ->assertStatus(401)
            ->assertJson([
                'status' => false,
                'message' => 'Utilisateur non authentifié.',
            ]);

        $this->getJson('/api/orders/1/rating')
            ->assertStatus(401)
            ->assertJson([
                'status' => false,
                'message' => 'Utilisateur non authentifié.',
            ]);
    }

    public function test_order_rating_endpoints_reject_unknown_user_id(): void
    {
        $this->getJson('/api/orders/1/rating/check?user_id=999999')
            ->assertStatus(404)
            ->assertJson([
                'status' => false,
                'message' => 'Utilisateur introuvable.',
            ]);

        $this->getJson('/api/orders/1/rating?user_id=999999')
            ->assertStatus(404)
            ->assertJson([
                'status' => false,
                'message' => 'Utilisateur introuvable.',
            ]);

        $this->postJson('/api/orders/1/rating', [
            'user_id' => 999999,
            'rating' => 5,
        ])
            ->assertStatus(404)
            ->assertJson([
                'status' => false,
                'message' => 'Utilisateur introuvable.',
            ]);
    }

    public function test_order_rating_store_validates_at_least_one_rating(): void
    {
        $user = User::factory()->create(['type' => 'user']);

        $this->postJson('/api/orders/1/rating', [
            'user_id' => $user->id,
        ])
            ->assertStatus(422)
            ->assertJson([
                'status' => false,
                'message' => 'Veuillez fournir au moins une note.',
            ]);
    }
}
