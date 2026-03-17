<?php

namespace App\Policies;

use App\Models\TravelOrder;
use App\Models\User;

class TravelOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, TravelOrder $travelOrder): bool
    {
        return $user->id === $travelOrder->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function updateStatus(User $user, TravelOrder $travelOrder): bool
    {
        return $user->id !== $travelOrder->user_id;
    }
}
