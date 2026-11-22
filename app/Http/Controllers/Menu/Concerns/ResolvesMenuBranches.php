<?php

declare(strict_types=1);

namespace App\Http\Controllers\Menu\Concerns;

use App\Enums\BranchStatus;
use App\Helpers\ConstantHelper;
use App\Http\Controllers\Kitchen\Concerns\ResolvesBranchScope;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Chi nhánh đang hoạt động mà người dùng được thao tác trong module Thực đơn.
 * Dùng lại cách tính phạm vi (ALL / BRANCH) của module Bếp để hai module hành xử giống nhau.
 */
trait ResolvesMenuBranches
{
    use ResolvesBranchScope;

    /**
     * @param  list<string>  $permissions  Quyền nào có (bất kỳ) thì chi nhánh theo phạm vi của vai trò đó được tính
     * @return Collection<int, Branch>
     */
    protected function accessibleBranches(User $user, array $permissions = [ConstantHelper::PERM_MENU_VIEW]): Collection
    {
        $allowedIds = [];

        foreach ($permissions as $permission) {
            $ids = $this->accessibleBranchIds($user, $permission);

            if ($ids === null) {
                // Có vai trò phạm vi toàn chuỗi cho quyền này.
                return Branch::query()->where('status', BranchStatus::ACTIVE)->orderBy('name')->get();
            }

            $allowedIds = array_merge($allowedIds, $ids);
        }

        return Branch::query()
            ->where('status', BranchStatus::ACTIVE)
            ->whereIn('id', array_values(array_unique($allowedIds)))
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  list<string>  $permissions
     */
    protected function authorizeBranch(User $user, Branch $branch, array $permissions = [ConstantHelper::PERM_MENU_VIEW]): void
    {
        abort_unless(
            $this->accessibleBranches($user, $permissions)->contains('id', $branch->getKey()),
            403,
            'Bạn không có quyền thao tác trên chi nhánh này.'
        );
    }
}
