<?php

namespace App\Filament\Widgets;

use App\Models\Company;
use App\Models\Rating;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalCompanies = Company::count();
        $pendingCount = Company::pending()->count();
        $approvedCount = Company::approved()->count();
        $totalRatings = Rating::count();
        $avgRating = Rating::avg('overall_rating');
        $avgStipend = Rating::whereNotNull('stipend_sar')->where('stipend_sar', '>', 0)->avg('stipend_sar');
        $jobOfferRate = $totalRatings > 0
            ? round(Rating::where('job_offer', true)->count() / $totalRatings * 100)
            : 0;

        return [
            Stat::make('جهات قيد المراجعة', $pendingCount)
                ->description($totalCompanies.' جهة إجمالاً')
                ->descriptionIcon('heroicon-o-building-office-2')
                ->color('warning')
                ->icon('heroicon-o-clock'),
            Stat::make('جهات موافق عليها', $approvedCount)
                ->description(round($totalCompanies > 0 ? $approvedCount / $totalCompanies * 100 : 0).'% من الإجمالي')
                ->descriptionIcon('heroicon-o-arrow-trending-up')
                ->color('success')
                ->icon('heroicon-o-check-circle'),
            Stat::make('إجمالي التقييمات', $totalRatings)
                ->description('متوسط التقييم: '.($avgRating ? number_format($avgRating, 1) : '0').' / 5')
                ->descriptionIcon('heroicon-o-star')
                ->color('primary')
                ->icon('heroicon-o-star'),
            Stat::make('متوسط المكافأة', $avgStipend ? number_format($avgStipend, 0).' ر.س' : 'غير متوفر')
                ->description('شهرياً')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('info')
                ->icon('heroicon-o-banknotes'),
            Stat::make('نسبة عروض العمل', $jobOfferRate.'%')
                ->description('من المتدربين حصلوا على عرض')
                ->descriptionIcon('heroicon-o-briefcase')
                ->color($jobOfferRate >= 50 ? 'success' : 'warning')
                ->icon('heroicon-o-briefcase'),
        ];
    }
}
