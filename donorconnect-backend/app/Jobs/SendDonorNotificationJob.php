<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use App\Models\WaLog;
use Exception;

class SendDonorNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = 300; // 5 minutes

    protected User $user;
    protected string $message;
    protected ?int $bloodRequestId;

    /**
     * Create a new job instance.
     */
    public function __construct(User $user, string $message, ?int $bloodRequestId = null)
    {
        $this->user = $user;
        $this->message = $message;
        $this->bloodRequestId = $bloodRequestId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // 1. Initial State — dedupe on (user, blood_request, phone, message)
        // so a queue retry of this same job updates the existing log row
        // instead of inserting a new one each attempt, while still keeping
        // separate log rows for different blood requests even if their
        // rendered message text happens to be identical.
        $log = WaLog::firstOrCreate(
            [
                'user_id' => $this->user->id,
                'blood_request_id' => $this->bloodRequestId,
                'phone' => $this->user->phone,
                'message' => $this->message,
            ],
            ['status' => 'pending']
        );
        $log->update(['status' => 'pending', 'error_message' => null]);

        try {
            $token = config('services.fonnte.token');

            if (!$token || $token === 'mock-token') {
                $log->update(['status' => 'failed', 'error_message' => 'Fonnte API Key (FONNTE_API_KEY) not set in .env']);
                return;
            }

            // 2. Fonnte HTTP POST payload
            $response = Http::timeout(10)->withHeaders([
                'Authorization' => $token,
            ])->post('https://api.fonnte.com/send', [
                'target' => $this->user->phone,
                'message' => $this->message,
                'countryCode' => '62',
            ]);

            // 3. Status validation
            if ($response->successful()) {
                $responseData = $response->json();
                if (isset($responseData['status']) && $responseData['status'] === true) {
                    $log->update(['status' => 'success']);
                    return;
                }
            }

            // 4. Force fail on bad response
            $errorMessage = $response->body();
            $log->update(['status' => 'failed', 'error_message' => substr($errorMessage, 0, 500)]);
            
            throw new Exception("Fonnte WA failed: " . $errorMessage);
            
        } catch (Exception $e) {
            $log->update([
                'status' => 'failed', 
                'error_message' => substr($e->getMessage(), 0, 500)
            ]);
            
            // Re-throw so Queue Worker detects failure and applies backoff
            throw $e;
        }
    }
}
