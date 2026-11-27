<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Enums\ApprovalStatus;
use App\Enums\PurchaseOrderStatus;
use App\Models\AuditLog;
use App\Models\PurchaseOrder;
use App\Models\User;

class PurchaseOrderProcessPresenter
{
    /**
     * Xây dựng dữ liệu tiến trình 4 bước và lịch sử chi tiết duyệt nhập hàng cho 1 lệnh nhập.
     *
     * @return array{
     *     approval_request: array<string, mixed>|null,
     *     process_steps: array<int, array<string, mixed>>,
     *     process_timeline: array<int, array<string, mixed>>
     * }
     */
    public function build(PurchaseOrder $po): array
    {
        $approvalRequest = $po->relationLoaded('approvalRequest') ? $po->approvalRequest : null;
        $auditLogs = $po->relationLoaded('auditLogs') ? $po->auditLogs : collect();
        $kitchenCheckin = $po->relationLoaded('kitchenDailyCheckin') ? $po->kitchenDailyCheckin : null;

        $timeline = $auditLogs->isNotEmpty()
            ? $this->buildTimelineFromAuditLogs($po, $auditLogs)
            : $this->buildFallbackTimeline($po, $approvalRequest, $kitchenCheckin);

        $steps = $this->buildProcessSteps($po, $approvalRequest, $timeline, $kitchenCheckin !== null);

        return [
            'approval_request' => $approvalRequest ? [
                'id' => $approvalRequest->id,
                'status' => $approvalRequest->status->value,
                'status_label' => $approvalRequest->status->label(),
                'requested_by_name' => $this->resolveUserName($approvalRequest->requester),
                'requested_at' => $approvalRequest->created_at?->toIso8601String(),
                'decided_by_name' => $this->resolveUserName($approvalRequest->decider),
                'decided_at' => $approvalRequest->decided_at?->toIso8601String(),
                'note' => $approvalRequest->note,
            ] : null,
            'process_steps' => $steps,
            'process_timeline' => $timeline,
        ];
    }

