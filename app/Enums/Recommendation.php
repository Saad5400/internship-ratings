<?php

namespace App\Enums;

enum Recommendation: string
{
    case Yes = 'yes';
    case Maybe = 'maybe';
    case No = 'no';

    public function label(): string
    {
        return match ($this) {
            self::Yes => 'أنصح به',
            self::Maybe => 'ربما',
            self::No => 'لا أنصح',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Yes => 'success',
            self::Maybe => 'warning',
            self::No => 'danger',
        };
    }

    /** Active pill CSS classes for the form UI */
    public function activeClasses(): string
    {
        return match ($this) {
            self::Yes => 'border-green-500 bg-green-50 text-green-700 ring-2 ring-green-500/20',
            self::Maybe => 'border-amber-500 bg-amber-50 text-amber-700 ring-2 ring-amber-500/20',
            self::No => 'border-red-500 bg-red-50 text-red-700 ring-2 ring-red-500/20',
        };
    }

    /** @return array<string, string> value => label pairs */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
