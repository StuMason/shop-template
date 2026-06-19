<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * Provision (or refresh) the admin account from ADMIN_EMAIL / ADMIN_PASSWORD.
 *
 * Runs on every boot, so the admin credentials are owned by config, not by a
 * hardcoded seed: set ADMIN_PASSWORD and redeploy to rotate it. When a real
 * admin email is configured, the placeholder `admin@example.com` default is
 * stripped of its roles so it can't be used as a backdoor.
 */
class EnsureAdminUser extends Command
{
    protected $signature = 'shop:ensure-admin';

    protected $description = 'Create or update the admin user from ADMIN_EMAIL / ADMIN_PASSWORD.';

    private const PLACEHOLDER_EMAIL = 'admin@example.com';

    public function handle(): int
    {
        $email = trim((string) config('shop.admin.email'));
        $password = config('shop.admin.password');
        $password = is_string($password) ? $password : null;

        if ($email === '') {
            $this->warn('ADMIN_EMAIL is not set; leaving the admin user untouched.');

            return self::SUCCESS;
        }

        $admin = User::query()->firstOrNew(['email' => $email]);
        $creating = ! $admin->exists;

        if ($creating) {
            $admin->name = 'Admin';
        }

        // The configured password is enforced whenever it is set; a brand-new
        // default admin gets a placeholder so local dev can still log in. The
        // model's `hashed` cast hashes the value on save.
        if ($password !== null && $password !== '') {
            $admin->password = $password;
        } elseif ($creating) {
            $admin->password = 'password';
        }

        $admin->save();
        $admin->assignRole('admin');

        $this->neutralisePlaceholder($email);

        $this->info(($creating ? 'Created' : 'Updated')." admin user {$email}.");

        return self::SUCCESS;
    }

    /**
     * Strip admin/staff roles from the seeded placeholder once a real admin
     * email is in use, so the well-known default can't sign in.
     */
    private function neutralisePlaceholder(string $configuredEmail): void
    {
        if ($configuredEmail === self::PLACEHOLDER_EMAIL) {
            return;
        }

        User::query()
            ->where('email', self::PLACEHOLDER_EMAIL)
            ->get()
            ->each(fn (User $user) => $user->syncRoles([]));
    }
}
