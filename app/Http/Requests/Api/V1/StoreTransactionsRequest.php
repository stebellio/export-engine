<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionsRequest extends FormRequest
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
            'items.*.amount' => 'required|numeric',
            'items.*.currency' => 'required|string|size:3',
            'items.*.occurred_at' => 'required|date',
            'items.*.payload' => 'nullable|array',
        ];
    }
}
