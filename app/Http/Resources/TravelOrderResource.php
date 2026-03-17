<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TravelOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'requester_name' => $this->requester_name,
            'destination' => $this->destination,
            'departure_date' => $this->departure_date->toDateString(),
            'return_date' => $this->return_date->toDateString(),
            'status' => $this->status->value,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
