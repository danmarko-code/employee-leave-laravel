<?php

namespace App\Support;

use App\Models\LeaveRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttachmentFile
{
    public const ALLOWED_MIMES = [
        'image/jpeg',
        'image/png',
        'application/pdf',
    ];

    /**
     * Clean an uploaded name for display only. It is never used as a path.
     */
    public static function sanitizeOriginalName(string $name): string
    {
        $name = str_replace(['\\', '/'], ' ', $name);
        $name = preg_replace('/[\x00-\x1F\x7F]/u', '', $name) ?? '';
        $name = preg_replace('/\s+/u', ' ', $name) ?? '';
        $name = trim($name, " \t\n\r\0\x0B\"';");

        if ($name === '') {
            return 'attachment';
        }

        return Str::limit($name, 120, '');
    }

    /**
     * Detect the real MIME type from file content, not from the client.
     */
    public static function mimeType(LeaveRequest $leaveRequest): ?string
    {
        if (empty($leaveRequest->attachment_path)
            || ! Storage::disk('local')->exists($leaveRequest->attachment_path)) {
            return null;
        }

        $handle = finfo_open(FILEINFO_MIME_TYPE);

        if ($handle === false) {
            return null;
        }

        $mime = finfo_file($handle, Storage::disk('local')->path($leaveRequest->attachment_path));
        finfo_close($handle);

        $mime = is_string($mime) ? strtolower($mime) : '';

        return in_array($mime, self::ALLOWED_MIMES, true) ? $mime : null;
    }

    /**
     * Name shown in the UI. The stored random path is never shown.
     */
    public static function displayName(LeaveRequest $leaveRequest): string
    {
        return $leaveRequest->attachment_original_name
            ?: $leaveRequest->attachment_name
            ?: 'Supporting Document';
    }
}
