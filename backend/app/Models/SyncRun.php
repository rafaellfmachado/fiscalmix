<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncRun extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'company_id',
        'doc_type',
        'started_at',
        'finished_at',
        'status',
        'nsu_start',
        'nsu_end',
        'docs_found',
        'docs_saved',
        'events_saved',
        'error_summary',
        'logs_path',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'error_summary' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function getDuration(): ?int
    {
        if (!$this->finished_at) {
            return null;
        }
        return $this->started_at->diffInSeconds($this->finished_at);
    }
}