    /**
     * @param \Illuminate\Support\Collection<int, AuditLog> $auditLogs
     * @return array<int, array<string, mixed>>
     */
    private function buildTimelineFromAuditLogs(PurchaseOrder $po, $auditLogs): array
    {
        $events = [];

        foreach ($auditLogs as $log) {
            $newVal = is_array($log->new_value) ? $log->new_value : [];
            $oldVal = is_array($log->old_value) ? $log->old_value : [];
            $actorName = $this->resolveUserName($log->user) ?? 'Hệ thống';
            $timestamp = $log->created_at?->toIso8601String();

            $events[] = match ($log->action) {
                'purchase_order_created' => [
                    'action' => $log->action,
                    'title' => 'Lập lệnh nhập hàng',
                    'status_transition' => 'Khởi tạo → Bản nháp',
                    'tone' => 'indigo',
                    'actor_name' => $actorName,
                    'timestamp' => $timestamp,
                    'note' => $newVal['note'] ?? null,
                    'details' => $this->buildCreationDetails($po, $newVal),
                ],
                'purchase_order_kitchen_checkin' => [
                    'action' => $log->action,
                    'title' => 'Nhập hàng trực tiếp từ Bếp',
                    'status_transition' => 'Bếp xác nhận → Đã nhận hàng',
                    'tone' => 'emerald',
                    'actor_name' => $actorName,
                    'timestamp' => $timestamp,
                    'note' => $newVal['note'] ?? $po->note,
                    'details' => $this->buildKitchenDetails($newVal),
                ],
                'purchase_order_updated' => [
                    'action' => $log->action,
                    'title' => 'Chỉnh sửa thông tin bản nháp',
                    'status_transition' => 'Bản nháp',
                    'tone' => 'slate',
                    'actor_name' => $actorName,
                    'timestamp' => $timestamp,
                    'note' => $newVal['note'] ?? null,
                    'details' => $this->buildUpdateDetails($oldVal, $newVal),
                ],
                'purchase_order_submitted' => [
                    'action' => $log->action,
                    'title' => 'Gửi yêu cầu phê duyệt',
                    'status_transition' => 'Bản nháp → Chờ duyệt',
                    'tone' => 'amber',
                    'actor_name' => $actorName,
                    'timestamp' => $timestamp,
                    'note' => $newVal['note'] ?? null,
                    'details' => [],
                ],
                'purchase_order_rejected' => [
                    'action' => $log->action,
                    'title' => 'Từ chối / Trả lại chỉnh sửa',
                    'status_transition' => 'Chờ duyệt → Bản nháp',
                    'tone' => 'rose',
                    'actor_name' => $actorName,
                    'timestamp' => $timestamp,
                    'note' => $newVal['note'] ?? null,
                    'details' => [],
                ],
                'purchase_order_approved' => [
                    'action' => $log->action,
                    'title' => 'Phê duyệt lệnh nhập hàng',
                    'status_transition' => 'Chờ duyệt → Đã duyệt',
                    'tone' => 'blue',
                    'actor_name' => $actorName,
                    'timestamp' => $timestamp,
                    'note' => $newVal['note'] ?? null,
                    'details' => [],
                ],
                'purchase_order_cancelled' => [
                    'action' => $log->action,
                    'title' => 'Hủy lệnh nhập hàng',
                    'status_transition' => $this->statusLabel($oldVal['status'] ?? null) . ' → Đã hủy',
                    'tone' => 'rose',
                    'actor_name' => $actorName,
                    'timestamp' => $timestamp,
                    'note' => $newVal['note'] ?? null,
                    'details' => [],
                ],
                'purchase_order_received' => [
                    'action' => $log->action,
                    'title' => 'Kiểm nhận & Nhập kho',
                    'status_transition' => 'Đã duyệt → Đã nhận hàng',
                    'tone' => 'emerald',
                    'actor_name' => $actorName,
                    'timestamp' => $timestamp,
                    'note' => $newVal['note'] ?? null,
                    'details' => $this->buildReceiveDetails($newVal),
                ],
                default => [
                    'action' => $log->action,
                    'title' => $log->action,
                    'status_transition' => null,
                    'tone' => 'slate',
                    'actor_name' => $actorName,
                    'timestamp' => $timestamp,
                    'note' => $newVal['note'] ?? null,
                    'details' => [],
                ],
            };
        }

        return $events;
    }

