<?php

namespace Tests\Feature;

use App\Jobs\SendDonorNotificationJob;
use App\Jobs\WaveChainJob;
use App\Models\AdminAlert;
use App\Models\BloodRequest;
use App\Models\User;
use App\Services\DonorFilterService;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WaveChainJobAlertTest extends TestCase
{
    use RefreshDatabase;

    public function test_wave_2_creates_alert_when_new_donors_notified()
    {
        Queue::fake();

        $donor = User::factory()->create(['role' => 'user']);
        $bloodRequest = BloodRequest::factory()->create(['status' => 'open', 'required_bags' => 2, 'deadline' => now()->addDay()]);

        $filterService = \Mockery::mock(DonorFilterService::class);
        $filterService->shouldReceive('filterEligibleDonors')
            ->once()
            ->andReturn(collect([(object) ['id' => $donor->id, 'distance_km' => 7.5]]));

        (new WaveChainJob($bloodRequest->id, 2))->handle($filterService, app(WhatsAppService::class));

        $this->assertDatabaseCount('admin_alerts', 1);
        $alert = AdminAlert::first();
        $this->assertEquals($bloodRequest->id, $alert->blood_request_id);
        $this->assertStringContainsString('Gelombang 2', $alert->message);
        $this->assertStringContainsString('1 pendonor baru', $alert->message);

        Queue::assertPushed(SendDonorNotificationJob::class, 1);
        Queue::assertPushed(WaveChainJob::class, 1); // chained to wave 3
    }

    public function test_wave_2_creates_alert_when_no_eligible_donors()
    {
        Queue::fake();

        $bloodRequest = BloodRequest::factory()->create(['status' => 'open', 'required_bags' => 2, 'deadline' => now()->addDay()]);

        $filterService = \Mockery::mock(DonorFilterService::class);
        $filterService->shouldReceive('filterEligibleDonors')->once()->andReturn(collect());

        (new WaveChainJob($bloodRequest->id, 2))->handle($filterService, app(WhatsAppService::class));

        $alert = AdminAlert::first();
        $this->assertStringContainsString('tidak ada pendonor baru', $alert->message);
    }

    public function test_wave_1_does_not_create_alert()
    {
        Queue::fake();

        $donor = User::factory()->create(['role' => 'user']);
        $bloodRequest = BloodRequest::factory()->create(['status' => 'open', 'required_bags' => 2, 'deadline' => now()->addDay()]);

        $filterService = \Mockery::mock(DonorFilterService::class);
        $filterService->shouldReceive('filterEligibleDonors')
            ->once()
            ->andReturn(collect([(object) ['id' => $donor->id, 'distance_km' => 2.0]]));

        (new WaveChainJob($bloodRequest->id, 1))->handle($filterService, app(WhatsAppService::class));

        $this->assertDatabaseCount('admin_alerts', 0);
    }

    public function test_repeated_wave_dispatch_does_not_duplicate_alert()
    {
        Queue::fake();

        $bloodRequest = BloodRequest::factory()->create(['status' => 'open', 'required_bags' => 2, 'deadline' => now()->addDay()]);

        $filterService = \Mockery::mock(DonorFilterService::class);
        $filterService->shouldReceive('filterEligibleDonors')->twice()->andReturn(collect());

        (new WaveChainJob($bloodRequest->id, 3))->handle($filterService, app(WhatsAppService::class));
        (new WaveChainJob($bloodRequest->id, 3))->handle($filterService, app(WhatsAppService::class));

        $this->assertDatabaseCount('admin_alerts', 1);
    }
}
