<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginAuthenticationTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'username' => 'auth_test_user',
            'password' => 'CorrectPassword@123',
            'is_active' => true,
        ]);
    }

    /** 1. Valid credentials log in successfully */
    public function test_valid_credentials_authenticate_and_redirect(): void
    {
        $response = $this->post(route('login.post'), [
            'username' => 'auth_test_user',
            'password' => 'CorrectPassword@123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->user);
    }

    /** 2. Wrong password fails with validation error and no 500 */
    public function test_wrong_password_fails_gracefully(): void
    {
        $response = $this->post(route('login.post'), [
            'username' => 'auth_test_user',
            'password' => 'CompletelyWrongPassword',
        ]);

        $response->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    /** 3. Corrupted or non-bcrypt password in DB does NOT throw 500 error */
    public function test_corrupted_password_in_db_does_not_throw_500_server_error(): void
    {
        // Directly store a malformed/non-bcrypt string in the database
        DB::table('users')->where('id', $this->user->id)->update([
            'password' => 'y2.ejXfnYzd79PrHXPoX3OP2.QYIflXz6tchryCXeILa9YYgidy5y',
        ]);

        $response = $this->post(route('login.post'), [
            'username' => 'auth_test_user',
            'password' => 'Admin@123',
        ]);

        // Must redirect back with error, NEVER return HTTP 500
        $response->assertStatus(302);
        $response->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    /** 4. Inactive user cannot log in */
    public function test_inactive_user_cannot_log_in(): void
    {
        $this->user->update(['is_active' => false]);

        $response = $this->post(route('login.post'), [
            'username' => 'auth_test_user',
            'password' => 'CorrectPassword@123',
        ]);

        $response->assertSessionHasErrors('login');
        $this->assertGuest();
    }
}