    /**
     * Dựng lại lịch sử từ các cột trên PO và ApprovalRequest cho dữ liệu cũ / dữ liệu mẫu chưa có AuditLog.
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildFallbackTimeline(PurchaseOrder $po, $approvalRequest, $kitchenCheckin): array
    {
        $events = [];
        $creatorName = $this->resolveUserName($po->creator) ?? 'Hệ thống';

        if ($kitchenCheckin !== null) {
            $events[] = [
                'action' => 'purchase_order_kitchen_checkin',
                'title' => 'Nhập hàng trực tiếp từ Bếp',
                'status_transition' => 'Bếp xác nhận → Đã nhận hàng',
                'tone' => 'emerald',
                'actor_name' => $this->resolveUserName($kitchenCheckin->user) ?? $creatorName,
                'timestamp' => $po->created_at?->toIso8601String(),
                'note' => $po->note,
                'details' => [
                    'Nguồn: Check-in Bếp',
                    'Số mặt hàng: ' . ($po->items?->count() ?? 0),
                ],
            ];

            return $events;
        }

        $events[] = [
            'action' => 'purchase_order_created',
            'title' => 'Lập lệnh nhập hàng',
            'status_transition' => 'Khởi tạo → Bản nháp',
            'tone' => 'indigo',
            'actor_name' => $creatorName,
            'timestamp' => $po->created_at?->toIso8601String(),
            'note' => $po->note,
            'details' => $this->buildCreationDetails($po, [
                'source' => $po->source?->value,
                'items_count' => $po->items?->count() ?? 0,
            ]),
        ];

        if ($approvalRequest !== null) {
            $events[] = [
                'action' => 'purchase_order_submitted',
                'title' => 'Gửi yêu cầu phê duyệt',
                'status_transition' => 'Bản nháp → Chờ duyệt',
                'tone' => 'amber',
                'actor_name' => $this->resolveUserName($approvalRequest->requester) ?? $creatorName,
                'timestamp' => $approvalRequest->created_at?->toIso8601String(),
                'note' => $approvalRequest->status === ApprovalStatus::PENDING ? $approvalRequest->note : null,
                'details' => [],
            ];

            if ($approvalRequest->decided_at !== null) {
                if ($approvalRequest->status === ApprovalStatus::APPROVED) {
                    $events[] = [
                        'action' => 'purchase_order_approved',
                        'title' => 'Phê duyệt lệnh nhập hàng',
                        'status_transition' => 'Chờ duyệt → Đã duyệt',
                        'tone' => 'blue',
                        'actor_name' => $this->resolveUserName($approvalRequest->decider)
                            ?? $this->resolveUserName($po->approver)
                            ?? 'Quản lý',
                        'timestamp' => $approvalRequest->decided_at->toIso8601String(),
                        'note' => $approvalRequest->note,
                        'details' => [],
                    ];
                } elseif ($approvalRequest->status === ApprovalStatus::REJECTED && $po->status !== PurchaseOrderStatus::CANCELLED) {
                    $events[] = [
                        'action' => 'purchase_order_rejected',
                        'title' => 'Từ chối / Trả lại chỉnh sửa',
                        'status_transition' => 'Chờ duyệt → Bản nháp',
                        'tone' => 'rose',
                        'actor_name' => $this->resolveUserName($approvalRequest->decider) ?? 'Quản lý',
                        'timestamp' => $approvalRequest->decided_at->toIso8601String(),
                        'note' => $approvalRequest->note,
                        'details' => [],
                    ];
                }
            }
        } elseif ($po->approved_at !== null) {
            $events[] = [
                'action' => 'purchase_order_approved',
                'title' => 'Phê duyệt lệnh nhập hàng',
                'status_transition' => 'Chờ duyệt → Đã duyệt',
                'tone' => 'blue',
                'actor_name' => $this->resolveUserName($po->approver) ?? 'Quản lý',
                'timestamp' => $po->approved_at->toIso8601String(),
                'note' => null,
                'details' => [],
            ];
        }

        if ($po->status === PurchaseOrderStatus::CANCELLED) {
            $events[] = [
                'action' => 'purchase_order_cancelled',
                'title' => 'Hủy lệnh nhập hàng',
                'status_transition' => 'Đã hủy',
                'tone' => 'rose',
                'actor_name' => $this->resolveUserName($approvalRequest?->decider) ?? $creatorName,
                'timestamp' => ($approvalRequest?->decided_at ?? $po->updated_at)?->toIso8601String(),
                'note' => $approvalRequest?->note ?? $po->note,
                'details' => [],
            ];
        }

        if ($po->received_at !== null) {
            $events[] = [
                'action' => 'purchase_order_received',
                'title' => 'Kiểm nhận & Nhập kho',
                'status_transition' => 'Đã duyệt → Đã nhận hàng',
                'tone' => 'emerald',
                'actor_name' => $this->resolveUserName($po->receiver) ?? 'Thủ kho',
                'timestamp' => $po->received_at->toIso8601String(),
                'note' => null,
                'details' => $this->buildReceiveSummaryFromItems($po),
            ];
        }

        return $events;
    }

    /**
     * @param array<int, array<string, mixed>> $timeline
     * @return array<int, array<string, mixed>>
     */
    private function buildProcessSteps(PurchaseOrder $po, $approvalRequest, array $timeline, bool $isKitchenCheckin): array
    {
        $lastByAction = [];
        foreach ($timeline as $event) {
            $lastByAction[$event['action']] = $event;
        }

        $createdEvent = $lastByAction['purchase_order_created'] ?? $lastByAction['purchase_order_kitchen_checkin'] ?? null;
        $submittedEvent = $lastByAction['purchase_order_submitted'] ?? null;
        $approvedEvent = $lastByAction['purchase_order_approved'] ?? null;
        $rejectedEvent = $lastByAction['purchase_order_rejected'] ?? null;
        $cancelledEvent = $lastByAction['purchase_order_cancelled'] ?? null;
        $receivedEvent = $lastByAction['purchase_order_received'] ?? null;

        $status = $po->status;

        // Step 1: Lập lệnh
        $step1 = [
            'key' => 'created',
            'label' => 'Lập lệnh nhập',
            'state' => 'done',
            'actor_name' => $createdEvent['actor_name'] ?? $this->resolveUserName($po->creator),
            'timestamp' => $createdEvent['timestamp'] ?? $po->created_at?->toIso8601String(),
            'summary' => $isKitchenCheckin ? 'Đồng bộ từ Bếp check-in' : ($po->source?->label() ?? 'Thủ công'),
        ];

        // Step 2: Gửi duyệt
        if ($isKitchenCheckin) {
            $step2 = [
                'key' => 'submitted',
                'label' => 'Gửi phê duyệt',
                'state' => 'skipped',
                'actor_name' => null,
                'timestamp' => null,
                'summary' => 'Bỏ qua (Bếp nhập trực tiếp)',
            ];
        } elseif (in_array($status, [PurchaseOrderStatus::PENDING_APPROVAL, PurchaseOrderStatus::APPROVED, PurchaseOrderStatus::RECEIVED], true)) {
            $step2 = [
                'key' => 'submitted',
                'label' => 'Gửi phê duyệt',
                'state' => 'done',
                'actor_name' => $submittedEvent['actor_name'] ?? $this->resolveUserName($approvalRequest?->requester) ?? $this->resolveUserName($po->creator),
                'timestamp' => $submittedEvent['timestamp'] ?? $approvalRequest?->created_at?->toIso8601String(),
                'summary' => 'Đã gửi trình duyệt',
            ];
        } elseif ($status === PurchaseOrderStatus::DRAFT && ($submittedEvent !== null || $rejectedEvent !== null)) {
            $step2 = [
                'key' => 'submitted',
                'label' => 'Gửi phê duyệt',
                'state' => 'done',
                'actor_name' => $submittedEvent['actor_name'] ?? $this->resolveUserName($approvalRequest?->requester),
                'timestamp' => $submittedEvent['timestamp'] ?? $approvalRequest?->created_at?->toIso8601String(),
                'summary' => 'Đã gửi (đang chờ gửi lại)',
            ];
        } elseif ($status === PurchaseOrderStatus::CANCELLED) {
            $step2 = [
                'key' => 'submitted',
                'label' => 'Gửi phê duyệt',
                'state' => $submittedEvent !== null ? 'done' : 'cancelled',
                'actor_name' => $submittedEvent['actor_name'] ?? null,
                'timestamp' => $submittedEvent['timestamp'] ?? null,
                'summary' => $submittedEvent !== null ? 'Đã gửi duyệt trước khi hủy' : 'Chưa gửi duyệt',
            ];
        } else {
            $step2 = [
                'key' => 'submitted',
                'label' => 'Gửi phê duyệt',
                'state' => 'current',
                'actor_name' => null,
                'timestamp' => null,
                'summary' => 'Đang ở bản nháp, chờ gửi duyệt',
            ];
        }

        // Step 3: Phê duyệt
        if ($isKitchenCheckin) {
            $step3 = [
                'key' => 'approved',
                'label' => 'Phê duyệt',
                'state' => 'done',
                'actor_name' => $this->resolveUserName($po->approver) ?? $this->resolveUserName($po->creator),
                'timestamp' => $po->approved_at?->toIso8601String() ?? $po->created_at?->toIso8601String(),
                'summary' => 'Tự động xác nhận từ Bếp',
            ];
        } elseif (in_array($status, [PurchaseOrderStatus::APPROVED, PurchaseOrderStatus::RECEIVED], true)) {
            $step3 = [
                'key' => 'approved',
                'label' => 'Phê duyệt',
                'state' => 'done',
                'actor_name' => $approvedEvent['actor_name'] ?? $this->resolveUserName($po->approver),
                'timestamp' => $approvedEvent['timestamp'] ?? $po->approved_at?->toIso8601String(),
                'summary' => $approvedEvent['note'] ?? $approvalRequest?->note ?? 'Đã phê duyệt',
            ];
        } elseif ($status === PurchaseOrderStatus::PENDING_APPROVAL) {
            $step3 = [
                'key' => 'approved',
                'label' => 'Phê duyệt',
                'state' => 'current',
                'actor_name' => null,
                'timestamp' => null,
                'summary' => 'Đang chờ quản lý phê duyệt',
            ];
        } elseif ($status === PurchaseOrderStatus::DRAFT && ($rejectedEvent !== null || $approvalRequest?->status === ApprovalStatus::REJECTED)) {
            $step3 = [
                'key' => 'approved',
                'label' => 'Phê duyệt',
                'state' => 'rejected',
                'actor_name' => $rejectedEvent['actor_name'] ?? $this->resolveUserName($approvalRequest?->decider),
                'timestamp' => $rejectedEvent['timestamp'] ?? $approvalRequest?->decided_at?->toIso8601String(),
                'summary' => $rejectedEvent['note'] ?? $approvalRequest?->note ?? 'Bị từ chối / Trả lại sửa',
            ];
        } elseif ($status === PurchaseOrderStatus::CANCELLED) {
            $step3 = [
                'key' => 'approved',
                'label' => 'Phê duyệt',
                'state' => 'cancelled',
                'actor_name' => $cancelledEvent['actor_name'] ?? null,
                'timestamp' => $cancelledEvent['timestamp'] ?? null,
                'summary' => $cancelledEvent['note'] ?? 'Đơn đã bị hủy',
            ];
        } else {
            $step3 = [
                'key' => 'approved',
                'label' => 'Phê duyệt',
                'state' => 'pending',
                'actor_name' => null,
                'timestamp' => null,
                'summary' => 'Chờ bước gửi duyệt',
            ];
        }

        // Step 4: Nhập kho
        if ($status === PurchaseOrderStatus::RECEIVED) {
            $step4 = [
                'key' => 'received',
                'label' => 'Kiểm nhận & Nhập kho',
                'state' => 'done',
                'actor_name' => $receivedEvent['actor_name'] ?? $this->resolveUserName($po->receiver) ?? $this->resolveUserName($po->creator),
                'timestamp' => $receivedEvent['timestamp'] ?? $po->received_at?->toIso8601String() ?? $po->created_at?->toIso8601String(),
                'summary' => 'Đã ghi sổ cái & tạo lô hàng',
            ];
        } elseif ($status === PurchaseOrderStatus::APPROVED) {
            $step4 = [
                'key' => 'received',
                'label' => 'Kiểm nhận & Nhập kho',
                'state' => 'current',
                'actor_name' => null,
                'timestamp' => null,
                'summary' => 'Chờ thủ kho xác nhận nhận hàng',
            ];
        } elseif ($status === PurchaseOrderStatus::CANCELLED) {
            $step4 = [
                'key' => 'received',
                'label' => 'Kiểm nhận & Nhập kho',
                'state' => 'cancelled',
                'actor_name' => null,
                'timestamp' => null,
                'summary' => 'Không thực hiện (đơn đã hủy)',
            ];
        } else {
            $step4 = [
                'key' => 'received',
                'label' => 'Kiểm nhận & Nhập kho',
                'state' => 'pending',
                'actor_name' => null,
                'timestamp' => null,
                'summary' => 'Chờ lệnh được phê duyệt',
            ];
        }

        return [$step1, $step2, $step3, $step4];
    }

