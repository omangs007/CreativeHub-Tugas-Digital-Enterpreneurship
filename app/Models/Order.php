<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\WalletTransactionType;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Pesanan. Seluruh kolom ditentukan server (snapshot harga, scope, komisi),
 * sehingga tidak ada atribut yang fillable dari input.
 */
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
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
            'status' => OrderStatus::class,
            'subtotal_amount' => 'integer',
            'commission_rate_bps' => 'integer',
            'commission_amount' => 'integer',
            'freelancer_amount' => 'integer',
            'total_amount' => 'integer',
            'delivery_days' => 'integer',
            'revisions_allowed' => 'integer',
            'revisions_used' => 'integer',
            'paid_at' => 'datetime',
            'delivered_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function freelancer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'freelancer_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function servicePackage(): BelongsTo
    {
        return $this->belongsTo(ServicePackage::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function successfulPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->where('status', PaymentStatus::Success);
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function conversation(): HasOne
    {
        return $this->hasOne(Conversation::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(OrderDelivery::class);
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(OrderRevision::class);
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    /**
     * Status dana pesanan bagi freelancer: 'held' (tertahan di escrow), 'released'
     * (sudah masuk saldo tersedia), atau null (belum ada dana terverifikasi).
     */
    public function escrowState(): ?string
    {
        $types = $this->relationLoaded('walletTransactions')
            ? $this->walletTransactions->pluck('type')
            : $this->walletTransactions()->pluck('type');

        return match (true) {
            $types->contains(WalletTransactionType::EscrowRelease) => 'released',
            $types->contains(WalletTransactionType::EscrowHold) => 'held',
            default => null,
        };
    }

    public function revisionsRemaining(): int
    {
        return max(0, $this->revisions_allowed - $this->revisions_used);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('created_at')->orderBy('id');
    }

    public function isFromProposal(): bool
    {
        return $this->proposal_id !== null;
    }

    public function involves(User $user): bool
    {
        return $this->customer_id === $user->id || $this->freelancer_id === $user->id;
    }

    public static function generateOrderNumber(): string
    {
        do {
            $number = 'ORD-'.now()->format('ymd').'-'.Str::upper(Str::random(6));
        } while (static::where('order_number', $number)->exists());

        return $number;
    }

    /**
     * Ubah status sesuai aturan transisi dan catat riwayatnya.
     * Pemanggil bertanggung jawab membungkus dalam transaksi bila ada perubahan lain.
     *
     * @throws ValidationException
     */
    public function transitionTo(OrderStatus $next, ?User $by = null, ?string $note = null): void
    {
        $current = $this->status;

        if (! $current->canTransitionTo($next)) {
            throw ValidationException::withMessages([
                'order' => __('Status pesanan tidak dapat diubah dari ":from" menjadi ":to".', [
                    'from' => $current->getLabel(),
                    'to' => $next->getLabel(),
                ]),
            ]);
        }

        $this->status = $next;

        match ($next) {
            OrderStatus::Paid => $this->paid_at = now(),
            OrderStatus::Delivered => $this->delivered_at = now(),
            OrderStatus::Completed => $this->completed_at = now(),
            OrderStatus::Cancelled => $this->cancelled_at = now(),
            default => null,
        };

        $this->save();
        $this->recordHistory($current, $next, $by, $note);
    }

    public function recordHistory(?OrderStatus $from, OrderStatus $to, ?User $by = null, ?string $note = null): void
    {
        $history = new OrderStatusHistory;
        $history->order_id = $this->id;
        $history->from_status = $from?->value;
        $history->to_status = $to->value;
        $history->changed_by = $by?->id;
        $history->note = $note;
        $history->save();
    }
}
