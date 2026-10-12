<?php

namespace Tests\Feature\Models;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_users_default_to_customer_role(): void
    {
        $user = User::factory()->create();

        $this->assertSame(UserRole::Customer, $user->fresh()->role);
    }

    public function test_role_is_cast_to_user_role_enum(): void
    {
        foreach (UserRole::cases() as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->assertSame($role, $user->fresh()->role);
        }
    }

    public function test_untrusted_mass_assignment_cannot_change_user_role(): void
    {
        $user = User::factory()->create();
        $user->fill(['role' => 'admin']);

        $this->assertSame(UserRole::Customer, $user->role);
    }
}
