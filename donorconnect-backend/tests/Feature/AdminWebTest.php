<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_admin_can_view_donor_directory()
    {
        $this->actingAs($this->admin);
        
        $response = $this->get('/admin/donors');
        $response->assertStatus(200);
        $response->assertViewHas('donors');
    }
}
