<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PortfolioItemRequest extends FormRequest
{
    /** Ukuran maksimal gambar dalam kilobyte. */
    public const MAX_IMAGE_KB = 2048;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('is_active', true)],
            'project_url' => ['nullable', 'url:http,https', 'max:255'],
            // SVG sengaja tidak diizinkan karena dapat memuat script.
            'image' => [
                $this->isMethod('post') ? 'required' : 'nullable',
                'file',
                'mimes:jpg,jpeg,png,webp',
                'max:'.self::MAX_IMAGE_KB,
                'dimensions:max_width=5000,max_height=5000',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'judul',
            'description' => 'deskripsi',
            'category_id' => 'kategori',
            'project_url' => 'tautan proyek',
            'image' => 'gambar',
        ];
    }
}
