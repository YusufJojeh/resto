<?php

namespace App\Modules\Inventory\Actions;

use App\Enums\InventoryMovementType;
use App\Models\User;
use App\Notifications\OperationalNotification;
use App\Modules\Inventory\Models\InventoryItem;
use App\Support\Notifications\BranchRoleNotifier;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AdjustStock
{
    public function handle(User $user, InventoryItem $item, float $adjustment, string $reason): InventoryItem
    {
        return DB::transaction(function () use ($user, $item, $adjustment, $reason) {
            $before = (float) $item->quantity;
            $after = round($before + $adjustment, 3);

            if ($after < 0) {
                throw new RuntimeException('Stock cannot go below zero.');
            }

            $type = $adjustment > 0 ? InventoryMovementType::Restock : InventoryMovementType::Deduction;

            $item->update(['quantity' => $after]);

            $item->stockMovements()->create([
                'user_id' => $user->id,
                'type' => $type,
                'quantity_change' => $adjustment,
                'quantity_before' => $before,
                'quantity_after' => $after,
                'reason' => $reason,
            ]);

            if ($after <= (float) $item->low_threshold) {
                app(BranchRoleNotifier::class)->notifyByRoles(
                    (int) $item->branch_id,
                    ['admin', 'manager'],
                    new OperationalNotification(
                        'low_inventory',
                        'Low inventory alert',
                        "{$item->name} is at or below threshold.",
                        (int) $item->branch_id,
                    ),
                );
            }

            return $item->refresh();
        });
    }
}
