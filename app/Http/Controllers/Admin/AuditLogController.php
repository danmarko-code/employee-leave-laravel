<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AuditLogFilterRequest;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    use AuthorizesRequests;

    /**
     * Read-only view of recorded actions.
     */
    public function index(AuditLogFilterRequest $request): View
    {
        $this->authorize('viewAny', AuditLog::class);

        $filters = $request->validated();
        $query = AuditLog::with('user');

        if (! empty($filters['action'])) {
            $query->where('action', 'like', '%' . $filters['action'] . '%');
        }

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (! empty($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        $perPage = min(max((int) $request->query('per_page', 10), 1), 25);
        $logs = $query->orderBy('created_at', 'desc')->orderBy('id', 'desc')->paginate($perPage)->withQueryString();
        $users = User::orderBy('name')->get(['id', 'name']);

        return view('admin.activity.index', compact('logs', 'filters', 'users'));
    }
}
