<?php

namespace App\Models;

use App\Enums\TravelOrderStatus;
use Database\Factories\TravelOrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TravelOrder extends Model
{
    /** @use HasFactory<TravelOrderFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'requester_name',
        'destination',
        'departure_date',
        'return_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => TravelOrderStatus::class,
            'departure_date' => 'date',
            'return_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
