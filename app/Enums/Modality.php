<?php

namespace App\Enums;

enum Modality: string
{
    case Onsite = 'onsite';
    case Hybrid = 'hybrid';
    case Remote = 'remote';

    public function label(): string
    {
        return match ($this) {
            self::Onsite => 'حضوري',
            self::Hybrid => 'هجين',
            self::Remote => 'عن بُعد',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Onsite => 'info',
            self::Hybrid => 'warning',
            self::Remote => 'success',
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
