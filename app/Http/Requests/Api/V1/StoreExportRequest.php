<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreExportRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        // Skeleton stage: we validate only the envelope. The detailed shape of
        // "sheets" (columns, filters, group_by, sort) is not interpreted yet
        // and is stored verbatim in the export config for later processing.
        return [
            'format' => 'sometimes|string|in:xlsx',
            'date_from' => 'sometimes|nullable|date',
            'date_to' => 'sometimes|nullable|date|after_or_equal:date_from',
            'sheets' => 'sometimes|array',
        ];
    }
}
