<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ValidateDateRangeMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_allows_valid_date_ranges()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/reports/reel-receipt?date_from=2026-05-01&date_to=2026-05-19');

        // It should pass validation and return a successful response (empty or populated list)
        $response->assertStatus(200);
    }

    public function test_it_validates_invalid_date_from_and_date_to()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/reports/reel-receipt?date_from=2026-05-19&date_to=2026-05-01');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['date_to']);
        $response->assertJsonFragment([
            'date_to' => ['The To Date must not be less than the From Date.']
        ]);
    }

    public function test_it_validates_invalid_start_date_and_end_date()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/reports/reports/cartage?start_date=2026-05-19&end_date=2026-05-01'); // using the cartage route or any valid one

        // Let's use the actual cartage report endpoint `/api/reports/cartage`
        $response = $this->actingAs($user)
            ->getJson('/api/reports/cartage?start_date=2026-05-19&end_date=2026-05-01');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['end_date']);
        $response->assertJsonFragment([
            'end_date' => ['The End Date must not be less than the Start Date.']
        ]);
    }
}
