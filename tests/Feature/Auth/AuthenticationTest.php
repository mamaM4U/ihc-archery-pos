<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard.access', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    public function test_inactive_users_cannot_authenticate(): void
    {
        $user = User::factory()->inactive()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
        $this->assertEquals(
            'Akun Anda tidak aktif. Silakan hubungi admin.',
            session('errors')->first('email')
        );
    }

    public function test_admin_middleware_allows_admin(): void
    {
        Route::middleware('role.admin')->get('/_test_admin', function () {
            return 'ok';
        });

        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/_test_admin');

        $response->assertStatus(200);
        $response->assertSee('ok');
    }

    public function test_admin_middleware_denies_non_admin(): void
    {
        Route::middleware('role.admin')->get('/_test_admin_deny', function () {
            return 'ok';
        });

        $member = User::factory()->member()->create();

        $response = $this->actingAs($member)->get('/_test_admin_deny');

        $response->assertStatus(403);
    }

    public function test_coach_middleware_allows_coach(): void
    {
        Route::middleware('role.coach')->get('/_test_coach', function () {
            return 'ok';
        });

        $coach = User::factory()->coach()->create();

        $response = $this->actingAs($coach)->get('/_test_coach');

        $response->assertStatus(200);
        $response->assertSee('ok');
    }

    public function test_coach_middleware_denies_non_coach(): void
    {
        Route::middleware('role.coach')->get('/_test_coach_deny', function () {
            return 'ok';
        });

        $member = User::factory()->member()->create();

        $response = $this->actingAs($member)->get('/_test_coach_deny');

        $response->assertStatus(403);
    }

    public function test_guardian_middleware_allows_guardian(): void
    {
        Route::middleware('role.guardian')->get('/_test_guardian', function () {
            return 'ok';
        });

        $guardian = User::factory()->guardian()->create();

        $response = $this->actingAs($guardian)->get('/_test_guardian');

        $response->assertStatus(200);
        $response->assertSee('ok');
    }

    public function test_guardian_middleware_denies_non_guardian(): void
    {
        Route::middleware('role.guardian')->get('/_test_guardian_deny', function () {
            return 'ok';
        });

        $member = User::factory()->member()->create();

        $response = $this->actingAs($member)->get('/_test_guardian_deny');

        $response->assertStatus(403);
    }

    public function test_member_middleware_allows_member(): void
    {
        Route::middleware('role.member')->get('/_test_member', function () {
            return 'ok';
        });

        $member = User::factory()->member()->create();

        $response = $this->actingAs($member)->get('/_test_member');

        $response->assertStatus(200);
        $response->assertSee('ok');
    }

    public function test_member_middleware_denies_non_member(): void
    {
        Route::middleware('role.member')->get('/_test_member_deny', function () {
            return 'ok';
        });

        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/_test_member_deny');

        $response->assertStatus(403);
    }
}
