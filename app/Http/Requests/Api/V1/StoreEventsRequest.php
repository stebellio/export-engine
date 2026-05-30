<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreEventsRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $max = (int) config('ingestion.max_items_per_batch');

        return [
            'items' => "required|array|min:1|max:{$max}",
            'items.*.player_id' => 'required|integer',
            'items.*.type' => 'required|string|max:64',
            'items.*.occurred_at' => 'required|date',
            'items.*.payload' => 'nullable|array',
        ];
    }
}
