<?php

namespace App\Http\Requests\TravelOrder;

use App\Enums\TravelOrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTravelOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in([TravelOrderStatus::Approved->value, TravelOrderStatus::Cancelled->value])],
        ];
    }
}
