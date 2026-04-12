<?php

namespace App\Filament\Resources\Ratings;

use App\Filament\Resources\Ratings\Pages\CreateRating;
use App\Filament\Resources\Ratings\Pages\EditRating;
use App\Filament\Resources\Ratings\Pages\ListRatings;
use App\Filament\Resources\Ratings\Pages\ViewRating;
use App\Filament\Resources\Ratings\Schemas\RatingForm;
use App\Filament\Resources\Ratings\Tables\RatingsTable;
use App\Models\Rating;
use BackedEnum;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class RatingResource extends Resource
{
    protected static ?string $model = Rating::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    protected static ?string $navigationLabel = 'التقييمات';

    protected static ?string $modelLabel = 'تقييم';

    protected static ?string $pluralModelLabel = 'التقييمات';

    protected static ?string $navigationGroup = 'إدارة المحتوى';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'role_title';

    public static function getNavigationBadge(): ?string
    {
        return (string) Rating::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['role_title', 'company.name', 'reviewer_name', 'city', 'department'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'الشركة' => $record->company?->name,
            'التقييم' => $record->overall_rating.' / 5',
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return RatingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RatingsTable::configure($table);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('الشركة والوظيفة')
                    ->icon('heroicon-o-briefcase')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('company.name')
                            ->label('الشركة')
                            ->weight('bold')
                            ->size('lg')
                            ->icon('heroicon-o-building-office-2'),
                        TextEntry::make('role_title')
                            ->label('المسمى الوظيفي')
                            ->icon('heroicon-o-briefcase'),
                        TextEntry::make('department')
                            ->label('القسم')
                            ->default('غير محدد'),
                        TextEntry::make('city')
                            ->label('المدينة')
                            ->icon('heroicon-o-map-pin')
                            ->default('غير محدد'),
                    ]),
                Section::make('تفاصيل التدريب')
                    ->icon('heroicon-o-calendar-days')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('duration_months')
                            ->label('المدة')
                            ->formatStateUsing(fn (int $state): string => $state.' شهر')
                            ->icon('heroicon-o-clock'),
                        TextEntry::make('sector')
                            ->label('نوع الجهة')
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'government' => 'حكومي',
                                'private' => 'خاص',
                                'nonprofit' => 'غير ربحي',
                                'other' => 'أخرى',
                                default => 'غير محدد',
                            })
                            ->badge(),
                        TextEntry::make('modality')
                            ->label('نمط التدريب')
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
                        TextEntry::make('stipend_sar')
                            ->label('المكافأة الشهرية')
                            ->formatStateUsing(fn (?int $state): string => $state ? number_format($state).' ر.س' : 'بدون مكافأة')
                            ->icon('heroicon-o-banknotes'),
                        IconEntry::make('had_supervisor')
                            ->label('مرشد مباشر')
                            ->boolean(),
                        IconEntry::make('mixed_env')
                            ->label('بيئة مختلطة')
                            ->boolean(),
                        IconEntry::make('job_offer')
                            ->label('عرض عمل بعد التدريب')
                            ->boolean(),
                    ]),
                Section::make('التقييمات')
                    ->icon('heroicon-o-star')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('rating_mentorship')
                            ->label('الإرشاد والدعم')
                            ->formatStateUsing(fn (int $state): string => $state.' / 5')
                            ->badge()
                            ->color(fn (int $state): string => match (true) {
                                $state >= 4 => 'success',
                                $state >= 3 => 'warning',
                                default => 'danger',
                            }),
                        TextEntry::make('rating_learning')
                            ->label('القيمة التعليمية')
                            ->formatStateUsing(fn (int $state): string => $state.' / 5')
                            ->badge()
                            ->color(fn (int $state): string => match (true) {
                                $state >= 4 => 'success',
                                $state >= 3 => 'warning',
                                default => 'danger',
                            }),
                        TextEntry::make('rating_culture')
                            ->label('بيئة العمل')
                            ->formatStateUsing(fn (int $state): string => $state.' / 5')
                            ->badge()
                            ->color(fn (int $state): string => match (true) {
                                $state >= 4 => 'success',
                                $state >= 3 => 'warning',
                                default => 'danger',
                            }),
                        TextEntry::make('rating_compensation')
                            ->label('المكافأة والمزايا')
                            ->formatStateUsing(fn (int $state): string => $state.' / 5')
                            ->badge()
                            ->color(fn (int $state): string => match (true) {
                                $state >= 4 => 'success',
                                $state >= 3 => 'warning',
                                default => 'danger',
                            }),
                        TextEntry::make('overall_rating')
                            ->label('التقييم العام')
                            ->formatStateUsing(fn (int $state): string => $state.' / 5')
                            ->badge()
                            ->size('lg')
                            ->color(fn (int $state): string => match (true) {
                                $state >= 4 => 'success',
                                $state >= 3 => 'warning',
                                default => 'danger',
                            }),
                        TextEntry::make('recommendation')
                            ->label('التوصية')
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
                    ]),
                Section::make('المراجعة')
                    ->icon('heroicon-o-chat-bubble-bottom-center-text')
                    ->schema([
                        TextEntry::make('pros')
                            ->label('المزايا')
                            ->icon('heroicon-o-hand-thumb-up')
                            ->color('success')
                            ->default('لم يتم ذكر مزايا'),
                        TextEntry::make('cons')
                            ->label('العيوب')
                            ->icon('heroicon-o-hand-thumb-down')
                            ->color('danger')
                            ->default('لم يتم ذكر عيوب'),
                        TextEntry::make('review_text')
                            ->label('المراجعة التفصيلية')
                            ->columnSpanFull()
                            ->prose(),
                    ]),
                Section::make('المقيّم')
                    ->icon('heroicon-o-user')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('reviewer_name')
                            ->label('الاسم')
                            ->default('مجهول')
                            ->icon('heroicon-o-user'),
                        TextEntry::make('reviewer_major')
                            ->label('التخصص')
                            ->default('غير محدد')
                            ->icon('heroicon-o-academic-cap'),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRatings::route('/'),
            'create' => CreateRating::route('/create'),
            'view' => ViewRating::route('/{record}'),
            'edit' => EditRating::route('/{record}/edit'),
        ];
    }
}
