<?php

namespace App\Enums;

enum ReviewerDegree: string
{
    case Bachelor = 'bachelor';
    case Master = 'master';
    case Phd = 'phd';
    case Diploma = 'diploma';

    public function label(): string
    {
        return match ($this) {
            self::Bachelor => 'بكالوريوس',
            self::Master => 'ماجستير',
            self::Phd => 'دكتوراه',
            self::Diploma => 'دبلوم',
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
