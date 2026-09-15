<?php

namespace App\Http\Requests\Pdf;

use Illuminate\Foundation\Http\FormRequest;

class MergePdfRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxKb = config('tools.max_upload_kb');
        $maxFiles = config('tools.max_files');

        return [
            'files' => ['required', 'array', 'min:2', "max:{$maxFiles}"],
            'files.*' => ['required', 'file', 'mimes:pdf', "max:{$maxKb}"],
        ];
    }
}
