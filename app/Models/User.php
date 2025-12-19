<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'telegram_id',
        'city_id',
        'phone',
        'subscription_status',
        'trial_ends_at',
        'is_active',
        'profile_completed_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
            'subscription_status' => 'string',
            'trial_ends_at' => 'datetime',
            'is_active' => 'boolean',
            'profile_completed_at' => 'datetime',
        ];
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin';
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isSuperAdmin();
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function masterSettings(): HasOne
    {
        return $this->hasOne(UserMasterSettings::class);
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'master_services', 'master_id', 'service_id')
            ->withPivot(['price', 'is_active'])
            ->withTimestamps();
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class, 'user_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function currentSubscription(): ?Subscription
    {
        return $this->hasOne(Subscription::class)
            ->ofMany(['starts_at' => 'max'], function ($query): void {
                $query->where('status', 'active')
                    ->where(function ($q): void {
                        $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
                    });
            })
            ->getResults();
    }

    public function currentTariff(): ?Tariff
    {
        $subscription = $this->currentSubscription();

        return $subscription?->tariff;
    }

    public function isTrialExpired(): bool
    {
        if ($this->subscription_status !== 'trial') {
            return false;
        }
        if ($this->trial_ends_at === null) {
            return false;
        }

        return now()->greaterThan($this->trial_ends_at);
    }
}
