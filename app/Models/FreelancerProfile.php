<?php

namespace App\Models;

use App\Enums\FreelancerAvailability;
use Database\Factories\FreelancerProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['headline', 'bio', 'skills', 'availability', 'portfolio_url'])]
class FreelancerProfile extends Model
{
    /** @use HasFactory<FreelancerProfileFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'availability' => FreelancerAvailability::Available->value,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'skills' => 'array',
            'availability' => FreelancerAvailability::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
