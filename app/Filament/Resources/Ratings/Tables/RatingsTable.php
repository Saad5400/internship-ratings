<?php

namespace App\Filament\Resources\Ratings\Tables;

use App\Enums\CompanyType;
use App\Enums\Modality;
use App\Enums\Recommendation;
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
use Illuminate\Database\Eloquent\Builder;

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
                TextColumn::make('company.type')
                    ->label('نوع الجهة')
                    ->formatStateUsing(fn ($state): string => $state?->label() ?? CompanyType::tryFrom((string) $state)?->label() ?? 'غير محدد')
                    ->badge()
                    ->toggleable(),
                TextColumn::make('city')
                    ->label('المدينة')
                    ->toggleable()
                    ->icon('heroicon-o-map-pin'),
                TextColumn::make('overall_rating')
                    ->label('التقييم')
                    ->badge()
                    ->color(fn (float $state): string => match (true) {
                        $state >= 4 => 'success',
                        $state >= 3 => 'warning',
                        default => 'danger',
                    })
                    ->formatStateUsing(fn (float $state): string => number_format($state, 1).' / 5')
                    ->sortable(),
                TextColumn::make('stipend_sar')
                    ->label('المكافأة')
                    ->formatStateUsing(fn (?int $state): string => $state ? number_format($state).' ر.س' : 'بدون')
                    ->sortable()
                    ->color(fn (?int $state): string => $state ? 'success' : 'gray'),
                TextColumn::make('modality')
                    ->label('النمط')
                    ->formatStateUsing(fn (string|Modality $state): string => $state instanceof Modality
                        ? $state->label()
                        : Modality::tryFrom((string) $state)?->label() ?? $state)
                    ->badge()
                    ->color(fn (string|Modality $state): string => $state instanceof Modality
                        ? $state->color()
                        : Modality::tryFrom((string) $state)?->color() ?? 'gray'),
                TextColumn::make('recommendation')
                    ->label('توصية')
                    ->formatStateUsing(fn (string|Recommendation $state): string => $state instanceof Recommendation
                        ? $state->label()
                        : Recommendation::tryFrom((string) $state)?->label() ?? $state)
                    ->badge()
                    ->color(fn (string|Recommendation $state): string => $state instanceof Recommendation
                        ? $state->color()
                        : Recommendation::tryFrom((string) $state)?->color() ?? 'gray'),
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
                    ->options(Modality::options()),
                SelectFilter::make('recommendation')
                    ->label('التوصية')
                    ->options(Recommendation::options()),
                SelectFilter::make('company_type')
                    ->label('نوع الجهة')
                    ->options([
                        'government' => 'حكومي',
                        'private' => 'خاص',
                        'nonprofit' => 'غير ربحي',
                        'other' => 'أخرى',
                    ])
                    ->query(fn ($query, array $data) => $query->when(
                        filled($data['value'] ?? null),
                        fn ($q) => $q->whereHas('company', fn ($cq) => $cq->where('type', $data['value']))
                    )),
                SelectFilter::make('overall_rating')
                    ->label('التقييم العام')
                    ->options([
                        '5' => '5 - ممتاز',
                        '4' => '4 - جيد جداً',
                        '3' => '3 - جيد',
                        '2' => '2 - مقبول',
                        '1' => '1 - ضعيف',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (blank($data['value'] ?? null)) {
                            return $query;
                        }

                        return $query->whereRaw('ROUND(overall_rating) = ?', [(int) $data['value']]);
                    }),
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
