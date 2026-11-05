<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Untuk data uji/demo. Alur aplikasi membuat pembayaran lewat PaymentProcessor.
 *
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'transaction_code' => 'TRX-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)),
            'order_id' => Order::factory(),
            'customer_id' => fn (array $attributes) => Order::find($attributes['order_id'])->customer_id,
            'method' => PaymentMethod::Qris,
            'provider' => 'dummy',
            'provider_reference' => 'DEMO-QRIS-'.Str::upper(Str::random(8)),
            'currency' => 'IDR',
            'amount' => fn (array $attributes) => Order::find($attributes['order_id'])->total_amount,
            'commission_amount' => fn (array $attributes) => Order::find($attributes['order_id'])->commission_amount,
            'freelancer_amount' => fn (array $attributes) => Order::find($attributes['order_id'])->freelancer_amount,
            'status' => PaymentStatus::Pending,
            'expires_at' => now()->addDay(),
        ];
    }
}
