<?php

namespace Tests\Feature;

use App\Jobs\ExpireStaleConfirmationsJob;
use App\Jobs\WaveChainJob;
use App\Models\BloodRequest;
use App\Models\DonorCandidate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ExpireStaleConfirmationsJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirmed_candidate_past_expiry_is_marked_expired()
    {
        Queue::fake();

        $donor = User::factory()->create(['role' => 'user']);
        $bloodRequest = BloodRequest::factory()->create(['status' => 'open', 'required_bags' => 1]);
        $candidate = DonorCandidate::create([
            'blood_request_id' => $bloodRequest->id,
            'user_id' => $donor->id,
            'status' => 'confirmed',
            'confirmed_at' => now()->subMinutes(config('donorconnect.confirmation_expiry_minutes', 120) + 1),
            'expires_at' => now()->subMinutes(1),
            'kode_verifikasi' => 'EXP001',
            'distance_km' => 2.5,
        ]);

        (new ExpireStaleConfirmationsJob())->handle();

        $candidate->refresh();
        $this->assertEquals('expired', $candidate->status);
    }

    public function test_confirmed_candidate_not_yet_expired_is_left_untouched()
    {
        $donor = User::factory()->create(['role' => 'user']);
        $bloodRequest = BloodRequest::factory()->create(['status' => 'open', 'required_bags' => 1]);
        $candidate = DonorCandidate::create([
            'blood_request_id' => $bloodRequest->id,
            'user_id' => $donor->id,
            'status' => 'confirmed',
            'confirmed_at' => now(),
            'expires_at' => now()->addMinutes(config('donorconnect.confirmation_expiry_minutes', 120)),
            'kode_verifikasi' => 'EXP002',
            'distance_km' => 2.5,
        ]);

        (new ExpireStaleConfirmationsJob())->handle();

        $candidate->refresh();
        $this->assertEquals('confirmed', $candidate->status);
    }

    public function test_expiring_the_only_confirmed_candidate_re_triggers_wave_when_quota_unmet()
    {
        Queue::fake();

        $donor = User::factory()->create(['role' => 'user']);
        $bloodRequest = BloodRequest::factory()->create(['status' => 'open', 'required_bags' => 2]);
        DonorCandidate::create([
            'blood_request_id' => $bloodRequest->id,
            'user_id' => $donor->id,
            'status' => 'confirmed',
            'confirmed_at' => now()->subMinutes(config('donorconnect.confirmation_expiry_minutes', 120) + 1),
            'expires_at' => now()->subMinutes(1),
            'kode_verifikasi' => 'EXP003',
            'distance_km' => 2.5,
        ]);

        (new ExpireStaleConfirmationsJob())->handle();

        Queue::assertPushed(WaveChainJob::class, 1);
    }

    public function test_no_re_trigger_when_quota_already_met_by_other_candidates()
    {
        Queue::fake();

        $donorA = User::factory()->create(['role' => 'user']);
        $donorB = User::factory()->create(['role' => 'user']);
        $bloodRequest = BloodRequest::factory()->create(['status' => 'open', 'required_bags' => 1]);

        DonorCandidate::create([
            'blood_request_id' => $bloodRequest->id,
            'user_id' => $donorA->id,
            'status' => 'confirmed',
            'confirmed_at' => now()->subMinutes(config('donorconnect.confirmation_expiry_minutes', 120) + 1),
            'expires_at' => now()->subMinutes(1),
            'kode_verifikasi' => 'EXP004',
            'distance_km' => 2.5,
        ]);
        DonorCandidate::create([
            'blood_request_id' => $bloodRequest->id,
            'user_id' => $donorB->id,
            'status' => 'verified',
            'confirmed_at' => now()->subDays(1),
            'verified_at' => now(),
            'kode_verifikasi' => 'EXP005',
            'distance_km' => 3.0,
        ]);

        (new ExpireStaleConfirmationsJob())->handle();

        Queue::assertNotPushed(WaveChainJob::class);
    }
}
