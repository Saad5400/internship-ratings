<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
{{-- Inline styles only: email clients ignore stylesheets, and Vite assets don't exist here. --}}
<body style="margin: 0; padding: 0; background-color: #f4f4f5; font-family: 'Segoe UI', Tahoma, Arial, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f5; padding: 32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width: 520px; background-color: #ffffff; border-radius: 12px; padding: 32px; text-align: right;">
                    <tr>
                        <td>
                            <h1 style="margin: 0 0 16px; font-size: 20px; color: #18181b;">
                                تقييمات جديدة بانتظار المراجعة
                            </h1>
                            @php
                                // Built in PHP so the joining «و» stays attached to the
                                // following word — Blade @if line breaks would insert a space.
                                $pendingParts = array_filter([
                                    $pendingRatings > 0 ? '<strong>'.e(trans_choice('counts.ratings', $pendingRatings)).'</strong>' : null,
                                    $pendingCompanies > 0 ? '<strong>'.e(trans_choice('counts.companies', $pendingCompanies)).'</strong>' : null,
                                ]);
                            @endphp
                            <p style="margin: 0 0 24px; font-size: 16px; line-height: 1.8; color: #3f3f46;">
                                بانتظارك {!! implode(' و', $pendingParts) !!} في قائمة المراجعة. راجعها الآن لتظهر للزوار بأسرع وقت.
                            </p>
                            <table role="presentation" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="border-radius: 8px; background-color: #3b82f6;">
                                        <a href="{{ $dashboardUrl }}" style="display: inline-block; padding: 12px 28px; font-size: 15px; font-weight: 600; color: #ffffff; text-decoration: none;">
                                            فتح لوحة المراجعة
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin: 24px 0 0; font-size: 13px; line-height: 1.7; color: #a1a1aa;">
                                يصلك هذا الملخص مرة كل يومين على الأكثر، وفقط عندما توجد مشاركات جديدة بانتظار المراجعة.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
