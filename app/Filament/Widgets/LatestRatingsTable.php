<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Ratings\RatingResource;
use App\Models\Rating;
use App\Support\ModerationStatus;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Model;

class LatestRatingsTable extends TableWidget
{
    protected static ?string $heading = 'التقييمات الأخيرة — بانتظار المراجعة أولاً';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Rating::query()
                    ->with('company')
                    ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
                    ->latest()
            )
            ->columns([
                TextColumn::make('company.name')
                    ->label('الجهة')
                    ->weight('bold'),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (string $state): string => ModerationStatus::color($state))
                    ->formatStateUsing(fn (string $state): string => ModerationStatus::label($state)),
                TextColumn::make('role_title')
                    ->label('المسمى الوظيفي'),
                TextColumn::make('overall_rating')
                    ->label('التقييم')
                    ->badge()
                    ->color(fn (float $state): string => match (true) {
                        $state >= 4 => 'success',
                        $state >= 3 => 'warning',
                        default => 'danger',
                    })
                    ->formatStateUsing(fn (float $state): string => number_format($state, 1).' / 5'),
                TextColumn::make('recommendation')
                    ->label('التوصية')
                    ->badge()
                    ->formatStateUsing(function ($state): string {
                        $value = $state instanceof \BackedEnum ? $state->value : $state;

                        return match ($value) {
                            'yes' => 'أنصح بها',
                            'maybe' => 'ربما',
                            'no' => 'لا أنصح',
                            default => (string) $value,
                        };
                    })
                    ->color(function ($state): string {
                        $value = $state instanceof \BackedEnum ? $state->value : $state;

                        return match ($value) {
                            'yes' => 'success',
                            'maybe' => 'warning',
                            'no' => 'danger',
                            default => 'gray',
                        };
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
                Action::make('approve')
                    ->label('موافقة')
                    ->button()
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->action(fn (Model $record) => $record->update(['status' => 'approved']))
                    ->successNotificationTitle('تمت الموافقة')
                    ->visible(fn (Model $record): bool => $record->status !== 'approved'),
                Action::make('reject')
                    ->label('رفض')
                    ->button()
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->action(fn (Model $record) => $record->update(['status' => 'rejected']))
                    ->successNotificationTitle('تم الرفض')
                    ->visible(fn (Model $record): bool => $record->status !== 'rejected'),
                ActionGroup::make([
                    ViewAction::make()
                        ->url(fn (Rating $record): string => RatingResource::getUrl('view', ['record' => $record])),
                ]),
            ])
            ->paginated([5]);
    }
}
