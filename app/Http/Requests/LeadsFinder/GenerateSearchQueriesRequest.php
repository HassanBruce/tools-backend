<?php

namespace App\Http\Requests\LeadsFinder;

use Illuminate\Foundation\Http\FormRequest;

class GenerateSearchQueriesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'niche' => ['required', 'string', 'max:200'],
            'pitch' => ['nullable', 'string', 'max:500'],
        ];
    }
}
