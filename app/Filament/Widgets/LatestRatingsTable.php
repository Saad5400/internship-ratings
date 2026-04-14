<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Ratings\RatingResource;
use App\Models\Rating;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestRatingsTable extends TableWidget
{
    protected static ?string $heading = 'أحدث التقييمات';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Rating::query()
                    ->with('company')
                    ->latest()
            )
            ->columns([
                TextColumn::make('company.name')
                    ->label('الجهة')
                    ->weight('bold'),
                TextColumn::make('role_title')
                    ->label('المسمى الوظيفي'),
                TextColumn::make('overall_rating')
                    ->label('التقييم')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state >= 4 => 'success',
                        $state >= 3 => 'warning',
                        default => 'danger',
                    })
                    ->formatStateUsing(fn (int $state): string => $state.' / 5'),
                TextColumn::make('recommendation')
                    ->label('التوصية')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'yes' => 'أنصح بها',
                        'maybe' => 'ربما',
                        'no' => 'لا أنصح',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'yes' => 'success',
                        'maybe' => 'warning',
                        'no' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('reviewer_name')
                    ->label('المقيّم')
                    ->default('مجهول')
                    ->icon('heroicon-o-user'),
                TextColumn::make('created_at')
                    ->label('التاريخ')
                    ->since(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn (Rating $record): string => RatingResource::getUrl('view', ['record' => $record])),
            ])
            ->paginated([5]);
    }
}
