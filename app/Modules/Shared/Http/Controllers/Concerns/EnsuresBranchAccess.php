<?php

namespace App\Modules\Shared\Http\Controllers\Concerns;

trait EnsuresBranchAccess
{
    protected function ensureBranchAccess(object $record, ?string $attribute = 'branch_id'): void
    {
        $branchId = data_get($record, $attribute ?? 'branch_id');

        abort_unless(
            $branchId !== null && (int) $branchId === (int) request()->user()->branch_id,
            404,
        );
    }
}
