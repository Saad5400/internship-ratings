<?php

namespace App\Enums;

enum SaudiCity: string
{
    // تصنيف (أ)
    case Mecca = 'مكة المكرمة';
    case Jeddah = 'جدة';
    case Riyadh = 'الرياض';
    case Medina = 'المدينة المنورة';
    case Dammam = 'الدمام';
    case Khobar = 'الخبر';
    case Dhahran = 'الظهران';
    case Buraydah = 'بريدة';
    case Abha = 'أبها';
    case Jizan = 'جازان';
    case Hail = 'حائل';
    case Tabuk = 'تبوك';
    case Najran = 'نجران';
    case Sakaka = 'سكاكا';
    case Bahah = 'الباحة';
    case Arar = 'عرعر';
    case Taif = 'الطائف';
    case HafrAlBatin = 'حفر الباطن';
    case AlAhsa = 'الأحساء';
    case Diriyah = 'الدرعية';
    case AlUla = 'العلا';

    // تصنيف (ب)
    case AlKharj = 'الخرج';
    case Yanbu = 'ينبع';
    case AlQatif = 'القطيف';
    case Unayzah = 'عنيزة';
    case KhamisMushait = 'خميس مشيط';
    case AlMajmaah = 'المجمعة';
    case Zulfi = 'الزلفي';
    case WadiAlDawasir = 'وادي الدواسر';
    case AlDuwadimi = 'الدوادمي';
    case Shaqra = 'شقراء';
    case Afif = 'عفيف';
    case AlQuwaiiyah = 'القويعية';
    case Rabigh = 'رابغ';
    case Jubail = 'الجبيل';
    case AlKhafji = 'الخفجي';
    case Buqayq = 'بقيق';
    case AlRass = 'الرس';
    case AlBukairiyah = 'البكيرية';
    case AlMuzahmiyah = 'المذنب';
    case Bisha = 'بيشة';
    case DhahranAlJanoub = 'ظهران الجنوب';
    case Namas = 'النماص';
    case MuhayilAsir = 'محايل عسير';
    case Baljurashi = 'بلجرشي';
    case Tayma = 'تيماء';
    case Sabya = 'صبياء';
    case Fifa = 'فيفا';
    case AlQurayyat = 'القريات';

    /** @return list<array{id: string, name: string}> For x-mary-choices combobox */
    public static function toChoicesOptions(): array
    {
        return collect(self::cases())
            ->map(fn (self $city) => ['id' => $city->value, 'name' => $city->value])
            ->all();
    }

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
