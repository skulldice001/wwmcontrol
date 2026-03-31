<?php

namespace App\Http\Controllers;

use App\Models\LibraryArticle;
use Illuminate\Http\Request;

class LibraryController extends Controller
{
    /** GET /library */
    public function index(Request $request)
    {
        $search = $request->get('search', '');

        if ($search) {
            $articles = LibraryArticle::published()
                ->where(fn($q) => $q->where('title', 'ilike', "%{$search}%")
                                    ->orWhere('content', 'ilike', "%{$search}%"))
                ->latest('published_at')
                ->paginate(12)
                ->withQueryString();

            return view('library.search', compact('articles', 'search'));
        }

        $counts = [];
        foreach (array_keys(LibraryArticle::CATEGORIES) as $cat) {
            $counts[$cat] = LibraryArticle::published()->where('category', $cat)->count();
        }

        return view('library.index', compact('counts'));
    }

    /** GET /library/{category} */
    public function category(string $category)
    {
        abort_unless(array_key_exists($category, LibraryArticle::CATEGORIES), 404);

        $articles = LibraryArticle::published()
            ->where('category', $category)
            ->latest('published_at')
            ->paginate(12);

        return view('library.category', compact('articles', 'category'));
    }

    /** GET /library/{category}/{article} */
    public function show(string $category, LibraryArticle $article)
    {
        abort_unless($article->isPublished() && $article->category === $category, 404);
        return view('library.show', compact('article', 'category'));
    }
}
