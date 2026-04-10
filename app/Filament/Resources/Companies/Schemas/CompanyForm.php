<?php

namespace App\Filament\Resources\Companies\Schemas;

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
                TextInput::make('name')
                    ->label('اسم الشركة')
                    ->required(),
                TextInput::make('website')
                    ->label('الموقع الإلكتروني')
                    ->url(),
                Textarea::make('description')
                    ->label('الوصف')
                    ->columnSpanFull(),
                Select::make('status')
                    ->label('الحالة')
                    ->options([
                        'pending' => 'قيد المراجعة',
                        'approved' => 'موافق عليه',
                        'rejected' => 'مرفوض',
                    ])
                    ->required()
                    ->default('pending'),
            ]);
    }
}
