<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Percakapan privat antara customer dan freelancer untuk satu pesanan atau satu penawaran.
 * Peserta ditentukan server dari pesanan/penawaran, tidak dari input.
 */
class Conversation extends Model
{
    protected $guarded = ['*'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function freelancer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'freelancer_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function hasParticipant(User $user): bool
    {
        return $this->customer_id === $user->id || $this->freelancer_id === $user->id;
    }

    public function otherParticipant(User $user): User
    {
        return $this->customer_id === $user->id ? $this->freelancer : $this->customer;
    }

    /**
     * @param  Builder<Conversation>  $query
     */
    public function scopeForParticipant(Builder $query, User $user): void
    {
        $query->where(fn (Builder $q) => $q->where('customer_id', $user->id)->orWhere('freelancer_id', $user->id));
    }

    public function subjectTitle(): string
    {
        return $this->order_id ? $this->order->title : $this->proposal->project->title;
    }

    public static function forOrder(Order $order): self
    {
        return static::findOrStart('order_id', $order->id, $order->customer_id, $order->freelancer_id);
    }

    public static function forProposal(Proposal $proposal): self
    {
        return static::findOrStart('proposal_id', $proposal->id, $proposal->project->customer_id, $proposal->freelancer_id);
    }

    /**
     * Ambil percakapan untuk subjek tertentu atau buat baru. Pembuatan bersamaan
     * ditahan unique index, lalu percakapan yang sudah ada diambil ulang.
     */
    private static function findOrStart(string $column, int $subjectId, int $customerId, int $freelancerId): self
    {
        $existing = static::where($column, $subjectId)->first();

        if ($existing) {
            return $existing;
        }

        $conversation = new static;
        $conversation->{$column} = $subjectId;
        $conversation->customer_id = $customerId;
        $conversation->freelancer_id = $freelancerId;

        try {
            $conversation->save();
        } catch (UniqueConstraintViolationException) {
            return static::where($column, $subjectId)->firstOrFail();
        }

        return $conversation;
    }
}
