<?php

namespace Tests\Feature;

use App\Models\BloodRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FamilyBloodRequestSubmissionTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'blood_type' => 'O',
            'rhesus' => '+',
            'required_bags' => 2,
            'patient_name' => 'Budi Santoso',
            'patient_relationship' => 'Orang Tua',
            'hospital_name' => 'RSUP Dr. M. Djamil',
            'hospital_address' => 'Jl. Perintis Kemerdekaan, Padang',
            'urgency_level' => 'urgent',
            'deadline' => now()->addDays(2)->format('Y-m-d H:i:s'),
        ], $overrides);
    }

    public function test_family_submission_requires_referral_letter()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/user/blood-requests', $this->validPayload());

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('referral_letter');
    }

    public function test_family_submission_with_valid_referral_letter_stores_it()
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $letter = UploadedFile::fake()->image('surat-rujukan.jpg');

        $payload = $this->validPayload();
        $payload['referral_letter'] = $letter;

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/user/blood-requests', $payload);

        $response->assertStatus(201);
        $response->assertJsonPath('data.status', 'pending_review');
        $this->assertStringContainsString('referral-letter', $response->json('data.referral_letter_url'));

        $bloodRequest = BloodRequest::where('requested_by_user_id', $user->id)->first();
        $this->assertNotNull($bloodRequest->referral_letter_path);
        Storage::disk('local')->assertExists($bloodRequest->referral_letter_path);
    }

    public function test_referral_letter_is_not_publicly_accessible_without_admin_login()
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $letter = UploadedFile::fake()->image('surat-rujukan.jpg');

        $payload = $this->validPayload();
        $payload['referral_letter'] = $letter;
        $this->actingAs($user, 'sanctum')->postJson('/api/user/blood-requests', $payload);

        $bloodRequest = BloodRequest::where('requested_by_user_id', $user->id)->first();

        // Guest cannot view it.
        $this->get(route('admin.blood-requests.referral-letter', $bloodRequest->id))
            ->assertRedirect(route('admin.login'));

        // A regular (non-admin) donor cannot view it either.
        $donor = User::factory()->create(['role' => 'user']);
        $this->actingAs($donor)
            ->get(route('admin.blood-requests.referral-letter', $bloodRequest->id))
            ->assertRedirect();
    }

    public function test_admin_can_view_referral_letter()
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $letter = UploadedFile::fake()->image('surat-rujukan.jpg');

        $payload = $this->validPayload();
        $payload['referral_letter'] = $letter;
        $this->actingAs($user, 'sanctum')->postJson('/api/user/blood-requests', $payload);

        $bloodRequest = BloodRequest::where('requested_by_user_id', $user->id)->first();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.blood-requests.referral-letter', $bloodRequest->id))
            ->assertStatus(200);
    }

    public function test_retrying_after_a_dropped_response_does_not_create_duplicate_submission()
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $payload = $this->validPayload();
        $payload['referral_letter'] = UploadedFile::fake()->image('surat-rujukan.jpg');

        $first = $this->actingAs($user, 'sanctum')
            ->postJson('/api/user/blood-requests', $payload);

        // Simulates the app never seeing the first response (connection
        // drops after the server already committed) and the user tapping
        // "Kirim Pengajuan" again with the same form data.
        $payload['referral_letter'] = UploadedFile::fake()->image('surat-rujukan.jpg');
        $second = $this->actingAs($user, 'sanctum')
            ->postJson('/api/user/blood-requests', $payload);

        $first->assertStatus(201);
        $second->assertStatus(201);
        $this->assertEquals($first->json('data.id'), $second->json('data.id'));

        $this->assertDatabaseCount('blood_requests', 1);
    }

    public function test_distinct_family_submissions_within_duplicate_window_are_not_collapsed()
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $payload = $this->validPayload(['patient_name' => 'Budi Santoso']);
        $payload['referral_letter'] = UploadedFile::fake()->image('surat-rujukan.jpg');
        $first = $this->actingAs($user, 'sanctum')
            ->postJson('/api/user/blood-requests', $payload);

        $payload = $this->validPayload(['patient_name' => 'Siti Aminah']);
        $payload['referral_letter'] = UploadedFile::fake()->image('surat-rujukan-2.jpg');
        $second = $this->actingAs($user, 'sanctum')
            ->postJson('/api/user/blood-requests', $payload);

        $first->assertStatus(201);
        $second->assertStatus(201);
        $this->assertNotEquals($first->json('data.id'), $second->json('data.id'));

        $this->assertDatabaseCount('blood_requests', 2);
    }

    public function test_family_submission_rejects_non_image_referral_letter()
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $letter = UploadedFile::fake()->create('surat-rujukan.pdf', 100, 'application/pdf');

        $payload = $this->validPayload();
        $payload['referral_letter'] = $letter;

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/user/blood-requests', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('referral_letter');
    }
}
