<?php

declare(strict_types=1);

namespace App\Http\Controllers\table;

use App\Enums\SessionStatus;
use App\Enums\TableStatus;
use App\Enums\UserStatus;
use App\Helpers\ConstantHelper;
use App\Http\Controllers\table\Concerns\ResolvesTableBranch;
use App\Models\AuditLog;
use App\Models\DiningSession;
use App\Models\DiningTable;
use App\Models\Role;
use App\Models\SessionTable;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DiningSessionController extends BaseController
{
    use ResolvesTableBranch;
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'table_id' => ['required', 'integer'],
            'guest_count' => ['required', 'integer', 'min:1', 'max:32767'],
            'opened_by' => [
                'nullable',
                'integer',
                Rule::exists((new User)->getTable(), 'id')->where('status', UserStatus::ACTIVE->value),
            ],
        ]);

        $branchId = $this->resolveBranchId($request);
        $table = DiningTable::query()
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->findOrFail($validated['table_id']);

        $openedBy = $validated['opened_by'] ?? Auth::id();
        if ($openedBy === null) {
            abort(401);
        }

        if (isset($validated['opened_by']) && ! $this->activeBranchWaiters($branchId)
            ->whereKey($validated['opened_by'])
            ->exists()) {
            throw ValidationException::withMessages([
                'opened_by' => 'Nhân viên phải đang hoạt động và thuộc chi nhánh hiện tại.',
            ]);
        }

        $session = DB::transaction(function () use ($table, $branchId, $openedBy, $validated): DiningSession|JsonResponse {
            $lockedTable = DiningTable::query()
                ->where('branch_id', $branchId)
                ->whereKey($table->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $hasOpenSession = SessionTable::query()
                ->where('table_id', $lockedTable->getKey())
                ->whereNull('left_at')
                ->whereIn('session_id', DiningSession::query()
                    ->select('id')
                    ->where('status', SessionStatus::OPEN))
                ->exists();

            if ($lockedTable->status !== TableStatus::AVAILABLE || $hasOpenSession) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bàn này không còn trống để mở phiên.',
                ], 409);
            }

            $session = DiningSession::query()->create([
                'branch_id' => $branchId,
                'opened_by' => $openedBy,
                'opened_at' => now(),
                'guest_count' => $validated['guest_count'],
                'status' => SessionStatus::OPEN,
            ]);

            SessionTable::query()->create([
                'session_id' => $session->getKey(),
                'table_id' => $lockedTable->getKey(),
                'joined_at' => now(),
            ]);

            $lockedTable->update(['status' => TableStatus::OCCUPIED]);

            return $session;
        });

        if ($session instanceof JsonResponse) {
            return $session;
        }

        return response()->json([
            'success' => true,
            'message' => 'Mở phiên dùng bữa thành công!',
            'data' => $session->toArray() + [
                'table_id' => $table->getKey(),
                'table' => $table,
            ],
        ], 201);
    }

    public function employees(Request $request): JsonResponse
    {
        $branchId = $this->resolveBranchId($request);
        $employees = $this->activeBranchWaiters($branchId)
            ->orderBy('full_name')
            ->get(['id', 'full_name']);

        return response()->json([
            'success' => true,
            'data' => $employees,
        ]);
    }

    public function closeByTable(Request $request, int $tableId): JsonResponse
    {
        $branchId = $this->resolveBranchId($request);

        $session = DB::transaction(function () use ($branchId, $tableId): DiningSession|JsonResponse {
            $table = DiningTable::query()
                ->where('branch_id', $branchId)
                ->where('is_active', true)
                ->whereKey($tableId)
                ->lockForUpdate()
                ->firstOrFail();

            $sessionTable = SessionTable::query()
                ->where('table_id', $table->getKey())
                ->whereNull('left_at')
                ->whereHas('session', fn (Builder $query) => $query->where('status', SessionStatus::OPEN))
                ->lockForUpdate()
                ->first();

            if ($sessionTable === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bàn này hiện không có phiên dùng bữa nào đang mở.',
                ], 404);
            }

            $session = DiningSession::query()
                ->whereKey($sessionTable->session_id)
                ->lockForUpdate()
                ->firstOrFail();
            $closedAt = now();
            $activeTableIds = SessionTable::query()
                ->where('session_id', $session->getKey())
                ->whereNull('left_at')
                ->pluck('table_id');

            $session->update([
                'status' => SessionStatus::CLOSED,
                'closed_at' => $closedAt,
            ]);
            SessionTable::query()
                ->where('session_id', $session->getKey())
                ->whereNull('left_at')
                ->update(['left_at' => $closedAt]);
            DiningTable::query()
                ->where('branch_id', $branchId)
                ->whereKey($activeTableIds)
                ->lockForUpdate()
                ->update(['status' => TableStatus::AVAILABLE]);

            return $session;
        });

        if ($session instanceof JsonResponse) {
            return $session;
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã đóng phiên dùng bữa.',
            'data' => $session,
        ]);
    }

    public function transferTable(Request $request, int $tableId): JsonResponse
    {
        $validated = $request->validate([
            'target_table_id' => ['required', 'integer', 'not_in:'.$tableId],
        ]);
        $branchId = $this->resolveBranchId($request);
        $userId = Auth::id();

        $result = DB::transaction(function () use ($request, $branchId, $tableId, $userId, $validated): array|JsonResponse {
            /** @var Collection<int, DiningTable> $lockedTables */
            $lockedTables = DiningTable::query()
                ->where('branch_id', $branchId)
                ->where('is_active', true)
                ->whereIn('id', [$tableId, $validated['target_table_id']])
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            $sourceTable = $lockedTables->first(
                fn (DiningTable $table): bool => $table->getKey() === $tableId,
            );
            $targetTable = $lockedTables->first(
                fn (DiningTable $table): bool => $table->getKey() === (int) $validated['target_table_id'],
            );

            if (! $sourceTable instanceof DiningTable || ! $targetTable instanceof DiningTable) {
                abort(404);
            }

            $sourceSessionTable = SessionTable::query()
                ->where('table_id', $sourceTable->getKey())
                ->whereNull('left_at')
                ->whereHas('session', fn (Builder $query) => $query->where('status', SessionStatus::OPEN))
                ->lockForUpdate()
                ->first();

            if ($sourceSessionTable === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bàn nguồn hiện không có phiên dùng bữa đang mở.',
                ], 409);
            }

            $targetHasOpenSession = SessionTable::query()
                ->where('table_id', $targetTable->getKey())
                ->whereNull('left_at')
                ->whereHas('session', fn (Builder $query) => $query->where('status', SessionStatus::OPEN))
                ->exists();

            if ($targetTable->status !== TableStatus::AVAILABLE || $targetHasOpenSession) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bàn đích không còn trống. Hãy chọn bàn khác.',
                ], 409);
            }

            $session = DiningSession::query()
                ->whereKey($sourceSessionTable->session_id)
                ->where('branch_id', $branchId)
                ->where('status', SessionStatus::OPEN)
                ->lockForUpdate()
                ->firstOrFail();
            $transferredAt = now();

            $sourceSessionTable->update(['left_at' => $transferredAt]);
            SessionTable::query()->create([
                'session_id' => $session->getKey(),
                'table_id' => $targetTable->getKey(),
                'joined_at' => $transferredAt,
            ]);
            $sourceTable->update(['status' => TableStatus::AVAILABLE]);
            $targetTable->update(['status' => TableStatus::OCCUPIED]);

            AuditLog::query()->create([
                'branch_id' => $branchId,
                'user_id' => $userId,
                'action' => 'table_transfer',
                'entity_type' => 'dining_session',
                'entity_id' => $session->getKey(),
                'old_value' => [
                    'table_id' => $sourceTable->getKey(),
                    'table_code' => $sourceTable->code,
                ],
                'new_value' => [
                    'table_id' => $targetTable->getKey(),
                    'table_code' => $targetTable->code,
                    'transferred_by' => $userId,
                ],
                'ip_address' => $request->ip(),
                'created_at' => $transferredAt,
            ]);

            return [
                'session' => $session,
                'source_table' => $sourceTable,
                'target_table' => $targetTable,
            ];
        });

        if ($result instanceof JsonResponse) {
            return $result;
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã chuyển phiên và toàn bộ đơn món sang bàn đích.',
            'data' => [
                'session_id' => $result['session']->getKey(),
                'source_table' => $result['source_table'],
                'target_table' => $result['target_table'],
            ],
        ]);
    }

    public function showByTable(Request $request, int $tableId): JsonResponse
    {
        $branchId = $this->resolveBranchId($request);
        $table = DiningTable::query()
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->findOrFail($tableId);

        $sessionTable = SessionTable::query()
            ->with('session.openedByUser')
            ->where('table_id', $table->getKey())
            ->whereNull('left_at')
            ->whereIn('session_id', DiningSession::query()
                ->select('id')
                ->where('status', SessionStatus::OPEN))
            ->latest('joined_at')
            ->first();

        if ($sessionTable === null) {
            return response()->json([
                'success' => false,
                'message' => 'Bàn này hiện không có phiên dùng bữa nào đang mở.',
            ], 404);
        }

        $session = $sessionTable->session;

        return response()->json([
            'success' => true,
            'data' => $session->toArray() + [
                'table' => $table,
                'employee' => $session->openedByUser,
            ],
        ]);
    }

    /**
     * @return Builder<User>
     */
    private function activeBranchWaiters(int $branchId): Builder
    {
        return User::query()
            ->where('status', UserStatus::ACTIVE->value)
            ->whereHas('userRoles', function (Builder $query) use ($branchId): void {
                $query->where('scope_type', 'branch')
                    ->where('scope_id', $branchId)
                    ->where('valid_from', '<=', now())
                    ->where(function (Builder $validityQuery): void {
                        $validityQuery->whereNull('valid_to')
                            ->orWhere('valid_to', '>=', now());
                    })
                    ->whereIn('role_id', Role::query()
                        ->select('id')
                        ->where('code', ConstantHelper::ROLE_WAITER));
            });
    }
}
