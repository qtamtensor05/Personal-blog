<?php

declare(strict_types=1);

namespace App\Presentation\Web\Controllers;

use App\Application\BlogDesign\Services\OwnedBlogGuard;
use App\Application\Blogs\Exceptions\BlogNotFound;
use App\Application\Content\Contracts\TaxonomyRepository;
use App\Application\Content\UseCases\ManageTaxonomy\ManageTaxonomyService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class TaxonomyController extends Controller
{
    private const SLUG = ['required', 'string', 'max:160', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'];

    public function index(
        int $blog,
        Request $request,
        OwnedBlogGuard $guard,
        TaxonomyRepository $taxonomy,
    ): View {
        try {
            $record = $guard->ensure((int) $request->user()->id, $blog);
        } catch (BlogNotFound) {
            abort(404);
        }

        return view('blogs.taxonomy', [
            'blog' => $record,
            'categories' => $taxonomy->categories($blog),
            'tags' => $taxonomy->tags($blog),
        ]);
    }

    public function storeCategory(
        int $blog,
        Request $request,
        ManageTaxonomyService $service,
    ): RedirectResponse {
        $data = $request->validate($this->categoryRules(), $this->validationMessages());

        return $this->run(
            $blog,
            fn () => $service->createCategory(
                (int) $request->user()->id,
                $blog,
                $data['name'],
                $data['slug'],
                $data['description'] ?? null,
                isset($data['parent_id']) ? (int) $data['parent_id'] : null,
            ),
            'Category created.',
        );
    }

    public function updateCategory(
        int $blog,
        int $category,
        Request $request,
        ManageTaxonomyService $service,
    ): RedirectResponse {
        $data = $request->validate(
            $this->categoryRules() + ['is_active' => ['required', 'boolean']],
            $this->validationMessages(),
        );

        return $this->run(
            $blog,
            fn () => $service->updateCategory(
                (int) $request->user()->id,
                $blog,
                $category,
                $data['name'],
                $data['slug'],
                $data['description'] ?? null,
                isset($data['parent_id']) ? (int) $data['parent_id'] : null,
                (bool) $data['is_active'],
            ),
            'Category updated.',
        );
    }

    public function destroyCategory(
        int $blog,
        int $category,
        Request $request,
        ManageTaxonomyService $service,
    ): RedirectResponse {
        return $this->run(
            $blog,
            fn () => $service->deleteCategory((int) $request->user()->id, $blog, $category),
            'Category deleted.',
        );
    }

    public function storeTag(
        int $blog,
        Request $request,
        ManageTaxonomyService $service,
    ): RedirectResponse {
        $data = $request->validate(
            [
                'name' => ['required', 'string', 'max:160'],
                'slug' => self::SLUG,
            ],
            $this->validationMessages(),
        );

        return $this->run(
            $blog,
            fn () => $service->createTag(
                (int) $request->user()->id,
                $blog,
                $data['name'],
                $data['slug'],
            ),
            'Tag created.',
        );
    }

    public function updateTag(
        int $blog,
        int $tag,
        Request $request,
        ManageTaxonomyService $service,
    ): RedirectResponse {
        $data = $request->validate(
            [
                'name' => ['required', 'string', 'max:160'],
                'slug' => self::SLUG,
            ],
            $this->validationMessages(),
        );

        return $this->run(
            $blog,
            fn () => $service->updateTag(
                (int) $request->user()->id,
                $blog,
                $tag,
                $data['name'],
                $data['slug'],
            ),
            'Tag updated.',
        );
    }

    public function destroyTag(
        int $blog,
        int $tag,
        Request $request,
        ManageTaxonomyService $service,
    ): RedirectResponse {
        return $this->run(
            $blog,
            fn () => $service->deleteTag((int) $request->user()->id, $blog, $tag),
            'Tag deleted.',
        );
    }

    /**
     * @return array<string, list<string>>
     */
    private function categoryRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'slug' => self::SLUG,
            'description' => ['nullable', 'string', 'max:1000'],
            'parent_id' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function validationMessages(): array
    {
        return [
            'name.required' => 'Nhập tên để người đọc nhận biết chuyên mục hoặc thẻ này.',
            'name.max' => 'Tên tối đa 160 ký tự. Hãy rút gọn tên rồi thử lại.',
            'slug.required' => 'Slug là phần cuối của đường dẫn. Hãy nhập slug hoặc dùng slug tự tạo từ tên.',
            'slug.regex' => 'Slug chỉ gồm chữ thường không dấu, số và dấu gạch ngang; ví dụ: mon-chay.',
            'slug.max' => 'Slug tối đa 160 ký tự. Hãy rút gọn slug rồi thử lại.',
            'parent_id.integer' => 'Chuyên mục cha không hợp lệ. Hãy chọn lại từ danh sách.',
        ];
    }

    private function run(int $blog, callable $action, string $message): RedirectResponse
    {
        try {
            $action();
        } catch (BlogNotFound) {
            abort(404);
        } catch (DomainException $exception) {
            return back()
                ->withErrors(['general' => $exception->getMessage()])
                ->withInput();
        }

        return redirect()->route('blogs.taxonomy', $blog)->with('status', $message);
    }
}
