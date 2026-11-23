<?php

declare(strict_types=1);

namespace App\Http\Controllers\table;

use App\Enums\SessionStatus;
use App\Enums\TableStatus;
use App\Enums\UserStatus;
use App\Enums\OrderItemStatus;
use App\Helpers\ConstantHelper;
use App\Http\Controllers\table\Concerns\ResolvesTableBranch;
use App\Models\AuditLog;
use App\Models\DiningSession;
use App\Models\DiningTable;
use App\Models\Role;
use App\Models\SessionTable;
use App\Models\User;
use App\Models\Order;
use App\Services\Payment\OrderPaymentService;
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

    public function __construct(private OrderPaymentService $payments) {}
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

    public function update(Request $request, int $sessionId): JsonResponse
    {
        $validated = $request->validate([
            'guest_count' => ['required', 'integer', 'min:1', 'max:32767'],
            'note' => ['nullable', 'string', 'max:255'],
            'opened_by' => [
                'nullable',
                'integer',
                Rule::exists((new User)->getTable(), 'id')->where('status', UserStatus::ACTIVE->value),
            ],
            'allow_overcapacity' => ['sometimes', 'boolean'],
        ]);

        $branchId = $this->resolveBranchId($request);
        $userId = Auth::id();

        $session = DB::transaction(function () use ($request, $branchId, $sessionId, $validated, $userId): DiningSession|JsonResponse {
            $session = DiningSession::query()
                ->whereKey($sessionId)
                ->where('branch_id', $branchId)
                ->where('status', SessionStatus::OPEN)
                ->lockForUpdate()
                ->first();

            if ($session === null) {
                abort(404);
            }

            if (isset($validated['opened_by']) && ! $this->activeBranchWaiters($branchId)
                ->whereKey($validated['opened_by'])
                ->exists()) {
                throw ValidationException::withMessages([
                    'opened_by' => 'Nhân viên phải đang hoạt động và thuộc chi nhánh hiện tại.',
                ]);
            }

            $capacity = SessionTable::query()
                ->where('session_id', $session->getKey())
                ->whereNull('left_at')
                ->join('dining_tables', 'session_tables.table_id', '=', 'dining_tables.id')
                ->sum('dining_tables.seats');

            if ((int) $validated['guest_count'] > (int) $capacity
                && ! ($validated['allow_overcapacity'] ?? false)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Số khách vượt sức chứa '.(int) $capacity.' chỗ của phiên.',
                    'requires_confirmation' => true,
                ], 422);
            }

            $oldValue = [
                'guest_count' => $session->guest_count,
                'note' => $session->note,
                'opened_by' => $session->opened_by,
            ];
            $session->update([
                'guest_count' => $validated['guest_count'],
                'note' => $validated['note'] ?? null,
                'opened_by' => $validated['opened_by'] ?? $session->opened_by,
            ]);

            AuditLog::query()->create([
                'branch_id' => $branchId,
                'user_id' => $userId,
                'action' => 'dining_session_update',
                'entity_type' => 'dining_session',
                'entity_id' => $session->getKey(),
                'old_value' => $oldValue,
                'new_value' => [
                    'guest_count' => $session->guest_count,
                    'note' => $session->note,
                    'opened_by' => $session->opened_by,
                    'allow_overcapacity' => (bool) ($validated['allow_overcapacity'] ?? false),
                ],
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);

            return $session;
        });

        if ($session instanceof JsonResponse) {
            return $session;
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã cập nhật phiên dùng bữa.',
            'data' => $session,
        ]);
    }

    public function closeByTable(Request $request, int $tableId): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);
        $branchId = $this->resolveBranchId($request);

        $session = DB::transaction(function () use ($branchId, $tableId, $validated, $request): DiningSession|JsonResponse {
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
            $activeOrders = Order::query()
                ->where('session_id', $session->getKey())
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->lockForUpdate()
                ->get();

            foreach ($activeOrders as $order) {
                $summary = $this->payments->summary($order);
                if ($this->payments->pendingQr($order) !== null) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Phiên chưa thể đóng vì còn giao dịch QR đang chờ thanh toán.',
                    ], 409);
                }
                if ($summary['remaining'] > 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Phiên chưa thể đóng vì order vẫn còn '.$summary['remaining'].'đ chưa thanh toán.',
                    ], 409);
                }
                $hasPendingItems = $order->items()
                    ->where('status', OrderItemStatus::PENDING->value)
                    ->exists();
                if ($hasPendingItems) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Phiên chưa thể đóng vì vẫn còn món chưa gửi xuống bếp.',
                    ], 409);
                }
            }
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
                ->update(['status' => TableStatus::CLEANING]);

            AuditLog::query()->create([
                'branch_id' => $branchId,
                'user_id' => Auth::id(),
                'action' => 'dining_session_close',
                'entity_type' => 'dining_session',
                'entity_id' => $session->getKey(),
                'old_value' => [
                    'status' => SessionStatus::OPEN->value,
                    'table_ids' => $activeTableIds->all(),
                ],
                'new_value' => [
                    'status' => SessionStatus::CLOSED->value,
                    'reason' => $validated['reason'] ?? 'Khách đã thanh toán và rời bàn',
                    'table_status' => TableStatus::CLEANING->value,
                ],
                'ip_address' => $request->ip(),
                'created_at' => $closedAt,
            ]);

            return $session;
        });

        if ($session instanceof JsonResponse) {
            return $session;
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã đóng phiên. Bàn đang chờ dọn.',
            'data' => $session,
        ]);
    }

    public function markCleaned(Request $request, int $tableId): JsonResponse
    {
        $branchId = $this->resolveBranchId($request);

        $table = DiningTable::query()
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->whereKey($tableId)
            ->lockForUpdate()
            ->firstOrFail();

        if ($table->status !== TableStatus::CLEANING) {
            return response()->json([
                'success' => false,
                'message' => 'Bàn hiện không ở trạng thái Cần dọn.',
            ], 409);
        }
        $cleanedTableIds = [$table->getKey()];

        DB::transaction(function () use ($branchId, $table, &$cleanedTableIds): void {
            $sessionIds = AuditLog::query()
                ->where('branch_id', $branchId)
                ->where('entity_type', 'dining_session')
                ->where('action', 'dining_session_close')
                ->where('new_value->table_status', TableStatus::CLEANING->value)
                ->whereJsonContains('old_value->table_ids', $table->getKey())
                ->pluck('entity_id')
                ->unique();

            if ($sessionIds->isNotEmpty()) {
                $mergedTableIds = SessionTable::query()
                    ->whereIn('session_id', $sessionIds)
                    ->pluck('table_id')
                    ->filter()
                    ->unique();
                $cleanedTableIds = $mergedTableIds->push($table->getKey())->unique()->values()->all();
            }

            DiningTable::query()
                ->where('branch_id', $branchId)
                ->whereIn('id', $cleanedTableIds)
                ->where('status', TableStatus::CLEANING)
                ->lockForUpdate()
                ->update(['status' => TableStatus::AVAILABLE]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Đã dọn xong tất cả bàn của phiên gộp.',
            'data' => [
                'table_ids' => $cleanedTableIds,
                'table' => $table->fresh(),
            ],
        ]);
    }

    public function mergeTables(Request $request, int $tableId): JsonResponse
    {
        $validated = $request->validate([
            'target_table_ids' => ['required', 'array', 'min:1'],
            'target_table_ids.*' => ['required', 'integer', 'distinct', 'not_in:'.$tableId],
            'allow_overcapacity' => ['sometimes', 'boolean'],
        ]);

        $branchId = $this->resolveBranchId($request);
        $userId = Auth::id();
        $targetIds = array_map('intval', $validated['target_table_ids']);

        $result = DB::transaction(function () use ($branchId, $tableId, $targetIds, $validated, $userId, $request): array|JsonResponse {
            $sourceTable = DiningTable::query()
                ->where('branch_id', $branchId)
                ->where('is_active', true)
                ->whereKey($tableId)
                ->lockForUpdate()
                ->first();
            $targetTables = DiningTable::query()
                ->where('branch_id', $branchId)
                ->where('is_active', true)
                ->whereIn('id', $targetIds)
                ->lockForUpdate()
                ->get();

            if (! $sourceTable instanceof DiningTable || $targetTables->count() !== count(array_unique($targetIds))) {
                abort(404);
            }

            $sourceLink = SessionTable::query()
                ->where('table_id', $sourceTable->getKey())
                ->whereNull('left_at')
                ->whereHas('session', fn (Builder $query) => $query->where('status', SessionStatus::OPEN))
                ->lockForUpdate()
                ->first();
            if ($sourceLink === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bàn nguồn không có phiên dùng bữa đang mở.',
                ], 409);
            }

            $blockedTarget = $targetTables->first(fn (DiningTable $table): bool =>
                $table->status !== TableStatus::AVAILABLE
                || SessionTable::query()
                    ->where('table_id', $table->getKey())
                    ->whereNull('left_at')
                    ->whereHas('session', fn (Builder $query) => $query->where('status', SessionStatus::OPEN))
                    ->exists()
            );
            if ($blockedTarget !== null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bàn '.$blockedTarget->code.' không còn trống.',
                ], 409);
            }

            $session = DiningSession::query()
                ->whereKey($sourceLink->session_id)
                ->lockForUpdate()
                ->firstOrFail();
            $sourceCapacity = (int) $sourceTable->seats;
            $totalCapacity = $sourceCapacity + (int) $targetTables->sum('seats');
            if ($session->guest_count > $totalCapacity && ! ($validated['allow_overcapacity'] ?? false)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sau khi gộp bàn, sức chứa tổng là '.$totalCapacity.' chỗ nhưng phiên có '.$session->guest_count.' khách.',
                    'requires_confirmation' => true,
                ], 422);
            }

            $mergedAt = now();
            foreach ($targetTables as $targetTable) {
                SessionTable::query()->create([
                    'session_id' => $session->getKey(),
                    'table_id' => $targetTable->getKey(),
                    'joined_at' => $mergedAt,
                ]);
                $targetTable->update(['status' => TableStatus::OCCUPIED]);
            }

            AuditLog::query()->create([
                'branch_id' => $branchId,
                'user_id' => $userId,
                'action' => 'dining_session_merge',
                'entity_type' => 'dining_session',
                'entity_id' => $session->getKey(),
                'old_value' => [
                    'table_ids' => [$sourceTable->getKey()],
                    'table_codes' => [$sourceTable->code],
                ],
                'new_value' => [
                    'table_ids' => [$sourceTable->getKey(), ...$targetTables->pluck('id')->all()],
                    'table_codes' => [$sourceTable->code, ...$targetTables->pluck('code')->all()],
                    'guest_count' => $session->guest_count,
                    'allow_overcapacity' => (bool) ($validated['allow_overcapacity'] ?? false),
                ],
                'ip_address' => $request->ip(),
                'created_at' => $mergedAt,
            ]);

            return [
                'session' => $session,
                'source_table' => $sourceTable,
                'target_tables' => $targetTables,
                'total_capacity' => $totalCapacity,
            ];
        });

        if ($result instanceof JsonResponse) {
            return $result;
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã gộp bàn vào phiên dùng bữa.',
            'data' => [
                'session_id' => $result['session']->getKey(),
                'source_table' => $result['source_table'],
                'target_tables' => $result['target_tables'],
                'total_capacity' => $result['total_capacity'],
            ],
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
