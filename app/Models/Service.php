<?php

namespace App\Models;

use App\Enums\ServiceStatus;
use App\Support\AuditLogger;
use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * `user_id`, `slug`, `thumbnail_path`, `status`, dan `published_at` sengaja tidak fillable:
 * nilainya ditentukan server (pemilik, slug unik, file tersimpan, alur status/moderasi),
 * bukan dari input form.
 */
#[Fillable(['category_id', 'title', 'description'])]
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
            'moderated_at' => 'datetime',
        ];
    }

    public function freelancer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
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
     * Filter berdasarkan harga mulai (harga paket aktif termurah).
     *
     * @param  Builder<Service>  $query
     */
    public function scopeStartingPriceBetween(Builder $query, ?int $min, ?int $max): void
    {
        $startingPrice = '(select min(price) from service_packages where service_packages.service_id = services.id and service_packages.is_active = true)';

        $query->when($min !== null, fn (Builder $q) => $q->whereRaw("{$startingPrice} >= ?", [$min]))
            ->when($max !== null, fn (Builder $q) => $q->whereRaw("{$startingPrice} <= ?", [$max]));
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

    /**
     * Slug unik dari judul: `judul`, `judul-2`, `judul-3`, dan seterusnya.
     */
    public static function uniqueSlug(string $title): string
    {
        $base = Str::limit(Str::slug($title), 160, '') ?: 'layanan';
        $slug = $base;

        for ($i = 2; static::where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }

    /**
     * Draf atau layanan ditolak diajukan untuk dimoderasi admin.
     *
     * @throws ValidationException
     */
    public function submitForReview(): void
    {
        if (! in_array($this->status, [ServiceStatus::Draft, ServiceStatus::Rejected], true)) {
            throw ValidationException::withMessages([
                'service' => __('Hanya layanan berstatus draf atau ditolak yang dapat diajukan review.'),
            ]);
        }

        if (! $this->activePackages()->exists()) {
            throw ValidationException::withMessages([
                'service' => __('Tambahkan minimal satu paket aktif sebelum mengajukan review.'),
            ]);
        }

        $this->status = ServiceStatus::PendingReview;
        $this->rejection_reason = null;
        $this->save();
    }

    /**
     * Admin menyetujui layanan yang menunggu review sehingga tampil di katalog.
     *
     * @throws ValidationException
     */
    public function approve(User $admin): void
    {
        $this->ensurePendingReview();

        DB::transaction(function () use ($admin) {
            $old = $this->only(['status', 'rejection_reason']);

            $this->status = ServiceStatus::Published;
            $this->published_at = now();
            $this->rejection_reason = null;
            $this->moderated_by = $admin->id;
            $this->moderated_at = now();
            $this->save();

            AuditLogger::record('service.approved', $this, $this->serializeForAudit($old), ['status' => $this->status->value], $admin);
        });
    }

    /**
     * Admin menolak layanan dengan alasan yang ditampilkan ke freelancer.
     *
     * @throws ValidationException
     */
    public function reject(User $admin, string $reason): void
    {
        $this->ensurePendingReview();

        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => __('Alasan penolakan wajib diisi.')]);
        }

        DB::transaction(function () use ($admin, $reason) {
            $old = $this->only(['status', 'rejection_reason']);

            $this->status = ServiceStatus::Rejected;
            $this->published_at = null;
            $this->rejection_reason = trim($reason);
            $this->moderated_by = $admin->id;
            $this->moderated_at = now();
            $this->save();

            AuditLogger::record('service.rejected', $this, $this->serializeForAudit($old), [
                'status' => $this->status->value,
                'rejection_reason' => $this->rejection_reason,
            ], $admin);
        });
    }

    /**
     * @throws ValidationException
     */
    private function ensurePendingReview(): void
    {
        if ($this->status !== ServiceStatus::PendingReview) {
            throw ValidationException::withMessages([
                'service' => __('Hanya layanan yang menunggu review yang dapat dimoderasi.'),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function serializeForAudit(array $values): array
    {
        return array_map(fn ($value) => $value instanceof \BackedEnum ? $value->value : $value, $values);
    }

    /**
     * Tarik layanan dari katalog atau dari antrean review, kembali menjadi draf.
     *
     * @throws ValidationException
     */
    public function withdraw(): void
    {
        if (! in_array($this->status, [ServiceStatus::Published, ServiceStatus::PendingReview], true)) {
            throw ValidationException::withMessages([
                'service' => __('Layanan ini tidak sedang tayang atau menunggu review.'),
            ]);
        }

        $this->status = ServiceStatus::Draft;
        $this->published_at = null;
        $this->save();
    }

    /**
     * Layanan published yang diedit pemiliknya harus dimoderasi ulang.
     */
    public function markEditedByOwner(): void
    {
        if ($this->status === ServiceStatus::Published) {
            $this->status = ServiceStatus::PendingReview;
            $this->published_at = null;
        }
    }

    public function isDeletable(): bool
    {
        return $this->status !== ServiceStatus::Published;
    }
}
