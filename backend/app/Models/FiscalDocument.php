<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FiscalDocument extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'account_id',
        'company_id',
        'doc_type',
        'direction',
        'access_key',
        'external_id',
        'number',
        'series',
        'issue_date',
        'competence',
        'issuer_cnpj_cpf',
        'issuer_name',
        'recipient_cnpj_cpf',
        'recipient_name',
        'total_value',
        'status',
        'storage_xml_path',
        'storage_pdf_path',
        'hash_sha256',
    ];

    protected $casts = [
        'issue_date' => 'datetime',
        'competence' => 'date',
        'total_value' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(FiscalEvent::class);
    }

    // Scopes
    public function scopeForAccount($query, string $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    public function scopeForCompany($query, string $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('doc_type', $type);
    }

    public function scopeByDirection($query, string $direction)
    {
        return $query->where('direction', $direction);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeInPeriod($query, $from, $to)
    {
        return $query->whereBetween('issue_date', [$from, $to]);
    }

    // Helpers
    public function isAuthorized(): bool
    {
        return $this->status === 'authorized';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function hasPdf(): bool
    {
        return !empty($this->storage_pdf_path);
    }

    public function getFormattedValue(): string
    {
        return 'R$ ' . number_format($this->total_value, 2, ',', '.');
    }
}
