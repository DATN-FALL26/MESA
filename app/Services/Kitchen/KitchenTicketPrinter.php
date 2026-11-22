<?php

declare(strict_types=1);

namespace App\Services\Kitchen;

use App\Enums\PrintDocType;
use App\Enums\PrinterType;
use App\Enums\PrintJobStatus;
use App\Models\KitchenTicket;
use App\Models\Printer;
use App\Models\PrintJob;
use Illuminate\Support\Collection;

/**
 * MOD04 – INV-17: in phiếu trả món (mô phỏng máy in nhiệt).
 *
 * - Tự in khi bếp bấm "Hoàn thành" phiếu: gửi lệnh tới máy in gắn với quầy chế biến.
 * - Máy in đang hoạt động: lệnh in thành công ngay. Máy in tắt / mất kết nối: lệnh in thất bại để KDS báo lỗi.
 * - Quầy chưa gắn máy in riêng: dùng máy in bếp của chi nhánh. Chi nhánh không có máy in bếp nào thì KDS báo lỗi.
 */
class KitchenTicketPrinter
{
    /**
     * Tự in phiếu trả món khi phiếu bếp vừa hoàn thành.
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

    /**
     * Máy in của quầy; quầy chưa gắn máy in riêng (vd. quầy Nước, quầy Món kèm) thì dùng máy in bếp của chi nhánh.
     */
    public function printerFor(KitchenTicket $ticket): ?Printer
    {
        $ticket->loadMissing('station.printer');

        return $ticket->station->printer ?? Printer::query()
            ->where('branch_id', $ticket->branch_id)
            ->where('type', PrinterType::KITCHEN)
            ->orderByDesc('is_active')
            ->orderBy('id')
            ->first();
    }

    private function send(KitchenTicket $ticket, int $retryCount): ?PrintJob
    {
        $printer = $this->printerFor($ticket);

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
