<?php

namespace App\Filament\Resources\Officials\Pages;

use App\Filament\Resources\Officials\OfficialResource;
use App\Models\Official;
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

    /** Chỉ tách lại họ/tên khi người dùng thật sự sửa tên; giữ nguyên cách tách cũ nếu không đổi. */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $full = Official::joinFullName($data['hoten'] ?? '', '');
        unset($data['hoten']);

        if (blank(trim((string) ($data['masothe'] ?? '')))) {
            $data['masothe'] = Official::codeFromFormData($data);
        }

        $record = $this->getRecord();
        if ($full !== $record->fullname) {
            [$data['ho'], $data['ten']] = Official::splitFullName($full);
        }

        return $data;
    }
}
