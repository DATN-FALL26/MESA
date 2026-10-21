<?php

declare(strict_types=1);

namespace App\Services\Kitchen;

use App\Enums\PrintDocType;
use App\Enums\PrintJobStatus;
use App\Models\KitchenTicket;
use App\Models\PrintJob;
use Illuminate\Support\Collection;

/**
 * MOD04 – INV-17: in phiếu bếp (mô phỏng máy in nhiệt).
 *
 * - Tự in khi order được gửi bếp: gửi lệnh tới máy in gắn với quầy chế biến.
 * - Máy in đang hoạt động: lệnh in thành công ngay. Máy in tắt / mất kết nối: lệnh in thất bại để KDS báo lỗi.
 * - Quầy chưa gắn máy in: không tạo được lệnh in, KDS báo lỗi để bếp in lại qua trình duyệt.
 */
class KitchenTicketPrinter
{
    /**
     * Tự in phiếu khi phiếu bếp vừa được tạo.
     */
    public function autoPrint(KitchenTicket $ticket): ?PrintJob
    {
        return $this->send($ticket, retryCount: 0);
    }

    /**
     * In lại phiếu (bếp bấm "In lại phiếu").
     */
    public function reprint(KitchenTicket $ticket): ?PrintJob
    {
        $previousJobCount = PrintJob::query()
            ->where('doc_type', PrintDocType::KITCHEN_TICKET)
            ->where('ref_id', $ticket->getKey())
            ->count();

        return $this->send($ticket, retryCount: $previousJobCount);
    }

    /**
     * Lệnh in gần nhất của từng phiếu bếp, theo mã phiếu.
     *
     * @param  Collection<int, int>  $ticketIds
     * @return Collection<int, PrintJob>
     */
    public function latestJobsFor(Collection $ticketIds): Collection
    {
        if ($ticketIds->isEmpty()) {
            return collect();
        }

        return PrintJob::query()
            ->where('doc_type', PrintDocType::KITCHEN_TICKET)
            ->whereIn('ref_id', $ticketIds->all())
            ->orderBy('id')
            ->get()
            ->keyBy('ref_id');
    }

    private function send(KitchenTicket $ticket, int $retryCount): ?PrintJob
    {
        $ticket->loadMissing('station.printer');
        $printer = $ticket->station->printer;

        if ($printer === null) {
            return null;
        }

        $printerIsOnline = $printer->is_active;

        return PrintJob::query()->create([
            'printer_id' => $printer->getKey(),
            'doc_type' => PrintDocType::KITCHEN_TICKET,
            'ref_id' => $ticket->getKey(),
            'payload' => [
                'ticket_no' => $ticket->ticket_no,
                'station' => $ticket->station->name,
                'order_id' => $ticket->order_id,
            ],
            'status' => $printerIsOnline ? PrintJobStatus::PRINTED : PrintJobStatus::FAILED,
            'retry_count' => $retryCount,
            'printed_at' => $printerIsOnline ? now() : null,
        ]);
    }
}
