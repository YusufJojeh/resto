<?php

namespace App\Modules\Orders\Models;

use App\Enums\OrderStatus;
use App\Models\User;
use App\Modules\Billing\Models\Invoice;
use App\Modules\Branches\Models\Branch;
use App\Modules\Tables\Models\RestaurantTable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'table_id',
        'user_id',
        'status',
        'notes',
        'cancellation_reason',
        'cancelled_at',
        'served_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'cancelled_at' => 'datetime',
            'served_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(RestaurantTable::class, 'table_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function getSubtotalAttribute(): string
    {
        return number_format((float) $this->items->sum('subtotal'), 2, '.', '');
    }
}
