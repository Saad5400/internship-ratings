<?php

namespace App\Filament\Resources\Companies\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class RatingsRelationManager extends RelationManager
{
    protected static string $relationship = 'ratings';

    protected static ?string $title = 'التقييمات';

    protected static ?string $modelLabel = 'تقييم';

    protected static ?string $pluralModelLabel = 'التقييمات';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('تفاصيل التدريب')
                    ->columns(2)
                    ->schema([
                        TextInput::make('role_title')->label('المسمى الوظيفي')->required(),
                        TextInput::make('department')->label('القسم'),
                        TextInput::make('city')->label('المدينة'),
                        TextInput::make('duration_months')->label('المدة (أشهر)')->required()->numeric()->minValue(1)->maxValue(24),
                        Select::make('sector')->label('نوع الجهة')->options([
                            'government' => 'حكومي',
                            'private' => 'خاص',
                            'nonprofit' => 'غير ربحي',
                            'other' => 'أخرى',
                        ]),
                        Select::make('modality')->label('نمط التدريب')->required()->options([
                            'onsite' => 'حضوري',
                            'hybrid' => 'هجين',
                            'remote' => 'عن بُعد',
                        ]),
                        TextInput::make('stipend_sar')->label('المكافأة (ر.س / شهر)')->numeric()->minValue(0),
                    ]),
                Section::make('التقييمات')
                    ->columns(3)
                    ->schema([
                        TextInput::make('rating_mentorship')->label('الإرشاد')->required()->numeric()->minValue(1)->maxValue(5),
                        TextInput::make('rating_learning')->label('التعلم')->required()->numeric()->minValue(1)->maxValue(5),
                        TextInput::make('rating_culture')->label('بيئة العمل')->required()->numeric()->minValue(1)->maxValue(5),
                        TextInput::make('rating_compensation')->label('المكافأة')->required()->numeric()->minValue(1)->maxValue(5),
                        TextInput::make('overall_rating')->label('التقييم العام')->required()->numeric()->minValue(1)->maxValue(5),
                        Select::make('recommendation')->label('التوصية')->required()->options([
                            'yes' => 'أنصح بها',
                            'maybe' => 'ربما',
                            'no' => 'لا أنصح',
                        ]),
                    ]),
                Section::make('المراجعة')
                    ->schema([
                        Toggle::make('had_supervisor')->label('يوجد مرشد مباشر'),
                        Toggle::make('mixed_env')->label('بيئة مختلطة'),
                        Toggle::make('job_offer')->label('عرض عمل بعد التدريب'),
                        Textarea::make('review_text')->label('المراجعة')->required()->columnSpanFull(),
                        TextInput::make('pros')->label('المزايا')->columnSpanFull(),
                        TextInput::make('cons')->label('العيوب')->columnSpanFull(),
                        TextInput::make('reviewer_name')->label('اسم المقيّم'),
                        TextInput::make('reviewer_major')->label('تخصص المقيّم'),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
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
                    ->formatStateUsing(fn (int $state): string => $state.' / 5'),
                TextColumn::make('recommendation')
                    ->label('توصية')
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
                TextColumn::make('modality')
                    ->label('النمط')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'onsite' => 'حضوري',
                        'hybrid' => 'هجين',
                        'remote' => 'عن بُعد',
                        default => $state,
                    }),
                TextColumn::make('stipend_sar')
                    ->label('المكافأة')
                    ->formatStateUsing(fn (?int $state): string => $state ? number_format($state).' ر.س' : 'بدون')
                    ->sortable(),
                TextColumn::make('reviewer_name')
                    ->label('المقيّم')
                    ->default('مجهول'),
                TextColumn::make('created_at')
                    ->label('التاريخ')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('recommendation')
                    ->label('التوصية')
                    ->options([
                        'yes' => 'أنصح بها',
                        'maybe' => 'ربما',
                        'no' => 'لا أنصح',
                    ]),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
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
