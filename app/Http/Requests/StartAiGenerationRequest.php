<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StartAiGenerationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'prompt' => ['required', 'string', 'min:8', 'max:4000'],
            'mode' => ['required', 'in:create,edit'],
            'form_id' => ['nullable', 'integer', 'exists:forms,id'],
        ];
    }
}
