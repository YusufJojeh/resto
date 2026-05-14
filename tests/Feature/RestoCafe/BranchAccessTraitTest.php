<?php

namespace Tests\Feature\RestoCafe;

use App\Modules\Shared\Http\Controllers\Concerns\EnsuresBranchAccess;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class BranchAccessTraitTest extends RestoCafeTestCase
{
    private function traitInstance(): object
    {
        return new class {
            use EnsuresBranchAccess {
                ensureBranchAccess as public;
            }
        };
    }

    public function test_aborts_when_branch_mismatch(): void
    {
        $t = $this->firstTable();
        $other = $this->makeSecondaryBranch();
        $foreign = $other['table'];

        $this->actingAs($this->admin());
        // simulate request with authenticated user having branch 1
        request()->setUserResolver(fn () => $this->admin());

        $this->expectException(HttpException::class);
        $this->traitInstance()->ensureBranchAccess($foreign);
    }

    public function test_passes_when_branch_matches(): void
    {
        $t = $this->firstTable();

        $this->actingAs($this->admin());
        request()->setUserResolver(fn () => $this->admin());

        $this->traitInstance()->ensureBranchAccess($t);
        $this->assertTrue(true); // no exception thrown
    }

    public function test_aborts_when_attribute_null(): void
    {
        $this->actingAs($this->admin());
        request()->setUserResolver(fn () => $this->admin());

        $foreignObj = (object) ['branch_id' => null];
        $this->expectException(HttpException::class);
        $this->traitInstance()->ensureBranchAccess($foreignObj);
    }

    public function test_custom_attribute_path(): void
    {
        $this->actingAs($this->admin());
        request()->setUserResolver(fn () => $this->admin());

        $record = (object) ['nested' => (object) ['branch_id' => 1]];
        $this->traitInstance()->ensureBranchAccess($record, 'nested.branch_id');

        $this->expectException(HttpException::class);
        $record2 = (object) ['nested' => (object) ['branch_id' => 999]];
        $this->traitInstance()->ensureBranchAccess($record2, 'nested.branch_id');
    }
}
