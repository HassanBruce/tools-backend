<?php

namespace App\Http\Requests\Pdf;

use Illuminate\Foundation\Http\FormRequest;

class LockPdfRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:pdf', 'max:'.config('tools.max_upload_kb')],
            'password' => ['required', 'string', 'min:1', 'max:200'],
            'owner_password' => ['nullable', 'string', 'max:200'],
        ];
    }
}
