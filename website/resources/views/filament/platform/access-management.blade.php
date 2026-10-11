<x-filament-panels::page>
    <div class="bm-admin">
        @if($message)<p class="notice" role="status">{{ $message }}</p>@endif
        @if($errors->any())<div class="error" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <p>Vai trò hệ thống chỉ đọc. Thay đổi quyền sẽ thu hồi phiên của tài khoản liên quan. Không tự thay đổi quyền của chính mình.</p>
        @if(auth()->user()->hasPlatformPermission('platform.roles.manage'))
        <div class="card">
            <h2>Vai trò</h2>
            @foreach($roles as $role)
                <div><strong>{{ $role->name }}</strong>
                @if(in_array($role->code, ['SUPER_ADMIN', 'PLATFORM_ADMIN']))<span class="badge">Hệ thống · chỉ đọc</span>
                @else<button class="button" wire:click="editRole({{ $role->id }})">Sửa</button>@endif</div>
            @endforeach
            <form wire:submit="mutate('save')" class="editor-form">
                <h3>{{ $form['role_id'] ? 'Sửa vai trò' : 'Tạo vai trò' }}</h3>
                <label>Tên vai trò<input wire:model="form.name" required maxlength="160" /></label>
                <fieldset><legend>Quyền được cấp</legend>
                @foreach($permissions as $permission)<label><input type="checkbox" wire:model="form.permissions" value="{{ $permission->id }}" />{{ $permission->description }} ({{ $permission->code }})</label>@endforeach
                </fieldset>
                <button class="button" type="submit" wire:loading.attr="disabled">Lưu vai trò</button>
                @if($form['role_id'])<button type="button" wire:click="mutate('delete')" wire:confirm="Xóa vai trò đang sửa? Vai trò có lượt gán sẽ bị chặn." wire:loading.attr="disabled">Xóa vai trò</button>@endif
                <button type="button" wire:click="$set('form', {name: '', permissions: [], role_id: null})">Tạo vai trò khác</button>
            </form>
        </div>
        @endif
        @if(auth()->user()->hasPlatformPermission('platform.admins.manage'))
        <div class="card">
            <h2>Gán vai trò quản trị</h2>
            <label>Tìm tài khoản theo email (tối đa 30 kết quả)<input type="search" wire:model.live.debounce.300ms="search" /></label>
            <form wire:submit="mutate('grant')" class="editor-form">
                <label>Tài khoản<select wire:model="userId" required><option value="">Chọn tài khoản</option>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->email }} · {{ $user->status }}</option>@endforeach</select></label>
                <label>Vai trò<select wire:model="roleId" required><option value="">Chọn vai trò</option>@foreach($roles as $role)<option value="{{ $role->id }}">{{ $role->name }}</option>@endforeach</select></label>
                <button class="button" type="submit" wire:loading.attr="disabled" wire:confirm="Gán vai trò cho tài khoản đã chọn? Các phiên của tài khoản này sẽ bị thu hồi.">Gán vai trò</button>
            </form>
            <h3>Các lượt gán</h3>
            @forelse($grants as $grant)
                <div>{{ $grant->email }} · {{ $grant->name }} · {{ $grant->expires_at ? 'Hết hạn: '.$grant->expires_at : 'Không thời hạn' }}
                <button wire:click="mutate('revoke', {{ $grant->user_id }}, {{ $grant->role_id }})" wire:confirm="Thu hồi vai trò {{ $grant->name }} của {{ $grant->email }}? Các phiên sẽ bị đăng xuất." wire:loading.attr="disabled">Thu hồi</button></div>
            @empty<p>Chưa có lượt gán.</p>@endforelse
            {{ $grants->links() }}
        </div>
        @endif
    </div>
</x-filament-panels::page>
