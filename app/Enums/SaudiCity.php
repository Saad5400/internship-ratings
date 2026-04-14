<?php

namespace App\Enums;

enum SaudiCity: string
{
    case Riyadh = 'الرياض';
    case Jeddah = 'جدة';
    case Mecca = 'مكة المكرمة';
    case Medina = 'المدينة المنورة';
    case Dammam = 'الدمام';
    case Khobar = 'الخبر';
    case Dhahran = 'الظهران';
    case Jubail = 'الجبيل';
    case Hofuf = 'الأحساء (الهفوف)';
    case Tabuk = 'تبوك';
    case Abha = 'أبها';
    case KhamisMushait = 'خميس مشيط';
    case Taif = 'الطائف';
    case Buraydah = 'بريدة';
    case Hail = 'حائل';
    case Najran = 'نجران';
    case Yanbu = 'ينبع';
    case HafrAlBatin = 'حفر الباطن';
    case Jizan = 'جازان';
    case Sakaka = 'سكاكا';
    case Arar = 'عرعر';
    case AlQatif = 'القطيف';
    case AlKharj = 'الخرج';
    case AlJouf = 'الجوف';
    case Unayzah = 'عنيزة';
    case Wajh = 'الوجه';
    case Lith = 'الليث';
    case Qunfudhah = 'القنفذة';
    case Bahah = 'الباحة';

    /** @return array<string, string> value => label pairs for select options */
    public static function toOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $city) => [$city->value => $city->value])
            ->all();
    }

    /** @return list<string> All valid city values for validation */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
