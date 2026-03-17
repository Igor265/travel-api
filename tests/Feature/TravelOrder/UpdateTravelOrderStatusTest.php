<?php

namespace Tests\Feature\TravelOrder;

use App\Models\TravelOrder;
use App\Models\User;
use App\Notifications\TravelOrderStatusChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UpdateTravelOrderStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_owner_can_approve_order(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $nonOwner = User::factory()->create();
        $order = TravelOrder::factory()->for($owner)->create();

        $this->actingAs($nonOwner)
            ->patchJson("/api/travel-orders/{$order->id}/status", ['status' => 'approved'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'approved');

        $this->assertDatabaseHas('travel_orders', ['id' => $order->id, 'status' => 'approved']);
    }

    public function test_non_owner_can_cancel_order(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $nonOwner = User::factory()->create();
        $order = TravelOrder::factory()->for($owner)->create();

        $this->actingAs($nonOwner)
            ->patchJson("/api/travel-orders/{$order->id}/status", ['status' => 'cancelled'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'cancelled');
    }

    public function test_owner_cannot_update_status(): void
    {
        $user = User::factory()->create();
        $order = TravelOrder::factory()->for($user)->create();

        $this->actingAs($user)
            ->patchJson("/api/travel-orders/{$order->id}/status", ['status' => 'approved'])
            ->assertStatus(403);
    }

    public function test_unauthenticated_user_receives_401(): void
    {
        $order = TravelOrder::factory()->create();

        $this->patchJson("/api/travel-orders/{$order->id}/status", ['status' => 'approved'])
            ->assertStatus(401);
    }

    public function test_cannot_transition_cancelled_to_approved(): void
    {
        $owner = User::factory()->create();
        $nonOwner = User::factory()->create();
        $order = TravelOrder::factory()->for($owner)->cancelled()->create();

        $this->actingAs($nonOwner)
            ->patchJson("/api/travel-orders/{$order->id}/status", ['status' => 'approved'])
            ->assertStatus(422);
    }

    public function test_cannot_transition_cancelled_to_cancelled(): void
    {
        $owner = User::factory()->create();
        $nonOwner = User::factory()->create();
        $order = TravelOrder::factory()->for($owner)->cancelled()->create();

        $this->actingAs($nonOwner)
            ->patchJson("/api/travel-orders/{$order->id}/status", ['status' => 'cancelled'])
            ->assertStatus(422);
    }

    public function test_approved_order_can_be_cancelled(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $nonOwner = User::factory()->create();
        $order = TravelOrder::factory()->for($owner)->approved()->create();

        $this->actingAs($nonOwner)
            ->patchJson("/api/travel-orders/{$order->id}/status", ['status' => 'cancelled'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'cancelled');
    }

    public function test_status_requested_is_rejected_by_validation(): void
    {
        $owner = User::factory()->create();
        $nonOwner = User::factory()->create();
        $order = TravelOrder::factory()->for($owner)->create();

        $this->actingAs($nonOwner)
            ->patchJson("/api/travel-orders/{$order->id}/status", ['status' => 'requested'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_notification_sent_on_approval(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $nonOwner = User::factory()->create();
        $order = TravelOrder::factory()->for($owner)->create();

        $this->actingAs($nonOwner)
            ->patchJson("/api/travel-orders/{$order->id}/status", ['status' => 'approved'])
            ->assertStatus(200);

        Notification::assertSentTo($owner, TravelOrderStatusChanged::class);
    }

    public function test_notification_sent_on_cancellation(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $nonOwner = User::factory()->create();
        $order = TravelOrder::factory()->for($owner)->create();

        $this->actingAs($nonOwner)
            ->patchJson("/api/travel-orders/{$order->id}/status", ['status' => 'cancelled'])
            ->assertStatus(200);

        Notification::assertSentTo($owner, TravelOrderStatusChanged::class);
    }

    public function test_notification_not_sent_on_invalid_transition(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $nonOwner = User::factory()->create();
        $order = TravelOrder::factory()->for($owner)->cancelled()->create();

        $this->actingAs($nonOwner)
            ->patchJson("/api/travel-orders/{$order->id}/status", ['status' => 'approved'])
            ->assertStatus(422);

        Notification::assertNothingSent();
    }

    public function test_cannot_transition_approved_to_approved(): void
    {
        $owner = User::factory()->create();
        $nonOwner = User::factory()->create();
        $order = TravelOrder::factory()->for($owner)->approved()->create();

        $this->actingAs($nonOwner)
            ->patchJson("/api/travel-orders/{$order->id}/status", ['status' => 'approved'])
            ->assertStatus(422)
            ->assertJsonPath('message', "Cannot transition from 'approved' to 'approved'.");
    }
}
