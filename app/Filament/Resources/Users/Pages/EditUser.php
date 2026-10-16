<?php

namespace App\Filament\Resources\Users\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    /** Luôn phải còn ít nhất một Quản trị đang hoạt động. */
    protected function beforeSave(): void
    {
        /** @var User $record */
        $record = $this->getRecord();
        $data = $this->form->getState();

        $demoted = $record->isAdmin() && (
            (isset($data['role']) && UserRole::tryFrom((string) ($data['role'] instanceof UserRole ? $data['role']->value : $data['role'])) !== UserRole::Admin)
            || (array_key_exists('is_active', $data) && ! $data['is_active'])
        );

        if ($demoted && ! User::otherActiveAdminsExist($record)) {
            Notification::make()->danger()->title('Không thể thực hiện')
                ->body('Phải còn ít nhất một Quản trị đang hoạt động.')->send();
            $this->halt();
        }
    }
}
