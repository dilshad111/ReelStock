<?php

namespace Tests\Feature\Domains\Inventory\Actions;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Reel;
use App\Models\ReelReceipt;
use App\Models\Supplier;
use App\Models\PaperQuality;
use App\Domains\Inventory\Actions\CreateReelReceiptAction;
use App\Domains\Inventory\DTOs\ReelReceiptDTO;

class CreateReelReceiptActionTest extends TestCase
{
    use RefreshDatabase;

    protected CreateReelReceiptAction $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = app(CreateReelReceiptAction::class);
    }

    public function test_it_creates_a_reel_and_receipt_with_correct_balances()
    {
        $supplier = Supplier::factory()->create();
        $paperQuality = PaperQuality::factory()->create();

        $dto = new ReelReceiptDTO(
            paperQualityId: $paperQuality->id,
            supplierId: $supplier->id,
            reelSize: 30.0,
            reelWeight: 1500.50,
            receivingDate: '2023-01-01',
            receivedBy: 'Admin',
            gsm: 150.0,
            burstingStrength: 25.5,
            ratePerKg: null,
            qcStatus: 'on_hold',
            remarks: 'Test receipt',
            poNumber: null,
            grnNumber: null
        );

        $receipt = $this->action->execute($dto);

        // Assert Receipt
        $this->assertInstanceOf(ReelReceipt::class, $receipt);
        $this->assertDatabaseHas('reel_receipts', [
            'id' => $receipt->id,
            'reel_id' => $receipt->reel_id,
            'receiving_date' => '2023-01-01',
            'received_by' => 'Admin',
            'remarks' => 'Test receipt',
            'qc_status' => 'on_hold'
        ]);

        $reel = $receipt->reel;

        // Assert Reel
        $this->assertInstanceOf(Reel::class, $reel);
        $this->assertDatabaseHas('reels', [
            'id' => $reel->id,
            'supplier_id' => $supplier->id,
            'paper_quality_id' => $paperQuality->id,
            'reel_size' => '30',
            'original_weight' => 1500.50,
            'balance_weight' => 1500.50, // Should match original_weight initially
            'status' => 'in_stock'
        ]);
        
        $this->assertNotEmpty($reel->reel_no); // Auto-generated
    }
}
