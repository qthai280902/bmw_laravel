<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function index(): View
    {
        return view('articles.index');
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
