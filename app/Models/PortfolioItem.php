<?php

namespace App\Models;

use Database\Factories\PortfolioItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * `user_id` dan `image_path` sengaja tidak fillable: pemilik diambil dari user yang login
 * dan path gambar hanya diisi dari file yang disimpan server.
 */
#[Fillable(['category_id', 'title', 'description', 'project_url'])]
class PortfolioItem extends Model
{
    /** @use HasFactory<PortfolioItemFactory> */
    use HasFactory;

    /** Batas jumlah item portofolio per freelancer. */
    public const MAX_PER_FREELANCER = 12;

    /** Disk penyimpanan gambar portofolio (publik, karena ditampilkan di profil). */
    public const DISK = 'public';

    public function freelancer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function imageUrl(): string
    {
        return Storage::disk(self::DISK)->url($this->image_path);
    }
}
