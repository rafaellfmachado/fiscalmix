<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FiscalEvent extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'fiscal_document_id',
        'event_type',
        'event_date',
        'protocol',
        'justification',
        'xml_content',
        'metadata',
    ];

    protected $casts = [
        'event_date' => 'datetime',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    // Relationships
    public function fiscalDocument(): BelongsTo
    {
        return $this->belongsTo(FiscalDocument::class);
    }

    // Scopes
    public function scopeByType($query, string $type)
    {
        return $query->where('event_type', $type);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('event_date', '>=', now()->subDays($days));
    }

    // Helpers
    public function getEventTypeName(): string
    {
        $types = [
            'cancelamento' => 'Cancelamento',
            'carta_correcao' => 'Carta de Correção',
            'confirmacao' => 'Confirmação da Operação',
            'ciencia' => 'Ciência da Operação',
            'desconhecimento' => 'Desconhecimento da Operação',
            'nao_realizada' => 'Operação Não Realizada',
        ];

        return $types[$this->event_type] ?? $this->event_type;
    }
}
