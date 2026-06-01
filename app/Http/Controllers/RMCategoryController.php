<?php

namespace App\Http\Controllers;

use App\Models\RMCategory;
use App\Models\RMSubcategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RMCategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = RMCategory::with(['subcategories' => function ($query) {
            $query->where('status', 'Active')->orderBy('name');
        }])->orderBy('name');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:rm_categories,name',
            'description' => 'nullable|string',
            'status' => 'nullable|in:Active,Inactive',
        ]);

        $category = RMCategory::create([
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($data['name']),
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? 'Active',
            'is_system' => false,
        ]);

        return response()->json($category->load('subcategories'), 201);
    }

    public function show(RMCategory $rmCategory)
    {
        return response()->json($rmCategory->load('subcategories'));
    }

    public function update(Request $request, RMCategory $rmCategory)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('rm_categories', 'name')->ignore($rmCategory->id)],
            'description' => 'nullable|string',
            'status' => 'required|in:Active,Inactive',
        ]);

        $rmCategory->update([
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($data['name'], $rmCategory->id),
            'description' => $data['description'] ?? null,
            'status' => $data['status'],
        ]);

        return response()->json($rmCategory->load('subcategories'));
    }

    public function destroy(RMCategory $rmCategory)
    {
        if ($rmCategory->items()->exists()) {
            return response()->json(['error' => 'Cannot delete a category assigned to raw materials.'], 422);
        }

        $rmCategory->delete();

        return response()->json(['message' => 'Category deleted successfully.']);
    }

    public function subcategories(Request $request)
    {
        $query = RMSubcategory::with('category')->orderBy('name');

        if ($request->filled('rm_category_id')) {
            $query->where('rm_category_id', $request->rm_category_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->get());
    }

    public function storeSubcategory(Request $request)
    {
        $data = $request->validate([
            'rm_category_id' => 'required|exists:rm_categories,id',
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('rm_subcategories', 'name')->where('rm_category_id', $request->rm_category_id),
            ],
            'status' => 'nullable|in:Active,Inactive',
        ]);

        $subcategory = RMSubcategory::create([
            'rm_category_id' => $data['rm_category_id'],
            'name' => $data['name'],
            'slug' => $this->uniqueSubcategorySlug($data['name'], $data['rm_category_id']),
            'status' => $data['status'] ?? 'Active',
            'is_system' => false,
        ]);

        return response()->json($subcategory->load('category'), 201);
    }

    public function updateSubcategory(Request $request, RMSubcategory $rmSubcategory)
    {
        $data = $request->validate([
            'rm_category_id' => 'required|exists:rm_categories,id',
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('rm_subcategories', 'name')
                    ->where('rm_category_id', $request->rm_category_id)
                    ->ignore($rmSubcategory->id),
            ],
            'status' => 'required|in:Active,Inactive',
        ]);

        $rmSubcategory->update([
            'rm_category_id' => $data['rm_category_id'],
            'name' => $data['name'],
            'slug' => $this->uniqueSubcategorySlug($data['name'], $data['rm_category_id'], $rmSubcategory->id),
            'status' => $data['status'],
        ]);

        return response()->json($rmSubcategory->load('category'));
    }

    public function destroySubcategory(RMSubcategory $rmSubcategory)
    {
        if ($rmSubcategory->items()->exists()) {
            return response()->json(['error' => 'Cannot delete a subcategory assigned to raw materials.'], 422);
        }

        $rmSubcategory->delete();

        return response()->json(['message' => 'Subcategory deleted successfully.']);
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 2;

        while (RMCategory::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    private function uniqueSubcategorySlug(string $name, int $categoryId, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 2;

        while (
            RMSubcategory::where('rm_category_id', $categoryId)
                ->where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
