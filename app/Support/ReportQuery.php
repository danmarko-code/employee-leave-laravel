<?php

namespace App\Support;

/**
 * Shared, validated filters for report and application listings.
 */
class ReportQuery
{
    public static function apply($query, array $filters)
    {
        if (! empty($filters['employee_id'])) {
            $query->where('user_id', $filters['employee_id']);
        }

        if (! empty($filters['leave_type_id'])) {
            $query->where('leave_type_id', $filters['leave_type_id']);
        }

        if (! empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['from'])) {
            $query->whereDate('end_date', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('start_date', '<=', $filters['to']);
        }

        return $query;
    }
}
