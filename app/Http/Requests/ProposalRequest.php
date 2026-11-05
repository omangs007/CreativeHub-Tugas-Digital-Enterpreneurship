<?php

namespace App\Http\Requests;

use App\Support\Rupiah;
use Illuminate\Foundation\Http\FormRequest;

class ProposalRequest extends FormRequest
{
    public const MIN_PRICE = 10_000;

    public const MAX_PRICE = 1_000_000_000;

    protected function prepareForValidation(): void
    {
        $this->merge(['price' => Rupiah::normalizeInput($this->input('price'))]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'price' => ['required', 'integer', 'min:'.self::MIN_PRICE, 'max:'.self::MAX_PRICE],
            'delivery_days' => ['required', 'integer', 'min:1', 'max:365'],
            'revisions' => ['required', 'integer', 'min:0', 'max:20'],
            'message' => ['required', 'string', 'min:30', 'max:3000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'price' => 'harga penawaran',
            'delivery_days' => 'estimasi pengerjaan',
            'revisions' => 'jumlah revisi',
            'message' => 'pesan penawaran',
        ];
    }
}
