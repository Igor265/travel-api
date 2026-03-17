<?php

namespace App\Http\Requests\TravelOrder;

use App\Enums\TravelOrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class ListTravelOrdersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', new Enum(TravelOrderStatus::class)],
            'destination' => ['nullable', 'string', 'max:255'],
            'departure_from' => ['nullable', 'date'],
            'departure_to' => ['nullable', 'date', 'after_or_equal:departure_from'],
            'return_from' => ['nullable', 'date'],
            'return_to' => ['nullable', 'date', 'after_or_equal:return_from'],
        ];
    }
}
