<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class EmployeeController extends Controller
{

    public function index(Request $request)
    {
        $query = Employee::query();

        if ($request->filled('search')) {
            $search = $request->search;

            $columns = Schema::getColumnListing('employees');

            $query->where(function ($q) use ($columns, $search) {
                foreach ($columns as $column) {
                    $q->orWhere($column, 'like', "%{$search}%");
                }
            });

            $data = $query->latest()->paginate($request->input('per_page', 10));

            return response()->json($data, 200);
        }


        $data = Employee::latest()->paginate($request->input('per_page', 10));

        $data->getCollection()->transform(function ($employee) {
            return [
                'id' => $employee->id,
                'is_active' => $employee->is_active,
                'name' => $employee->name,
                'code' => $employee->code,
                'branch' => optional($employee->branch)->name,
            ];
        });

        return response()->json($data, 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'is_active' => 'nullable',
            'name' => 'string|required',
            'code' => 'string|required',
            'branch_id' => 'required|integer'
        ]);

        if (Employee::where('code', $request->input('code'))->exists()) {
            return response()->json(['message' => 'عفواً، يوجد موظف مسجل بهذا الكود بالفعل.'], 422);
        }

        Employee::create($request->all());

        return response()->json(['message' => 'تم إضافة الموظف بنجاح.'], 201);
    }

    public function show(string $id)
    {
        $data = Employee::findOrFail($id);

        return response()->json([
            'data' => $data
        ], 200);
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'is_active' => 'nullable',
            'name' => 'string|required',
            'code' => 'string|required',
            'branch_id' => 'nullable|integer'
        ]);

        $data = Employee::findOrFail($id);

        if (Employee::where('code', $request->input('code'))->where('id', '!=', $id)->exists()) {
            return response()->json(['message' => 'عفواً، يوجد موظف مسجل بهذا الكود بالفعل.'], 422);
        }

        $data->update($request->all());

        return response(['message' => 'تم تحديث بيانات الموظف بنجاح.'], 200);
    }

    public function destroy(string $id)
    {
        Employee::destroy($id);

        return response()->json(['message' => 'تم حذف الموظف بنجاح.'], 200);
    }

    public function all()
    {
        $data = Employee::all();

        return response()->json(['data' => $data], 200);
    }

    public function filter(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|integer',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        $employee = Employee::findOrFail($request->input('employee_id'));

        // Filter reviews in date range
        $reviews = $employee->reviews()
            ->whereBetween('created_at', [
                $request->input('start_date'),
                $request->input('end_date')
            ])
            ->get();

        // Count reviews by rating
        $ratingsCount = [
            '1' => $reviews->where('value', 1)->count(),
            '2' => $reviews->where('value', 2)->count(),
            '3' => $reviews->where('value', 3)->count(),
            '4' => $reviews->where('value', 4)->count(),
        ];

        // Collect all notes
        $notes = $reviews->pluck('notes')->filter()->values();

        return response()->json([
            'ratings' => $ratingsCount,
            'notes' => $notes,
        ]);
    }

    // Branch employees
    public function employees_branch(string $id)
    {
        $employees = Employee::where('branch_id', $id)->where('is_active', true)->get();

        return response()->json(['data' => $employees], 200);
    }
}
