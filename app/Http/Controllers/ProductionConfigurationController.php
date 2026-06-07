<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\FluteFactor;
use App\Models\MachineOperator;
use App\Models\OptimizationRule;
use App\Models\PrintingColor;
use App\Models\ProductionMachine;
use App\Services\MachineSpeedOptimizer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductionConfigurationController extends Controller
{
    public function printingColors(Request $request)
    {
        $query = PrintingColor::query();
        $this->applySearch($query, $request, ['ink_code', 'ink_name']);
        $this->applySort($query, $request, ['ink_code', 'ink_name', 'created_at'], 'ink_code');

        return $query->paginate($request->integer('per_page', 15));
    }

    public function storePrintingColor(Request $request)
    {
        $data = $request->validate([
            'ink_code' => ['required', 'string', 'max:50', 'unique:printing_colors,ink_code'],
            'ink_name' => ['required', 'string', 'max:255'],
        ]);

        $data['created_by'] = optional($request->user())->id;
        $data['updated_by'] = optional($request->user())->id;

        return response()->json(PrintingColor::create($data), 201);
    }

    public function updatePrintingColor(Request $request, PrintingColor $printingColor)
    {
        $data = $request->validate([
            'ink_code' => ['required', 'string', 'max:50', Rule::unique('printing_colors', 'ink_code')->ignore($printingColor->id)],
            'ink_name' => ['required', 'string', 'max:255'],
        ]);

        $data['updated_by'] = optional($request->user())->id;
        $printingColor->update($data);

        return response()->json($printingColor);
    }

    public function destroyPrintingColor(PrintingColor $printingColor)
    {
        $printingColor->delete();
        return response()->noContent();
    }

    public function departments(Request $request)
    {
        $query = Department::withCount('machines');
        $this->applySearch($query, $request, ['department_name']);
        $this->applySort($query, $request, ['department_name', 'created_at'], 'department_name');

        return $query->paginate($request->integer('per_page', 15));
    }

    public function storeDepartment(Request $request)
    {
        $data = $request->validate([
            'department_name' => ['required', 'string', 'max:255', 'unique:departments,department_name'],
        ]);

        $data['created_by'] = optional($request->user())->id;
        $data['updated_by'] = optional($request->user())->id;

        return response()->json(Department::create($data), 201);
    }

    public function updateDepartment(Request $request, Department $department)
    {
        $data = $request->validate([
            'department_name' => ['required', 'string', 'max:255', Rule::unique('departments', 'department_name')->ignore($department->id)],
        ]);

        $data['updated_by'] = optional($request->user())->id;
        $department->update($data);

        return response()->json($department);
    }

    public function destroyDepartment(Department $department)
    {
        $department->delete();
        return response()->noContent();
    }

    public function machines(Request $request)
    {
        $query = ProductionMachine::with('department')->withCount('operators');
        $this->applySearch($query, $request, ['machine_name']);

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        $this->applySort($query, $request, ['machine_name', 'base_speed', 'minimum_speed', 'created_at'], 'machine_name');

        return $query->paginate($request->integer('per_page', 15));
    }

    public function storeMachine(Request $request)
    {
        $data = $request->validate([
            'machine_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('production_machines', 'machine_name')->where('department_id', $request->department_id),
            ],
            'department_id' => ['required', 'exists:departments,id'],
            'base_speed' => ['nullable', 'numeric', 'min:0'],
            'minimum_speed' => ['nullable', 'numeric', 'min:0'],
        ]);

        $data['created_by'] = optional($request->user())->id;
        $data['updated_by'] = optional($request->user())->id;

        return response()->json(ProductionMachine::create($data)->load('department'), 201);
    }

    public function updateMachine(Request $request, ProductionMachine $machine)
    {
        $data = $request->validate([
            'machine_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('production_machines', 'machine_name')
                    ->where('department_id', $request->department_id)
                    ->ignore($machine->id),
            ],
            'department_id' => ['required', 'exists:departments,id'],
            'base_speed' => ['nullable', 'numeric', 'min:0'],
            'minimum_speed' => ['nullable', 'numeric', 'min:0'],
        ]);

        $data['updated_by'] = optional($request->user())->id;
        $machine->update($data);

        return response()->json($machine->load('department'));
    }

    public function destroyMachine(ProductionMachine $machine)
    {
        $machine->delete();
        return response()->noContent();
    }

    public function operators(Request $request)
    {
        $query = MachineOperator::with('machine.department');
        $this->applySearch($query, $request, ['operator_name']);

        if ($request->filled('machine_id')) {
            $query->where('machine_id', $request->machine_id);
        }

        $this->applySort($query, $request, ['operator_name', 'created_at'], 'operator_name');

        return $query->paginate($request->integer('per_page', 15));
    }

    public function storeOperator(Request $request)
    {
        $data = $request->validate([
            'operator_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('machine_operators', 'operator_name')->where('machine_id', $request->machine_id),
            ],
            'machine_id' => ['required', 'exists:production_machines,id'],
        ]);

        $data['created_by'] = optional($request->user())->id;
        $data['updated_by'] = optional($request->user())->id;

        return response()->json(MachineOperator::create($data)->load('machine.department'), 201);
    }

    public function updateOperator(Request $request, MachineOperator $operator)
    {
        $data = $request->validate([
            'operator_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('machine_operators', 'operator_name')
                    ->where('machine_id', $request->machine_id)
                    ->ignore($operator->id),
            ],
            'machine_id' => ['required', 'exists:production_machines,id'],
        ]);

        $data['updated_by'] = optional($request->user())->id;
        $operator->update($data);

        return response()->json($operator->load('machine.department'));
    }

    public function destroyOperator(MachineOperator $operator)
    {
        $operator->delete();
        return response()->noContent();
    }

    public function fluteFactors(Request $request)
    {
        $query = FluteFactor::query();
        $this->applySearch($query, $request, ['flute_type', 'description']);
        $this->applySort($query, $request, ['flute_type', 'factor', 'created_at'], 'flute_type');

        return $query->paginate($request->integer('per_page', 15));
    }

    public function storeFluteFactor(Request $request)
    {
        $data = $this->validateFluteFactor($request);
        $data['created_by'] = optional($request->user())->id;
        $data['updated_by'] = optional($request->user())->id;

        return response()->json(FluteFactor::create($data), 201);
    }

    public function updateFluteFactor(Request $request, FluteFactor $fluteFactor)
    {
        $data = $this->validateFluteFactor($request, $fluteFactor);
        $data['updated_by'] = optional($request->user())->id;
        $fluteFactor->update($data);

        return response()->json($fluteFactor);
    }

    public function destroyFluteFactor(FluteFactor $fluteFactor)
    {
        $fluteFactor->delete();
        return response()->noContent();
    }

    public function optimizationRules(Request $request)
    {
        $query = OptimizationRule::query();
        $this->applySearch($query, $request, ['parameter_name', 'condition_field', 'condition_value']);

        if ($request->filled('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        $this->applySort($query, $request, ['parameter_name', 'condition_field', 'adjustment_value', 'created_at'], 'created_at', 'desc');

        return $query->paginate($request->integer('per_page', 15));
    }

    public function storeOptimizationRule(Request $request)
    {
        $data = $this->validateOptimizationRule($request);
        $data['created_by'] = optional($request->user())->id;
        $data['updated_by'] = optional($request->user())->id;

        return response()->json(OptimizationRule::create($data), 201);
    }

    public function updateOptimizationRule(Request $request, OptimizationRule $rule)
    {
        $data = $this->validateOptimizationRule($request);
        $data['updated_by'] = optional($request->user())->id;
        $rule->update($data);

        return response()->json($rule);
    }

    public function destroyOptimizationRule(OptimizationRule $rule)
    {
        $rule->delete();
        return response()->noContent();
    }

    public function applyOptimizationRules(Request $request, MachineSpeedOptimizer $optimizer)
    {
        $data = $request->validate([
            'base_speed' => ['required', 'numeric', 'min:0'],
            'minimum_speed' => ['nullable', 'numeric', 'min:0'],
            'job' => ['required', 'array'],
        ]);

        return response()->json($optimizer->apply(
            (float) $data['base_speed'],
            $data['job'],
            isset($data['minimum_speed']) ? (float) $data['minimum_speed'] : null
        ));
    }

    public function lookups()
    {
        return response()->json([
            'departments' => Department::orderBy('department_name')->get(),
            'machines' => ProductionMachine::with('department')->orderBy('machine_name')->get(),
            'printing_colors' => PrintingColor::orderBy('ink_code')->get(),
            'flute_factors' => FluteFactor::where('is_active', true)->orderBy('flute_type')->get(),
        ]);
    }

    private function validateFluteFactor(Request $request, ?FluteFactor $fluteFactor = null): array
    {
        $data = $request->validate([
            'flute_type' => ['required', 'string', 'max:20', Rule::unique('flute_factors', 'flute_type')->ignore($fluteFactor?->id)],
            'factor' => ['required', 'numeric', 'gt:0'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $data['flute_type'] = strtoupper(trim($data['flute_type']));

        return $data;
    }

    private function validateOptimizationRule(Request $request): array
    {
        return $request->validate([
            'parameter_name' => ['required', 'string', 'max:255'],
            'condition_field' => ['required', Rule::in(OptimizationRule::CONDITION_FIELDS)],
            'operator' => ['required', Rule::in(OptimizationRule::OPERATORS)],
            'condition_value' => ['required', 'string'],
            'adjustment_type' => ['required', Rule::in(OptimizationRule::ADJUSTMENT_TYPES)],
            'adjustment_value' => ['required', 'numeric', 'gt:0'],
            'is_active' => ['boolean'],
        ]);
    }

    private function applySearch($query, Request $request, array $columns): void
    {
        if (!$request->filled('search')) {
            return;
        }

        $search = $request->search;
        $query->where(function ($q) use ($columns, $search) {
            foreach ($columns as $column) {
                $q->orWhere($column, 'like', "%{$search}%");
            }
        });
    }

    private function applySort($query, Request $request, array $allowed, string $default, string $direction = 'asc'): void
    {
        $sortBy = in_array($request->sort_by, $allowed, true) ? $request->sort_by : $default;
        $sortDirection = strtolower($request->sort_direction ?? $direction) === 'desc' ? 'desc' : 'asc';

        $query->orderBy($sortBy, $sortDirection);
    }
}
