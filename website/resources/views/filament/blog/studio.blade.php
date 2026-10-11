<x-filament-panels::page>
<div class="bm-admin">
@if(!$blog)<div class="empty-state"><h2>Bạn chưa có blog</h2><a class="button" href="/templates">Chọn mẫu và tạo blog</a></div>@else
<div class="preview-toolbar"><label>Blog đang quản lý<select wire:model.live="blogId">@foreach($blogs as $item)<option value="{{ $item->id }}">{{ $item->name }}</option>@endforeach</select></label><span class="badge">{{ $blog->status }}</span><a href="/b/{{ $blog->slug }}">Xem blog live ↗</a><a href="/workspace">Tạo blog khác</a></div>
<nav class="bm-tabs" aria-label="Quản lý blog">@foreach(['overview'=>'Tổng quan','posts'=>'Bài viết','pages'=>'Trang','categories'=>'Danh mục','tags'=>'Thẻ','media'=>'Media','design'=>'Giao diện','navigation'=>'Menu','settings'=>'Cài đặt'] as $key=>$label)<button wire:click="$set('section','{{ $key }}')" @class(['active'=>$section===$key])>{{ $label }}</button>@endforeach</nav>
@if($message)<p class="notice" role="status">{{ $message }}</p>@endif
@if($errors->any())<div role="alert" class="error"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<p wire:loading role="status">Đang xử lý…</p>
@if($section==='overview')<div class="card"><h2>{{ $blog->name }}</h2><p>{{ $rows->count() }} bài viết · {{ $blog->status }}</p><p>Viết bài đầu tiên, tùy chỉnh bản nháp giao diện rồi xuất bản để blog xuất hiện công khai.</p><button class="button" wire:click="$set('section','posts')">Quản lý bài viết</button></div>
@elseif(in_array($section,['posts','pages']))
@if($editing===null)<div class="filter-bar"><label>Tìm kiếm<input wire:model.live.debounce.400ms="q" type="search" /></label><label>Trạng thái<select wire:model.live="status"><option value="">Tất cả</option>@foreach(['DRAFT'=>'Nháp','PUBLISHED'=>'Đã xuất bản','SCHEDULED'=>'Đã lên lịch','ARCHIVED'=>'Lưu trữ'] as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label><button class="button" wire:click="edit">Tạo {{ $section==='posts'?'bài viết':'trang' }}</button></div><div class="table-scroll"><table><thead><tr><th>Tiêu đề</th><th>Trạng thái</th><th>Lịch (UTC)</th><th>Thao tác</th></tr></thead><tbody>@forelse($rows as $row)<tr><td>{{ $row->title }}@if($row->draft_data)<span class="badge">Có thay đổi nháp</span>@endif</td><td>{{ $row->status }}</td><td>{{ $row->scheduled_at ?? $row->published_at }}</td><td><button wire:click="edit({{ $row->id }})">Chỉnh sửa</button><button wire:click="archive({{ $row->id }})" wire:confirm="Lưu trữ {{ $row->title }}? Nội dung sẽ ngừng hiển thị công khai.">Lưu trữ</button></td></tr>@empty<tr><td colspan="4">Chưa có nội dung phù hợp.</td></tr>@endforelse</tbody></table></div>
@else
<div class="editor-form" wire:key="content-{{ $section }}-{{ $editing }}-{{ $fingerprint }}" x-data="{ dirty: false }" x-on:input="dirty=true" x-on:beforeunload.window="if(dirty) { $event.preventDefault(); $event.returnValue=''; }">
<div class="card"><div class="form-grid"><label>Tiêu đề<input wire:model="form.title" required maxlength="160" /></label><label>Đường dẫn<input wire:model="form.slug" required /><small>Đường dẫn bài live được giữ nguyên khi sửa nháp.</small></label></div>@if($section==='posts')<label>Mô tả ngắn<textarea wire:model="form.excerpt"></textarea></label>@endif
<div wire:ignore x-data x-init="$nextTick(() => { const mount = () => window.ButMayEditors.content($el, $wire, @js($form['content_json'])); if(window.ButMayEditors) mount(); else window.addEventListener('butmay-editors-ready', mount, {once:true}); })"><div class="editor-toolbar"><button type="button" data-command="bold">Đậm</button><button type="button" data-command="italic">Nghiêng</button><button type="button" data-command="heading">Tiêu đề</button><button type="button" data-command="bulletList">Danh sách</button><button type="button" data-command="undo">Hoàn tác</button><label>Chèn ảnh<select data-media><option value="">Chọn ảnh trong blog</option>@foreach($media->where('status','READY') as $asset)<option value="{{ $asset->id }}" data-src="{{ $asset->secure_url }}" data-alt="{{ $asset->alt_text }}">{{ $asset->original_name }}</option>@endforeach</select></label></div><div data-tiptap class="prose tiptap-area" aria-label="Nội dung bài viết"></div></div>
<div class="form-grid"><label>SEO tiêu đề<input wire:model="form.seo_title" maxlength="255" /></label><label>SEO mô tả<textarea wire:model="form.seo_description" maxlength="500"></textarea></label></div>
@if($section==='posts')<div class="form-grid"><label>Ảnh bìa<select wire:model="form.cover_media_id"><option value="">Không có ảnh bìa</option>@foreach($media->where('status','READY') as $asset)<option value="{{ $asset->id }}">{{ $asset->original_name }}</option>@endforeach</select></label><label>Danh mục<select wire:model="form.category_ids" multiple>@foreach($categories as $term)<option value="{{ $term->id }}">{{ $term->name }}</option>@endforeach</select></label><label>Thẻ<select wire:model="form.tag_ids" multiple>@foreach($tags as $term)<option value="{{ $term->id }}">{{ $term->name }}</option>@endforeach</select></label></div>@endif
<label>Lịch xuất bản (UTC)<input type="datetime-local" wire:model="form.scheduled_at" /></label><p>Nháp của bài đang live không thay nội dung công khai. Sửa nháp sẽ hủy lịch cũ nếu không lên lịch lại.</p></div>
<div class="sticky-actions"><button wire:click="save('draft')" wire:loading.attr="disabled" class="button">Lưu nháp</button><button wire:click="save('publish')" wire:loading.attr="disabled" wire:confirm="Xuất bản nội dung này lên blog?" class="button">Xuất bản</button><button wire:click="save('schedule')" wire:loading.attr="disabled">Lên lịch</button>@if($editing)<a target="_blank" rel="noopener" href="/workspace/blogs/{{ $blogId }}/preview/{{ $section }}/{{ $editing }}">Xem trước nháp đã lưu ↗</a>@endif<button wire:click="$set('editing',null)" x-on:click="if(dirty && !confirm('Rời editor? Thay đổi chưa lưu sẽ mất.')) $event.stopImmediatePropagation()">Quay lại</button></div>
</div>@endif
@elseif(in_array($section,['categories','tags']))<form class="card" wire:submit="addTerm"><h2>Thêm {{ $section==='tags'?'thẻ':'danh mục' }}</h2><div class="form-grid"><label>Tên<input wire:model="form.name" required /></label><label>Đường dẫn<input wire:model="form.slug" required /></label></div><button class="button" wire:loading.attr="disabled">Thêm</button></form><ul class="card">@forelse($rows as $row)<li>{{ $row->name }} · {{ $row->slug }}</li>@empty<li>Chưa có phân loại.</li>@endforelse</ul>
@elseif($section==='media')<form class="card" wire:submit="upload"><h2>Thư viện media</h2><label>Ảnh JPEG/PNG/WebP/GIF<input type="file" wire:model="image" accept="image/jpeg,image/png,image/webp,image/gif" /></label><label>Văn bản thay thế<input wire:model="alt" maxlength="500" /></label><button class="button" wire:loading.attr="disabled">Tải lên</button><p wire:loading wire:target="image">Đang tải tệp…</p></form><label>Tìm ảnh<input wire:model.live.debounce.400ms="q" type="search" /></label><div class="template-grid">@forelse($media as $asset)<article class="card">@if($asset->status==='READY')<img src="{{ $asset->secure_url }}" alt="{{ $asset->alt_text }}" loading="lazy" />@endif<h3>{{ $asset->original_name }}</h3><p>{{ $asset->status }} · {{ $asset->width }} × {{ $asset->height }} · {{ number_format(($asset->bytes ?? 0)/1024) }} KB</p><p>{{ $asset->alt_text }}</p>@if($asset->status==='READY')<button wire:click="editMedia({{ $asset->id }})">Sửa alt text</button>@endif<button wire:click="deleteMedia({{ $asset->id }})" wire:loading.attr="disabled" wire:confirm="Xóa {{ $asset->original_name }}? Tài nguyên sẽ bị xóa ở nhà cung cấp nếu không còn được sử dụng.">{{ $asset->status==='DELETING'?'Thử xóa lại':'Xóa tài nguyên' }}</button></article>@empty<p class="empty-state">Chưa có ảnh. Ảnh chỉ được chèn sau khi tải lên thành công.</p>@endforelse</div>@if($editing)<form wire:submit="saveMedia" class="card"><label>Văn bản thay thế<textarea wire:model="form.alt_text" maxlength="500"></textarea></label><button class="button" wire:loading.attr="disabled">Lưu alt text</button></form>@endif<p>Xóa bị chặn nếu tài nguyên còn nằm trong nội dung, nháp, giao diện hoặc cài đặt.</p>
@elseif(in_array($section,['design','navigation']))<div class="card"><h2>Giao diện nháp</h2><p>Đổi mẫu chỉ ảnh hưởng bản nháp. Bài viết, trang và media được giữ nguyên.</p><label>Mẫu nguồn<select wire:model="templateId">@foreach($templates as $template)<option value="{{ $template->id }}">{{ $template->name }}</option>@endforeach</select></label>@include('filament.shared.design-fields')<label>Trang trong menu<select multiple wire:model="form.menu_page_keys">@foreach($pages as $item)<option value="{{ $item->page_key }}">{{ $item->title }}</option>@endforeach</select></label><div class="actions"><button class="button" wire:click="saveDesign" wire:loading.attr="disabled">Lưu nháp</button><a href="/workspace/blogs/{{ $blogId }}/preview-design" target="_blank" rel="noopener">Xem trước nháp đã lưu</a><button class="button" wire:click="saveDesign(true)" wire:loading.attr="disabled" wire:confirm="Xuất bản giao diện nháp và đưa blog lên live?">Xuất bản giao diện</button></div></div>
@elseif($section==='settings')
<form class="card" wire:submit="saveSettings">
    <h2>Cài đặt {{ $blog->name }}</h2>

    <label>Tên<input wire:model="form.name" required maxlength="160" /></label>

    <div class="form-group">
        <label>Đường dẫn blog (slug)
            <input wire:model="form.slug" required maxlength="160" />
        </label>
        <p class="field-hint">
            ⚠️ Thay đổi đường dẫn sẽ đổi URL công khai của blog từ
            <code>/b/{{ $blog->slug }}</code> thành <code>/b/{slug-mới}</code>.
            Các liên kết cũ sẽ không còn hoạt động.
            Không có chuyển hướng tự động.
        </p>
    </div>

    <label>Mô tả<textarea wire:model="form.description" maxlength="2000"></textarea></label>

    <label>Hiển thị
        <select wire:model="form.visibility">
            <option value="PUBLIC">Công khai</option>
            <option value="UNLISTED">Không liệt kê (có URL trực tiếp)</option>
            <option value="PRIVATE">Riêng tư (chỉ chủ blog)</option>
        </select>
    </label>

    <label class="checkbox-label">
        <input type="checkbox" wire:model="form.discoverable" />
        Cho phép khám phá (hiển thị trong danh sách blog công khai)
    </label>

    <label>Ngôn ngữ (locale)
        <select wire:model="form.locale">
            <option value="vi">Tiếng Việt (vi)</option>
            <option value="en">English (en)</option>
        </select>
    </label>

    <label>Múi giờ
        <select wire:model="form.timezone">
            <option value="Asia/Ho_Chi_Minh">Việt Nam (Asia/Ho_Chi_Minh)</option>
            <option value="UTC">UTC</option>
            <option value="Asia/Bangkok">Bangkok (Asia/Bangkok)</option>
            <option value="Asia/Singapore">Singapore (Asia/Singapore)</option>
            <option value="Asia/Tokyo">Tokyo (Asia/Tokyo)</option>
            <option value="Europe/London">London (Europe/London)</option>
            <option value="America/New_York">New York (America/New_York)</option>
        </select>
    </label>

    <label>Số bài mỗi trang<input type="number" wire:model="form.posts_per_page" min="1" max="50" /></label>

    <label>SEO tiêu đề<input wire:model="form.seo_title" maxlength="255" /></label>
    <label>SEO mô tả<textarea wire:model="form.seo_description" maxlength="500"></textarea></label>

    @foreach(['logo_media_id'=>'Logo','favicon_media_id'=>'Favicon','og_media_id'=>'Ảnh chia sẻ'] as $field=>$label)
    <label>{{ $label }}<select wire:model="form.{{ $field }}"><option value="">Không chọn</option>@foreach($media->where('status','READY') as $asset)<option value="{{ $asset->id }}">{{ $asset->original_name }}</option>@endforeach</select></label>
    @endforeach

    <fieldset>
        <legend>Liên kết mạng xã hội</legend>
        @foreach(['twitter'=>'Twitter/X','facebook'=>'Facebook','instagram'=>'Instagram','youtube'=>'YouTube','github'=>'GitHub','linkedin'=>'LinkedIn'] as $key=>$label)
        <label>{{ $label }}<input wire:model="form.social_links.{{ $key }}" type="url" maxlength="255" placeholder="https://..." /></label>
        @endforeach
    </fieldset>

    <button class="button" wire:loading.attr="disabled">Lưu cài đặt</button>
</form>
@endif
@endif</div>
</x-filament-panels::page>
