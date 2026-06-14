<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function index(Request $request): View
    {
        $articles = Article::query()
            ->published()
            ->when(
                $request->filled('category') && in_array($request->string('category')->toString(), Article::categoryValues(), true),
                fn ($query) => $query->where('category', $request->string('category')->toString()),
            )
            ->latest('published_at')
            ->paginate(9)
            ->withQueryString();

        return view('articles.index', [
            'articles' => $articles,
            'categories' => Article::categories(),
        ]);
    }

    public function show(Article $article): View
    {
        abort_unless($article->isPublished(), 404);

        $relatedArticles = Article::query()
            ->published()
            ->whereKeyNot($article->id)
            ->where('category', $article->category)
            ->latest('published_at')
            ->limit(3)
            ->get();

        return view('articles.show', [
            'article' => $article,
            'relatedArticles' => $relatedArticles,
        ]);
    }
}
