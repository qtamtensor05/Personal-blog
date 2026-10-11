<?php

namespace App\Modules\Identity\Actions;

use App\Models\User;
use App\Modules\AccessControl\LastSuperAdminGuard;
use App\Shared\Contracts\AuditRecorder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

final class UpdateAccountAction
{
    public function execute(User $actor, string $section, array $input): void
    {
        abort_unless(in_array($section, ['profile', 'email', 'password'], true), 404);
        DB::transaction(function () use ($actor, $section, $input): void {
            if ($section === 'email') {
                DB::table('platform_roles')->orderBy('id')->lockForUpdate()->get();
                app(LastSuperAdminGuard::class)->assertCanDeactivate($actor->id);
            }
            $user = User::query()->whereKey($actor->id)->lockForUpdate()->firstOrFail();
            abort_unless($user->canAccessApplication(), 403);
            $rules = match ($section) {
                'profile' => ['name' => 'required|string|max:160'],
                'email' => ['email' => ['required', 'email', 'max:254', Rule::unique('users', 'email')->ignore($user->id)], 'current_password' => 'required|string'],
                'password' => ['password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()], 'current_password' => 'required|string'],
            };
            $data = Validator::make($input, $rules)->validate();
            if ($section !== 'profile' && ! Hash::check($data['current_password'], $user->password)) {
                throw ValidationException::withMessages(['current_password' => 'Mật khẩu hiện tại không đúng.']);
            }
            $values = match ($section) {
                'profile' => ['name' => $data['name']],
                'email' => ['email' => $data['email'], 'email_verified_at' => null, 'status' => 'PENDING_VERIFICATION'],
                'password' => ['password' => Hash::make($data['password'])],
            };
            if ($section === 'email' && $data['email'] === $user->email) {
                throw ValidationException::withMessages(['email' => 'Hãy nhập địa chỉ email mới.']);
            }
            if ($section !== 'profile') {
                $values['remember_token'] = Str::random(60);
                DB::table('sessions')->where('user_id', $user->id)->delete();
                DB::table('password_reset_tokens')->where('email', $user->email)->delete();
            }
            try {
                DB::table('users')->where('id', $user->id)->update([...$values, 'updated_at' => now()]);
            } catch (UniqueConstraintViolationException $exception) {
                if ($section !== 'email') {
                    throw $exception;
                }
                throw ValidationException::withMessages(['email' => 'Email không khả dụng. Hãy chọn địa chỉ khác.']);
            }
            app(AuditRecorder::class)->record($user->id, 'identity.'.$section.'.updated', 'users', $user->id);
        });
    }
}
