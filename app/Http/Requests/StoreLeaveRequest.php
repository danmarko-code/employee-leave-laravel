<?php

namespace App\Http\Requests;

use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Support\AdminGuard;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreLeaveRequest extends FormRequest
{
    /**
     * Calculated working days cached for controller use.
     */
    public ?int $workingDaysCount = null;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'leave_type_id' => ['required', 'integer', 'exists:leave_types,id'],
            'start_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            if ($v->errors()->isNotEmpty()) {
                return;
            }

            $user = Auth::user();

            if ($user->isAdmin() && ! AdminGuard::hasOtherActiveAdmin($user)) {
                $v->errors()->add('leave_type_id', 'No other administrator is available to review this request. Ask to have a second administrator added.');
                return;
            }

            $start = Carbon::parse($this->input('start_date'));
            $end = Carbon::parse($this->input('end_date'));
            $leaveType = LeaveType::find($this->input('leave_type_id'));

            if (! $leaveType || ! $leaveType->is_active) {
                $v->errors()->add('leave_type_id', 'The selected leave type is not available.');
                return;
            }

            // Document requirement
            if ($leaveType->requires_doc && ! $this->hasFile('attachment')) {
                $v->errors()->add('attachment', "Supporting documentation is required for {$leaveType->name}.");
            }

            // Calculate working days (Mon-Fri)
            $period = CarbonPeriod::create($start, $end);
            $workingDays = 0;
            foreach ($period as $date) {
                if (! $date->isWeekend()) {
                    $workingDays++;
                }
            }

            if ($workingDays === 0) {
                $v->errors()->add('start_date', 'The selected date range contains no working days (weekends only).');
                return;
            }

            $this->workingDaysCount = $workingDays;

            // Check for overlapping/clashing requests
            $overlap = LeaveRequest::where('user_id', $user->id)
                ->whereIn('status', ['pending', 'approved'])
                ->where(function ($q) use ($start, $end) {
                    $q->whereBetween('start_date', [$start->toDateString(), $end->toDateString()])
                        ->orWhereBetween('end_date', [$start->toDateString(), $end->toDateString()])
                        ->orWhere(function ($sub) use ($start, $end) {
                            $sub->where('start_date', '<=', $start->toDateString())
                                ->where('end_date', '>=', $end->toDateString());
                        });
                })->first();

            if ($overlap) {
                $v->errors()->add('start_date', 'These dates overlap with an existing ' . $overlap->leaveType->name . ' request (' . $overlap->start_date->format('M d') . ' – ' . $overlap->end_date->format('M d') . ').');
                return;
            }

            // Check user balance
            $year = (int) $start->format('Y');
            $balance = LeaveBalance::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'leave_type_id' => $leaveType->id,
                    'year' => $year,
                ],
                [
                    'allocated_days' => $leaveType->days_allowed,
                    'used_days' => 0,
                    'pending_days' => 0,
                ]
            );

            $available = (float) $balance->available_days;

            if ($workingDays > $available) {
                $v->errors()->add('start_date', "This application requires {$workingDays} working days, but you only have {$available} days available for {$leaveType->name}.");
            }
        });
    }
}
