<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithBmwPagination;
use App\Models\Article;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

class ArticleHub extends Component
{
    use WithBmwPagination;

    #[Url(as: 'category', except: '')]
    public string $category = '';

    #[Url(as: 'search', except: '')]
    public string $search = '';

    public function mount(): void
    {
        if ($this->category !== '' && ! in_array($this->category, Article::categoryValues(), true)) {
            $this->category = '';
        }
    }

    public function selectCategory(string $category = ''): void
    {
        $this->category = in_array($category, Article::categoryValues(), true) ? $category : '';
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->category = '';
        $this->search = '';
        $this->resetPage();
    }

    public function render(): View
    {
        $activeCategory = in_array($this->category, Article::categoryValues(), true) ? $this->category : '';
        $keyword = trim($this->search);

        $articles = Article::query()
            ->published()
            ->when(
                $activeCategory !== '',
                fn ($query) => $query->where('category', $activeCategory),
            )
            ->when(
                $keyword !== '',
                function ($query) use ($keyword): void {
                    $query->where(function ($subQuery) use ($keyword): void {
                        $subQuery->where('title', 'like', "%{$keyword}%")
                            ->orWhere('excerpt', 'like', "%{$keyword}%")
                            ->orWhere('body', 'like', "%{$keyword}%");
                    });
                },
            )
            ->latest('published_at')
            ->paginate(9);

        return view('livewire.article-hub', [
            'articles' => $articles,
            'categories' => Article::categories(),
            'activeCategory' => $activeCategory,
        ]);
    }
}
