<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UnlockDonorsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = 60;

    /**
     * Handle: unlock donor yang sudah melewati masa tunggu cooldown.
     * Dijalankan oleh scheduler setiap hari.
     */
    public function handle(): void
    {
        $cooldownDays = config('donorconnect.donation_cooldown_days', 56);

        // Cari donor yang:
        // - last_donor_date tidak null
        // - last_donor_date + cooldown hari <= hari ini
        // - is_available = false (belum di-unlock)
        $unlocked = User::where('role', 'user')
            ->where('is_available', false)
            ->whereNotNull('last_donor_date')
            ->where('last_donor_date', '<=', now()->subDays($cooldownDays)->toDateString())
            ->update(['is_available' => true]);

        if ($unlocked > 0) {
            Log::info("UNLOCK: {$unlocked} donors unlocked ({$cooldownDays}-day cooldown passed)");
        }
    }
}
