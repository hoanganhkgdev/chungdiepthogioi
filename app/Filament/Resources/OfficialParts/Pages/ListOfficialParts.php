<?php

namespace App\Filament\Resources\OfficialParts\Pages;

use App\Filament\Resources\OfficialParts\OfficialPartResource;
use App\Filament\Widgets\PartStats;
use App\Models\OfficialPart;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListOfficialParts extends ListRecords
{
    protected static string $resource = OfficialPartResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [PartStats::class];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Tất cả')->badge(OfficialPart::count()),
            'shown' => Tab::make('Đang hiển thị')->badge(OfficialPart::where('status', 1)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 1)),
            'empty' => Tab::make('Chưa có chứng điệp')->badge(OfficialPart::whereNotIn('id', array_keys(OfficialPart::officialCounts()))->count())->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereRaw('coalesce(oc.n, 0) = 0')),
            'hidden' => Tab::make('Đang ẩn')->badge(OfficialPart::where('status', 0)->count())->badgeColor('gray')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 0)),
        ];
    }
}
