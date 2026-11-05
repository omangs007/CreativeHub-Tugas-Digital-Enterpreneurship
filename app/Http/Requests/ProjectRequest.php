<?php

namespace App\Http\Requests;

use App\Support\Rupiah;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectRequest extends FormRequest
{
    public const MIN_BUDGET = 10_000;

    public const MAX_BUDGET = 1_000_000_000;

    protected function prepareForValidation(): void
    {
        $this->merge([
            'budget_min' => Rupiah::normalizeInput($this->input('budget_min')),
            'budget_max' => Rupiah::normalizeInput($this->input('budget_max')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $budget = ['nullable', 'integer', 'min:'.self::MIN_BUDGET, 'max:'.self::MAX_BUDGET];

        return [
            'title' => ['required', 'string', 'min:10', 'max:150'],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->where('is_active', true)],
            'description' => ['required', 'string', 'min:30', 'max:5000'],
            'budget_min' => $budget,
            'budget_max' => [...$budget, Rule::when($this->filled('budget_min'), 'gte:budget_min')],
            'deadline' => ['nullable', 'date', 'after:today', 'before_or_equal:'.now()->addYear()->toDateString()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'judul',
            'category_id' => 'kategori',
            'description' => 'deskripsi',
            'budget_min' => 'anggaran minimum',
            'budget_max' => 'anggaran maksimum',
            'deadline' => 'tenggat',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'budget_max.gte' => 'Anggaran maksimum tidak boleh lebih kecil dari anggaran minimum.',
            'deadline.after' => 'Tenggat harus setelah hari ini.',
            'deadline.before_or_equal' => 'Tenggat maksimal satu tahun dari sekarang.',
        ];
    }
}
