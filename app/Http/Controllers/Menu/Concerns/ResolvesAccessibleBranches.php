<?php

declare(strict_types=1);

namespace App\Http\Controllers\Menu\Concerns;

use App\Enums\BranchStatus;
use App\Enums\ScopeType;
use App\Models\Branch;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Eloquent\Collection;

trait ResolvesAccessibleBranches
{
    /**
     * Chi nhánh đang hoạt động mà người dùng được phép thao tác.
     * Vai trò phạm vi ALL thấy toàn chuỗi; phạm vi BRANCH chỉ thấy chi nhánh được gán.
     *
     * @return Collection<int, Branch>
     */
    protected function accessibleBranches(User $user): Collection
    {
        $scopes = UserRole::query()
            ->where('user_id', $user->getKey())
            ->where('valid_from', '<=', now())
            ->where(fn ($query) => $query->whereNull('valid_to')->orWhere('valid_to', '>=', now()))
            ->get(['scope_type', 'scope_id']);

        $query = Branch::query()
            ->where('status', BranchStatus::ACTIVE)
            ->orderBy('name');

        $hasAllScope = $scopes->contains(
            static fn (UserRole $role): bool => $role->scope_type === ScopeType::ALL
        );

        if (! $hasAllScope) {
            $query->whereIn(
                'id',
                $scopes->filter(static fn (UserRole $role): bool => $role->scope_type === ScopeType::BRANCH)
                    ->pluck('scope_id')
                    ->filter()
                    ->all()
            );
        }

        return $query->get();
    }

    protected function authorizeBranch(User $user, Branch $branch): void
    {
        abort_unless(
            $this->accessibleBranches($user)->contains('id', $branch->getKey()),
            403,
            'Bạn không có quyền thao tác trên chi nhánh này.'
        );
    }
}
