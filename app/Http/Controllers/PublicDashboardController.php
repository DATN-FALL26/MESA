<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\BranchStatus;
use App\Helpers\ConstantHelper;
use App\Models\Branch;
use Illuminate\View\View;

class PublicDashboardController extends Controller
{
    public function show(): View
    {
        if (auth()->check()) {
            abort_unless(auth()->user()->hasPermission(ConstantHelper::PERM_DASHBOARD_VIEW), 403);

            return view('dashboard');
        }

        return view('customer.dashboard', [
            'branches' => Branch::query()
                ->where('status', BranchStatus::ACTIVE)
                ->orderBy('name')
                ->get(),
        ]);
    }
}
