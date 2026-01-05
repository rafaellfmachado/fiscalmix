<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certificate extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'company_id',
        'type',
        'encrypted_blob',
        'encrypted_dek',
        'fingerprint',
        'pairing_token',
        'metadata',
        'valid_from',
        'valid_to',
        'last_heartbeat',
        'status',
    ];

    protected $casts = [
        'metadata' => 'array',
        'valid_from' => 'datetime',
        'valid_to' => 'datetime',
        'last_heartbeat' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = [
        'encrypted_blob',
        'encrypted_dek',
        'pairing_token',
    ];

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('valid_to', '>', now());
    }

    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->where('status', 'active')
            ->whereBetween('valid_to', [now(), now()->addDays($days)]);
    }

    // Helpers
    public function isActive(): bool
    {
        return $this->status === 'active' && $this->valid_to > now();
    }

    public function isExpired(): bool
    {
        return $this->valid_to <= now();
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        return $this->valid_to <= now()->addDays($days) && !$this->isExpired();
    }

    public function daysUntilExpiry(): int
    {
        return now()->diffInDays($this->valid_to, false);
    }

    public function isA1(): bool
    {
        return $this->type === 'A1';
    }

    public function isA3(): bool
    {
        return $this->type === 'A3';
    }
}