    /**
     * @param array<string, mixed> $newVal
     * @return array<int, string>
     */
    private function buildCreationDetails(PurchaseOrder $po, array $newVal): array
    {
        $details = [];
        $sourceLabel = $po->source?->label() ?? (($newVal['source'] ?? 'manual') === 'ai_suggestion' ? 'Đề xuất từ AI' : 'Thủ công');
        $details[] = "Nguồn: {$sourceLabel}";

        if (isset($newVal['items_count'])) {
            $details[] = 'Số mặt hàng: ' . (int) $newVal['items_count'];
        }

        if (isset($newVal['total_amount'])) {
            $details[] = 'Tổng dự kiến: ' . number_format((float) $newVal['total_amount'], 0, ',', '.') . ' đ';
        }

        return $details;
    }

    /**
     * @param array<string, mixed> $newVal
     * @return array<int, string>
     */
    private function buildKitchenDetails(array $newVal): array
    {
        $details = ['Nguồn: Check-in Bếp'];
        if (isset($newVal['items_count'])) {
            $details[] = 'Số mặt hàng: ' . (int) $newVal['items_count'];
        }
        if (isset($newVal['total_amount'])) {
            $details[] = 'Giá trị nhập: ' . number_format((float) $newVal['total_amount'], 0, ',', '.') . ' đ';
        }

        return $details;
    }

