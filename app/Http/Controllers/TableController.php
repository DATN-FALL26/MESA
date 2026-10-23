<?php

namespace App\Http\Controllers;

use App\Models\Table;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TableController extends Controller
{
    /**
     * Hien thi giao dien so do ban (Web View)
     */
    public function index(): View
    {
        $tables = Table::orderBy('zone')
            ->orderBy('name')
            ->get()
            ->groupBy('zone');

        return view('tables.index', compact('tables'));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'zone' => ['required', 'string', 'max:255'],
            'capacity' => ['sometimes', 'integer', 'min:1', 'max:255'],
            'status' => ['sometimes', 'in:empty,occupied,cleaning'],
        ]);

        $table = Table::create($validated);

        return response()->json([
            'success' => true,
            'data' => $table,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'zone' => ['sometimes', 'required', 'string', 'max:255'],
            'capacity' => ['sometimes', 'required', 'integer', 'min:1', 'max:255'],
            'status' => ['sometimes', 'required', 'in:empty,occupied,cleaning'],
        ]);

        $table = Table::findOrFail($id);
        $table->update($validated);

        return response()->json([
            'success' => true,
            'data' => $table->refresh(),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $table = Table::findOrFail($id);

        if ($table->status === 'occupied' || $table->diningSessions()->where('status', 'open')->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa bàn đang có khách.',
            ], 409);
        }

        $table->delete();

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * API: Lay danh sach va trang thai ban theo khu vuc.
     */
    public function status(Request $request): JsonResponse
    {
        $tables = Table::when($request->query('zone') !== null, function ($query) use ($request) {
            return $query->where('zone', $request->query('zone'));
        })
            ->select(['id', 'name', 'zone', 'capacity', 'status'])
            ->orderBy('zone')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $tables,
        ]);
    }
}
