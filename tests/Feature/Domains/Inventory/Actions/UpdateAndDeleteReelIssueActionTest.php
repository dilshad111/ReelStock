<?php

namespace Tests\Feature\Domains\Inventory\Actions;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Reel;
use App\Models\ReelIssue;
use App\Models\ReelReturn;
use App\Domains\Inventory\Actions\UpdateReelIssueAction;
use App\Domains\Inventory\Actions\DeleteReelIssueAction;
use Exception;

class UpdateAndDeleteReelIssueActionTest extends TestCase
{
    use RefreshDatabase;

    protected UpdateReelIssueAction $updateAction;
    protected DeleteReelIssueAction $deleteAction;

    protected function setUp(): void
    {
        parent::setUp();
        $this->updateAction = app(UpdateReelIssueAction::class);
        $this->deleteAction = app(DeleteReelIssueAction::class);
    }

    public function test_it_updates_an_issue_and_recalculates_balance()
    {
        $reel = Reel::factory()->create([
            'original_weight' => 1000,
            'balance_weight' => 700, // 1000 - 300 (initial issue)
        ]);

        $issue = ReelIssue::factory()->create([
            'reel_id' => $reel->id,
            'quantity_issued' => 300,
            'return_to_stock_weight' => 0,
            'net_consumed_weight' => 300,
        ]);

        $updateData = [
            'issue_date' => '2023-01-05',
            'quantity_issued' => 500,
            'return_to_stock_weight' => 100,
            'issued_to' => 'Line 1',
            'remarks' => 'Updated remarks',
        ];

        // Old net: 300. New net: 400 (500 - 100).
        // Balance before update: 700.
        // Balance before ANY issues (recalculated): 1000.
        // New balance: 1000 - 400 = 600.

        $updatedIssue = $this->updateAction->execute($issue, $updateData);

        $this->assertDatabaseHas('reel_issues', [
            'id' => $issue->id,
            'quantity_issued' => 500,
            'return_to_stock_weight' => 100,
            'net_consumed_weight' => 400,
        ]);

        $this->assertDatabaseHas('reels', [
            'id' => $reel->id,
            'balance_weight' => 600,
        ]);

        // Should also have an auto-return
        $this->assertDatabaseHas('reel_returns', [
            'id' => $updatedIssue->auto_return_id,
            'remaining_weight' => 600, // Wait, looking at the code, it sets remaining_weight to $newBalance
        ]);
    }

    public function test_it_deletes_an_issue_and_restores_balance()
    {
        $reel = Reel::factory()->create([
            'original_weight' => 1000,
            'balance_weight' => 600, // 1000 - 400
        ]);

        $issue = ReelIssue::factory()->create([
            'reel_id' => $reel->id,
            'quantity_issued' => 500,
            'return_to_stock_weight' => 100,
            'net_consumed_weight' => 400,
        ]);

        $this->deleteAction->execute($issue->id);

        $this->assertDatabaseMissing('reel_issues', ['id' => $issue->id]);

        $this->assertDatabaseHas('reels', [
            'id' => $reel->id,
            'balance_weight' => 1000,
            'status' => 'in_stock'
        ]);
    }
}
