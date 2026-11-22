<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PrinterType;
use App\Helpers\ConstantHelper;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $branch_id
 * @property string $name
 * @property PrinterType $type
 * @property string $connection
 * @property bool $is_active
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Branch $branch
 * @property-read Collection<int, Station> $stations
 * @property-read Collection<int, PrintJob> $printJobs
 */
class Printer extends Model
{
    protected $table = ConstantHelper::TABLE_PRINTERS;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => PrinterType::class,
            'is_active' => 'boolean',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function stations(): HasMany
    {
        return $this->hasMany(Station::class, 'printer_id');
    }

    public function printJobs(): HasMany
    {
        return $this->hasMany(PrintJob::class, 'printer_id');
    }
}
