<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-admin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Interactively create a new administrator account with strong password validation';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('--- Create Initial Administrator Account ---');

        // 1. Ask for Name
        $name = $this->ask('Enter administrator name');
        $nameValidator = Validator::make(['name' => $name], [
            'name' => ['required', 'string', 'max:255'],
        ]);

        if ($nameValidator->fails()) {
            foreach ($nameValidator->errors()->all() as $error) {
                $this->error($error);
            }
            return self::FAILURE;
        }

        // 2. Ask for Email
        $email = $this->ask('Enter administrator email');
        $emailValidator = Validator::make(['email' => $email], [
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
        ]);

        if ($emailValidator->fails()) {
            foreach ($emailValidator->errors()->all() as $error) {
                $this->error($error);
            }
            return self::FAILURE;
        }

        // 3. Ask for Password (hidden input)
        $password = $this->secret('Enter administrator password (min 12 chars, mixed case, number, symbol)');
        $passwordConfirmation = $this->secret('Confirm administrator password');

        if ($password !== $passwordConfirmation) {
            $this->error('Passwords do not match.');
            return self::FAILURE;
        }

        $passwordValidator = Validator::make(['password' => $password], [
            'password' => [
                'required',
                'string',
                Password::min(12)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
        ]);

        if ($passwordValidator->fails()) {
            foreach ($passwordValidator->errors()->all() as $error) {
                $this->error($error);
            }
            return self::FAILURE;
        }

        // 4. Resolve or create admin role
        $role = Role::firstOrCreate(
            ['name' => 'admin'],
            [
                'display_name' => 'Administrator',
                'description' => 'System administrator with full access to user management, leave approvals, reports, and settings.',
            ]
        );

        // 5. Create user
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role_id' => $role->id,
            'color' => '#0F8B8D',
            'must_change_password' => false,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // 6. Record audit log entry
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'admin.created',
            'description' => "Initial administrator account [{$user->email}] created via Artisan command.",
            'ip_address' => '127.0.0.1',
            'created_at' => now(),
        ]);

        $this->newLine();
        $this->info("✓ Administrator [{$user->email}] successfully created!");
        $this->table(
            ['Field', 'Value'],
            [
                ['ID', $user->id],
                ['Name', $user->name],
                ['Email', $user->email],
                ['Role', $role->display_name],
                ['Created At', $user->created_at->toDateTimeString()],
            ]
        );

        return self::SUCCESS;
    }
}
