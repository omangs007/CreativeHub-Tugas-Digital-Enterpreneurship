<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\RoleName;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')->withTimestamps();
    }

    public function freelancerProfile(): HasOne
    {
        return $this->hasOne(FreelancerProfile::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'customer_id');
    }

    public function proposals(): HasMany
    {
        return $this->hasMany(Proposal::class, 'freelancer_id');
    }

    public function customerOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    public function freelancerOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'freelancer_id');
    }

    public function receivedReviews(): HasMany
    {
        return $this->hasMany(Review::class, 'freelancer_id');
    }

    public function portfolioItems(): HasMany
    {
        return $this->hasMany(PortfolioItem::class);
    }

    /**
     * Freelancer yang profilnya boleh tampil publik: memiliki role Freelancer dan profil.
     *
     * @param  Builder<User>  $query
     */
    public function scopePublicFreelancers(Builder $query): void
    {
        $query->whereHas('roles', fn (Builder $q) => $q->where('name', RoleName::Freelancer->value))
            ->whereHas('freelancerProfile')
            ->where('is_active', true);
    }

    public function avatarUrl(): ?string
    {
        return $this->avatar_path ? Storage::disk('public')->url($this->avatar_path) : null;
    }

    public function hasRole(RoleName $role): bool
    {
        return $this->roles->contains('name', $role->value);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(RoleName::Admin);
    }

    /**
     * Role hanya diberikan melalui kode server (registrasi, aktivasi freelancer,
     * atau command admin), tidak pernah dari input browser.
     */
    public function assignRole(RoleName $role): void
    {
        $this->roles()->syncWithoutDetaching([Role::findByName($role)->id]);

        $this->unsetRelation('roles');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'admin' && $this->is_active && $this->isAdmin();
    }
}
