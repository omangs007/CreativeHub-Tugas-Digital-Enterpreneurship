<?php

namespace App\Http\Requests;

use App\Enums\PackageTier;
use App\Support\Rupiah;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ServiceRequest extends FormRequest
{
    public const MIN_PRICE = 10_000;

    public const MAX_PRICE = 100_000_000;

    /**
     * Izinkan harga ditulis dengan pemisah ribuan, misalnya "1.250.000".
     * Nilai desimal seperti "150000.50" tetap ditolak oleh aturan integer.
     */
    protected function prepareForValidation(): void
    {
        $packages = $this->input('packages');

        if (! is_array($packages)) {
            return;
        }

        foreach ($packages as $tier => $package) {
            if (is_array($package) && array_key_exists('price', $package)) {
                $packages[$tier]['price'] = Rupiah::normalizeInput($package['price']);
            }
        }

        $this->merge(['packages' => $packages]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tiers = array_map(fn (PackageTier $tier) => $tier->value, PackageTier::cases());

        $rules = [
            'title' => ['required', 'string', 'min:10', 'max:150'],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->where('is_active', true)],
            'description' => ['required', 'string', 'min:30', 'max:5000'],
            'thumbnail' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=5000,max_height=5000'],
            'packages' => ['required', 'array:'.implode(',', $tiers)],
        ];

        foreach ($tiers as $tier) {
            $prefix = "packages.{$tier}";
            $onlyIfEnabled = "exclude_unless:{$prefix}.enabled,1";

            $rules["{$prefix}.enabled"] = ['nullable', 'boolean'];
            $rules["{$prefix}.name"] = [$onlyIfEnabled, 'required', 'string', 'max:100'];
            $rules["{$prefix}.description"] = [$onlyIfEnabled, 'required', 'string', 'max:1000'];
            $rules["{$prefix}.price"] = [$onlyIfEnabled, 'required', 'integer', 'min:'.self::MIN_PRICE, 'max:'.self::MAX_PRICE];
            $rules["{$prefix}.delivery_days"] = [$onlyIfEnabled, 'required', 'integer', 'min:1', 'max:365'];
            $rules["{$prefix}.revisions"] = [$onlyIfEnabled, 'required', 'integer', 'min:0', 'max:20'];
        }

        return $rules;
    }

    /**
     * @return array<int, \Closure>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $enabled = collect($this->input('packages', []))
                    ->filter(fn ($package) => is_array($package) && filter_var($package['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN));

                if ($enabled->isEmpty()) {
                    $validator->errors()->add('packages', __('Aktifkan minimal satu paket.'));
                }
            },
        ];
    }

    /**
     * Paket yang diaktifkan, dengan nilai integer yang sudah tervalidasi.
     *
     * @return array<string, array{name: string, description: string, price: int, delivery_days: int, revisions: int}>
     */
    public function enabledPackages(): array
    {
        return collect($this->validated('packages'))
            ->filter(fn (array $package) => (bool) ($package['enabled'] ?? false))
            ->map(fn (array $package) => [
                'name' => $package['name'],
                'description' => $package['description'],
                'price' => (int) $package['price'],
                'delivery_days' => (int) $package['delivery_days'],
                'revisions' => (int) $package['revisions'],
            ])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $attributes = [
            'title' => 'judul',
            'category_id' => 'kategori',
            'description' => 'deskripsi',
            'thumbnail' => 'thumbnail',
            'packages' => 'paket',
        ];

        foreach (PackageTier::cases() as $tier) {
            $label = 'paket '.$tier->label();
            $attributes["packages.{$tier->value}.name"] = "nama {$label}";
            $attributes["packages.{$tier->value}.description"] = "deskripsi {$label}";
            $attributes["packages.{$tier->value}.price"] = "harga {$label}";
            $attributes["packages.{$tier->value}.delivery_days"] = "waktu pengerjaan {$label}";
            $attributes["packages.{$tier->value}.revisions"] = "jumlah revisi {$label}";
        }

        return $attributes;
    }
}
