<?php

namespace App\Http\Controllers\table;

use App\Enums\SessionStatus;
use App\Enums\TableStatus;
use App\Helpers\ConstantHelper;
use App\Http\Controllers\table\Concerns\ResolvesTableBranch;
use App\Models\Area;
use App\Models\AuditLog;
use App\Models\DiningTable;
use App\Models\SessionTable;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TableController extends BaseController
{
    use ResolvesTableBranch;

    public function index(Request $request): View
    {
        $currentBranchId = $this->resolveBranchId($request);
        $branches = $this->accessibleBranches($request);
        $areas = Area::query()
            ->where('branch_id', $currentBranchId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get();

        /** @var Collection<int, DiningTable> $tables */
        $tables = DiningTable::query()
            ->with('area')
            ->where('branch_id', $currentBranchId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $openSessionTables = SessionTable::query()
            ->with('session.openedByUser')
            ->whereIn('table_id', $tables->pluck('id'))
            ->whereNull('left_at')
            ->whereHas('session', fn (Builder $query) => $query->where('status', SessionStatus::OPEN))
            ->get();
        $openSessionsByTableId = $openSessionTables->mapWithKeys(
            fn (SessionTable $sessionTable) => [$sessionTable->table_id => $sessionTable->session],
        );
        $transferLogs = AuditLog::query()
            ->where('branch_id', $currentBranchId)
            ->where('entity_type', 'dining_session')
            ->where('action', 'table_transfer')
            ->orderByDesc('created_at')
            ->select(['entity_id', 'old_value', 'new_value', 'user_id', 'created_at'])
            ->limit(50)
            ->get();
        $transferActors = User::query()
            ->whereIn('id', $transferLogs->pluck('user_id')->filter()->unique())
            ->pluck('full_name', 'id');
        $tableTransferHistoryByTableId = [];
        foreach ($transferLogs as $log) {
            $history = [
                'from' => $log->old_value['table_code'] ?? '—',
                'to' => $log->new_value['table_code'] ?? '—',
                'actor' => $transferActors->get($log->user_id, '—'),
                'at' => $log->created_at?->format('d/m/Y H:i') ?? '—',
            ];
            $tableIds = array_unique(array_filter([
                (int) ($log->old_value['table_id'] ?? 0),
                (int) ($log->new_value['table_id'] ?? 0),
            ]));

            foreach ($tableIds as $tableId) {
                $tableTransferHistoryByTableId[$tableId][] = $history;
            }
        }
        $tableTransferHistoryByTableId = collect($tableTransferHistoryByTableId)
            ->map(fn (array $history) => collect($history));

        foreach ($tables as $table) {
            if ($openSessionsByTableId->has($table->getKey())) {
                $table->setAttribute('status', TableStatus::OCCUPIED);
            }
        }

        $tables = $tables->groupBy(fn ($table) => $table->area?->name ?? 'Khu vực chung');

        $view = $request->user()->hasPermission(ConstantHelper::PERM_TABLE_LAYOUT_MANAGE)
            ? 'tables.admin.index'
            : 'tables.pos.index';

        return view($view, compact(
            'tables',
            'areas',
            'branches',
            'currentBranchId',
            'openSessionsByTableId',
            'tableTransferHistoryByTableId',
        ));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->tableRules($request));
        $table = DiningTable::query()->create($validated);

        return response()->json([
            'success' => true,
            'data' => $table,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $branchId = $this->resolveBranchId($request);
        $table = DiningTable::query()
            ->where('branch_id', $branchId)
            ->findOrFail($id);
        $validated = $request->validate($this->tableRules($request, $table));

        $result = DB::transaction(function () use ($branchId, $id, $validated): DiningTable|JsonResponse {
            $lockedTable = DiningTable::query()
                ->where('branch_id', $branchId)
                ->whereKey($id)
                ->lockForUpdate()
                ->firstOrFail();

            $hasOpenSession = $lockedTable->sessionTables()
                ->whereNull('left_at')
                ->whereHas('session', fn (Builder $query) => $query->where('status', SessionStatus::OPEN))
                ->exists();

            if ($hasOpenSession && $validated['status'] !== TableStatus::OCCUPIED->value) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bàn đang có phiên dùng bữa mở. Hãy đóng phiên trước khi đổi trạng thái bàn.',
                ], 409);
            }

            $lockedTable->update($validated);

            return $lockedTable->refresh();
        });

        if ($result instanceof JsonResponse) {
            return $result;
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $branchId = $this->resolveBranchId($request);
        $table = DiningTable::query()
            ->where('branch_id', $branchId)
            ->findOrFail($id);

        $hasOpenSession = $table->sessionTables()
            ->whereNull('left_at')
            ->whereHas('session', fn (Builder $query) => $query->where('status', SessionStatus::OPEN))
            ->exists();

        if ($hasOpenSession) {
            return response()->json([
                'success' => false,
                'message' => 'Bàn đang có phiên phục vụ mở. Hãy đóng phiên trước khi xóa bàn.',
            ], 409);
        }

        if (\in_array($table->status, [TableStatus::OCCUPIED, TableStatus::RESERVED], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa bàn đang được sử dụng hoặc đã đặt trước.',
            ], 409);
        }

        $table->delete();

        return response()->json(['success' => true]);
    }

    public function status(Request $request): JsonResponse
    {
        $branchId = $this->resolveBranchId($request);
        /** @var Collection<int, DiningTable> $tables */
        $tables = DiningTable::query()
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->when($request->query('area_id') !== null, fn (Builder $query) => $query
                ->where('area_id', $request->query('area_id')))
            ->select(['id', 'code', 'area_id', 'seats', 'status', 'sort_order'])
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get();

        $openSessionsByTableId = SessionTable::query()
            ->with('session.openedByUser')
            ->whereIn('table_id', $tables->pluck('id'))
            ->whereNull('left_at')
            ->whereHas('session', fn (Builder $query) => $query->where('status', SessionStatus::OPEN))
            ->get()
            ->mapWithKeys(fn (SessionTable $sessionTable) => [$sessionTable->table_id => $sessionTable->session]);

        foreach ($tables as $table) {
            $openSession = $openSessionsByTableId->get($table->getKey());
            if ($openSession !== null) {
                $table->setAttribute('status', TableStatus::OCCUPIED);
                $table->setAttribute('open_session', [
                    'id' => $openSession->getKey(),
                    'guest_count' => $openSession->guest_count,
                    'employee_name' => $openSession->openedByUser->full_name,
                ]);
            } else {
                $table->setAttribute('open_session', null);
            }
        }

        return response()->json([
            'success' => true,
            'data' => $tables,
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function tableRules(Request $request, ?DiningTable $table = null): array
    {
        $branchId = $this->resolveBranchId($request);
        $uniqueCode = Rule::unique((new DiningTable)->getTable(), 'code')
            ->where('branch_id', $branchId)
            ->whereNull('deleted_at');

        if ($table !== null) {
            $uniqueCode->ignore($table->getKey());
        }

        return [
            'branch_id' => ['required', 'integer', Rule::in([$branchId]), Rule::exists('branches', 'id')],
            'area_id' => [
                'required',
                'integer',
                Rule::exists((new Area)->getTable(), 'id')->where('branch_id', $branchId),
            ],
            'code' => ['required', 'string', 'max:20', $uniqueCode],
            'seats' => ['required', 'integer', 'min:1', 'max:32767'],
            'status' => ['required', Rule::in(TableStatus::values())],
        ];
    }
}
