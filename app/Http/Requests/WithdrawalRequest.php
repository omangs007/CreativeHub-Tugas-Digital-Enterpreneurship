<?php

namespace App\Http\Requests;

use App\Services\Wallet;
use App\Support\Rupiah;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi pengajuan penarikan (dipakai untuk pratinjau dan konfirmasi).
 * Kecukupan saldo diperiksa ulang di Wallet::requestWithdrawal dengan baris terkunci.
 */
class WithdrawalRequest extends FormRequest
{
    public const MAX_AMOUNT = 1_000_000_000;

    protected function prepareForValidation(): void
    {
        $this->merge([
            'amount' => Rupiah::normalizeInput($this->input('amount')),
            'account_number' => preg_replace('/[\s-]+/', '', (string) $this->input('account_number')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'integer', 'min:'.Wallet::minimumWithdrawal(), 'max:'.self::MAX_AMOUNT],
            'bank_name' => ['required', 'string', 'min:2', 'max:60'],
            'account_number' => ['required', 'string', 'regex:/^[0-9]{5,30}$/'],
            'account_holder' => ['required', 'string', 'min:3', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'amount' => 'nominal penarikan',
            'bank_name' => 'bank / e-wallet',
            'account_number' => 'nomor rekening',
            'account_holder' => 'nama pemilik rekening',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'account_number.regex' => 'Nomor rekening hanya berisi 5–30 digit angka.',
        ];
    }

    /**
     * @return array{bank_name: string, account_number: string, account_holder: string}
     */
    public function bank(): array
    {
        return [
            'bank_name' => trim((string) $this->validated('bank_name')),
            'account_number' => (string) $this->validated('account_number'),
            'account_holder' => trim((string) $this->validated('account_holder')),
        ];
    }
}
