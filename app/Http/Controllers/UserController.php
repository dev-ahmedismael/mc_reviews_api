<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class UserController extends Controller
{
    public function login(Request $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'البريد الإلكتروني الذي أدخلته غير مسجل لدينا.'], 404);
        }

        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'كلمة المرور غير صحيحة.'], 401);
        }

        $token = auth()->login($user);
        return response()->json([
            'message' => 'تم تسجيل الدخول بنجاح.',
            'token'   => $token,
        ], 200);
    }

    public function logout(Request $request)
    {
        Auth::guard('api')->logout();



        return response()->json(['message' => 'تم تسجيل الخروج بنجاح.']);
    }

    public function change_password(Request $request)
    {
        $request->validate([
            'current_password' => ['required'],
            'new_password' => ['required', 'min:8',],
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'كلمة المرور الحالية غير صحيحة.'], 422);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return response()->json(['message' => 'تم تغيير كلمة المرور بنجاح.']);
    }

    public function edit_profile(Request $request)
    {
        $request->validate(
            [
                'name' => 'required|string',
                'email' => 'required|email|unique:users,email,' . Auth::id(),
            ]
        );

        $user = Auth::user();

        $found_user  = User::findOrFail($user->id);

        $found_user->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        return response()->json([
            'message' => 'تم تحديث الملف الشخصي بنجاح.',
            'user' => $user,
        ]);
    }

    public function authenticated_user()
    {
        $user = Auth::user();
        return response()->json($user, 200);
    }

    public function index(Request $request)
    {
        $query = User::query();

        if ($request->filled('search')) {
            $search = $request->search;

            $columns = Schema::getColumnListing('users');

            $query->where(function ($q) use ($columns, $search) {
                foreach ($columns as $column) {
                    $q->orWhere($column, 'like', "%{$search}%");
                }
            });

            $data = $query->latest()->paginate($request->input('per_page', 10));

            return response()->json($data, 200);
        }


        $data = User::latest()->paginate($request->input('per_page', 10));

        $data->getCollection()->transform(function ($employee) {
            $role = $employee->getRoleNames()->first();

            $translatedRole = match ($role) {
                'admin' => 'مدير',
                'supervisor' => 'مشرف',
                default => $role,
            };

            return [
                'id' => $employee->id,
                'name' => $employee->name,
                'email' => $employee->email,
                'role' => $translatedRole,
            ];
        });


        return response()->json($data, 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|string'
        ]);

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

        $user->assignRole($request->input('role'));

        return response()->json(['message' => 'تم إنشاء المستخدم بنجاح.', 'data' => $user], 201);
    }

    public function show(string $id)
    {
        $data = User::findOrFail($id);

        return response()->json([
            'data' => $data
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8',
            'role' => 'nullable|string'
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        if ($request->filled('role')) {
            $user->syncRoles([$request->input('role')]);
        }

        return response()->json(['message' => 'تم تحديث بيانات المستخدم بنجاح.', 'data' => $user], 200);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if ($user->id == 1) {
            return response()->json(['message' => 'عفواً، لا يمكن حذف حساب المالك.'], 422);
        }

        User::destroy($id);

        return response()->json(['message' => 'تم حذف المستخدم بنجاح.'], 200);
    }
}
