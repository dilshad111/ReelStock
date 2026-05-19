<?php

namespace Tests\Feature\Domains\Inventory\Actions;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Reel;
use App\Models\ReelReceipt;
use App\Domains\Inventory\Actions\UpdateReelReceiptAction;
use App\Domains\Inventory\Actions\DeleteReelReceiptAction;
use Exception;

class UpdateAndDeleteReelReceiptActionTest extends TestCase
{
    use RefreshDatabase;

    protected UpdateReelReceiptAction $updateAction;
    protected DeleteReelReceiptAction $deleteAction;

    protected function setUp(): void
    {
        parent::setUp();
        $this->updateAction = app(UpdateReelReceiptAction::class);
        $this->deleteAction = app(DeleteReelReceiptAction::class);
    }

    public function test_it_updates_a_receipt_and_syncs_reel()
    {
        $reel = Reel::factory()->create([
            'original_weight' => 1000,
            'balance_weight' => 1000,
        ]);

        $receipt = ReelReceipt::factory()->create([
            'reel_id' => $reel->id,
            'gsm' => 150,
        ]);

        $updateReceiptData = ['gsm' => 160];
        $updateReelData = ['reel_size' => 45];
        $newOriginalWeight = 1200.0;

        $this->updateAction->execute($receipt, $updateReceiptData, $updateReelData, $newOriginalWeight);

        $this->assertDatabaseHas('reel_receipts', [
            'id' => $receipt->id,
            'gsm' => 160,
        ]);

        $this->assertDatabaseHas('reels', [
            'id' => $reel->id,
            'reel_size' => '45',
            'original_weight' => 1200,
            'balance_weight' => 1200,
        ]);
    }

    public function test_it_deletes_a_receipt_and_the_reel_if_no_other_receipts()
    {
        $reel = Reel::factory()->create();
        $receipt = ReelReceipt::factory()->create(['reel_id' => $reel->id]);

        $this->deleteAction->execute($receipt);

        $this->assertDatabaseMissing('reel_receipts', ['id' => $receipt->id]);
        $this->assertDatabaseMissing('reels', ['id' => $reel->id]);
    }

    public function test_it_prevents_deleting_receipt_if_reel_has_issues()
    {
        $reel = Reel::factory()->create();
        $receipt = ReelReceipt::factory()->create(['reel_id' => $reel->id]);
        
        // Create an issue
        \App\Models\ReelIssue::factory()->create(['reel_id' => $reel->id]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cannot delete this receipt because the associated reel has issue or return records.');

        $this->deleteAction->execute($receipt);
    }
}
