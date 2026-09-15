<?php

namespace App\Http\Requests\Pdf;

use Illuminate\Foundation\Http\FormRequest;

class SplitPdfRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:pdf', 'max:'.config('tools.max_upload_kb')],
            'mode' => ['required', 'in:all,ranges'],
            'ranges' => ['required_if:mode,ranges', 'nullable', 'string', 'max:1000'],
        ];
    }
}
