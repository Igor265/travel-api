<?php

namespace Database\Factories;

use App\Enums\TravelOrderStatus;
use App\Models\TravelOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TravelOrder>
 */
class TravelOrderFactory extends Factory
{
    public function definition(): array
    {
        $departure = $this->faker->dateTimeBetween('+1 day', '+30 days');
        $return = $this->faker->dateTimeBetween($departure, '+60 days');

        return [
            'user_id' => User::factory(),
            'requester_name' => $this->faker->name(),
            'destination' => $this->faker->city(),
            'departure_date' => $departure->format('Y-m-d'),
            'return_date' => $return->format('Y-m-d'),
            'status' => TravelOrderStatus::Requested,
        ];
    }

    public function approved(): static
    {
        return $this->state(['status' => TravelOrderStatus::Approved]);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => TravelOrderStatus::Cancelled]);
    }
}
