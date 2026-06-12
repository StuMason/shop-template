<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\WeeklyDigestNotification;
use App\Support\ShopMetrics;
use Illuminate\Console\Command;

class SendWeeklyDigest extends Command
{
    protected $signature = 'shop:send-weekly-digest';

    protected $description = 'Email admins the week in numbers';

    public function handle(ShopMetrics $metrics): int
    {
        $weekly = $metrics->forPeriod(now()->subWeek());

        $admins = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'admin'))
            ->get();

        foreach ($admins as $admin) {
            $admin->notify(new WeeklyDigestNotification($weekly));
        }

        $this->info("Digest sent to {$admins->count()} admins.");

        return self::SUCCESS;
    }
}
