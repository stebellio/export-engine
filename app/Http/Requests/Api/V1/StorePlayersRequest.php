<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StorePlayersRequest extends FormRequest
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
            'items.*.email' => 'nullable|email|max:255',
            'items.*.registered_at' => 'nullable|date',
        ];
    }
}
