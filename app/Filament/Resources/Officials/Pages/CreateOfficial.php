<?php

namespace App\Filament\Resources\Officials\Pages;

use App\Filament\Resources\Officials\OfficialResource;
use App\Models\Official;
use Filament\Resources\Pages\CreateRecord;

class CreateOfficial extends CreateRecord
{
    protected static string $resource = OfficialResource::class;

    /** Các trường dùng chung cho cả một đợt giới đàn. */
    public const BATCH_FIELDS = [
        'part_id', 'thogioi', 'tochucngay', 'diachigioidan', 'daigioidan', 'ngay_cap', 'thang_cap', 'nam_cap',
        'htdandau', 'yetma', 'giaotho', 'denhat', 'denhi', 'detam', 'detu', 'dengu', 'deluc', 'dethat',
    ];

    public function mount(): void
    {
        parent::mount();

        // /admin/officials/create?from={id}: nhập tiếp cùng đợt, sao chép thông tin giới đàn từ bản ghi gốc
        if ($source = Official::find((int) request()->query('from'))) {
            $this->form->fill($source->only(self::BATCH_FIELDS) + ['status' => true, 'weight' => 0]);
        }
    }

    protected function preserveFormDataWhenCreatingAnother(array $data): array
    {
        return array_intersect_key($data, array_flip(self::BATCH_FIELDS)) + ['status' => true, 'weight' => 0];
    }
}
