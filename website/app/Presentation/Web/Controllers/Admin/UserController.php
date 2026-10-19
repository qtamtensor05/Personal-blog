<?php

declare(strict_types=1);

namespace App\Presentation\Web\Controllers\Admin;

use App\Application\AccessControl\Services\PermissionResolver;
use App\Application\Administration\Contracts\AdminUserRepository;
use App\Application\Administration\UseCases\ManageUsers\ManageUsersService;
use App\Application\Identity\Exceptions\EmailAlreadyRegistered;
use App\Application\Identity\UseCases\RegisterUser\RegisterUserUseCase;
use App\Domain\AccessControl\Enums\PlatformPermission;
use App\Domain\Identity\Enums\UserStatus;
use DateTimeImmutable;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class UserController extends Controller
{
    private const PER_PAGE = 25;

    public function index(Request $request, AdminUserRepository $users): View
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:254'],
            'status' => ['nullable', 'in:PENDING_VERIFICATION,ACTIVE,LOCKED,SUSPENDED,DISABLED'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $page = (int) ($data['page'] ?? 1);
        $result = $users->search(
            $data['q'] ?? null,
            isset($data['status']) ? UserStatus::from($data['status']) : null,
            $page,
            self::PER_PAGE,
        );

        return view('admin.users.index', [
            'users' => $result['items'],
            'total' => $result['total'],
            'page' => $page,
            'lastPage' => max(1, (int) ceil($result['total'] / self::PER_PAGE)),
            'filters' => $data,
        ]);
    }

    public function createAdmin(
        Request $request,
        AdminUserRepository $users,
        RegisterUserUseCase $registerUser,
    ): RedirectResponse {
        $data = $request->validate([
            'email' => ['required', 'string', 'email', 'max:254'],
        ]);

        $name = trim((string) preg_replace('/[._-]+/', ' ', strstr($data['email'], '@', true) ?: $data['email']));
        $name = ucwords($name === '' ? 'Administrator' : $name);

        try {
            $user = $registerUser->execute(
                name: $name,
                email: $data['email'],
                password: (string) config('identity.admin_default_password'),
            );
        } catch (EmailAlreadyRegistered) {
            throw ValidationException::withMessages([
                'email' => 'Email address is already registered.',
            ]);
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['email' => $exception->getMessage()]);
        }

        $adminRole = collect($users->roles())->firstWhere('code', 'SUPER_ADMIN');

        if ($adminRole === null) {
            throw new DomainException('The SUPER_ADMIN role is not configured.');
        }

        $users->grantRole(
            $user->id(),
            (int) $adminRole['id'],
            (int) $request->user()->id,
            null,
        );

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'Admin account created. A verification email was sent to the address.');
    }

    public function show(int $user, Request $request, AdminUserRepository $users, PermissionResolver $permissions): View
    {
        $record = $users->find($user) ?? abort(404);

        return view('admin.users.show', [
            'user' => $record,
            'roles' => $users->roles(),
            'permissions' => $users->permissions(),
            'permissionOverrides' => $users->permissionOverrides($user),
            'isSelf' => $record->id === (int) $request->user()->id,
            'canManageRoles' => $permissions->has((int) $request->user()->id, PlatformPermission::ManageRoles),
        ]);
    }

    public function restrict(int $user, Request $request, ManageUsersService $service): RedirectResponse
    {
        $data = $request->validate([
            'kind' => ['required', 'in:LOCK,SUSPEND,DISABLE'],
            'reason' => ['required', 'string', 'max:2000'],
            'ends_at' => ['nullable', 'date'],
        ]);

        return $this->run($user, fn () => $service->restrict(
            (int) $request->user()->id,
            $user,
            $data['kind'],
            $data['reason'],
            isset($data['ends_at']) ? new DateTimeImmutable($data['ends_at']) : null,
        ), 'Restriction applied. The user has been signed out.');
    }

    public function lift(int $user, Request $request, ManageUsersService $service): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:2000']]);

        return $this->run(
            $user,
            fn () => $service->lift((int) $request->user()->id, $user, $data['reason']),
            'Restriction lifted.',
        );
    }

    public function grantRole(int $user, Request $request, ManageUsersService $service): RedirectResponse
    {
        $data = $request->validate([
            'role_id' => ['required', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date'],
        ]);

        return $this->run($user, fn () => $service->grantRole(
            (int) $request->user()->id,
            $user,
            (int) $data['role_id'],
            isset($data['expires_at']) ? new DateTimeImmutable($data['expires_at']) : null,
        ), 'Role granted.');
    }

    public function revokeRole(int $user, int $role, Request $request, ManageUsersService $service): RedirectResponse
    {
        return $this->run(
            $user,
            fn () => $service->revokeRole((int) $request->user()->id, $user, $role),
            'Role revoked.',
        );
    }

    public function setPermission(
        int $user,
        Request $request,
        ManageUsersService $service,
    ): RedirectResponse {
        $data = $request->validate([
            'permission_id' => ['required', 'integer', 'min:1'],
            'effect' => ['required', 'in:GRANT,DENY'],
        ]);

        return $this->run($user, fn () => $service->setPermissionOverride(
            (int) $request->user()->id,
            $user,
            (int) $data['permission_id'],
            $data['effect'],
        ), 'User permission updated.');
    }

    public function auditLogs(Request $request, AdminUserRepository $users): View
    {
        $page = max(1, (int) $request->query('page', 1));
        $result = $users->auditLogs($page, 50);

        return view('admin.audit-logs', [
            'logs' => $result['items'],
            'page' => $page,
            'lastPage' => max(1, (int) ceil($result['total'] / 50)),
        ]);
    }

    private function run(int $user, callable $action, string $message): RedirectResponse
    {
        try {
            $action();
        } catch (DomainException $exception) {
            return back()->withErrors(['general' => $exception->getMessage()])->withInput();
        }

        return redirect()->route('admin.users.show', $user)->with('status', $message);
    }
}
