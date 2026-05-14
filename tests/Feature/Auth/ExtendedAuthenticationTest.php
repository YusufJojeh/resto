<?php

namespace Tests\Feature\Auth;

use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\Settings\ProfileController;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

class TestVerifiableUser extends User implements MustVerifyEmailContract
{
    use MustVerifyEmail;

    protected $table = 'users';
}

class ExtendedAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'guest'])->group(function () {
            Route::get('/__test/auth/register', [RegisteredUserController::class, 'create']);
            Route::post('/__test/auth/register', [RegisteredUserController::class, 'store']);

            Route::get('/__test/auth/forgot-password', [PasswordResetLinkController::class, 'create']);
            Route::post('/__test/auth/forgot-password', [PasswordResetLinkController::class, 'store']);

            Route::get('/__test/auth/reset-password/{token}', [NewPasswordController::class, 'create']);
            Route::post('/__test/auth/reset-password', [NewPasswordController::class, 'store']);
        });

        Route::middleware(['web', 'auth'])->group(function () {
            Route::get('/__test/auth/confirm-password', [ConfirmablePasswordController::class, 'show']);
            Route::post('/__test/auth/confirm-password', [ConfirmablePasswordController::class, 'store']);

            Route::delete('/__test/settings/profile', [ProfileController::class, 'destroy']);

            Route::get('/__test/auth/verify-email', EmailVerificationPromptController::class)
                ->name('verification.notice');
            Route::post('/__test/auth/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
                ->name('verification.send');
            Route::get('/__test/auth/verify-email/{id}/{hash}', VerifyEmailController::class)
                ->middleware('signed')
                ->name('verification.verify');
        });

        app('router')->getRoutes()->refreshNameLookups();
        app('router')->getRoutes()->refreshActionLookups();
    }

    protected function tearDown(): void
    {
        RateLimiter::clear($this->throttleKeyFor('user@example.com'));
        RateLimiter::clear($this->throttleKeyFor('jose@example.com'));

        parent::tearDown();
    }

    public function test_successful_login_updates_last_login_and_clears_rate_limiter(): void
    {
        $user = User::factory()->create([
            'email' => 'jose@example.com',
            'last_login_at' => null,
        ]);

        $key = $this->throttleKeyFor($user->email);
        RateLimiter::hit($key);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
        $this->assertSame(0, RateLimiter::attempts($key));
    }

    public function test_deactivated_users_are_logged_out_and_receive_validation_error(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'is_active' => false,
        ]);

        $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect('/login')
            ->assertSessionHasErrors([
                'email' => 'Your account has been deactivated.',
            ]);

        $this->assertGuest();
        $this->assertNull($user->fresh()->last_login_at);
    }

    public function test_login_requests_are_throttled_after_too_many_failures(): void
    {
        Event::fake([Lockout::class]);
        $user = User::factory()->create(['email' => 'user@example.com']);

        foreach (range(1, 5) as $attempt) {
            $this->from('/login')->post('/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ])->assertRedirect('/login');
        }

        $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        Event::assertDispatched(Lockout::class);
        $this->assertGuest();
    }

    public function test_registration_controller_can_render_and_register_users_when_exposed(): void
    {
        $this->get('/__test/auth/register')->assertOk();

        $this->post('/__test/auth/register', [
            'name' => 'Walk-in User',
            'email' => 'walkin@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'walkin@example.com',
            'branch_id' => null,
        ]);
    }

    public function test_forgot_password_controller_renders_and_sends_reset_link(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'reset@example.com']);

        $this->get('/__test/auth/forgot-password')->assertOk();

        $this->from('/__test/auth/forgot-password')->post('/__test/auth/forgot-password', [
            'email' => $user->email,
        ])->assertRedirect('/__test/auth/forgot-password')
            ->assertSessionHas('status', 'A reset link will be sent if the account exists.');

        Notification::assertCount(1);
    }

    public function test_new_password_controller_renders_and_resets_password(): void
    {
        Event::fake([PasswordReset::class]);
        $user = User::factory()->create(['email' => 'reset@example.com']);
        $token = Password::broker()->createToken($user);

        $this->get('/__test/auth/reset-password/'.$token.'?email='.$user->email)
            ->assertOk();

        $this->post('/__test/auth/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])->assertRedirect(route('login'))
            ->assertSessionHas('status');

        $this->assertTrue(Hash::check('NewPassword123!', $user->fresh()->password));
        Event::assertDispatched(PasswordReset::class);
    }

    public function test_new_password_controller_rejects_invalid_token(): void
    {
        $user = User::factory()->create(['email' => 'reset@example.com']);

        $this->from('/__test/auth/reset-password/bad-token')->post('/__test/auth/reset-password', [
            'token' => 'bad-token',
            'email' => $user->email,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])->assertRedirect('/__test/auth/reset-password/bad-token')
            ->assertSessionHasErrors('email');
    }

    public function test_confirm_password_controller_accepts_valid_password_and_rejects_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/__test/auth/confirm-password')
            ->assertOk();

        $this->actingAs($user)
            ->from('/__test/auth/confirm-password')
            ->post('/__test/auth/confirm-password', [
                'password' => 'wrong-password',
            ])->assertRedirect('/__test/auth/confirm-password')
            ->assertSessionHasErrors('password');

        $this->actingAs($user)
            ->post('/__test/auth/confirm-password', [
                'password' => 'password',
            ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertIsInt(session('auth.password_confirmed_at'));
    }

    public function test_profile_destroy_deletes_account_and_invalidates_session_when_exposed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->delete('/__test/settings/profile', [
                'password' => 'password',
            ])->assertRedirect('/');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_email_verification_prompt_renders_for_unverified_users_and_redirects_verified_users(): void
    {
        $unverified = $this->createVerifiableUser(['email_verified_at' => null]);

        $this->actingAs($unverified)
            ->get('/__test/auth/verify-email')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('auth/verify-email')
                ->where('status', null));

        $verified = $this->createVerifiableUser(['email_verified_at' => now()]);

        $this->actingAs($verified)
            ->get('/__test/auth/verify-email')
            ->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_email_verification_notification_only_sends_for_unverified_users(): void
    {
        Notification::fake();

        $unverified = $this->createVerifiableUser(['email_verified_at' => null]);

        $this->actingAs($unverified)
            ->from('/__test/auth/verify-email')
            ->post('/__test/auth/email/verification-notification')
            ->assertRedirect('/__test/auth/verify-email')
            ->assertSessionHas('status', 'verification-link-sent');

        Notification::assertSentTo($unverified, \Illuminate\Auth\Notifications\VerifyEmail::class);

        Notification::fake();

        $verified = $this->createVerifiableUser(['email_verified_at' => now()]);

        $this->actingAs($verified)
            ->post('/__test/auth/email/verification-notification')
            ->assertRedirect(route('dashboard', absolute: false));

        Notification::assertNothingSent();
    }

    public function test_verify_email_controller_marks_unverified_users_verified_and_dispatches_event(): void
    {
        Event::fake([Verified::class]);
        $user = $this->createVerifiableUser(['email_verified_at' => null]);

        $this->actingAs($user)
            ->get($this->verificationUrlFor($user))
            ->assertRedirect(route('dashboard', absolute: false).'?verified=1');

        $this->assertNotNull($user->fresh()->email_verified_at);
        Event::assertDispatched(Verified::class);
    }

    public function test_verify_email_controller_redirects_verified_users_without_dispatching_event(): void
    {
        Event::fake([Verified::class]);
        $user = $this->createVerifiableUser(['email_verified_at' => now()]);

        $this->actingAs($user)
            ->get($this->verificationUrlFor($user))
            ->assertRedirect(route('dashboard', absolute: false).'?verified=1');

        Event::assertNotDispatched(Verified::class);
    }

    private function throttleKeyFor(string $email): string
    {
        return Str::transliterate(Str::lower($email).'|127.0.0.1');
    }

    private function createVerifiableUser(array $attributes = []): TestVerifiableUser
    {
        $attributes = array_merge([
            'name' => 'Verifiable User',
            'email' => 'verifiable-'.Str::random(6).'@example.com',
            'password' => 'password',
            'email_verified_at' => null,
        ], $attributes);

        /** @var TestVerifiableUser $user */
        $user = TestVerifiableUser::query()->create(collect($attributes)->except('email_verified_at')->all());
        $user->forceFill(['email_verified_at' => $attributes['email_verified_at']])->save();

        return $user;
    }

    private function verificationUrlFor(TestVerifiableUser $user): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(30),
            [
                'id' => $user->getKey(),
                'hash' => sha1($user->getEmailForVerification()),
            ],
        );
    }
}
