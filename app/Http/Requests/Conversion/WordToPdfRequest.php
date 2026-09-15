<?php

namespace App\Http\Requests\Conversion;

use Illuminate\Foundation\Http\FormRequest;

class WordToPdfRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:doc,docx,odt,rtf,txt', 'max:'.config('tools.max_upload_kb')],
        ];
    }
}
