<?php

namespace App\Filament\Resources\Ratings\Schemas;

use App\Enums\SaudiCity;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class RatingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('الجهة والوظيفة')
                    ->description('معلومات عن مكان وطبيعة التدريب')
                    ->icon('heroicon-o-briefcase')
                    ->columns(2)
                    ->schema([
                        Select::make('company_id')
                            ->relationship('company', 'name')
                            ->label('الجهة')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->native(false),
                        TextInput::make('role_title')
                            ->label('المسمى الوظيفي')
                            ->placeholder('مثال: مطور برمجيات'),
                        TextInput::make('department')
                            ->label('القسم')
                            ->placeholder('مثال: تقنية المعلومات'),
                        Select::make('city')
                            ->label('المدينة')
                            ->options(SaudiCity::toOptions())
                            ->searchable()
                            ->native(false),
                    ]),
                Section::make('تفاصيل التدريب')
                    ->description('معلومات عن مدة ونمط التدريب')
                    ->icon('heroicon-o-calendar-days')
                    ->columns(2)
                    ->schema([
                        Select::make('duration_months')
                            ->label('المدة (بالاشهر)')
                            ->options(array_combine(range(1, 12), range(1, 12)))
                            ->placeholder('اختياري')
                            ->native(false),
                        Select::make('sector')
                            ->label('نوع الجهة')
                            ->options([
                                'government' => 'حكومي',
                                'private' => 'خاص',
                                'nonprofit' => 'غير ربحي',
                                'other' => 'أخرى',
                            ])
                            ->native(false),
                        Select::make('modality')
                            ->label('نمط التدريب')
                            ->required()
                            ->options([
                                'onsite' => 'حضوري',
                                'hybrid' => 'هجين',
                                'remote' => 'عن بُعد',
                            ])
                            ->native(false),
                        TextInput::make('stipend_sar')
                            ->label('المكافأة الشهرية')
                            ->numeric()
                            ->minValue(0)
                            ->suffix('ر.س / شهر'),
                        Grid::make(3)
                            ->schema([
                                Toggle::make('had_supervisor')
                                    ->label('يوجد مرشد مباشر')
                                    ->onIcon('heroicon-o-check')
                                    ->offIcon('heroicon-o-x-mark'),
                                Toggle::make('mixed_env')
                                    ->label('بيئة مختلطة')
                                    ->onIcon('heroicon-o-check')
                                    ->offIcon('heroicon-o-x-mark'),
                                Toggle::make('job_offer')
                                    ->label('عرض عمل بعد التدريب')
                                    ->onIcon('heroicon-o-check')
                                    ->offIcon('heroicon-o-x-mark'),
                            ])
                            ->columnSpanFull(),
                    ]),
                Section::make('التقييمات')
                    ->description('قيّم تجربتك من 1 إلى 5')
                    ->icon('heroicon-o-star')
                    ->columns(3)
                    ->schema([
                        TextInput::make('rating_mentorship')
                            ->label('الإرشاد والدعم')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(5)
                            ->suffix('/ 5'),
                        TextInput::make('rating_learning')
                            ->label('القيمة التعليمية')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(5)
                            ->suffix('/ 5'),
                        TextInput::make('rating_culture')
                            ->label('بيئة العمل')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(5)
                            ->suffix('/ 5'),
                        TextInput::make('rating_compensation')
                            ->label('المكافأة والمزايا')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(5)
                            ->suffix('/ 5'),
                        TextInput::make('overall_rating')
                            ->label('التقييم العام')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(5)
                            ->suffix('/ 5'),
                        Select::make('recommendation')
                            ->label('التوصية')
                            ->required()
                            ->options([
                                'yes' => 'أنصح بها',
                                'maybe' => 'ربما',
                                'no' => 'لا أنصح',
                            ])
                            ->native(false),
                    ]),
                Section::make('المراجعة')
                    ->description('شارك تجربتك بالتفصيل')
                    ->icon('heroicon-o-chat-bubble-bottom-center-text')
                    ->schema([
                        TextInput::make('pros')
                            ->label('المزايا')
                            ->placeholder('ما الذي أعجبك في التدريب؟')
                            ->columnSpanFull(),
                        TextInput::make('cons')
                            ->label('العيوب')
                            ->placeholder('ما الذي يمكن تحسينه؟')
                            ->columnSpanFull(),
                        Textarea::make('review_text')
                            ->label('المراجعة التفصيلية')
                            ->required()
                            ->rows(4)
                            ->placeholder('اكتب تجربتك بالتفصيل...')
                            ->columnSpanFull(),
                    ]),
                Section::make('المقيّم')
                    ->description('معلومات اختيارية عن المقيّم')
                    ->icon('heroicon-o-user')
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        TextInput::make('reviewer_name')
                            ->label('اسم المقيّم')
                            ->placeholder('اختياري'),
                        TextInput::make('reviewer_major')
                            ->label('تخصص المقيّم')
                            ->placeholder('مثال: علوم الحاسب'),
                    ]),
            ]);
    }
}
