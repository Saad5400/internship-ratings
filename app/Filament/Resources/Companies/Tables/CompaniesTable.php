<?php

namespace App\Filament\Resources\Companies\Tables;

use App\Enums\CompanyType;
use App\Support\ModerationStatus;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class CompaniesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn ($record) => $record->website ? $record->website : null),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (string $state): string => ModerationStatus::color($state))
                    ->formatStateUsing(fn (string $state): string => ModerationStatus::label($state))
                    ->sortable(),
                TextColumn::make('type')
                    ->label('نوع الجهة')
                    ->formatStateUsing(fn ($state): string => $state instanceof CompanyType
                        ? $state->label()
                        : CompanyType::tryFrom((string) $state)?->label() ?? 'غير محدد')
                    ->badge()
                    ->sortable(),
                TextColumn::make('ratings_count')
                    ->label('التقييمات')
                    ->counts('ratings')
                    ->sortable()
                    ->icon('heroicon-o-star')
                    ->color('primary'),
                TextColumn::make('ratings_avg_overall_rating')
                    ->label('متوسط التقييم')
                    ->avg('ratings', 'overall_rating')
                    ->numeric(1)
                    ->sortable()
                    ->badge()
                    ->color(fn ($state): string => match (true) {
                        $state === null => 'gray',
                        $state >= 4 => 'success',
                        $state >= 3 => 'warning',
                        default => 'danger',
                    })
                    ->formatStateUsing(fn ($state): string => $state ? number_format($state, 1).' / 5' : 'لا يوجد')
                    ->toggleable(),
                TextColumn::make('website')
                    ->label('الموقع')
                    ->url(fn ($record) => $record->website, shouldOpenInNewTab: true)
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(ModerationStatus::options()),
                SelectFilter::make('type')
                    ->label('نوع الجهة')
                    ->options(CompanyType::options()),
                TernaryFilter::make('has_ratings')
                    ->label('لديها تقييمات')
                    ->queries(
                        true: fn (Builder $query) => $query->has('ratings'),
                        false: fn (Builder $query) => $query->doesntHave('ratings'),
                    ),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('موافقة')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->action(fn (Model $record) => $record->update(['status' => 'approved']))
                    ->requiresConfirmation()
                    ->visible(fn (Model $record): bool => $record->status !== 'approved'),
                Action::make('reject')
                    ->label('رفض')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->action(fn (Model $record) => $record->update(['status' => 'rejected']))
                    ->requiresConfirmation()
                    ->visible(fn (Model $record): bool => $record->status !== 'rejected'),
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('approve')
                        ->label('موافقة')
                        ->color('success')
                        ->icon('heroicon-o-check-circle')
                        ->action(fn (Collection $records) => $records->each->update(['status' => 'approved']))
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('reject')
                        ->label('رفض')
                        ->color('danger')
                        ->icon('heroicon-o-x-circle')
                        ->action(fn (Collection $records) => $records->each->update(['status' => 'rejected']))
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make()
                        ->label('حذف'),
                ]),
            ]);
    }
}
