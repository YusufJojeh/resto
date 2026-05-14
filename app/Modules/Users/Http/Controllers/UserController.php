<?php

namespace App\Modules\Users\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Branches\Models\Branch;
use App\Modules\Shared\Http\Controllers\Concerns\EnsuresBranchAccess;
use App\Modules\Users\Requests\StoreUserRequest;
use App\Modules\Users\Requests\UpdateUserRequest;
use App\Support\Subscription\PlanLimitKey;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    use EnsuresBranchAccess;

    public function index(): Response
    {
        $branchId = request()->user()->branch_id;

        $paginator = User::query()
            ->where('branch_id', $branchId)
            ->with('branch')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('users/index', [
            'users' => collect($paginator->items())->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'branch_id' => $user->branch_id,
                'is_active' => $user->is_active,
                'roles' => $user->getRoleNames()->values(),
            ])->values()->all(),
            'usersPagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('users/form', ['user' => null]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $branch = Branch::query()->with('plan')->findOrFail((int) $request->user()->branch_id);
        $activeUsersCount = User::query()->where('branch_id', $branch->id)->where('is_active', true)->count();
        if ($branch->isAtOrOverPlanLimit(PlanLimitKey::MAX_USERS, $activeUsersCount)) {
            return back()->with('error', 'Your plan\'s maximum number of active staff accounts has been reached.')->withInput();
        }

        $user = User::query()->create([
            'branch_id' => $request->user()->branch_id,
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $user->syncRoles([$request->validated('role')]);

        return to_route('users.index')->with('success', 'User created.');
    }

    public function edit(User $user): Response
    {
        $this->ensureBranchAccess($user);

        return Inertia::render('users/form', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => $user->is_active,
                'role' => $user->getRoleNames()->first(),
            ],
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->ensureBranchAccess($user);

        $payload = [
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'is_active' => $request->boolean('is_active', true),
        ];

        if ($request->filled('password')) {
            $payload['password'] = $request->validated('password');
        }

        $user->update($payload);
        $user->syncRoles([$request->validated('role')]);

        return to_route('users.index')->with('success', 'User updated.');
    }

    public function deactivate(User $user): RedirectResponse
    {
        $this->ensureBranchAccess($user);
        abort_if($user->id === request()->user()->id, 422, 'You cannot deactivate your own account.');
        $user->update(['is_active' => false]);

        return to_route('users.index')->with('success', 'User deactivated.');
    }
}
