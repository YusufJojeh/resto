<?php

namespace App\Modules\Tables\Http\Controllers;

use App\Enums\UserRole;
use App\Events\TableStatusChanged;
use App\Http\Controllers\Controller;
use App\Modules\Branches\Models\Branch;
use App\Modules\Shared\Http\Controllers\Concerns\EnsuresBranchAccess;
use App\Modules\Tables\Models\RestaurantTable;
use App\Modules\Tables\Requests\StoreTableRequest;
use App\Support\Subscription\PlanLimitKey;
use App\Modules\Tables\Requests\UpdateTableRequest;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TableController extends Controller
{
    use EnsuresBranchAccess;

    public function index(): Response
    {
        $branchId = request()->user()->branch_id;

        return Inertia::render('tables/index', [
            'canManage' => request()->user()->hasAnyRole([UserRole::Admin->value, UserRole::Manager->value]),
            'canCreateOrder' => request()->user()->hasAnyRole([UserRole::Admin->value, UserRole::Manager->value, UserRole::Waiter->value]),
            'tables' => RestaurantTable::query()
                ->where('branch_id', $branchId)
                ->orderBy('number')
                ->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('tables/form', ['table' => null]);
    }

    public function store(StoreTableRequest $request): RedirectResponse
    {
        $branch = Branch::query()->with('plan')->findOrFail((int) $request->user()->branch_id);
        $tablesCount = RestaurantTable::query()->where('branch_id', $branch->id)->count();
        if ($branch->isAtOrOverPlanLimit(PlanLimitKey::MAX_TABLES, $tablesCount)) {
            return back()->with('error', 'Your plan\'s maximum number of tables has been reached.')->withInput();
        }

        RestaurantTable::query()->create([
            'branch_id' => $request->user()->branch_id,
            ...$request->validated(),
        ]);

        return to_route('tables.index')->with('success', 'Table created.');
    }

    public function edit(RestaurantTable $table): Response
    {
        $this->ensureBranchAccess($table);

        return Inertia::render('tables/form', ['table' => $table]);
    }

    public function update(UpdateTableRequest $request, RestaurantTable $table): RedirectResponse
    {
        $this->ensureBranchAccess($table);
        $table->update($request->safe()->except('status'));

        return to_route('tables.index')->with('success', 'Table updated.');
    }

    public function status(Request $request, RestaurantTable $table): RedirectResponse
    {
        $this->ensureBranchAccess($table);
        abort_unless($request->user()->hasAnyRole([UserRole::Admin->value, UserRole::Manager->value]), 403);

        $validated = $request->validate([
            'status' => ['required', 'in:available,reserved'],
        ]);

        $previousStatus = $table->status->value;
        $table->update(['status' => $validated['status']]);

        TableStatusChanged::dispatch($table->refresh(), $previousStatus);

        return back()->with('success', 'Table status updated.');
    }
}
