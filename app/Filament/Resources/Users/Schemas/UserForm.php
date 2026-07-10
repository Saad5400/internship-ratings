<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Closure;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('بيانات المستخدم')
                    ->description('البيانات الأساسية لحساب المستخدم')
                    ->icon('heroicon-o-user')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('الاسم')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('مثال: سعد'),
                        TextInput::make('email')
                            ->label('البريد الإلكتروني')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->placeholder('name@example.com'),
                        TextInput::make('password')
                            ->label('كلمة المرور')
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->maxLength(255)
                            ->helperText('اتركها فارغة عند التعديل للإبقاء على كلمة المرور الحالية.')
                            ->columnSpanFull(),
                    ]),
                Section::make('الصلاحيات')
                    ->icon('heroicon-o-shield-check')
                    ->schema([
                        Toggle::make('is_admin')
                            ->label('صلاحية الوصول للوحة الإدارة')
                            ->default(true)
                            ->helperText('يمنح هذا المستخدم صلاحية الدخول إلى لوحة الإدارة.')
                            ->rule(static function (?User $record): Closure {
                                return static function (string $attribute, mixed $value, Closure $fail) use ($record): void {
                                    $isDemotingLastAdmin = $record !== null
                                        && $record->is_admin
                                        && ! $value
                                        && User::query()->where('is_admin', true)->count() <= 1;

                                    if ($isDemotingLastAdmin) {
                                        $fail('لا يمكن إزالة آخر مدير.');
                                    }
                                };
                            }),
                    ]),
            ]);
    }
}
