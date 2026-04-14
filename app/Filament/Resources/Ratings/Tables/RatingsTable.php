<?php

namespace App\Filament\Resources\Ratings\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class RatingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')
                    ->label('الجهة')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('role_title')
                    ->label('المسمى الوظيفي')
                    ->searchable()
                    ->description(fn ($record) => $record->department),
                TextColumn::make('city')
                    ->label('المدينة')
                    ->toggleable()
                    ->icon('heroicon-o-map-pin'),
                TextColumn::make('overall_rating')
                    ->label('التقييم')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state >= 4 => 'success',
                        $state >= 3 => 'warning',
                        default => 'danger',
                    })
                    ->formatStateUsing(fn (int $state): string => $state.' / 5')
                    ->sortable(),
                TextColumn::make('stipend_sar')
                    ->label('المكافأة')
                    ->formatStateUsing(fn (?int $state): string => $state ? number_format($state).' ر.س' : 'بدون')
                    ->sortable()
                    ->color(fn (?int $state): string => $state ? 'success' : 'gray'),
                TextColumn::make('modality')
                    ->label('النمط')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'onsite' => 'حضوري',
                        'hybrid' => 'هجين',
                        'remote' => 'عن بُعد',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'onsite' => 'info',
                        'hybrid' => 'warning',
                        'remote' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('recommendation')
                    ->label('توصية')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'yes' => 'أنصح بها',
                        'maybe' => 'ربما',
                        'no' => 'لا أنصح',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'yes' => 'success',
                        'maybe' => 'warning',
                        'no' => 'danger',
                        default => 'gray',
                    }),
                IconColumn::make('job_offer')
                    ->label('عرض عمل')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('reviewer_name')
                    ->label('المقيّم')
                    ->default('مجهول')
                    ->searchable()
                    ->icon('heroicon-o-user')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('التاريخ')
                    ->date()
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('modality')
                    ->label('نمط التدريب')
                    ->options([
                        'onsite' => 'حضوري',
                        'hybrid' => 'هجين',
                        'remote' => 'عن بُعد',
                    ]),
                SelectFilter::make('recommendation')
                    ->label('التوصية')
                    ->options([
                        'yes' => 'أنصح بها',
                        'maybe' => 'ربما',
                        'no' => 'لا أنصح',
                    ]),
                SelectFilter::make('sector')
                    ->label('نوع الجهة')
                    ->options([
                        'government' => 'حكومي',
                        'private' => 'خاص',
                        'nonprofit' => 'غير ربحي',
                        'other' => 'أخرى',
                    ]),
                SelectFilter::make('overall_rating')
                    ->label('التقييم العام')
                    ->options([
                        '5' => '5 - ممتاز',
                        '4' => '4 - جيد جداً',
                        '3' => '3 - جيد',
                        '2' => '2 - مقبول',
                        '1' => '1 - ضعيف',
                    ]),
                TernaryFilter::make('job_offer')
                    ->label('عرض عمل'),
                TernaryFilter::make('had_supervisor')
                    ->label('مرشد مباشر'),
            ])
            ->filtersFormColumns(3)
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()->label('حذف'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('حذف'),
                ]),
            ]);
    }
}
