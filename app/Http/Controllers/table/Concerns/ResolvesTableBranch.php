<?php

declare(strict_types=1);

namespace App\Http\Controllers\table\Concerns;

use App\Enums\BranchStatus;
use App\Helpers\ConstantHelper;
use App\Models\Branch;
use App\Models\User;
use App\Services\Auth\BranchAccessService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

trait ResolvesTableBranch
{
    protected BranchAccessService $branchAccess;

    protected function resolveBranchId(Request $request, bool $required = true): ?int
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        if ($request->filled('branch_id')) {
            $branchId = $request->integer('branch_id');
            $this->branchAccess->authorizeBranch($user, $branchId, [ConstantHelper::PERM_TABLE_VIEW]);
            $request->session()->put('branch_id', $branchId);

            return $branchId;
        }

        $sessionBranchId = $request->session()->get('branch_id');
        if (is_numeric($sessionBranchId)) {
            $sessionBranchId = (int) $sessionBranchId;
            if ($this->branchAccess->canAccessBranch($user, $sessionBranchId, [ConstantHelper::PERM_TABLE_VIEW])) {
                return $sessionBranchId;
            }

            $request->session()->forget('branch_id');
        }

        $branches = $this->accessibleBranches($request);
        if ($branches->count() === 1) {
            $branchId = (int) $branches->first()->getKey();
            $request->session()->put('branch_id', $branchId);

            return $branchId;
        }

        if ($required) {
            abort(422, 'Vui lòng chọn chi nhánh trước khi thao tác.');
        }

        return null;
    }

    protected function requireBranchId(Request $request): int
    {
        return $this->resolveBranchId($request) ?? abort(422, 'Vui lòng chọn chi nhánh trước khi thao tác.');
    }

    /**
     * @return Collection<int, Branch>
     */
    protected function accessibleBranches(Request $request): Collection
    {
        $branchIds = $this->branchAccess->accessibleBranchIds(
            $request->user(),
            [ConstantHelper::PERM_TABLE_VIEW]
        );

        return Branch::query()
            ->where('status', BranchStatus::ACTIVE)
            ->when(is_array($branchIds), fn ($query) => $query->whereIn('id', $branchIds))
            ->orderBy('name')
            ->get();
    }
}
