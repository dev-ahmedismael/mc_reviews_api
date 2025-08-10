<?php

namespace App\Http\Controllers;

use App\Http\Requests\PositionRequest;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class PositionController extends Controller
{
    public function all()
    {
        $positions = Position::with('employees')->get();

        return response()->json(['data' => $positions]);
    }


    public function index(Request $request)
    {
        $query = Position::query();

        if ($request->filled('search')) {
            $search = $request->search;

            $columns = Schema::getColumnListing('positions');

            $query->where(function ($q) use ($columns, $search) {
                foreach ($columns as $column) {
                    $q->orWhere($column, 'like', "%{$search}%");
                }
            });

            $data = $query->latest()->paginate($request->input('per_page', 10));

            return response()->json($data, 200);
        }

        $data = Position::latest()->paginate($request->input('per_page', 10));

        $data->getCollection()->transform(function ($position) {
            return [
                'id' => $position->id,
                'name' => $position->name,
            ];
        });

        return response()->json($data, 200);
    }

    public function store(PositionRequest $request)
    {
        $validated = $request->validated();

        Position::create($validated);

        return response()->json(['message' => 'تم إضافة الوظيفة بنجاح.']);
    }

    public function update(PositionRequest $request, string $id)
    {
        $validated = $request->validated();

        $position = Position::findOrFail($id);

        $position->update($validated);

        return response()->json(['message' => 'تم تحديث بيانات الوظيفة بنجاح.']);
    }

    public function Show(string $id)
    {
        $position = Position::with('employees')->findOrFail($id);

        return response()->json(['data' => $position]);
    }

    public function destroy(string $id)
    {
        Position::destroy($id);

        return response()->json(['message' => 'تم حذف الوظيفة بنجاح.']);
    }
}
