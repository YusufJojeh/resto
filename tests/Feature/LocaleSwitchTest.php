<?php

namespace Tests\Feature;

use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleSwitchTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_locale_sets_cookie_and_redirects_back(): void
    {
        $response = $this->from('/')->post(route('locale.update'), ['locale' => 'ar']);

        $response->assertRedirect('/');
        $response->assertCookie(SetLocale::COOKIE, 'ar');
    }

    public function test_post_locale_rejects_unsupported_value(): void
    {
        $this->from('/')
            ->post(route('locale.update'), ['locale' => 'fr'])
            ->assertStatus(422);
    }

    public function test_locale_cookie_is_applied_on_subsequent_request(): void
    {
        $this->withCookie(SetLocale::COOKIE, 'ar')
            ->get('/')
            ->assertOk();

        $this->assertSame('ar', app()->getLocale());
    }

    public function test_default_locale_when_no_cookie(): void
    {
        $this->get('/')->assertOk();
        $this->assertSame('en', app()->getLocale());
    }

    public function test_dir_for_arabic_is_rtl(): void
    {
        $this->assertSame('rtl', SetLocale::dirFor('ar'));
    }

    public function test_dir_for_non_arabic_is_ltr(): void
    {
        $this->assertSame('ltr', SetLocale::dirFor('en'));
        $this->assertSame('ltr', SetLocale::dirFor('fr'));
        $this->assertSame('ltr', SetLocale::dirFor(''));
    }
}
