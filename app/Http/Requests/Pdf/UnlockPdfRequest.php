<?php

namespace App\Http\Requests\Pdf;

use Illuminate\Foundation\Http\FormRequest;

class UnlockPdfRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:pdf', 'max:'.config('tools.max_upload_kb')],
            'password' => ['required', 'string', 'max:200'],
        ];
    }
}
