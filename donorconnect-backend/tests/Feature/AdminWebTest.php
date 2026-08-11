<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use App\Models\User;
use App\Models\BloodRequest;

class AdminWebTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'testadmin@example.com',
            'password' => bcrypt('password123')
        ]);

        $this->user = User::factory()->create([
            'role' => 'user',
            'is_available' => true
        ]);
    }

    public function test_admin_login_page_is_accessible()
    {
        $response = $this->get('/admin/login');
        $response->assertStatus(200);
    }

    public function test_admin_can_login_with_valid_credentials()
    {
        $response = $this->post('/admin/login', [
            'email' => 'testadmin@example.com',
            'password' => 'password123'
        ]);

        $response->assertRedirect('/admin/dashboard');
        $this->assertAuthenticatedAs($this->admin);
    }

    public function test_regular_user_cannot_login_to_admin_panel()
    {
        $response = $this->post('/admin/login', [
            'email' => $this->user->email,
            'password' => 'password'
        ]);

        $response->assertSessionHasErrors();
        $this->assertGuest();
    }

    public function test_admin_dashboard_renders_stats()
    {
        $this->actingAs($this->admin);
        
        $response = $this->get('/admin/dashboard');
        $response->assertStatus(200);
        $response->assertViewHasAll(['totalDonors', 'activeRequestsCount', 'donorsTodayCount', 'recentRequests']);
    }

    public function test_admin_can_view_blood_request_create_form()
    {
        $this->actingAs($this->admin);
        $response = $this->get('/admin/blood-requests/create');
        $response->assertStatus(200);
    }

    public function test_admin_can_store_new_blood_request()
    {
        $this->actingAs($this->admin);
        
        $response = $this->post('/admin/blood-requests', [
            'type' => 'emergency',
            'blood_type' => 'AB',
            'rhesus' => '+',
            'required_bags' => 3,
            'urgency_level' => 'critical',
            'hospital_name' => 'RS Cipto',
            'hospital_address' => 'Jakarta',
            'latitude' => -6.12345,
            'longitude' => 106.12345,
            'deadline' => now()->addDays(3)->format('Y-m-d H:i:s'),
            'notes' => 'Emergency surgery'
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('blood_requests', [
            'admin_id' => $this->admin->id,
            'hospital_name' => 'RS Cipto',
            'urgency_level' => 'critical'
        ]);
    }

    public function test_double_submit_does_not_create_duplicate_blood_request()
    {
        $this->actingAs($this->admin);

        $payload = [
            'type' => 'emergency',
            'blood_type' => 'AB',
            'rhesus' => '+',
            'required_bags' => 3,
            'urgency_level' => 'critical',
            'hospital_name' => 'RS Cipto',
            'hospital_address' => 'Jakarta',
            'latitude' => -6.12345,
            'longitude' => 106.12345,
            'deadline' => now()->addDays(3)->format('Y-m-d H:i:s'),
            'notes' => 'Emergency surgery',
        ];

        $first = $this->post('/admin/blood-requests', $payload);
        $second = $this->post('/admin/blood-requests', $payload);

        $first->assertRedirect();
        $second->assertRedirect();
        $this->assertEquals($first->headers->get('Location'), $second->headers->get('Location'));

        $this->assertDatabaseCount('blood_requests', 1);
    }

    public function test_double_submit_with_datetime_local_deadline_format_does_not_create_duplicate()
    {
        $this->actingAs($this->admin);

        // <input type="datetime-local"> submits "Y-m-d\TH:i", not the
        // "Y-m-d H:i:s" the DB stores it as after the `datetime` cast — the
        // duplicate-detection query must normalize before comparing, or
        // real double-clicks from the actual form slip straight through.
        $payload = [
            'type' => 'emergency',
            'blood_type' => 'AB',
            'rhesus' => '+',
            'required_bags' => 3,
            'urgency_level' => 'critical',
            'hospital_name' => 'RS Cipto',
            'hospital_address' => 'Jakarta',
            'latitude' => -6.12345,
            'longitude' => 106.12345,
            'deadline' => now()->addDays(3)->format('Y-m-d\TH:i'),
            'notes' => 'Emergency surgery',
        ];

        $first = $this->post('/admin/blood-requests', $payload);
        $second = $this->post('/admin/blood-requests', $payload);

        $first->assertRedirect();
        $second->assertRedirect();
        $this->assertEquals($first->headers->get('Location'), $second->headers->get('Location'));

        $this->assertDatabaseCount('blood_requests', 1);
    }

    public function test_distinct_requests_within_duplicate_window_are_not_collapsed()
    {
        $this->actingAs($this->admin);

        $basePayload = [
            'type' => 'emergency',
            'blood_type' => 'AB',
            'rhesus' => '+',
            'required_bags' => 3,
            'urgency_level' => 'critical',
            'hospital_name' => 'RS Cipto',
            'hospital_address' => 'Jakarta',
            'latitude' => -6.12345,
            'longitude' => 106.12345,
            'deadline' => now()->addDays(3)->format('Y-m-d H:i:s'),
        ];

        $first = $this->post('/admin/blood-requests', $basePayload + ['notes' => 'Pasien A - kamar 3']);
        $second = $this->post('/admin/blood-requests', $basePayload + ['notes' => 'Pasien B - kamar 7']);

        $first->assertRedirect();
        $second->assertRedirect();
        $this->assertNotEquals($first->headers->get('Location'), $second->headers->get('Location'));

        $this->assertDatabaseCount('blood_requests', 2);
        $this->assertDatabaseHas('blood_requests', ['notes' => 'Pasien A - kamar 3']);
        $this->assertDatabaseHas('blood_requests', ['notes' => 'Pasien B - kamar 7']);
    }

    public function test_duplicate_guard_lock_is_released_after_submission()
    {
        $this->actingAs($this->admin);

        $payload = [
            'type' => 'emergency',
            'blood_type' => 'AB',
            'rhesus' => '+',
            'required_bags' => 3,
            'urgency_level' => 'critical',
            'hospital_name' => 'RS Cipto',
            'hospital_address' => 'Jakarta',
            'latitude' => -6.12345,
            'longitude' => 106.12345,
            'deadline' => now()->addDays(3)->format('Y-m-d H:i:s'),
            'notes' => 'Emergency surgery',
        ];

        $this->post('/admin/blood-requests', $payload)->assertRedirect();

        $duplicateKey = 'blood_request_duplicate:' . $this->admin->id . ':' . md5(implode("\x1f", [
            $payload['hospital_name'],
            $payload['blood_type'],
            $payload['rhesus'],
            $payload['required_bags'],
            $payload['urgency_level'],
            $payload['type'],
            $payload['deadline'],
            '',
            $payload['notes'],
        ]));

        // If store() ever leaks the lock instead of releasing it once the
        // request finishes, this acquisition attempt would fail/block —
        // catching a regression that would otherwise deadlock every
        // subsequent submission with the same fields.
        $lock = Cache::lock($duplicateKey, 1);
        $this->assertTrue($lock->get(), 'Duplicate-guard lock was not released after the request finished.');
        $lock->release();
    }

    public function test_admin_can_view_donor_directory()
    {
        $this->actingAs($this->admin);
        
        $response = $this->get('/admin/donors');
        $response->assertStatus(200);
        $response->assertViewHas('donors');
    }
}