    /**
     * @param array<string, mixed> $oldVal
     * @param array<string, mixed> $newVal
     * @return array<int, string>
     */
    private function buildUpdateDetails(array $oldVal, array $newVal): array
    {
        $details = [];
        if (isset($newVal['items_count'])) {
            $details[] = 'Số mặt hàng: ' . (int) $newVal['items_count'];
        }
        if (isset($newVal['total_amount'])) {
            $oldAmt = isset($oldVal['total_amount']) ? number_format((float) $oldVal['total_amount'], 0, ',', '.') . ' đ' : null;
            $newAmt = number_format((float) $newVal['total_amount'], 0, ',', '.') . ' đ';
            $details[] = $oldAmt !== null && $oldAmt !== $newAmt
                ? "Tổng tiền: {$oldAmt} → {$newAmt}"
                : "Tổng tiền: {$newAmt}";
        }

        return $details;
    }

    /**
     * @param array<string, mixed> $newVal
     * @return array<int, string>
     */
    private function buildReceiveDetails(array $newVal): array
    {
        $details = [];
        if (isset($newVal['items_count'])) {
            $details[] = 'Số mặt hàng kiểm nhận: ' . (int) $newVal['items_count'];
        }
        if (isset($newVal['batches_created'])) {
            $details[] = 'Lô hàng khởi tạo: ' . (int) $newVal['batches_created'];
        }
        if (isset($newVal['total_received_amount'])) {
            $details[] = 'Giá trị thực nhận: ' . number_format((float) $newVal['total_received_amount'], 0, ',', '.') . ' đ';
        }
        if (isset($newVal['has_discrepancy'])) {
            $details[] = $newVal['has_discrepancy'] ? 'Có chênh lệch SL đặt / nhận' : 'Đủ số lượng đặt';
        }

        return $details;
    }

