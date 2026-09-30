<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    // Hiển thị danh sách toàn bộ tài khoản
    public function index()
    {
        $users = User::paginate(10);
        return view('admin.users.index', compact('users'));
    }

    // Cập nhật quyền cho user trực tiếp từ web
    public function updateRole(Request $request, User $user)
    {
        $request->validate([
            'role' => 'required|in:ceo,manager,cashier,chef,staff,customer',
        ]);

        $user->update([
            'role' => $request->role,
        ]);

        return redirect()->back()->with('success', 'Đã cập nhật quyền thành công cho tài khoản ' . $user->name);
    }
}