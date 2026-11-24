<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Str;

/**
 * Turns stored audit action codes and descriptions into short, safe text
 * for the dashboard card. The stored values stay untouched for the full log.
 */
class ActivityPresentation
{
    /** @var array<string, string> */
    private const LABELS = [
        'admin.created' => 'Administrator created',
        'department.created' => 'Department created',
        'department.deactivated' => 'Department deactivated',
        'department.updated' => 'Department updated',
        'leave.approved' => 'Approved',
        'leave.cancelled' => 'Leave cancelled',
        'leave.created' => 'Leave submitted',
        'leave.rejected' => 'Rejected',
        'leave_balance.updated' => 'Leave balance updated',
        'leave_type.created' => 'Leave type created',
        'leave_type.deleted' => 'Leave type deleted',
        'leave_type.toggled' => 'Leave type status changed',
        'leave_type.updated' => 'Leave type updated',
        'password.changed' => 'Password changed',
        'password.reset' => 'Password reset',
        'password.reset_requested' => 'Password reset requested',
        'position.created' => 'Position created',
        'position.deactivated' => 'Position deactivated',
        'position.updated' => 'Position updated',
        'profile.updated' => 'Profile updated',
        'report.exported' => 'Report exported',
        'user.assignment.updated' => 'User assignment updated',
        'user.created' => 'User created',
        'user.deleted' => 'User deleted',
        'user.temporary_password.reset' => 'Temporary password reset',
        'user.updated' => 'User updated',
        'auth.failed' => 'Sign-in failed',
        'auth.lockout' => 'Account locked',
        'auth.login' => 'Signed in',
        'auth.logout' => 'Signed out',
    ];

    public static function label(string $action): string
    {
        return self::LABELS[$action] ?? Str::headline(str_replace(['.', '_'], ' ', $action));
    }

    public static function description(?User $actor, ?string $description, string $fallback = ''): string
    {
        $text = trim((string) $description);

        if ($text === '') {
            return $fallback;
        }

        $text = str_replace(['[', ']'], '', $text);

        foreach (array_filter([$actor?->email, $actor?->name]) as $needle) {
            $text = str_ireplace((string) $needle, '', $text);
        }

        $text = preg_replace('/\s*\bby\s*$/iu', '', $text) ?? $text;
        $text = preg_replace('/\s*([.,;:!?])/u', '$1', $text) ?? $text;
        $text = preg_replace('/\s{2,}/u', ' ', $text) ?? $text;
        $text = trim($text);

        return $text !== '' ? $text : $fallback;
    }
}
