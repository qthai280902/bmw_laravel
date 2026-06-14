<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreArticleRequest;
use App\Http\Requests\UpdateArticleRequest;
use App\Models\Article;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function index(Request $request): View
    {
        $articles = Article::query()
            ->with('user')
            ->when(
                $request->filled('status') && in_array($request->string('status')->toString(), Article::statusValues(), true),
                fn ($query) => $query->where('status', $request->string('status')->toString()),
            )
            ->when(
                $request->filled('category') && in_array($request->string('category')->toString(), Article::categoryValues(), true),
                fn ($query) => $query->where('category', $request->string('category')->toString()),
            )
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->toString();

                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('title', 'like', '%'.$search.'%')
                        ->orWhere('excerpt', 'like', '%'.$search.'%');
                });
            })
            ->latest('updated_at')
            ->paginate(12)
            ->withQueryString();

        return view('admin.articles.index', [
            'articles' => $articles,
            'categories' => Article::categories(),
            'statuses' => Article::statuses(),
        ]);
    }

    public function create(): View
    {
        return view('admin.articles.create', [
            'article' => new Article([
                'status' => Article::STATUS_DRAFT,
                'category' => Article::CATEGORY_OFFERS,
            ]),
            'categories' => Article::categories(),
            'statuses' => Article::statuses(),
        ]);
    }

    public function store(StoreArticleRequest $request): RedirectResponse
    {
        $data = $this->prepareArticleData($request->validated());
        $data['user_id'] = $request->user()?->id;

        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $request->file('cover_image')->store('articles', 'public');
        }

        $article = Article::create($data);

        return redirect()
            ->route('admin.articles.edit', $article)
            ->with('success', 'Bài viết đã được tạo thành công.');
    }

    public function edit(Article $article): View
    {
        return view('admin.articles.edit', [
            'article' => $article,
            'categories' => Article::categories(),
            'statuses' => Article::statuses(),
        ]);
    }

    public function update(UpdateArticleRequest $request, Article $article): RedirectResponse
    {
        $data = $this->prepareArticleData($request->validated(), $article);

        if ($request->hasFile('cover_image')) {
            $this->deleteCoverImage($article);
            $data['cover_image'] = $request->file('cover_image')->store('articles', 'public');
        }

        $article->update($data);

        return redirect()
            ->route('admin.articles.edit', $article)
            ->with('success', 'Bài viết đã được cập nhật.');
    }

    public function destroy(Article $article): RedirectResponse
    {
        $this->deleteCoverImage($article);
        $article->delete();

        return redirect()
            ->route('admin.articles.index')
            ->with('success', 'Bài viết đã được xóa.');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function prepareArticleData(array $data, ?Article $article = null): array
    {
        $data['slug'] = filled($data['slug'] ?? null)
            ? Str::slug((string) $data['slug'])
            : Article::generateUniqueSlug((string) $data['title'], $article?->id);

        if ($article && $data['slug'] !== $article->slug) {
            $data['slug'] = Article::generateUniqueSlug($data['slug'], $article->id);
        }

        if ($data['status'] === Article::STATUS_PUBLISHED && blank($data['published_at'] ?? null)) {
            $data['published_at'] = now();
        }

        if ($data['status'] === Article::STATUS_DRAFT) {
            $data['published_at'] = null;
        }

        return $data;
    }

    private function deleteCoverImage(Article $article): void
    {
        if (filled($article->cover_image) && Storage::disk('public')->exists($article->cover_image)) {
            Storage::disk('public')->delete($article->cover_image);
        }
    }
}