    /**
     * @return array<int, string>
     */
    private function buildReceiveSummaryFromItems(PurchaseOrder $po): array
    {
        if (! $po->relationLoaded('items')) {
            return [];
        }

        $items = $po->items;
        $hasDiscrepancy = $items->contains(fn ($i) => abs((float) $i->received_qty - (float) $i->quantity) > 0.0001);
        $totalReceived = $items->sum(fn ($i) => (float) $i->received_qty * (float) $i->unit_price);

        $details = [
            'Số mặt hàng: ' . $items->count(),
            'Giá trị thực nhận: ' . number_format((float) $totalReceived, 0, ',', '.') . ' đ',
            $hasDiscrepancy ? 'Có chênh lệch SL đặt / nhận' : 'Đủ số lượng đặt',
        ];

        if ($po->relationLoaded('stockBatches') && $po->stockBatches->isNotEmpty()) {
            $details[] = 'Lô hàng khởi tạo: ' . $po->stockBatches->count();
        }

        return $details;
    }

    private function statusLabel(?string $statusValue): string
    {
        if ($statusValue === null) {
            return 'Không rõ';
        }

        $enum = PurchaseOrderStatus::tryFrom($statusValue);

        return $enum?->label() ?? $statusValue;
    }

    private function resolveUserName(?User $user): ?string
    {
        if ($user === null) {
            return null;
        }

        return $user->full_name ?: $user->name ?: $user->username;
    }
}
