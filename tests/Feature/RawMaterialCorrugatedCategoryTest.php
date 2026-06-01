<?php

namespace Tests\Feature;

use App\Models\RMCategory;
use App\Models\RMItem;
use App\Models\RMSubcategory;
use App\Domains\RawMaterial\Actions\CreateRMItemAction;
use App\Http\Controllers\PaperQualityController;
use App\Http\Controllers\RMItemController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RawMaterialCorrugatedCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_corrugated_raw_material_categories_and_subcategories_are_seeded(): void
    {
        $this->assertDatabaseHas('rm_categories', ['name' => 'Paper & Board']);
        $this->assertDatabaseHas('rm_categories', ['name' => 'Adhesives & Chemicals']);
        $this->assertDatabaseHas('rm_subcategories', ['name' => 'Corn Starch']);
        $this->assertDatabaseHas('rm_subcategories', ['name' => 'Flexographic Ink']);
        $this->assertDatabaseHas('rm_subcategories', ['name' => 'Maintenance Tools']);
    }

    public function test_rm_item_requires_category_and_validates_dependent_subcategory(): void
    {
        $adhesives = RMCategory::where('name', 'Adhesives & Chemicals')->firstOrFail();
        $inks = RMCategory::where('name', 'Inks & Coatings')->firstOrFail();
        $inkSubcategory = RMSubcategory::where('rm_category_id', $inks->id)->firstOrFail();

        $basePayload = [
            'name' => 'Industrial Test Material',
            'unit_type' => 'Kg',
            'material_type' => 'Direct Material',
            'cost_price' => 125,
            'opening_stock' => 10,
            'min_stock_alert' => 5,
            'reorder_level' => 6,
            'minimum_stock' => 5,
            'maximum_stock' => 50,
            'status' => 'Active',
        ];

        try {
            app(RMItemController::class)->store(
                Request::create('/api/rm-items', 'POST', $basePayload),
                app(CreateRMItemAction::class)
            );
            $this->fail('Expected category validation to fail.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('rm_category_id', $exception->errors());
        }

        try {
            app(RMItemController::class)->store(
                Request::create('/api/rm-items', 'POST', array_merge($basePayload, [
                    'rm_category_id' => $adhesives->id,
                    'rm_subcategory_id' => $inkSubcategory->id,
                ])),
                app(CreateRMItemAction::class)
            );
            $this->fail('Expected dependent subcategory validation to fail.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('rm_subcategory_id', $exception->errors());
        }
    }

    public function test_rm_item_can_be_created_with_corrugated_category_fields(): void
    {
        $category = RMCategory::where('name', 'Packaging Consumables')->firstOrFail();
        $subcategory = RMSubcategory::where('rm_category_id', $category->id)->where('name', 'PP Straps')->firstOrFail();

        $response = app(RMItemController::class)->store(Request::create('/api/rm-items', 'POST', [
            'name' => 'PP Strap Blue 12mm',
            'rm_category_id' => $category->id,
            'rm_subcategory_id' => $subcategory->id,
            'unit_type' => 'Roll',
            'material_type' => 'Consumable',
            'cost_price' => 450,
            'opening_stock' => 25,
            'min_stock_alert' => 8,
            'reorder_level' => 10,
            'minimum_stock' => 8,
            'maximum_stock' => 100,
            'gst_tax_code' => 'GST-17',
            'status' => 'Active',
        ]), app(CreateRMItemAction::class));

        $this->assertSame(201, $response->getStatusCode());
        $this->assertDatabaseHas('rm_items', [
            'name' => 'PP Strap Blue 12mm',
            'rm_category_id' => $category->id,
            'rm_subcategory_id' => $subcategory->id,
            'material_type' => 'Consumable',
            'gst_tax_code' => 'GST-17',
        ]);
    }

    public function test_paper_quality_creates_paper_board_rm_item(): void
    {
        $response = app(PaperQualityController::class)->store(Request::create('/api/paper-qualities', 'POST', [
            'quality' => 'Test Kraft Liner',
            'gsm_range' => '150-160 gsm',
            'min_gsm' => 150,
            'max_gsm' => 160,
        ]));

        $this->assertSame(201, $response->getStatusCode());

        $paperBoard = RMCategory::where('name', 'Paper & Board')->firstOrFail();

        $this->assertTrue(
            RMItem::where('name', 'Test Kraft Liner 150-160 gsm')
                ->where('rm_category_id', $paperBoard->id)
                ->where('unit_type', 'Kg')
                ->exists()
        );
    }
}
