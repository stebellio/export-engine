<?php

namespace App\Http\Requests\Api\V1;

use App\Exports\SheetRegistry;
use Illuminate\Foundation\Http\FormRequest;

class StoreExportRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'format' => 'sometimes|string|in:xlsx',
            'date_from' => 'sometimes|nullable|date',
            'date_to' => 'sometimes|nullable|date|after_or_equal:date_from',
            'sheets' => 'required|array|min:1',
            'sheets.*.name' => 'required|string',
        ];
    }

    /**
     * Validazione semantica dei fogli: per ogni foglio richiesto si recupera la
     * relativa classe dal registry e le si delega la validazione della config.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $sheets = $this->input('sheets');
            if (!is_array($sheets)) {
                return;
            }

            $registry = new SheetRegistry();

            foreach ($sheets as $index => $sheet) {
                $key = "sheets.$index";

                if (!is_array($sheet)) {
                    $validator->errors()->add($key, "Il foglio #$index non è un oggetto valido.");
                    continue;
                }

                $name = $sheet['name'] ?? null;
                if (! is_string($name) || $name === '') {
                    // Già coperto dalla regola strutturale sheets.*.name.
                    continue;
                }

                $instance = $registry->get($name);
                if ($instance === null) {
                    $validator->errors()->add(
                        "$key.name",
                        "Foglio sconosciuto: '$name'. Ammessi: ".implode(', ', $registry->names()).'.'
                    );
                    continue;
                }

                foreach ($instance->validate($sheet) as $message) {
                    $validator->errors()->add($key, "Foglio '$name': $message");
                }
            }
        });
    }
}
