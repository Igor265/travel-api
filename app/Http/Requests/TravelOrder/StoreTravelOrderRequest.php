<?php

namespace App\Http\Requests\TravelOrder;

use Illuminate\Foundation\Http\FormRequest;

class StoreTravelOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'requester_name' => ['required', 'string', 'max:255'],
            'destination' => ['required', 'string', 'max:255'],
            'departure_date' => ['required', 'date', 'after_or_equal:today'],
            'return_date' => ['required', 'date', 'after_or_equal:departure_date'],
        ];
    }
}
