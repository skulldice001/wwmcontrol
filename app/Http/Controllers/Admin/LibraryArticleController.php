<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LibraryArticle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LibraryArticleController extends Controller
{
    public function index(Request $request)
    {
        $query = LibraryArticle::withTrashed(false)->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(fn($qb) => $qb->where('title', 'ilike', "%{$q}%")
                                        ->orWhere('content', 'ilike', "%{$q}%"));
        }

        $articles  = $query->paginate(20)->withQueryString();
        $counts    = [
            'all'       => LibraryArticle::count(),
            'draft'     => LibraryArticle::where('status', 'draft')->count(),
            'published' => LibraryArticle::where('status', 'published')->count(),
        ];

        return view('admin.library.index', compact('articles', 'counts'));
    }

    public function create()
    {
        return view('admin.library.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'    => 'required|string|max:255',
            'category' => 'required|in:' . implode(',', array_keys(LibraryArticle::CATEGORIES)),
            'content'  => 'required|string',
            'excerpt'  => 'nullable|string|max:500',
        ]);

        $data['created_by'] = Auth::guard('staff')->id();
        $data['status']     = 'draft';

        LibraryArticle::create($data);

        return redirect()->route('admin.library.index')->with('success', 'Bài viết đã được tạo.');
    }

    public function edit(LibraryArticle $library)
    {
        return view('admin.library.edit', ['article' => $library]);
    }

    public function update(Request $request, LibraryArticle $library)
    {
        $data = $request->validate([
            'title'    => 'required|string|max:255',
            'category' => 'required|in:' . implode(',', array_keys(LibraryArticle::CATEGORIES)),
            'content'  => 'required|string',
            'excerpt'  => 'nullable|string|max:500',
        ]);

        $library->update($data);

        return back()->with('success', 'Đã lưu thay đổi.');
    }

    public function destroy(LibraryArticle $library)
    {
        $library->delete();
        return redirect()->route('admin.library.index')->with('success', 'Đã xoá bài viết.');
    }

    public function publish(LibraryArticle $library)
    {
        $library->update([
            'status'       => 'published',
            'published_at' => $library->published_at ?? now(),
        ]);

        return back()->with('success', 'Đã xuất bản bài viết.');
    }

    public function unpublish(LibraryArticle $library)
    {
        $library->update(['status' => 'draft']);
        return back()->with('success', 'Đã chuyển về bản nháp.');
    }
}
