<?php

namespace App\Filament\Pages;

use App\Modules\AccessControl\Actions\ManageAccessAction;
use App\Modules\AccessControl\Queries\AccessWorkspace;
use Filament\Pages\Page;
use Livewire\WithPagination;

class AccessManagement extends Page
{
    use WithPagination;

    protected static ?string $title = 'Vai trò và quản trị viên';

    protected static ?string $navigationLabel = 'Quyền quản trị';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected string $view = 'filament.platform.access-management';

    public array $form = ['name' => '', 'permissions' => [], 'role_id' => null];

    public string $search = '';

    public string $message = '';

    public ?int $userId = null;

    public ?int $roleId = null;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user?->canAccessApplication() && ($user->hasPlatformPermission('platform.roles.manage') || $user->hasPlatformPermission('platform.admins.manage'));
    }

    public function editRole(int $id): void
    {
        $name = app(AccessWorkspace::class)->get(auth()->user(), '')['roles']->firstWhere('id', $id)?->name ?? '';
        $this->form = ['role_id' => $id, 'name' => $name, 'permissions' => app(AccessWorkspace::class)->rolePermissions(auth()->user(), $id)];
        $this->resetValidation();
    }

    public function mutate(string $operation, ?int $userId = null, ?int $roleId = null): void
    {
        $input = in_array($operation, ['save', 'delete']) ? $this->form : ['user_id' => $userId ?? $this->userId, 'role_id' => $roleId ?? $this->roleId];
        app(ManageAccessAction::class)->execute(auth()->user(), $operation, $input);
        $this->message = 'Đã cập nhật quyền quản trị.';
        $this->form = ['name' => '', 'permissions' => [], 'role_id' => null];
    }

    protected function getViewData(): array
    {
        return app(AccessWorkspace::class)->get(auth()->user(), $this->search);
    }
}
