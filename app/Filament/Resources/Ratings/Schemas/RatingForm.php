<?php

namespace App\Filament\Resources\Ratings\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class RatingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')
                    ->relationship('company', 'name')
                    ->label('الشركة')
                    ->required(),
                TextInput::make('role_title')->label('المسمى الوظيفي')->required(),
                TextInput::make('department')->label('القسم'),
                TextInput::make('city')->label('المدينة'),
                TextInput::make('duration_months')->label('المدة (أشهر)')->required()->numeric()->minValue(1)->maxValue(24),
                Select::make('sector')->label('نوع الجهة')->options([
                    'government' => 'حكومي',
                    'private' => 'خاص',
                    'nonprofit' => 'غير ربحي',
                    'other' => 'أخرى',
                ]),
                Select::make('modality')->label('نمط التدريب')->required()->options([
                    'onsite' => 'حضوري',
                    'hybrid' => 'هجين',
                    'remote' => 'عن بُعد',
                ]),
                TextInput::make('stipend_sar')->label('المكافأة (ر.س / شهر)')->numeric()->minValue(0),
                Toggle::make('had_supervisor')->label('يوجد مرشد مباشر'),
                Toggle::make('mixed_env')->label('بيئة مختلطة'),
                Toggle::make('job_offer')->label('عرض عمل بعد التدريب'),
                TextInput::make('rating_mentorship')->label('الإرشاد والدعم')->required()->numeric()->minValue(1)->maxValue(5),
                TextInput::make('rating_learning')->label('القيمة التعليمية')->required()->numeric()->minValue(1)->maxValue(5),
                TextInput::make('rating_culture')->label('بيئة العمل')->required()->numeric()->minValue(1)->maxValue(5),
                TextInput::make('rating_compensation')->label('المكافأة والمزايا')->required()->numeric()->minValue(1)->maxValue(5),
                TextInput::make('overall_rating')->label('التقييم العام')->required()->numeric()->minValue(1)->maxValue(5),
                Select::make('recommendation')->label('التوصية')->required()->options([
                    'yes' => 'أنصح بها',
                    'maybe' => 'ربما',
                    'no' => 'لا أنصح',
                ]),
                TextInput::make('pros')->label('المزايا')->columnSpanFull(),
                TextInput::make('cons')->label('العيوب')->columnSpanFull(),
                Textarea::make('review_text')->label('المراجعة')->required()->columnSpanFull(),
                TextInput::make('reviewer_name')->label('اسم المقيّم'),
                TextInput::make('reviewer_major')->label('تخصص المقيّم'),
            ]);
    }
}
