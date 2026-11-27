<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StationCode;
use App\Helpers\ConstantHelper;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $branch_id
 * @property StationCode $station_code
 * @property string $name
 * @property int|null $printer_id
 * @property bool $is_active
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Branch $branch
 * @property-read Printer|null $printer
 * @property-read Collection<int, KitchenTicket> $kitchenTickets
 */
class Station extends Model
{
    protected $table = ConstantHelper::TABLE_STATIONS;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'station_code' => StationCode::class,
            'is_active' => 'boolean',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function printer(): BelongsTo
    {
        return $this->belongsTo(Printer::class, 'printer_id');
    }

    public function kitchenTickets(): HasMany
    {
        return $this->hasMany(KitchenTicket::class, 'station_id');
    }
}
