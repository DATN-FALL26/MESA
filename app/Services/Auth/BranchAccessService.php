<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Enums\BranchStatus;
use App\Enums\ScopeType;
use App\Helpers\ConstantHelper;
use App\Models\Branch;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Eloquent\Builder;

class BranchAccessService
{
    /**
     * @param  list<string>|null  $permissions
     * @return list<int>|null Null means access to every active branch.
     */
    public function accessibleBranchIds(User $user, ?array $permissions = null): ?array
    {
        $assignments = $user->userRoles()
            ->where('valid_from', '<=', now())
            ->where(fn (Builder $query) => $query
                ->whereNull('valid_to')
                ->orWhere('valid_to', '>=', now()))
            ->when($permissions !== null && $permissions !== [], fn (Builder $query) => $query
                ->whereHas('role.permissions', fn (Builder $permissionQuery) => $permissionQuery
                    ->whereIn('permissions.code', $permissions)))
            ->with('role:id,code')
            ->get(['role_id', 'scope_type', 'scope_id']);

        if ($assignments->contains(
            fn (UserRole $assignment): bool => $assignment->scope_type === ScopeType::ALL
                && $assignment->role?->code === ConstantHelper::ROLE_ADMIN
        )) {
            return null;
        }

        $branchIds = $assignments
            ->where('scope_type', ScopeType::BRANCH)
            ->pluck('scope_id')
            ->map(fn (mixed $branchId): int => (int) $branchId)
            ->unique()
            ->values()
            ->all();

        if ($branchIds === []) {
            return [];
        }

        return Branch::query()
            ->where('status', BranchStatus::ACTIVE)
            ->whereIn('id', $branchIds)
            ->pluck('id')
            ->map(fn (mixed $branchId): int => (int) $branchId)
            ->all();
    }

    /**
     * @param  list<string>|null  $permissions
     */
    public function canAccessBranch(User $user, int $branchId, ?array $permissions = null): bool
    {
        if (! Branch::query()
            ->whereKey($branchId)
            ->where('status', BranchStatus::ACTIVE)
            ->exists()) {
            return false;
        }

        $branchIds = $this->accessibleBranchIds($user, $permissions);

        return $branchIds === null || in_array($branchId, $branchIds, true);
    }

    /**
     * @param  list<string>|null  $permissions
     */
    public function authorizeBranch(User $user, int $branchId, ?array $permissions = null): void
    {
        abort_unless($this->canAccessBranch($user, $branchId, $permissions), 404);
    }
}
