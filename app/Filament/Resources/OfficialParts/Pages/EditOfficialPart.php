<?php

namespace App\Filament\Resources\OfficialParts\Pages;

use App\Filament\Resources\OfficialParts\OfficialPartResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOfficialPart extends EditRecord
{
    protected static string $resource = OfficialPartResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('officials')->label('Xem các chứng điệp')->icon('heroicon-o-identification')->color('gray')
                ->url(fn () => OfficialPartResource::officialsUrl($this->getRecord())),
            DeleteAction::make()
                ->disabled(fn () => $this->getRecord()->officials()->exists())
                ->tooltip(fn () => $this->getRecord()->officials()->exists() ? 'Giới đàn còn chứng điệp, không thể xóa' : null),
        ];
    }
}
