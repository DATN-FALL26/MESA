<?php

declare(strict_types=1);

namespace App\Http\Controllers\Kitchen\Concerns;

use App\Enums\ScopeType;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Eloquent\Builder;

/**
 * Xác định chi nhánh người dùng được thao tác dựa trên phạm vi (scope) của vai trò mang quyền tương ứng.
 */
trait ResolvesBranchScope
{
    /**
     * Trả về danh sách chi nhánh, hoặc null nếu được thao tác toàn chuỗi.
     *
     * @return list<int>|null
     */
    protected function accessibleBranchIds(User $user, string $permission): ?array
    {
        $userRoles = $user->userRoles()
            ->where('valid_from', '<=', now())
            ->where(fn (Builder $query) => $query->whereNull('valid_to')->orWhere('valid_to', '>=', now()))
            ->whereHas('role.permissions', fn (Builder $query) => $query->where('permissions.code', $permission))
            ->get(['scope_type', 'scope_id']);

        if ($userRoles->contains(fn (UserRole $userRole): bool => $userRole->scope_type === ScopeType::ALL)) {
            return null;
        }

        return $userRoles
            ->where('scope_type', ScopeType::BRANCH)
            ->pluck('scope_id')
            ->map(fn (mixed $branchId): int => (int) $branchId)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  list<int>|null  $branchIds
     */
    protected function abortUnlessBranchAccessible(?array $branchIds, int $branchId): void
    {
        abort_unless($branchIds === null || in_array($branchId, $branchIds, true), 403);
    }
}
