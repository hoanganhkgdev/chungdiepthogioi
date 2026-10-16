<?php

namespace App\Filament\Widgets;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** Thẻ thống kê riêng cho trang Tài khoản (không hiện ở dashboard). */
class UserStats extends StatsOverviewWidget
{
    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected ?string $pollingInterval = null;

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        $total = User::count();
        $never = User::whereNull('last_login_at')->where('is_active', true)->count();

        return [
            Stat::make('Tổng tài khoản', number_format($total))
                ->description(User::where('role', UserRole::Admin->value)->count().' quản trị · '.User::where('role', UserRole::Editor->value)->count().' nhập liệu')
                ->descriptionIcon(Heroicon::OutlinedUsers),
            Stat::make('Đang hoạt động', number_format(User::where('is_active', true)->count()))
                ->description(User::where('is_active', false)->count().' đã khóa')
                ->descriptionIcon(Heroicon::OutlinedCheckCircle)
                ->color('success'),
            Stat::make('Chưa từng đăng nhập', number_format($never))
                ->description($never ? 'Nhớ gửi mật khẩu cho họ' : 'Mọi người đều đã đăng nhập')
                ->descriptionIcon(Heroicon::OutlinedClock)
                ->color($never ? 'warning' : 'success'),
        ];
    }
}
