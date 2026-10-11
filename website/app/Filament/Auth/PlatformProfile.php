<?php

namespace App\Filament\Auth;

use App\Modules\Identity\Actions\UpdateAccountAction;
use Filament\Auth\Pages\EditProfile;
use Filament\Schemas\Components\Component;
use Illuminate\Database\Eloquent\Model;

class PlatformProfile extends EditProfile
{
    protected function getEmailFormComponent(): Component
    {
        return parent::getEmailFormComponent()->disabled()->dehydrated(false)->helperText('Đổi email và mật khẩu tại /account; cần xác minh lại email.');
    }

    protected function getPasswordFormComponent(): Component
    {
        return parent::getPasswordFormComponent()->hidden()->dehydrated(false);
    }

    protected function getPasswordConfirmationFormComponent(): Component
    {
        return parent::getPasswordConfirmationFormComponent()->hidden()->dehydrated(false);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        app(UpdateAccountAction::class)->execute($record, 'profile', ['name' => $data['name']]);

        return $record->refresh();
    }
}
