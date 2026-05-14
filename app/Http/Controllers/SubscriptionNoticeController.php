<?php

namespace App\Http\Controllers;

use App\Modules\Branches\Models\Branch;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionNoticeController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $branch = $request->user()?->branch_id
            ? Branch::query()->find($request->user()->branch_id)
            : null;

        return Inertia::render('subscription/notice', [
            'branch' => [
                'name' => $branch?->name,
            ],
        ]);
    }
}
