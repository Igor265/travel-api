<?php

namespace Tests\Feature\TravelOrder;

use App\Enums\TravelOrderStatus;
use App\Models\TravelOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListTravelOrdersTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_receives_401(): void
    {
        $this->getJson('/api/travel-orders')->assertStatus(401);
    }

    public function test_owner_scoping_hides_other_users_orders(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        TravelOrder::factory()->for($user)->count(2)->create();
        TravelOrder::factory()->for($other)->count(3)->create();

        $response = $this->actingAs($user)->getJson('/api/travel-orders');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_filter_by_status(): void
    {
        $user = User::factory()->create();
        TravelOrder::factory()->for($user)->create(['status' => TravelOrderStatus::Requested]);
        TravelOrder::factory()->for($user)->approved()->create();
        TravelOrder::factory()->for($user)->cancelled()->create();

        $response = $this->actingAs($user)->getJson('/api/travel-orders?status=approved');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('approved', $response->json('data.0.status'));
    }

    public function test_filter_by_destination_partial_match(): void
    {
        $user = User::factory()->create();
        TravelOrder::factory()->for($user)->create(['destination' => 'Paris, France']);
        TravelOrder::factory()->for($user)->create(['destination' => 'London']);

        $response = $this->actingAs($user)->getJson('/api/travel-orders?destination=paris');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Paris, France', $response->json('data.0.destination'));
    }

    public function test_filter_by_departure_date_range(): void
    {
        $user = User::factory()->create();
        TravelOrder::factory()->for($user)->create(['departure_date' => '2026-04-01', 'return_date' => '2026-04-10']);
        TravelOrder::factory()->for($user)->create(['departure_date' => '2026-06-01', 'return_date' => '2026-06-10']);

        $response = $this->actingAs($user)->getJson('/api/travel-orders?departure_from=2026-03-01&departure_to=2026-05-01');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('2026-04-01', $response->json('data.0.departure_date'));
    }

    public function test_filter_by_return_date_range(): void
    {
        $user = User::factory()->create();
        TravelOrder::factory()->for($user)->create(['departure_date' => '2026-04-01', 'return_date' => '2026-04-15']);
        TravelOrder::factory()->for($user)->create(['departure_date' => '2026-06-01', 'return_date' => '2026-06-20']);

        $response = $this->actingAs($user)->getJson('/api/travel-orders?return_from=2026-04-01&return_to=2026-05-01');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('2026-04-15', $response->json('data.0.return_date'));
    }

    public function test_response_is_paginated(): void
    {
        $user = User::factory()->create();
        TravelOrder::factory()->for($user)->count(20)->create();

        $response = $this->actingAs($user)->getJson('/api/travel-orders');

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_per_page_parameter_controls_page_size(): void
    {
        $user = User::factory()->create();
        TravelOrder::factory()->for($user)->count(10)->create();

        $response = $this->actingAs($user)->getJson('/api/travel-orders?per_page=3');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
        $this->assertEquals(10, $response->json('meta.total'));
    }
}
