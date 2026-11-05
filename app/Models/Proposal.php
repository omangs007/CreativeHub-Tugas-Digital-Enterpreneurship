<?php

namespace App\Models;

use App\Enums\ProposalStatus;
use Database\Factories\ProposalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Penawaran freelancer untuk proyek customer.
 * `project_id`, `freelancer_id`, dan `status` ditentukan server, tidak fillable.
 */
#[Fillable(['price', 'delivery_days', 'revisions', 'message'])]
class Proposal extends Model
{
    /** @use HasFactory<ProposalFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => ProposalStatus::Pending->value,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ProposalStatus::class,
            'price' => 'integer',
            'delivery_days' => 'integer',
            'revisions' => 'integer',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function freelancer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'freelancer_id');
    }

    public function order(): HasOne
    {
        return $this->hasOne(Order::class);
    }

    public function isPending(): bool
    {
        return $this->status === ProposalStatus::Pending;
    }
}
