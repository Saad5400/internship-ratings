<?php

namespace App\Filament\Widgets;

use App\Models\Rating;
use Filament\Widgets\ChartWidget;

class RatingsDistributionChart extends ChartWidget
{
    protected ?string $heading = 'توزيع التقييمات';

    protected ?string $description = 'عدد التقييمات حسب الدرجة';

    protected static ?int $sort = 2;

    protected ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $distribution = [];
        for ($i = 1; $i <= 5; $i++) {
            $distribution[$i] = Rating::where('overall_rating', $i)->count();
        }

        return [
            'datasets' => [
                [
                    'label' => 'عدد التقييمات',
                    'data' => array_values($distribution),
                    'backgroundColor' => [
                        'rgba(239, 68, 68, 0.8)',
                        'rgba(249, 115, 22, 0.8)',
                        'rgba(245, 158, 11, 0.8)',
                        'rgba(34, 197, 94, 0.8)',
                        'rgba(16, 185, 129, 0.8)',
                    ],
                    'borderColor' => [
                        'rgb(239, 68, 68)',
                        'rgb(249, 115, 22)',
                        'rgb(245, 158, 11)',
                        'rgb(34, 197, 94)',
                        'rgb(16, 185, 129)',
                    ],
                    'borderWidth' => 1,
                ],
            ],
            'labels' => ['1 - ضعيف', '2 - مقبول', '3 - جيد', '4 - جيد جداً', '5 - ممتاز'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
