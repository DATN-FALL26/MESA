<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\BranchStatus;
use App\Models\Branch;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PublicDashboardController extends Controller
{
    public function show(): View|RedirectResponse
    {
        if (auth()->check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('customer.dashboard', [
            'branches' => Branch::query()
                ->where('status', BranchStatus::ACTIVE)
                ->orderBy('name')
                ->get(),
        ]);
    }
}
