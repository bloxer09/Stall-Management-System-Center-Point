<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'profile_picture',
        'google_id',
        'facebook_id',
        'two_factor_enabled',
        'two_factor_confirmed_at',
        'email_verified_at',
        'phone_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'deactivated_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_enabled' => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Deactivated by an admin: cannot sign in by any method. Set with forceFill, never mass-assigned.
     */
    public function isDeactivated(): bool
    {
        return $this->deactivated_at !== null;
    }

    /**
     * Two-factor authentication applies to admin accounts only.
     */
    public function hasTwoFactorEnabled(): bool
    {
        return $this->isAdmin() && (bool) $this->two_factor_enabled;
    }

    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    public function mappings()
    {
        return $this->hasMany(StallMapping::class);
    }

    public function activeMappings()
    {
        return $this->hasMany(StallMapping::class)->where('is_active', true);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments()
    {
        return $this->hasMany(Invoice::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    public function getAvatarUrlAttribute(): string
    {
        if ($this->profile_picture) {
            if (filter_var($this->profile_picture, FILTER_VALIDATE_URL)) {
                return $this->profile_picture;
            }
            $cleanPath = ltrim(str_replace('/storage/', '', $this->profile_picture), '/');
            return asset('storage/' . $cleanPath);
        }

        $name = urlencode($this->name);
        return "https://ui-avatars.com/api/?name={$name}&color=7F56D9&background=F9F5FF";
    }
}
