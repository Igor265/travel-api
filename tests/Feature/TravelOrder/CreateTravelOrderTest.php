<?php

namespace Tests\Feature\TravelOrder;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateTravelOrderTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'requester_name' => 'Test',
            'destination' => 'Belo Horizonte',
            'departure_date' => now()->addDays(5)->toDateString(),
            'return_date' => now()->addDays(10)->toDateString(),
        ], $overrides);
    }

    public function test_authenticated_user_can_create_travel_order(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/travel-orders', $this->validPayload());

        $response->assertStatus(201)
            ->assertJsonStructure(['data' => [
                'id', 'user_id', 'requester_name', 'destination',
                'departure_date', 'return_date', 'status',
            ]])
            ->assertJsonPath('data.status', 'requested')
            ->assertJsonPath('data.user_id', $user->id);

        $this->assertDatabaseHas('travel_orders', ['destination' => 'Belo Horizonte', 'user_id' => $user->id]);
    }

    public function test_unauthenticated_user_cannot_create_travel_order(): void
    {
        $this->postJson('/api/travel-orders', $this->validPayload())
            ->assertStatus(401);
    }

    public function test_user_id_always_comes_from_token_not_body(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $payload = $this->validPayload(['user_id' => $other->id]);

        $response = $this->actingAs($owner)->postJson('/api/travel-orders', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.user_id', $owner->id);
    }

    public function test_requester_name_is_required(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/travel-orders', $this->validPayload(['requester_name' => '']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['requester_name']);
    }

    public function test_destination_is_required(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/travel-orders', $this->validPayload(['destination' => '']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['destination']);
    }

    public function test_departure_date_must_be_today_or_future(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/travel-orders', $this->validPayload([
                'departure_date' => now()->subDay()->toDateString(),
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['departure_date']);
    }

    public function test_return_date_must_be_on_or_after_departure_date(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/travel-orders', $this->validPayload([
                'departure_date' => now()->addDays(10)->toDateString(),
                'return_date' => now()->addDays(5)->toDateString(),
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['return_date']);
    }
}
