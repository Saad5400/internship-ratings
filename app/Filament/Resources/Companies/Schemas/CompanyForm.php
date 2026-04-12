<?php

namespace App\Filament\Resources\Companies\Schemas;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CompanyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('معلومات الشركة')
                    ->description('البيانات الأساسية للشركة')
                    ->icon('heroicon-o-building-office-2')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('اسم الشركة')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('مثال: شركة أرامكو السعودية'),
                        TextInput::make('website')
                            ->label('الموقع الإلكتروني')
                            ->url()
                            ->suffixIcon('heroicon-o-globe-alt')
                            ->placeholder('https://example.com'),
                        Textarea::make('description')
                            ->label('الوصف')
                            ->rows(3)
                            ->placeholder('وصف مختصر عن الشركة ونشاطها...')
                            ->columnSpanFull(),
                    ]),
                Section::make('حالة المراجعة')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->schema([
                        Select::make('status')
                            ->label('الحالة')
                            ->options([
                                'pending' => 'قيد المراجعة',
                                'approved' => 'موافق عليه',
                                'rejected' => 'مرفوض',
                            ])
                            ->required()
                            ->default('pending')
                            ->native(false),
                    ]),
            ]);
    }
}
