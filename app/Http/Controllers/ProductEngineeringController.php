<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\EngineeringProduct;
use App\Models\EngineeringProductRevision;
use App\Models\PaperQuality;
use App\Models\PrintingColor;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductEngineeringController extends Controller
{
    private const PROCESSES = [
        'Corrugation',
        'Corrugation + Online Slitting',
        'Manual Scoring',
        'Printing',
        'Printing + Slotting',
        'Printing + Slotting + Gluing + Packing',
        'Die Cutting',
        'Gluing',
        'Stitching',
        'Packing',
        'Window Pasting',
    ];

    public function index(Request $request)
    {
        $query = EngineeringProduct::with('customer')
            ->withCount('components');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('product_code', 'like', "%{$search}%")
                    ->orWhere('product_name', 'like', "%{$search}%")
                    ->orWhere('product_category', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($cq) => $cq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('product_category')) {
            $query->where('product_category', $request->product_category);
        }

        return response()->json(
            $query->orderByDesc('id')->paginate($request->integer('per_page', 15))
        );
    }

    public function lookups()
    {
        return response()->json([
            'customers' => Customer::orderBy('name')->get(['id', 'name']),
            'suppliers' => Supplier::orderBy('name')->get(['id', 'name']),
            'paper_qualities' => PaperQuality::orderBy('quality')
                ->get(['id', 'quality', 'gsm_range', 'standard_gsm', 'min_gsm', 'max_gsm']),
            'printing_colors' => PrintingColor::orderBy('ink_code')->get(['id', 'ink_code', 'ink_name']),
            'processes' => collect(self::PROCESSES)->map(fn ($name) => ['label' => $name, 'value' => $name])->values(),
            'categories' => EngineeringProduct::query()
                ->whereNotNull('product_category')
                ->distinct()
                ->orderBy('product_category')
                ->pluck('product_category')
                ->values(),
        ]);
    }

    public function show(EngineeringProduct $productEngineering)
    {
        return response()->json($this->loadProduct($productEngineering));
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        return DB::transaction(function () use ($data) {
            $product = EngineeringProduct::create([
                'product_number' => $this->nextProductNumber(),
                'product_created_date' => now()->toDateString(),
                ...$this->productPayload($data),
                'revision_number' => (int) ($data['revision_number'] ?? 1),
                'revision_date' => $data['revision_date'] ?? now()->toDateString(),
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            $this->syncComponents($product, $data['components']);
            $product = $this->loadProduct($product);
            $this->recordRevision($product, $data['change_notes'] ?? 'Original Product Master version.');

            return response()->json($this->loadProduct($product), 201);
        });
    }

    public function update(Request $request, EngineeringProduct $productEngineering)
    {
        $data = $this->validatedData($request, $productEngineering->id);

        return DB::transaction(function () use ($data, $productEngineering) {
            $createRevision = (bool) ($data['create_revision'] ?? false);
            $revisionNumber = $createRevision
                ? ((int) $productEngineering->revision_number + 1)
                : (int) ($data['revision_number'] ?? $productEngineering->revision_number);

            $productEngineering->update([
                ...$this->productPayload($data),
                'revision_number' => $revisionNumber,
                'revision_date' => $createRevision ? now()->toDateString() : ($data['revision_date'] ?? $productEngineering->revision_date),
                'updated_by' => Auth::id(),
            ]);

            $productEngineering->components()->delete();
            $this->syncComponents($productEngineering, $data['components']);

            $product = $this->loadProduct($productEngineering);
            if ($createRevision) {
                $this->recordRevision($product, $data['change_notes'] ?? 'Product engineering revision updated.');
            }

            return response()->json($this->loadProduct($product));
        });
    }

    public function destroy(EngineeringProduct $productEngineering)
    {
        $productEngineering->delete();

        return response()->json(['message' => 'Product engineering master deleted successfully.']);
    }

    public function explode(Request $request, EngineeringProduct $productEngineering)
    {
        $quantity = max(0, (float) $request->query('quantity', 0));
        $product = $this->loadProduct($productEngineering);

        return response()->json([
            'product_id' => $product->id,
            'product_code' => $product->product_code,
            'product_name' => $product->product_name,
            'product_quantity' => $quantity,
            'revision_number' => $product->revision_number,
            'components' => $product->components->where('is_active', true)->map(function ($component) use ($quantity) {
                return [
                    'component_id' => $component->id,
                    'component_code' => $component->component_code,
                    'component_name' => $component->component_name,
                    'quantity_per_product' => $component->quantity_per_product,
                    'required_quantity' => round($quantity * $component->quantity_per_product, 4),
                    'specification' => $component->specification,
                    'bom_layers' => $component->bomLayers,
                    'routing' => $component->routings->where('is_active', true)->values(),
                ];
            })->values(),
        ]);
    }

    private function validatedData(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'product_code' => [
                'required',
                'string',
                'max:100',
                Rule::unique('engineering_products', 'product_code')->ignore($ignoreId),
            ],
            'product_name' => ['required', 'string', 'max:255'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'product_category' => ['nullable', 'string', 'max:120'],
            'fefco_code' => ['nullable', 'string', 'max:20'],
            'product_number' => ['nullable', 'string', 'max:40'],
            'product_created_date' => ['nullable', 'date'],
            'revision_number' => ['nullable', 'integer', 'min:1'],
            'revision_date' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['Active', 'Inactive'])],
            'remarks' => ['nullable', 'string'],
            'change_notes' => ['nullable', 'string'],
            'create_revision' => ['nullable', 'boolean'],
            'components' => ['required', 'array', 'min:1'],
            'components.*.component_code' => ['required', 'string', 'max:100'],
            'components.*.component_name' => ['required', 'string', 'max:255'],
            'components.*.quantity_per_product' => ['required', 'numeric', 'min:0.0001'],
            'components.*.component_type' => ['nullable', 'string', 'max:120'],
            'components.*.is_active' => ['nullable', 'boolean'],
            'components.*.sort_order' => ['nullable', 'integer', 'min:1'],
            'components.*.specification.length' => ['nullable', 'numeric', 'min:0'],
            'components.*.specification.width' => ['nullable', 'numeric', 'min:0'],
            'components.*.specification.height' => ['nullable', 'numeric', 'min:0'],
            'components.*.specification.uom' => ['nullable', 'string', 'max:20'],
            'components.*.specification.ply_type' => ['nullable', 'string', 'max:40'],
            'components.*.specification.flute_type' => ['nullable', 'string', 'max:80'],
            'components.*.specification.board_grade' => ['nullable', 'string', 'max:160'],
            'components.*.specification.joint_type' => ['nullable', 'string', 'max:120'],
            'components.*.specification.is_printed' => ['nullable', 'boolean'],
            'components.*.specification.printing_colors' => ['nullable', 'integer', 'min:0', 'max:12'],
            'components.*.specification.printing_color_codes' => ['nullable', 'array'],
            'components.*.specification.printing_color_codes.*' => ['nullable', 'string', 'max:50'],
            'components.*.specification.bundle_quantity' => ['nullable', 'integer', 'min:0'],
            'components.*.specification.special_instructions' => ['nullable', 'string'],
            'components.*.bom_layers' => ['nullable', 'array'],
            'components.*.bom_layers.*.layer_sequence' => ['required_with:components.*.bom_layers', 'integer', 'min:1'],
            'components.*.bom_layers.*.layer_label' => ['nullable', 'string', 'max:120'],
            'components.*.bom_layers.*.paper_type' => ['required_with:components.*.bom_layers', 'string', 'max:255'],
            'components.*.bom_layers.*.paper_quality_id' => ['nullable', 'exists:paper_qualities,id'],
            'components.*.bom_layers.*.gsm' => ['nullable', 'integer', 'min:1'],
            'components.*.bom_layers.*.supplier_id' => ['nullable', 'exists:suppliers,id'],
            'components.*.routings' => ['nullable', 'array'],
            'components.*.routings.*.sequence_no' => ['required_with:components.*.routings', 'integer', 'min:1'],
            'components.*.routings.*.process_name' => ['required_with:components.*.routings', 'string', 'max:160'],
            'components.*.routings.*.process_order' => ['nullable', 'integer', 'min:1'],
            'components.*.routings.*.is_active' => ['nullable', 'boolean'],
            'components.*.routings.*.process_instructions' => ['nullable', 'string'],
            'components.*.routings.*.parameters' => ['nullable', 'array'],
        ]);
    }

    private function productPayload(array $data): array
    {
        return [
            'product_code' => $data['product_code'],
            'product_name' => $data['product_name'],
            'customer_id' => $data['customer_id'] ?? null,
            'product_category' => $data['product_category'] ?? null,
            'fefco_code' => $data['fefco_code'] ?? '0201',
            'status' => $data['status'],
            'remarks' => $data['remarks'] ?? null,
        ];
    }

    private function syncComponents(EngineeringProduct $product, array $components): void
    {
        foreach ($components as $index => $componentData) {
            $component = $product->components()->create([
                'component_code' => $componentData['component_code'],
                'component_name' => $componentData['component_name'],
                'quantity_per_product' => $componentData['quantity_per_product'],
                'component_type' => $componentData['component_type'] ?? null,
                'is_active' => (bool) ($componentData['is_active'] ?? true),
                'sort_order' => (int) ($componentData['sort_order'] ?? $index + 1),
            ]);

            $spec = $componentData['specification'] ?? [];
            $component->specification()->create([
                'length' => $spec['length'] ?? null,
                'width' => $spec['width'] ?? null,
                'height' => $spec['height'] ?? null,
                'uom' => $spec['uom'] ?? 'mm',
                'ply_type' => $spec['ply_type'] ?? null,
                'flute_type' => $spec['flute_type'] ?? null,
                'board_grade' => $spec['board_grade'] ?? null,
                'joint_type' => $spec['joint_type'] ?? null,
                'is_printed' => (bool) ($spec['is_printed'] ?? false),
                'printing_colors' => (int) ($spec['printing_colors'] ?? 0),
                'printing_color_codes' => array_values(array_filter($spec['printing_color_codes'] ?? [])),
                'bundle_quantity' => $spec['bundle_quantity'] ?? null,
                'special_instructions' => $spec['special_instructions'] ?? null,
            ]);

            foreach (($componentData['bom_layers'] ?? []) as $layer) {
                $paperQuality = !empty($layer['paper_quality_id'])
                    ? PaperQuality::find($layer['paper_quality_id'])
                    : null;
                $component->bomLayers()->create([
                    'layer_sequence' => $layer['layer_sequence'],
                    'layer_label' => $layer['layer_label'] ?? null,
                    'paper_type' => $paperQuality
                        ? trim($paperQuality->quality . ' (' . $paperQuality->gsm_range . ')')
                        : $layer['paper_type'],
                    'paper_quality_id' => $layer['paper_quality_id'] ?? null,
                    'gsm' => $paperQuality
                        ? (int) round((float) ($paperQuality->standard_gsm ?: $paperQuality->min_gsm ?: $paperQuality->max_gsm))
                        : ($layer['gsm'] ?? null),
                    'supplier_id' => $layer['supplier_id'] ?? null,
                ]);
            }

            foreach (($componentData['routings'] ?? []) as $routing) {
                $component->routings()->create([
                    'sequence_no' => $routing['sequence_no'],
                    'process_name' => $routing['process_name'],
                    'process_order' => $routing['process_order'] ?? $routing['sequence_no'],
                    'is_active' => (bool) ($routing['is_active'] ?? true),
                    'process_instructions' => $routing['process_instructions'] ?? null,
                    'parameters' => $routing['parameters'] ?? [],
                ]);
            }
        }
    }

    private function recordRevision(EngineeringProduct $product, ?string $notes): EngineeringProductRevision
    {
        return EngineeringProductRevision::updateOrCreate(
            [
                'engineering_product_id' => $product->id,
                'revision_number' => $product->revision_number,
            ],
            [
                'revision_date' => $product->revision_date ?: now()->toDateString(),
                'change_notes' => $notes,
                'snapshot_data' => $product->toArray(),
                'created_by' => Auth::id(),
            ]
        );
    }

    private function loadProduct(EngineeringProduct $product): EngineeringProduct
    {
        return $product->load([
            'customer',
            'components.specification',
            'components.bomLayers.supplier',
            'components.bomLayers.paperQuality',
            'components.routings',
            'revisions.creator',
        ]);
    }

    private function nextProductNumber(): string
    {
        $lastId = (int) EngineeringProduct::withTrashed()->max('id');

        return 'QC-PM-' . str_pad((string) (110001 + $lastId), 6, '0', STR_PAD_LEFT);
    }
}
