<?php

namespace App\Models;

use App\Enums\PackageTier;
use Database\Factories\ServicePackageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tier', 'name', 'description', 'price', 'delivery_days', 'revisions', 'is_active'])]
class ServicePackage extends Model
{
    /** @use HasFactory<ServicePackageFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tier' => PackageTier::class,
            'price' => 'integer',
            'delivery_days' => 'integer',
            'revisions' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
