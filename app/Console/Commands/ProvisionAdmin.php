<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

#[Signature('app:provision-admin')]
#[Description('Create the first administrator account')]
class ProvisionAdmin extends Command
{
    public function handle(): int
    {
        if (User::where('role', UserRole::Admin->value)->exists()) {
            $this->components->error('An administrator already exists.');

            return self::FAILURE;
        }

        $validatedData = Validator::make([
            'name' => $this->ask('Administrator name?'),
            'email' => $this->ask('Administrator email?'),
            'password' => $this->secret('Administrator password?'),
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::min(8)],
        ]);

        if ($validatedData->fails()) {
            $this->components->error('The administrator details are invalid.');

            return self::FAILURE;
        }

        $admin = new User($validatedData->validated());
        $admin->role = UserRole::Admin;
        $admin->save();

        $this->components->info('Administrator account created.');

        return self::SUCCESS;
    }
}
