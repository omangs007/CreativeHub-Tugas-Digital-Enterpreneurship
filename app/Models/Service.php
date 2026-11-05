<?php

namespace App\Models;

use App\Enums\ServiceStatus;
use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * `status` dan `published_at` sengaja tidak fillable: perubahan status
 * dilakukan oleh alur moderasi, bukan dari input form.
 */
#[Fillable(['category_id', 'title', 'slug', 'description', 'thumbnail_path'])]
class Service extends Model
{
    /** @use HasFactory<ServiceFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => ServiceStatus::Draft->value,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ServiceStatus::class,
            'published_at' => 'datetime',
        ];
    }

    public function freelancer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function packages(): HasMany
    {
        return $this->hasMany(ServicePackage::class);
    }

    public function activePackages(): HasMany
    {
        return $this->packages()->where('is_active', true);
    }

    /**
     * Layanan yang boleh dilihat publik: berstatus published, kategorinya aktif,
     * dan memiliki minimal satu paket aktif.
     *
     * @param  Builder<Service>  $query
     */
    public function scopePubliclyVisible(Builder $query): void
    {
        $query->where('status', ServiceStatus::Published)
            ->whereHas('category', fn (Builder $q) => $q->where('is_active', true))
            ->whereHas('activePackages');
    }

    /**
     * Data yang dibutuhkan kartu layanan di katalog.
     *
     * @param  Builder<Service>  $query
     */
    public function scopeForCatalogCard(Builder $query): void
    {
        $query->with(['category', 'freelancer'])
            ->withMin('activePackages as starting_price', 'price')
            ->withMin('activePackages as fastest_delivery_days', 'delivery_days');
    }
}
