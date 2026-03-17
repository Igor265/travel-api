<?php

namespace App\Enums;

enum TravelOrderStatus: string
{
    case Requested = 'requested';
    case Approved = 'approved';
    case Cancelled = 'cancelled';

    public function canTransitionTo(self $new): bool
    {
        return match ($this) {
            self::Requested => in_array($new, [self::Approved, self::Cancelled]),
            self::Approved => $new === self::Cancelled,
            self::Cancelled => false,
        };
    }
}
