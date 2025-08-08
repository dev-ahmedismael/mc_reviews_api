<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $query = Post::query();

        if ($request->filled('search')) {
            $search = $request->search;

            $columns = Schema::getColumnListing('posts');

            $query->where(function ($q) use ($columns, $search) {
                foreach ($columns as $column) {
                    $q->orWhere($column, 'like', "%{$search}%");
                }
            });

            $data = $query->with('branch')
                ->latest()
                ->paginate($request->input('per_page', 10));

            $data->getCollection()->transform(function ($post) {
                $post->branch_name = $post->branch ? $post->branch->name : null;
                return $post;
            });

            return response()->json($data, 200);
        }

        $data = Post::with('branch')
            ->latest()
            ->paginate($request->input('per_page', 10));

        $data->getCollection()->transform(function ($post) {
            $post->branch_name = $post->branch ? $post->branch->name : null;
            return $post;
        });

        return response()->json($data, 200);
    }


    public function store(Request $request)
    {
        $request->validate([
            'branch_id' => 'required',
            'post' => 'string|required',
        ]);

        Post::create($request->all());

        return response()->json(['message' => 'تم إضافة الخبر بنجاح.'], 201);
    }

    public function show(string $id)
    {
        $data = Post::findOrFail($id);

        return response()->json([
            'data' => $data
        ], 200);
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'branch_id' => 'required',
            'post' => 'required|string'
        ]);

        $data = Post::findOrFail($id);

        $data->update($request->all());

        return response(['message' => 'تم تحديث الخبر بنجاح.'], 200);
    }

    public function destroy(string $id)
    {
        Post::destroy($id);

        return response()->json(['message' => 'تم حذف الخبر بنجاح.'], 200);
    }
}
