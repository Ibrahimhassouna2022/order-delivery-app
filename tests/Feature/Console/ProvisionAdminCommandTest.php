<?php

namespace Tests\Feature\Console;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProvisionAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_can_provision_the_initial_admin_with_a_hidden_password(): void
    {
        $this->artisan('app:provision-admin')
            ->expectsQuestion('Administrator name?', 'Initial Administrator')
            ->expectsQuestion('Administrator email?', 'admin@example.test')
            ->expectsQuestion('Administrator password?', 'correct-horse-battery')
            ->expectsOutputToContain('Administrator account created')
            ->assertExitCode(0);

        $admin = User::where('email', 'admin@example.test')->firstOrFail();

        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertTrue(Hash::check('correct-horse-battery', $admin->password));
    }

    public function test_command_refuses_to_create_another_initial_admin(): void
    {
        User::factory()->create(['role' => UserRole::Admin]);

        $this->artisan('app:provision-admin')
            ->expectsOutputToContain('An administrator already exists')
            ->assertExitCode(1);

        $this->assertDatabaseCount('users', 1);
    }

    public function test_command_rejects_invalid_initial_admin_data(): void
    {
        $this->artisan('app:provision-admin')
            ->expectsQuestion('Administrator name?', '')
            ->expectsQuestion('Administrator email?', 'not-an-email')
            ->expectsQuestion('Administrator password?', 'short')
            ->expectsOutputToContain('The administrator details are invalid')
            ->assertExitCode(1);

        $this->assertDatabaseCount('users', 0);
    }
}
