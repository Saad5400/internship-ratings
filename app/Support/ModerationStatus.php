<?php

namespace App\Support;

/**
 * Single source of truth for the moderation status (pending/approved/rejected)
 * display vocabulary shared across the admin panel.
 */
final class ModerationStatus
{
    public static function label(string $status): string
    {
        return match ($status) {
            'pending' => 'قيد المراجعة',
            'approved' => 'موافق عليه',
            'rejected' => 'مرفوض',
            default => $status,
        };
    }

    public static function color(string $status): string
    {
        return match ($status) {
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
            default => 'gray',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            'pending' => 'قيد المراجعة',
            'approved' => 'موافق عليه',
            'rejected' => 'مرفوض',
        ];
    }
}
