<?php

namespace App\Filament\Resources\Ratings\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RatingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')
                    ->label('الشركة')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('role_title')
                    ->label('المسمى الوظيفي')
                    ->searchable(),
                TextColumn::make('overall_rating')
                    ->label('التقييم')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state >= 4 => 'success',
                        $state >= 3 => 'warning',
                        default => 'danger',
                    })
                    ->sortable(),
                TextColumn::make('stipend_sar')
                    ->label('المكافأة')
                    ->formatStateUsing(fn (?int $state): string => $state ? number_format($state).' ر.س' : 'بدون')
                    ->sortable(),
                TextColumn::make('modality')
                    ->label('النمط')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'onsite' => 'حضوري',
                        'hybrid' => 'هجين',
                        'remote' => 'عن بُعد',
                        default => $state,
                    })
                    ->badge(),
                TextColumn::make('recommendation')
                    ->label('توصية')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'yes' => 'نعم',
                        'maybe' => 'ربما',
                        'no' => 'لا',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'yes' => 'success',
                        'maybe' => 'warning',
                        'no' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('reviewer_name')
                    ->label('المقيّم')
                    ->default('مجهول')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('التاريخ')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([])
            ->recordActions([
                ViewAction::make(),
                DeleteAction::make()->label('حذف'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('حذف'),
                ]),
            ]);
    }
}
