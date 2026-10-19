<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BranchStatus;
use App\Helpers\ConstantHelper;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $address
 * @property string|null $phone
 * @property string $timezone
 * @property BranchStatus $status
 * @property CarbonInterface|null $opened_at
 * @property array<string, mixed>|null $settings
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property CarbonInterface|null $deleted_at
 * @property-read Collection<int, Area> $areas
 * @property-read Collection<int, DiningTable> $diningTables
 * @property-read Collection<int, DiningSession> $diningSessions
 * @property-read Collection<int, BranchMenuItem> $branchMenuItems
 * @property-read Collection<int, Order> $orders
 * @property-read Collection<int, Printer> $printers
 * @property-read Collection<int, Station> $stations
 * @property-read Collection<int, KitchenTicket> $kitchenTickets
 * @property-read Collection<int, Payment> $payments
 * @property-read Collection<int, Invoice> $invoices
 * @property-read Collection<int, Warehouse> $warehouses
 * @property-read Collection<int, PurchaseOrder> $purchaseOrders
 * @property-read Collection<int, AiRun> $aiRuns
 * @property-read Collection<int, AiForecast> $aiForecasts
 * @property-read Collection<int, AiAlert> $aiAlerts
 * @property-read Collection<int, ApprovalRequest> $approvalRequests
 * @property-read Collection<int, DocumentSequence> $documentSequences
 * @property-read Collection<int, Notification> $notifications
 * @property-read Collection<int, DailySalesSummary> $dailySalesSummaries
 * @property-read Collection<int, DailyItemSales> $dailyItemSales
 */
class Branch extends Model
{
    use SoftDeletes;

    protected $table = ConstantHelper::TABLE_BRANCHES;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => BranchStatus::class,
            'opened_at' => 'date',
            'settings' => 'array',
        ];
    }

    public function areas(): HasMany
    {
        return $this->hasMany(Area::class, 'branch_id');
    }

    public function diningTables(): HasMany
    {
        return $this->hasMany(DiningTable::class, 'branch_id');
    }

    public function diningSessions(): HasMany
    {
        return $this->hasMany(DiningSession::class, 'branch_id');
    }

    public function branchMenuItems(): HasMany
    {
        return $this->hasMany(BranchMenuItem::class, 'branch_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'branch_id');
    }

    public function printers(): HasMany
    {
        return $this->hasMany(Printer::class, 'branch_id');
    }

    public function stations(): HasMany
    {
        return $this->hasMany(Station::class, 'branch_id');
    }

    public function kitchenTickets(): HasMany
    {
        return $this->hasMany(KitchenTicket::class, 'branch_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'branch_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'branch_id');
    }

    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class, 'branch_id');
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'branch_id');
    }

    public function aiRuns(): HasMany
    {
        return $this->hasMany(AiRun::class, 'branch_id');
    }

    public function aiForecasts(): HasMany
    {
        return $this->hasMany(AiForecast::class, 'branch_id');
    }

    public function aiAlerts(): HasMany
    {
        return $this->hasMany(AiAlert::class, 'branch_id');
    }

    public function approvalRequests(): HasMany
    {
        return $this->hasMany(ApprovalRequest::class, 'branch_id');
    }

    public function documentSequences(): HasMany
    {
        return $this->hasMany(DocumentSequence::class, 'branch_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'branch_id');
    }

    public function dailySalesSummaries(): HasMany
    {
        return $this->hasMany(DailySalesSummary::class, 'branch_id');
    }

    public function dailyItemSales(): HasMany
    {
        return $this->hasMany(DailyItemSales::class, 'branch_id');
    }
}
