<?php

namespace App\Models;

use App\Support\ActivityPresentation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'description',
        'ip_address',
        'user_agent',
        'properties',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function label(): string
    {
        return ActivityPresentation::label((string) $this->action);
    }

    /**
     * Dashboard text only. Uses the eager-loaded actor so no query runs per row.
     */
    public function summary(): string
    {
        return ActivityPresentation::description(
            $this->relationLoaded('user') ? $this->user : null,
            $this->description,
            $this->label()
        );
    }
}
