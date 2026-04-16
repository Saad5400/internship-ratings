<?php

namespace App\Enums;

enum CompanyType: string
{
    case Government = 'government';
    case Private = 'private';
    case NonProfit = 'nonprofit';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Government => 'حكومي',
            self::Private => 'خاص',
            self::NonProfit => 'غير ربحي',
            self::Other => 'أخرى',
        };
    }

    /** @return array<string, string> value => label pairs */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type) => [$type->value => $type->label()])
            ->all();
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
