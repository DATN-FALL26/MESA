<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        // dd($user->role, $roles);
        // Kiểm tra quyền
        if (in_array($user->role, $roles)) {
            return $next($request);
        }

        abort(403, 'Cấm truy cập! Bạn không có quyền hạn vào khu vực này.');
    }
}
