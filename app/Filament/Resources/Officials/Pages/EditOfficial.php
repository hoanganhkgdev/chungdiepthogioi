<?php

namespace App\Filament\Resources\Officials\Pages;

use App\Filament\Resources\Officials\OfficialResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOfficial extends EditRecord
{
    protected static string $resource = OfficialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')->label('In chứng điệp')->icon('heroicon-o-printer')->color('gray')
                ->url(fn () => url($this->getRecord()->path()).'?print=1')->openUrlInNewTab(),
            Action::make('next')->label('Nhập tiếp cùng đợt')->icon('heroicon-o-document-duplicate')->color('primary')
                ->url(fn () => OfficialResource::getUrl('create').'?from='.$this->getRecord()->id),
            DeleteAction::make(),
        ];
    }
}
