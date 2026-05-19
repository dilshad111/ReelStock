<?php

namespace Tests\Feature\Domains\Inventory\Actions;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Reel;
use App\Models\ReelReturn;
use App\Domains\Inventory\Actions\CreateReelReturnAction;
use App\Domains\Inventory\DTOs\ReelReturnDTO;

class CreateReelReturnActionTest extends TestCase
{
    use RefreshDatabase;

    protected CreateReelReturnAction $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = app(CreateReelReturnAction::class);
    }

    public function test_it_creates_a_return_and_deducts_balance()
    {
        $reel = Reel::factory()->create([
            'original_weight' => 1000,
            'balance_weight' => 1000,
            'status' => 'in_stock'
        ]);

        $dto = new ReelReturnDTO(
            reelNo: $reel->reel_no,
            returnDate: '2023-01-03',
            remainingWeight: 400,
            returnedTo: 'supplier',
            condition: 'damaged',
            returnLocation: null,
            vehicleNumber: 'TRK-999',
            returnToSupplierId: $reel->supplier_id,
            remarks: 'Returning damaged goods',
            challanNo: 'RT001'
        );

        $return = $this->action->execute($dto);

        $this->assertInstanceOf(ReelReturn::class, $return);
        
        $this->assertDatabaseHas('reel_returns', [
            'id' => $return->id,
            'reel_id' => $reel->id,
            'remaining_weight' => 400,
            'returned_to' => 'supplier',
            'condition' => 'damaged',
            'challan_no' => 'RT001'
        ]);

        $this->assertDatabaseHas('reels', [
            'id' => $reel->id,
            'balance_weight' => 600, // 1000 - 400
            'status' => 'returned_to_supplier'
        ]);
    }

    public function test_it_prevents_returning_more_than_balance()
    {
        $reel = Reel::factory()->create([
            'original_weight' => 300,
            'balance_weight' => 300,
        ]);

        $dto = new ReelReturnDTO(
            reelNo: $reel->reel_no,
            returnDate: '2023-01-03',
            remainingWeight: 400,
            returnedTo: 'supplier',
            condition: 'damaged',
            returnLocation: null,
            vehicleNumber: null,
            returnToSupplierId: null,
            remarks: 'Too much',
            challanNo: 'RT002'
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Remaining weight cannot exceed original weight');

        $this->action->execute($dto);
    }
}
