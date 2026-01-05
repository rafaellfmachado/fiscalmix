<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasUuids, Notifiable;

    protected $fillable = [
        'account_id',
        'name',
        'email',
        'password',
        'mfa_enabled',
        'mfa_secret',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'mfa_secret',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'mfa_enabled' => 'boolean',
        'password' => 'hashed',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function companyPermissions(): HasMany
    {
        return $this->hasMany(CompanyUserPermission::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    // Helpers
    public function hasAccessToCompany(string $companyId): bool
    {
        return $this->companyPermissions()
            ->where('company_id', $companyId)
            ->exists();
    }

    public function getCompanyRole(string $companyId): ?string
    {
        $permission = $this->companyPermissions()
            ->where('company_id', $companyId)
            ->first();

        return $permission?->role;
    }
}
