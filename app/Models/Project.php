<?php

namespace App\Models;

use App\Enums\ProjectStatus;
use App\Support\Rupiah;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

/**
 * Kebutuhan custom yang diposting customer untuk ditawar freelancer.
 * `customer_id`, `status`, dan `closed_at` ditentukan server, tidak fillable.
 */
#[Fillable(['category_id', 'title', 'description', 'budget_min', 'budget_max', 'deadline'])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => ProjectStatus::Open->value,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ProjectStatus::class,
            'budget_min' => 'integer',
            'budget_max' => 'integer',
            'deadline' => 'date',
            'closed_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Proyek terbuka di kategori aktif, yang boleh dilihat publik dan ditawar freelancer.
     *
     * @param  Builder<Project>  $query
     */
    public function scopePubliclyVisible(Builder $query): void
    {
        $query->where('status', ProjectStatus::Open)
            ->whereHas('category', fn (Builder $q) => $q->where('is_active', true));
    }

    public function isOpen(): bool
    {
        return $this->status === ProjectStatus::Open;
    }

    /**
     * Customer menutup proyek yang masih terbuka tanpa memilih freelancer.
     *
     * @throws ValidationException
     */
    public function close(): void
    {
        if (! $this->isOpen()) {
            throw ValidationException::withMessages(['project' => __('Hanya proyek terbuka yang dapat ditutup.')]);
        }

        $this->status = ProjectStatus::Closed;
        $this->closed_at = now();
        $this->save();
    }

    public function budgetLabel(): string
    {
        $min = $this->budget_min;
        $max = $this->budget_max;

        return match (true) {
            $min !== null && $max !== null && $min === $max => Rupiah::format($min),
            $min !== null && $max !== null => Rupiah::format($min).' – '.Rupiah::format($max),
            $min !== null => __('Mulai :price', ['price' => Rupiah::format($min)]),
            $max !== null => __('Hingga :price', ['price' => Rupiah::format($max)]),
            default => __('Anggaran fleksibel'),
        };
    }
}
