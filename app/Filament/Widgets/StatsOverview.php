<?php

namespace App\Filament\Widgets;

use App\Models\Company;
use App\Models\Rating;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('شركات قيد المراجعة', Company::pending()->count())
                ->color('warning')
                ->icon('heroicon-o-clock'),
            Stat::make('شركات موافق عليها', Company::approved()->count())
                ->color('success')
                ->icon('heroicon-o-check-circle'),
            Stat::make('إجمالي التقييمات', Rating::count())
                ->color('primary')
                ->icon('heroicon-o-star'),
        ];
    }
}
