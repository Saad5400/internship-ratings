<?php

namespace App\Filament\Resources\Ratings\Pages;

use App\Filament\Resources\Ratings\RatingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListRatings extends ListRecords
{
    protected static string $resource = RatingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('الكل')
                ->icon('heroicon-o-list-bullet'),
            'recommended' => Tab::make('موصى بها')
                ->icon('heroicon-o-hand-thumb-up')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('recommendation', 'yes'))
                ->badgeColor('success'),
            'not_recommended' => Tab::make('غير موصى بها')
                ->icon('heroicon-o-hand-thumb-down')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('recommendation', 'no'))
                ->badgeColor('danger'),
            'high_rated' => Tab::make('تقييم عالي')
                ->icon('heroicon-o-star')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('overall_rating', '>=', 4))
                ->badgeColor('success'),
        ];
    }
}
