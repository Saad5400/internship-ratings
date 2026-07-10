<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-o-envelope'),
                IconColumn::make('is_admin')
                    ->label('مدير')
                    ->boolean(),
                TextColumn::make('email_verified_at')
                    ->label('تاريخ التحقق')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TernaryFilter::make('is_admin')
                    ->label('المدراء'),
            ])
            ->recordActions([
                EditAction::make()->label('تعديل'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('حذف')
                        ->authorizeIndividualRecords(fn (User $record): bool => static::canDeleteUser($record)),
                ]),
            ]);
    }

    /**
     * Determine whether the given user may be deleted: never yourself, and never the last admin.
     */
    public static function canDeleteUser(User $record): bool
    {
        if ($record->is(auth()->user())) {
            return false;
        }

        $wouldOrphanAdmins = $record->is_admin
            && User::query()->where('is_admin', true)->count() <= 1;

        return ! $wouldOrphanAdmins;
    }
}
