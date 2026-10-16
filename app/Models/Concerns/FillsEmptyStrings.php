<?php

namespace App\Models\Concerns;

/**
 * Các bảng NukeViet cũ khai báo cột NOT NULL không có default; form Filament gửi null khi để trống.
 * Chuyển null về '' (hoặc 0 cho cột số) trước khi lưu.
 */
trait FillsEmptyStrings
{
    protected static function bootFillsEmptyStrings(): void
    {
        static::saving(function ($model) {
            foreach ($model->emptyStringColumns() as $col) {
                if ($model->getAttribute($col) === null) {
                    $model->setAttribute($col, '');
                }
            }
            foreach ($model->emptyZeroColumns() as $col) {
                if ($model->getAttribute($col) === null) {
                    $model->setAttribute($col, 0);
                }
            }
        });
    }

    abstract public function emptyStringColumns(): array;

    public function emptyZeroColumns(): array
    {
        return [];
    }
}
