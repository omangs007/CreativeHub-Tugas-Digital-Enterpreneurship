<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Transaksi pembayaran SIMULASI. Seluruh kolom diisi server (PaymentProcessor),
 * tidak ada atribut yang fillable dari input.
 */
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    protected $guarded = ['*'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'amount' => 'integer',
            'commission_amount' => 'integer',
            'freelancer_amount' => 'integer',
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(PaymentEvent::class)->orderBy('created_at')->orderBy('id');
    }

    public static function generateTransactionCode(): string
    {
        do {
            $code = 'TRX-'.now()->format('Ymd').'-'.Str::upper(Str::random(8));
        } while (static::where('transaction_code', $code)->exists());

        return $code;
    }

    public function isExpired(): bool
    {
        return $this->status === PaymentStatus::Pending && $this->expires_at?->isPast();
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    public function logEvent(string $event, ?PaymentStatus $from, ?PaymentStatus $to, ?User $user = null, ?array $payload = null): void
    {
        $log = new PaymentEvent;
        $log->payment_id = $this->id;
        $log->event = $event;
        $log->from_status = $from?->value;
        $log->to_status = $to?->value;
        $log->user_id = $user?->id;
        $log->payload = $payload;
        $log->ip_address = request()?->ip();
        $log->save();
    }
}
