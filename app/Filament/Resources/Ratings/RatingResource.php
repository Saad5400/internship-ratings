<?php

namespace App\Filament\Resources\Ratings;

use App\Enums\CompanyType;
use App\Enums\Modality;
use App\Enums\Recommendation;
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

    protected static string|\UnitEnum|null $navigationGroup = 'إدارة المحتوى';

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
            'الجهة' => $record->company?->name,
            'التقييم' => number_format((float) $record->overall_rating, 1).' / 5',
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

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('الجهة والوظيفة')
                    ->icon('heroicon-o-briefcase')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('company.name')
                            ->label('الجهة')
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
                        TextEntry::make('company.type')
                            ->label('نوع الجهة')
                            ->formatStateUsing(fn ($state): string => $state instanceof CompanyType
                                ? $state->label()
                                : CompanyType::tryFrom((string) $state)?->label() ?? 'غير محدد')
                            ->badge(),
                        TextEntry::make('modality')
                            ->label('نمط التدريب')
                            ->formatStateUsing(fn (string|Modality $state): string => $state instanceof Modality
                                ? $state->label()
                                : Modality::tryFrom($state)?->label() ?? $state)
                            ->badge()
                            ->color(fn (string|Modality $state): string => $state instanceof Modality
                                ? $state->color()
                                : Modality::tryFrom((string) $state)?->color() ?? 'gray'),
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
                        TextEntry::make('rating_learning')
                            ->label('القيمة التعليمية')
                            ->formatStateUsing(fn (int $state): string => $state.' / 5')
                            ->badge()
                            ->color(fn (int $state): string => match (true) {
                                $state >= 4 => 'success',
                                $state >= 3 => 'warning',
                                default => 'danger',
                            }),
                        TextEntry::make('rating_mentorship')
                            ->label('جودة الإرشاد')
                            ->formatStateUsing(fn (int $state): string => $state.' / 5')
                            ->badge()
                            ->color(fn (int $state): string => match (true) {
                                $state >= 4 => 'success',
                                $state >= 3 => 'warning',
                                default => 'danger',
                            }),
                        TextEntry::make('rating_real_work')
                            ->label('العمل الحقيقي والمشاريع')
                            ->formatStateUsing(fn (int $state): string => $state.' / 5')
                            ->badge()
                            ->color(fn (int $state): string => match (true) {
                                $state >= 4 => 'success',
                                $state >= 3 => 'warning',
                                default => 'danger',
                            }),
                        TextEntry::make('rating_team_environment')
                            ->label('بيئة الفريق')
                            ->formatStateUsing(fn (int $state): string => $state.' / 5')
                            ->badge()
                            ->color(fn (int $state): string => match (true) {
                                $state >= 4 => 'success',
                                $state >= 3 => 'warning',
                                default => 'danger',
                            }),
                        TextEntry::make('rating_organization')
                            ->label('التنظيم ووضوح التوقعات')
                            ->formatStateUsing(fn (int $state): string => $state.' / 5')
                            ->badge()
                            ->color(fn (int $state): string => match (true) {
                                $state >= 4 => 'success',
                                $state >= 3 => 'warning',
                                default => 'danger',
                            }),
                        TextEntry::make('overall_rating')
                            ->label('التقييم العام')
                            ->formatStateUsing(fn (float $state): string => number_format($state, 1).' / 5')
                            ->badge()
                            ->size('lg')
                            ->color(fn (float $state): string => match (true) {
                                $state >= 4 => 'success',
                                $state >= 3 => 'warning',
                                default => 'danger',
                            }),
                        TextEntry::make('recommendation')
                            ->label('التوصية')
                            ->formatStateUsing(fn (string|Recommendation $state): string => $state instanceof Recommendation
                                ? $state->label()
                                : Recommendation::tryFrom((string) $state)?->label() ?? $state)
                            ->badge()
                            ->color(fn (string|Recommendation $state): string => $state instanceof Recommendation
                                ? $state->color()
                                : Recommendation::tryFrom((string) $state)?->color() ?? 'gray'),
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
