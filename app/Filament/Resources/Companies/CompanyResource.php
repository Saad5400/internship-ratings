<?php

namespace App\Filament\Resources\Companies;

use App\Filament\Resources\Companies\Pages\CreateCompany;
use App\Filament\Resources\Companies\Pages\EditCompany;
use App\Filament\Resources\Companies\Pages\ListCompanies;
use App\Filament\Resources\Companies\Pages\ViewCompany;
use App\Filament\Resources\Companies\RelationManagers\RatingsRelationManager;
use App\Filament\Resources\Companies\Schemas\CompanyForm;
use App\Filament\Resources\Companies\Tables\CompaniesTable;
use App\Models\Company;
use BackedEnum;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?string $navigationLabel = 'الشركات';

    protected static ?string $modelLabel = 'شركة';

    protected static ?string $pluralModelLabel = 'الشركات';

    protected static ?string $navigationGroup = 'إدارة المحتوى';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationBadge(): ?string
    {
        $count = Company::pending()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'شركات قيد المراجعة';
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'website', 'description'];
    }

    public static function form(Schema $schema): Schema
    {
        return CompanyForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CompaniesTable::configure($table);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('معلومات الشركة')
                    ->icon('heroicon-o-building-office-2')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name')
                            ->label('اسم الشركة')
                            ->weight('bold')
                            ->size('lg'),
                        TextEntry::make('website')
                            ->label('الموقع الإلكتروني')
                            ->url(fn ($record) => $record->website, shouldOpenInNewTab: true)
                            ->icon('heroicon-o-globe-alt')
                            ->default('غير متوفر'),
                        TextEntry::make('description')
                            ->label('الوصف')
                            ->default('لا يوجد وصف')
                            ->columnSpanFull(),
                    ]),
                Section::make('الإحصائيات')
                    ->icon('heroicon-o-chart-bar')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('status')
                            ->label('الحالة')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'approved' => 'success',
                                'rejected' => 'danger',
                                'pending' => 'warning',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'approved' => 'موافق عليه',
                                'rejected' => 'مرفوض',
                                'pending' => 'قيد المراجعة',
                                default => $state,
                            }),
                        TextEntry::make('average_rating')
                            ->label('متوسط التقييم')
                            ->formatStateUsing(fn ($state) => $state ? number_format($state, 1).' / 5' : 'لا يوجد')
                            ->icon('heroicon-o-star'),
                        TextEntry::make('ratings_count')
                            ->label('عدد التقييمات')
                            ->icon('heroicon-o-chat-bubble-left-right'),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RatingsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCompanies::route('/'),
            'create' => CreateCompany::route('/create'),
            'view' => ViewCompany::route('/{record}'),
            'edit' => EditCompany::route('/{record}/edit'),
        ];
    }
}
