<?php

namespace App\Http\Requests;

use App\Enums\FreelancerAvailability;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class FreelancerProfileUpdateRequest extends FormRequest
{
    public const MAX_SKILLS = 10;

    /**
     * Skills dikirim sebagai teks dipisah koma, lalu diubah menjadi daftar unik.
     */
    protected function prepareForValidation(): void
    {
        if (! is_string($this->input('skills'))) {
            return;
        }

        $skills = collect(explode(',', $this->input('skills')))
            ->map(fn (string $skill) => Str::squish($skill))
            ->filter()
            ->unique(fn (string $skill) => Str::lower($skill))
            ->values()
            ->all();

        $this->merge(['skills' => $skills]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'headline' => ['nullable', 'string', 'max:120'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'skills' => ['nullable', 'array', 'max:'.self::MAX_SKILLS],
            'skills.*' => ['string', 'max:40'],
            'availability' => ['required', Rule::enum(FreelancerAvailability::class)],
            'portfolio_url' => ['nullable', 'url:http,https', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'headline' => 'headline',
            'bio' => 'bio',
            'skills' => 'keahlian',
            'skills.*' => 'keahlian',
            'availability' => 'status ketersediaan',
            'portfolio_url' => 'tautan portofolio',
        ];
    }
}
