<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $locale = $request->input('locale');

        abort_unless(in_array($locale, SetLocale::SUPPORTED, true), 422);

        // 1 year, signed not required (cookie is non-sensitive)
        return back()->withCookie(cookie(SetLocale::COOKIE, $locale, 60 * 24 * 365));
    }
}
