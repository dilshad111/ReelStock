<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Warehouse;
use App\Models\Customer;
use App\Models\Product;
use App\Models\CurrentFGStock;
use App\Models\FGStockLedger;
use App\Models\FGReceipt;
use App\Models\FGDamage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class FGDamageTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $warehouse;
    private $customer;
    private $product;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup Role and User
        $role = Role::create(['name' => 'Admin']);
        $this->user = User::factory()->create([
            'role_id' => $role->id,
            'email' => 'admin@test.com'
        ]);

        // Seed basic permissions
        \Illuminate\Support\Facades\DB::table('user_permissions')->insert([
            [
                'user_id' => $this->user->id,
                'menu' => 'fg-damages',
                'can_view' => true,
                'can_add' => true,
                'can_edit' => true,
                'can_delete' => true,
                'can_see_amounts' => true,
            ],
            [
                'user_id' => $this->user->id,
                'menu' => 'fg-reports',
                'can_view' => true,
                'can_add' => true,
                'can_edit' => true,
                'can_delete' => true,
                'can_see_amounts' => true,
            ]
        ]);

        // 2. Setup master data
        $this->warehouse = Warehouse::firstOrCreate(
            ['code' => 'WH001'],
            [
                'name' => 'Main Warehouse',
                'status' => 'Active',
                'allow_negative_stock' => false
            ]
        );

        $this->customer = Customer::create([
            'name' => 'Acme Corp',
            'email' => 'acme@test.com',
            'phone' => '123456',
            'status' => 'Active'
        ]);

        $this->product = Product::create([
            'item_code' => 'BOX-001',
            'item_name' => 'Standard Carton Box',
            'customer_id' => $this->customer->id,
            'uom' => 'Pcs',
            'length' => 10,
            'width' => 10,
            'height' => 10,
            'rate' => 15.50,
            'opening_balance' => 0
        ]);

        // Seed opening balance in cache and ledger
        CurrentFGStock::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 1000
        ]);

        FGStockLedger::create([
            'transaction_type' => 'opening',
            'reference_id' => $this->product->id,
            'document_number' => 'OPENING',
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'customer_id' => $this->customer->id,
            'quantity_in' => 1000,
            'quantity_out' => 0,
            'balance_after' => 1000,
            'transaction_date' => now()->toDateString(),
            'created_by' => $this->user->id
        ]);

        Sanctum::actingAs($this->user);
    }

    public function test_cannot_log_damage_exceeding_stock_in_strict_warehouse()
    {
        $payload = [
            'date' => now()->toDateString(),
            'customer_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 1500, // Exceeds 1000 available
            'reason' => 'Water Damage',
            'remarks' => 'Flooding in Aisle 4'
        ];

        $response = $this->postJson('/api/fg-damages', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['quantity']);
    }

    public function test_can_log_damage_within_stock_reduces_balance()
    {
        $payload = [
            'date' => now()->toDateString(),
            'customer_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 300,
            'reason' => 'Water Damage',
            'remarks' => 'Wet floor'
        ];

        $response = $this->postJson('/api/fg-damages', $payload);

        $response->assertStatus(201);
        $response->assertJsonPath('status', 'posted');

        // Check stock cache is reduced to 700
        $this->assertEquals(700, CurrentFGStock::where('product_id', $this->product->id)->sum('quantity'));

        // Check ledger logged negative-impact entry
        $ledgerEntry = FGStockLedger::where('product_id', $this->product->id)
            ->where('transaction_type', 'damage')
            ->first();

        $this->assertNotNull($ledgerEntry);
        $this->assertEquals(300, $ledgerEntry->quantity_out);
        $this->assertEquals(700, $ledgerEntry->balance_after);
    }

    public function test_can_reverse_posted_damage_restores_stock()
    {
        // 1. Create a damage log via API
        $payload = [
            'date' => now()->toDateString(),
            'customer_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 200,
            'reason' => 'Compression',
            'remarks' => 'Compressed cartons'
        ];

        $res = $this->postJson('/api/fg-damages', $payload);
        $res->assertStatus(201);
        $damageId = $res->json('id');

        // Check stock cache is reduced to 800
        $this->assertEquals(800, CurrentFGStock::where('product_id', $this->product->id)->sum('quantity'));

        // 2. Perform Reversal
        $response = $this->postJson("/api/fg-damages/{$damageId}/reverse", [
            'reason' => 'Mistake'
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        // Check stock is restored to 1000
        $this->assertEquals(1000, CurrentFGStock::where('product_id', $this->product->id)->sum('quantity'));

        // Check damage status is updated to reversed
        $this->assertEquals('reversed', FGDamage::find($damageId)->status);

        // Check ledger logged reversal entry
        $reversalLedger = FGStockLedger::where('product_id', $this->product->id)
            ->where('transaction_type', 'damage_reversal')
            ->first();

        $this->assertNotNull($reversalLedger);
        $this->assertEquals(200, $reversalLedger->quantity_in);
    }

    public function test_cannot_reverse_already_reversed_damage()
    {
        $damage = FGDamage::create([
            'damage_number' => 'DMG-000002',
            'date' => now()->toDateString(),
            'customer_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 100,
            'reason' => 'Expired',
            'status' => 'reversed',
            'created_by' => $this->user->id
        ]);

        $response = $this->postJson("/api/fg-damages/{$damage->id}/reverse", [
            'reason' => 'Again'
        ]);

        $response->assertStatus(422);
    }

    public function test_stock_report_incorporates_damages()
    {
        // Log a damage of 150
        $damage = FGDamage::create([
            'damage_number' => 'DMG-000003',
            'date' => now()->toDateString(),
            'customer_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 150,
            'reason' => 'Pest Damage',
            'status' => 'posted',
            'created_by' => $this->user->id
        ]);

        // Add to ledger
        FGStockLedger::create([
            'transaction_type' => 'damage',
            'reference_id' => $damage->id,
            'document_number' => $damage->damage_number,
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'customer_id' => $this->customer->id,
            'quantity_in' => 0,
            'quantity_out' => 150,
            'balance_after' => 850,
            'transaction_date' => now()->toDateString(),
            'created_by' => $this->user->id
        ]);

        // Update the cached stock to reflect the damage
        CurrentFGStock::where('product_id', $this->product->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->update(['quantity' => 850]);

        $response = $this->getJson('/api/fg-reports/stock');

        $response->assertStatus(200);

        // Verify total_damaged is returned and balance correctly calculated
        $data = $response->json();
        $this->assertEquals(150, $data[0]['products'][0]['total_damaged']);
        $this->assertEquals(850, $data[0]['products'][0]['current_balance']);
    }

    public function test_job_report_incorporates_wastage_and_damages()
    {
        // Log a receipt with good quantity and wastage
        FGReceipt::create([
            'date' => now()->toDateString(),
            'customer_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'job_number' => 'JOB-W123',
            'production_date' => now()->toDateString(),
            'quantity_produced' => 500,
            'wastage' => 50,
            'remarks' => 'Good run with waste',
            'created_by' => $this->user->id
        ]);

        // Log a damage of 30 on this job
        FGDamage::create([
            'damage_number' => 'DMG-JOB-W123',
            'date' => now()->toDateString(),
            'customer_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'job_number' => 'JOB-W123',
            'quantity' => 30,
            'reason' => 'Defect',
            'status' => 'posted',
            'created_by' => $this->user->id
        ]);

        $response = $this->getJson('/api/fg-reports/job');

        $response->assertStatus(200);

        // Verify totals are loaded correctly
        $data = $response->json('data');
        $this->assertNotEmpty($data);
        $jobData = collect($data)->firstWhere('job_number', 'JOB-W123');
        $this->assertNotNull($jobData);
        $this->assertEquals(500, $jobData['total_produced']);
        $this->assertEquals(50, $jobData['total_wastage']);
        $this->assertEquals(30, $jobData['total_damaged']);
        $this->assertEquals(470, $jobData['remaining_balance']);
    }
}
