<?php

namespace App\Jobs;

use App\Models\BloodRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckRequestStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = 60;

    /**
     * Handle: cek semua request 'open' → auto-fulfill / auto-cancel.
     * Dijalankan oleh scheduler setiap 5 menit.
     */
    public function handle(): void
    {
        $now = now();

        // ── 1. Auto-fulfill: verified >= required_bags ──
        $openRequests = BloodRequest::where('status', 'open')->get();

        foreach ($openRequests as $request) {
            // checkAndAutoFulfill() returns true when the transition to
            // 'fulfilled' actually happened on this call — use that to drive
            // the log + WA notification without an extra status re-check.
            if ($request->checkAndAutoFulfill()) {
                Log::info("Request #{$request->id} AUTO-FULFILLED (quota of verified donors met)");
                app(\App\Services\WhatsAppService::class)->notifyRequesterFulfilled($request);
                continue;
            }

            // ── 2. Auto-cancel: deadline terlewati ──
            if ($request->deadline && $now->greaterThan($request->deadline)) {
                $request->update(['status' => 'cancelled']);
                Log::info("Request #{$request->id} AUTO-CANCELLED (deadline {$request->deadline} passed)");
            }
        }
    }
}
