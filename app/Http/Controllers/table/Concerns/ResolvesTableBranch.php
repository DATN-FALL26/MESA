<?php

declare(strict_types=1);

namespace App\Http\Controllers\table\Concerns;

use App\Enums\BranchStatus;
use App\Enums\ScopeType;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

trait ResolvesTableBranch
{
    /**
     * Resolve the current branch ID from request parameter, session, authenticated user's role scope,
     * or fallback to the first active branch in the system.
     */
    protected function resolveBranchId(Request $request): int
    {
        // 1. Explicit request input/query
        if ($request->filled('branch_id')) {
            $branchId = (int) $request->input('branch_id');
            if ($branchId > 0 && Branch::query()->whereKey($branchId)->exists()) {
                $request->session()->put('branch_id', $branchId);

                return $branchId;
            }
        }

        // 2. From session if already set and valid
        $sessionBranchId = (int) $request->session()->get('branch_id');
        if ($sessionBranchId > 0 && Branch::query()->whereKey($sessionBranchId)->exists()) {
            return $sessionBranchId;
        }

        // 3. From user's assigned branch in role scope
        $user = $request->user();
        if ($user !== null) {
            $userBranchId = $user->userRoles()
                ->where('scope_type', ScopeType::BRANCH)
                ->where('valid_from', '<=', now())
                ->where(fn ($query) => $query->whereNull('valid_to')->orWhere('valid_to', '>=', now()))
                ->value('scope_id');

            if ($userBranchId !== null && Branch::query()->whereKey((int) $userBranchId)->exists()) {
                $branchId = (int) $userBranchId;
                $request->session()->put('branch_id', $branchId);

                return $branchId;
            }
        }

        // 4. Fallback to the first active branch
        $defaultBranchId = Branch::query()
            ->where('status', BranchStatus::ACTIVE)
            ->orderBy('id')
            ->value('id');

        if ($defaultBranchId !== null) {
            $branchId = (int) $defaultBranchId;
            $request->session()->put('branch_id', $branchId);

            return $branchId;
        }

        // 5. Ultimate fallback
        return 1;
    }

    /**
     * Get active branches accessible by the current user.
     *
     * @return Collection<int, Branch>
     */
    protected function accessibleBranches(Request $request): Collection
    {
        $user = $request->user();

        if ($user !== null && $user->userRoles()->where('scope_type', ScopeType::ALL)->exists()) {
            return Branch::query()
                ->where('status', BranchStatus::ACTIVE)
                ->orderBy('name')
                ->get();
        }

        if ($user !== null) {
            $branchIds = $user->userRoles()
                ->where('scope_type', ScopeType::BRANCH)
                ->pluck('scope_id')
                ->filter()
                ->unique()
                ->all();

            if (! empty($branchIds)) {
                return Branch::query()
                    ->where('status', BranchStatus::ACTIVE)
                    ->whereIn('id', $branchIds)
                    ->orderBy('name')
                    ->get();
            }
        }

        return Branch::query()
            ->where('status', BranchStatus::ACTIVE)
            ->orderBy('name')
            ->get();
    }
}
