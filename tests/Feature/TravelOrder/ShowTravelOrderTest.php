<?php

namespace Tests\Feature\TravelOrder;

use App\Models\TravelOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowTravelOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_their_travel_order(): void
    {
        $user = User::factory()->create();
        $order = TravelOrder::factory()->for($user)->create();

        $this->actingAs($user)
            ->getJson("/api/travel-orders/{$order->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $order->id);
    }

    public function test_non_owner_receives_403(): void
    {
        $owner = User::factory()->create();
        $nonOwner = User::factory()->create();
        $order = TravelOrder::factory()->for($owner)->create();

        $this->actingAs($nonOwner)
            ->getJson("/api/travel-orders/{$order->id}")
            ->assertStatus(403);
    }

    public function test_unauthenticated_user_receives_401(): void
    {
        $order = TravelOrder::factory()->create();

        $this->getJson("/api/travel-orders/{$order->id}")
            ->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_order(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/travel-orders/99999')
            ->assertStatus(404);
    }
}
