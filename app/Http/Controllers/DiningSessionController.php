<?php

namespace App\Http\Controllers;

use App\Models\DiningSession;
use App\Models\Table;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DiningSessionController extends Controller
{
    /**
     * API: Tạo lượt dùng bữa mới (Mở bàn)
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'table_id' => 'required|exists:tables,id',
            'customer_count' => 'required|integer|min:1',
            'employee_id' => 'nullable|integer',
        ]);

        $table = Table::findOrFail($validated['table_id']);

        if ($table->status === 'occupied') {
            return response()->json([
                'success' => false,
                'message' => 'Bàn này hiện đang có khách sử dụng!',
            ], 400);
        }

        // Dùng Transaction để đảm bảo cả 2 thao tác cùng thành công
        $session = DB::transaction(function () use ($table, $validated) {
            $session = DiningSession::create([
                'table_id' => $table->id,
                'customer_count' => $validated['customer_count'],
                'employee_id' => $validated['employee_id'] ?? null,
                'status' => 'open',
                'opened_at' => now(),
            ]);

            // Cập nhật trạng thái bàn sang 'occupied' (Có khách)
            $table->update(['status' => 'occupied']);

            return $session;
        });

        return response()->json([
            'success' => true,
            'message' => 'Mở phiên dùng bữa thành công!',
            'data' => $session->load('table'),
        ], 201);
    }

    /**
     * API: Lấy thông tin chi tiết phiên dùng bữa hiện tại của bàn
     */
    public function showByTable(int $tableId): JsonResponse
    {
        $session = DiningSession::where('table_id', $tableId)
            ->where('status', 'open')
            ->with('table')
            ->latest()
            ->first();

        if (! $session) {
            return response()->json([
                'success' => false,
                'message' => 'Bàn này hiện không có phiên dùng bữa nào đang mở.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $session,
        ]);
    }
}
