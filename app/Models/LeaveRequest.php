<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveRequest extends Model
{
    use HasFactory;

    /**
     * Statuses a request may move to from each current status.
     */
    public const TRANSITIONS = [
        'pending' => ['approved', 'rejected'],
        'approved' => [],
        'rejected' => [],
        'cancelled' => [],
    ];

    protected $fillable = [
        'user_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'days',
        'reason',
        'status',
        'decided_by',
        'decided_at',
        'decision_note',
        'attachment_path',
        'attachment_name',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'decided_at' => 'datetime',
            'days' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(RequestHistory::class);
    }

    /**
     * Only allow decisions that follow the documented status flow.
     */
    public function canTransitionTo(string $status): bool
    {
        return in_array($status, self::TRANSITIONS[$this->status] ?? [], true);
    }
}
