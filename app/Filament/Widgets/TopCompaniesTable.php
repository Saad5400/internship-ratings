<?php

namespace App\Filament\Widgets;

use App\Models\Company;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class TopCompaniesTable extends TableWidget
{
    protected static ?string $heading = 'أفضل الجهات تقييماً';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Company::query()
                    ->approved()
                    ->has('ratings')
                    ->withCount('ratings')
                    ->withAvg('ratings', 'overall_rating')
                    ->orderByDesc('ratings_avg_overall_rating')
            )
            ->columns([
                TextColumn::make('name')
                    ->label('الجهة')
                    ->weight('bold')
                    ->icon('heroicon-o-building-office-2'),
                TextColumn::make('ratings_avg_overall_rating')
                    ->label('متوسط التقييم')
                    ->numeric(1)
                    ->badge()
                    ->color(fn (?float $state): string => match (true) {
                        $state >= 4 => 'success',
                        $state >= 3 => 'warning',
                        default => 'danger',
                    })
                    ->formatStateUsing(fn (?float $state): string => number_format($state ?? 0, 1).' / 5'),
                TextColumn::make('ratings_count')
                    ->label('عدد التقييمات')
                    ->icon('heroicon-o-star')
                    ->color('primary'),
            ])
            ->paginated([5]);
    }
}
