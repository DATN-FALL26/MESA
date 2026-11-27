<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserStatus;
use App\Helpers\ConstantHelper;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Arr;

/**
 * @property int $id
 * @property string $username
 * @property string|null $employee_code
 * @property string $full_name
 * @property string|null $email
 * @property string|null $phone
 * @property string $password
 * @property string|null $pin_hash
 * @property int|null $department_id
 * @property UserStatus $status
 * @property CarbonInterface|null $last_login_at
 * @property string|null $remember_token
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property CarbonInterface|null $deleted_at
 * @property-read Department|null $department
 * @property-read Collection<int, UserRole> $userRoles
 * @property-read Collection<int, Role> $roles
 * @property-read Collection<int, DiningSession> $openedDiningSessions
 * @property-read Collection<int, Order> $createdOrders
 * @property-read Collection<int, Order> $paymentRequestedOrders
 * @property-read Collection<int, Order> $cancelledOrders
 * @property-read Collection<int, OrderBatch> $releasedOrderBatches
 * @property-read Collection<int, OrderItem> $cancelledOrderItems
 * @property-read Collection<int, OrderStatusHistory> $orderStatusHistories
 * @property-read Collection<int, KitchenTicket> $updatedKitchenTickets
 * @property-read Collection<int, Payment> $confirmedPayments
 * @property-read Collection<int, StockMovement> $createdStockMovements
 * @property-read Collection<int, PurchaseOrder> $createdPurchaseOrders
 * @property-read Collection<int, PurchaseOrder> $approvedPurchaseOrders
 * @property-read Collection<int, PurchaseOrder> $receivedPurchaseOrders
 * @property-read Collection<int, StockAdjustment> $createdStockAdjustments
 * @property-read Collection<int, StockAdjustment> $approvedStockAdjustments
 * @property-read Collection<int, AiRun> $triggeredAiRuns
 * @property-read Collection<int, AiAlert> $acknowledgedAiAlerts
 * @property-read Collection<int, ApprovalRequest> $requestedApprovals
 * @property-read Collection<int, ApprovalRequest> $decidedApprovals
 * @property-read Collection<int, Notification> $targetNotifications
 * @property-read Collection<int, NotificationRead> $notificationReads
 */
class User extends Authenticatable
{
    use Notifiable, SoftDeletes;

    protected $table = ConstantHelper::TABLE_USERS;

    protected $guarded = [];

    protected $hidden = [
        'password',
        'pin_hash',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => UserStatus::class,
            'last_login_at' => 'datetime',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function userRoles(): HasMany
    {
        return $this->hasMany(UserRole::class, 'user_id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, ConstantHelper::TABLE_USER_ROLES, 'user_id', 'role_id');
    }

    public function activeRoles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, ConstantHelper::TABLE_USER_ROLES, 'user_id', 'role_id')
            ->wherePivot('valid_from', '<=', now())
            ->where(fn ($query) => $query
                ->whereNull('user_roles.valid_to')
                ->orWhere('user_roles.valid_to', '>=', now())
            );
    }

    public function hasRole(string|array $roles): bool
    {
        $roles = Arr::wrap($roles);

        if ($roles === []) {
            return false;
        }

        return $this->activeRoles()
            ->whereIn('roles.code', $roles)
            ->exists();
    }

    public function hasAnyRole(string|array $roles): bool
    {
        return $this->hasRole($roles);
    }

    public function hasPermission(string $permission): bool
    {
        return Permission::query()
            ->where('permissions.code', $permission)
            ->whereHas('roles', function ($query) {
                $query->whereHas('userRoles', function ($userRoleQuery) {
                    $userRoleQuery
                        ->where('user_roles.user_id', $this->getKey())
                        ->where('user_roles.valid_from', '<=', now())
                        ->where(function ($validityQuery) {
                            $validityQuery
                                ->whereNull('user_roles.valid_to')
                                ->orWhere('user_roles.valid_to', '>=', now());
                        });
                });
            })
            ->exists();
    }

    public function hasAnyPermission(string|array $permissions): bool
    {
        $permissions = Arr::wrap($permissions);

        if ($permissions === []) {
            return false;
        }

        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    public function openedDiningSessions(): HasMany
    {
        return $this->hasMany(DiningSession::class, 'opened_by');
    }

    public function createdOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'created_by');
    }

    public function paymentRequestedOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'payment_requested_by');
    }

    public function cancelledOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'cancelled_by');
    }

    public function releasedOrderBatches(): HasMany
    {
        return $this->hasMany(OrderBatch::class, 'released_by');
    }

    public function cancelledOrderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'cancelled_by');
    }

    public function orderStatusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class, 'changed_by');
    }

    public function updatedKitchenTickets(): HasMany
    {
        return $this->hasMany(KitchenTicket::class, 'updated_by');
    }

    public function confirmedPayments(): HasMany
    {
        return $this->hasMany(Payment::class, 'confirmed_by');
    }

    public function createdStockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'created_by');
    }

    public function createdPurchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'created_by');
    }

    public function approvedPurchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'approved_by');
    }

    public function receivedPurchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'received_by');
    }

    public function createdStockAdjustments(): HasMany
    {
        return $this->hasMany(StockAdjustment::class, 'created_by');
    }

    public function approvedStockAdjustments(): HasMany
    {
        return $this->hasMany(StockAdjustment::class, 'approved_by');
    }

    public function triggeredAiRuns(): HasMany
    {
        return $this->hasMany(AiRun::class, 'triggered_by');
    }

    public function acknowledgedAiAlerts(): HasMany
    {
        return $this->hasMany(AiAlert::class, 'acknowledged_by');
    }

    public function requestedApprovals(): HasMany
    {
        return $this->hasMany(ApprovalRequest::class, 'requested_by');
    }

    public function decidedApprovals(): HasMany
    {
        return $this->hasMany(ApprovalRequest::class, 'decided_by');
    }

    public function targetNotifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'target_user_id');
    }

    public function notificationReads(): HasMany
    {
        return $this->hasMany(NotificationRead::class, 'user_id');
    }
}
