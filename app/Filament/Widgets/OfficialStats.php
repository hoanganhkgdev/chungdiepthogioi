<?php

namespace App\Filament\Widgets;

use App\Models\Official;
use App\Models\OfficialPart;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OfficialStats extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $total = Official::count();
        $shown = Official::where('status', 1)->count();
        $recent = Official::where('addtime', '>=', now()->subDays(30)->timestamp)->count();

        return [
            Stat::make('Tổng chứng điệp', number_format($total))
                ->description('Tất cả bản ghi')
                ->descriptionIcon(Heroicon::OutlinedIdentification),
            Stat::make('Đang hiển thị', number_format($shown))
                ->description($total ? round($shown / $total * 100).'% tổng số' : '—')
                ->descriptionIcon(Heroicon::OutlinedEye)
                ->color('success'),
            Stat::make('Thêm trong 30 ngày', number_format($recent))
                ->description('Nhập tay hoặc import')
                ->descriptionIcon(Heroicon::OutlinedPlusCircle),
            Stat::make('Giới đàn', number_format(OfficialPart::where('status', 1)->count()))
                ->description('Đang hoạt động')
                ->descriptionIcon(Heroicon::OutlinedBuildingLibrary),
        ];
    }
}
