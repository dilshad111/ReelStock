<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use App\Models\SampleSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SampleSubmissionTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['email' => 'superadmin@qc.com']);
        $this->customer = Customer::create([
            'name' => 'Test Customer',
            'code' => 'CUST-001',
            'email' => 'test@cust.com',
            'phone' => '123456789'
        ]);
    }

    public function test_can_create_sample_submission_with_made_by_and_joinery()
    {
        $payload = [
            'customer_id' => $this->customer->id,
            'sample_date' => '2026-06-30',
            'length' => 120.00,
            'width' => 80.00,
            'height' => 45.00,
            'uom' => 'mm',
            'quantity' => 5,
            'print_type' => 'un-print',
            'ply' => '3',
            'size_approval_only' => true,
            'remarks' => 'Test sample remarks',
            'sample_made_by' => 'David Smith',
            'joinery_technique' => 'Side-Pasting'
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/sample-submissions', $payload);

        $response->assertStatus(201);

        $this->assertDatabaseHas('sample_submissions', [
            'customer_id' => $this->customer->id,
            'sample_made_by' => 'David Smith',
            'joinery_technique' => 'Side-Pasting',
            'quantity' => 5
        ]);
    }

    public function test_invalid_joinery_technique_is_validated()
    {
        $payload = [
            'customer_id' => $this->customer->id,
            'sample_date' => '2026-06-30',
            'length' => 120.00,
            'width' => 80.00,
            'height' => 45.00,
            'uom' => 'mm',
            'quantity' => 5,
            'print_type' => 'un-print',
            'ply' => '3',
            'size_approval_only' => true,
            'remarks' => 'Test sample remarks',
            'sample_made_by' => 'David Smith',
            'joinery_technique' => 'Invalid-Technique' // invalid option
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/sample-submissions', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['joinery_technique']);
    }
}
