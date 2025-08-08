<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Category::query();

        if ($request->filled('search')) {
            $search = $request->search;

            $columns = Schema::getColumnListing('categories');

            $query->where(function ($q) use ($columns, $search) {
                foreach ($columns as $column) {
                    $q->orWhere($column, 'like', "%{$search}%");
                }
            });

            $data = $query->latest()->paginate($request->input('per_page', 10));

            return response()->json($data, 200);
        }


        $data = Category::latest()->paginate($request->input('per_page', 10));

        return response()->json($data, 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
        ]);

        Category::create($request->all());

        return response()->json(['message' => 'تم إضافة القسم بنجاح.'], 201);
    }

    public function show(string $id)
    {
        $data = Category::findOrFail($id);

        return response()->json([
            'data' => $data
        ], 200);
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'title' => 'required|string',
        ]);

        $data = Category::findOrFail($id);

        $data->update($request->all());

        return response(['message' => 'تم تحديث بيانات القسم بنجاح.'], 200);
    }

    public function destroy(string $id)
    {
        Category::destroy($id);

        return response()->json(['message' => 'تم حذف القسم بنجاح.'], 200);
    }
}
