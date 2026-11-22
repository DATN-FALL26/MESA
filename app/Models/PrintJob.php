<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PrintDocType;
use App\Enums\PrintJobStatus;
use App\Helpers\ConstantHelper;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $printer_id
 * @property PrintDocType $doc_type
 * @property int $ref_id
 * @property array<string, mixed>|null $payload
 * @property PrintJobStatus $status
 * @property int $retry_count
 * @property CarbonInterface|null $printed_at
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Printer $printer
 */
class PrintJob extends Model
{
    protected $table = ConstantHelper::TABLE_PRINT_JOBS;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'doc_type' => PrintDocType::class,
            'payload' => 'array',
            'status' => PrintJobStatus::class,
            'retry_count' => 'integer',
            'printed_at' => 'datetime',
        ];
    }

    public function printer(): BelongsTo
    {
        return $this->belongsTo(Printer::class, 'printer_id');
    }
}
