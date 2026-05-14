<?php

namespace App\Support\Notifications;

use App\Enums\UserRole;
use App\Models\User;
use App\Notifications\OperationalNotification;

class BranchRoleNotifier
{
    /** @param list<UserRole|string> $roles */
    public function notifyByRoles(int $branchId, array $roles, OperationalNotification $notification): void
    {
        $roleValues = array_map(static fn ($role) => $role instanceof UserRole ? $role->value : (string) $role, $roles);

        User::query()
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->role($roleValues)
            ->get()
            ->each(fn (User $user) => $user->notify($notification));
    }
}
