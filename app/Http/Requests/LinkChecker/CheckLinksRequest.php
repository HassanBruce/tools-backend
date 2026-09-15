<?php

namespace App\Http\Requests\LinkChecker;

use Illuminate\Foundation\Http\FormRequest;

class CheckLinksRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $max = config('tools.link_checker.max_urls');

        return [
            'urls' => ['required_without:sitemap_url', 'array', "max:{$max}"],
            'urls.*' => ['url', 'max:2048'],
            'sitemap_url' => ['required_without:urls', 'nullable', 'url', 'max:2048'],
        ];
    }
}
