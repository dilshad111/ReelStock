<?php

namespace Tests\Feature\Domains\Inventory\Actions;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Reel;
use App\Models\ReelIssue;
use App\Models\ReelReturn;
use App\Domains\Inventory\Actions\CreateReelIssueAction;
use App\Domains\Inventory\DTOs\ReelIssueDTO;
use Illuminate\Validation\ValidationException;

class CreateReelIssueActionTest extends TestCase
{
    use RefreshDatabase;

    protected CreateReelIssueAction $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = app(CreateReelIssueAction::class);
    }

    public function test_it_creates_an_issue_and_deducts_balance()
    {
        $reel = Reel::factory()->create([
            'original_weight' => 1000,
            'balance_weight' => 1000,
        ]);

        $dto = new ReelIssueDTO(
            reelNo: $reel->reel_no,
            issueDate: '2023-01-02',
            quantityIssued: 400,
            returnToStockWeight: 0,
            issuedTo: 'Factory',
            remarks: 'First issue',
            returnLocation: null
        );

        $issue = $this->action->execute($dto);

        $this->assertInstanceOf(ReelIssue::class, $issue);
        
        $this->assertDatabaseHas('reel_issues', [
            'id' => $issue->id,
            'reel_id' => $reel->id,
            'quantity_issued' => 400,
            'return_to_stock_weight' => 0,
            'net_consumed_weight' => 400,
        ]);

        $this->assertDatabaseHas('reels', [
            'id' => $reel->id,
            'balance_weight' => 600, // 1000 - 400
        ]);
    }

    public function test_it_prevents_issuing_more_than_balance()
    {
        $reel = Reel::factory()->create([
            'original_weight' => 500,
            'balance_weight' => 500,
        ]);

        $dto = new ReelIssueDTO(
            reelNo: $reel->reel_no,
            issueDate: '2023-01-02',
            quantityIssued: 600,
            returnToStockWeight: 0,
            issuedTo: 'Factory',
            remarks: 'Over issue',
            returnLocation: null
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient balance weight');

        $this->action->execute($dto);
    }

    public function test_it_creates_automatic_return_when_scrap_generated()
    {
        $reel = Reel::factory()->create([
            'original_weight' => 1000,
            'balance_weight' => 1000,
        ]);

        // Issue 500, return 100 to stock, net consumed = 400
        $dto = new ReelIssueDTO(
            reelNo: $reel->reel_no,
            issueDate: '2023-01-02',
            quantityIssued: 500,
            returnToStockWeight: 100,
            issuedTo: 'Factory',
            remarks: 'Partial use',
            returnLocation: 'GoDown'
        );

        $issue = $this->action->execute($dto);

        // Reel balance should be reduced by net_consumed_weight (1000 - 400 = 600)
        $this->assertDatabaseHas('reels', [
            'id' => $reel->id,
            'balance_weight' => 600, 
        ]);

        $this->assertDatabaseHas('reel_issues', [
            'id' => $issue->id,
            'quantity_issued' => 500,
            'return_to_stock_weight' => 100,
            'net_consumed_weight' => 400,
        ]);

        $this->assertDatabaseHas('reel_returns', [
            'id' => $issue->auto_return_id,
            'reel_id' => $reel->id,
            'return_date' => '2023-01-02',
            'remaining_weight' => 100,
            'returned_to' => 'stock',
            'return_location' => 'GoDown'
        ]);
    }
}
