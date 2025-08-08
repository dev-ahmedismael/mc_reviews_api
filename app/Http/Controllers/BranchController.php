<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $query = Branch::query();

        if ($request->filled('search')) {
            $search = $request->search;

            $columns = Schema::getColumnListing('branches');

            $query->where(function ($q) use ($columns, $search) {
                foreach ($columns as $column) {
                    $q->orWhere($column, 'like', "%{$search}%");
                }
            });

            $data = $query->latest()->paginate($request->input('per_page', 10));

            return response()->json($data, 200);
        }


        $data = Branch::latest()->paginate($request->input('per_page', 10));

        return response()->json($data, 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'string|required',
            'domain' => 'string|required'
        ]);

        if (Branch::where('domain', $request->input('domain'))->exists()) {
            return response()->json(['message' => 'الرابط الذي أدخلته مستخدم بالفعل.'], 400);
        }
        Branch::create($request->all());

        return response()->json(['message' => 'تم حفظ بيانات الفرع بنجاح.'], 201);
    }

    public function show(string $id)
    {
        $data = Branch::findOrFail($id);

        return response()->json([
            'data' => $data
        ], 200);
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'name' => 'required|string',
            'domain' => [
                'required',
                'string',
                Rule::unique('branches', 'domain')->ignore($id),
            ],
        ], [
            'domain.unique' => 'هذا النطاق مستخدم من قبل فرع آخر.',
        ]);

        $data = Branch::findOrFail($id);

        $data->update($request->all());

        return response(['message' => 'تم تحديث بيانات الفرع بنجاح.'], 200);
    }

    public function destroy(string $id)
    {
        Branch::destroy($id);

        return response()->json(['message' => 'تم حذف الفرع بنجاح.'], 200);
    }
}
