<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReportFilterRequest;
use App\Models\AuditLog;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Support\ReportQuery;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ReportController extends Controller
{
    use AuthorizesRequests;

    public function index(ReportFilterRequest $request): View
    {
        $filters = $request->validated();
        $query = ReportQuery::apply(
            LeaveRequest::with(['user', 'leaveType', 'decidedBy'])->orderBy('created_at', 'desc')->orderBy('id', 'desc'),
            $filters
        );

        $perPage = min(max((int) $request->query('per_page', 10), 1), 25);
        $requests = $query->paginate($perPage)->withQueryString();

        $counts = [
            'all' => (clone $query)->count(),
            'approved' => (clone $query)->where('status', 'approved')->count(),
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'rejected' => (clone $query)->where('status', 'rejected')->count(),
            'days' => (float) (clone $query)->where('status', 'approved')->sum('days'),
        ];

        $users = User::orderBy('name')->get(['id', 'name']);
        $leaveTypes = LeaveType::orderBy('name')->get(['id', 'name']);

        return view('admin.reports.index', compact('requests', 'filters', 'counts', 'users', 'leaveTypes'));
    }

    /**
     * Export the filtered records as CSV, neutralising spreadsheet formulas.
     */
    public function export(ReportFilterRequest $request): Response
    {
        $filters = $request->validated();
        $records = ReportQuery::apply(
            LeaveRequest::with(['user', 'leaveType', 'decidedBy'])->orderBy('id'),
            $filters
        )->take(10000)->get();

        $handle = fopen('php://temp', 'r+');

        fputcsv($handle, $this->row([
            'ID', 'Employee', 'Email', 'Leave type', 'Start', 'End',
            'Days', 'Status', 'Reason', 'Submitted', 'Decided by', 'Remarks',
        ]));

        foreach ($records as $record) {
            fputcsv($handle, $this->row([
                $record->id,
                $record->user?->name,
                $record->user?->email,
                $record->leaveType?->name,
                $record->start_date->toDateString(),
                $record->end_date->toDateString(),
                $record->days,
                $record->status,
                $record->reason,
                $record->created_at->toDateTimeString(),
                $record->decidedBy?->name,
                $record->decision_note,
            ]));
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'report.exported',
            'description' => 'Exported ' . $records->count() . ' leave records to CSV.',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="leave-report-' . now()->toDateString() . '.csv"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Prefix cells that spreadsheets would treat as a formula.
     */
    private function row(array $cells): array
    {
        return array_map(function ($cell) {
            $value = (string) $cell;

            if (preg_match('/^[=+\-@]/', $value)) {
                return "'" . $value;
            }

            return $value;
        }, $cells);
    }
}
