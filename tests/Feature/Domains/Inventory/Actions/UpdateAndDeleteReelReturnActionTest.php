<?php

namespace Tests\Feature\Domains\Inventory\Actions;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Reel;
use App\Models\ReelReturn;
use App\Domains\Inventory\Actions\UpdateReelReturnAction;
use App\Domains\Inventory\Actions\DeleteReelReturnAction;
use Exception;

class UpdateAndDeleteReelReturnActionTest extends TestCase
{
    use RefreshDatabase;

    protected UpdateReelReturnAction $updateAction;
    protected DeleteReelReturnAction $deleteAction;

    protected function setUp(): void
    {
        parent::setUp();
        $this->updateAction = app(UpdateReelReturnAction::class);
        $this->deleteAction = app(DeleteReelReturnAction::class);
    }

    public function test_it_updates_a_return_and_recalculates_balance()
    {
        $reel = Reel::factory()->create([
            'original_weight' => 1000,
            'balance_weight' => 700, // 1000 - 300 (initial return)
        ]);

        $return = ReelReturn::factory()->create([
            'reel_id' => $reel->id,
            'remaining_weight' => 300,
            'returned_to' => 'supplier',
        ]);

        $updateData = [
            'return_date' => '2023-01-10',
            'remaining_weight' => 500,
            'condition' => 'damaged',
            'remarks' => 'Updated remarks',
        ];

        // Old weight: 300. New weight: 500.
        // Balance before update: 700.
        // Restored balance: 700 + 300 = 1000.
        // New balance: 1000 - 500 = 500.

        $this->updateAction->execute($return, $updateData);

        $this->assertDatabaseHas('reel_returns', [
            'id' => $return->id,
            'remaining_weight' => 500,
        ]);

        $this->assertDatabaseHas('reels', [
            'id' => $reel->id,
            'balance_weight' => 500,
            'status' => 'returned_to_supplier'
        ]);
    }

    public function test_it_deletes_a_return_and_restores_balance()
    {
        $reel = Reel::factory()->create([
            'original_weight' => 1000,
            'balance_weight' => 600, // 1000 - 400
        ]);

        $return = ReelReturn::factory()->create([
            'reel_id' => $reel->id,
            'remaining_weight' => 400,
            'returned_to' => 'supplier',
        ]);

        $this->deleteAction->execute($return->id);

        $this->assertDatabaseMissing('reel_returns', ['id' => $return->id]);

        $this->assertDatabaseHas('reels', [
            'id' => $reel->id,
            'balance_weight' => 1000,
            'status' => 'in_stock'
        ]);
    }
}
