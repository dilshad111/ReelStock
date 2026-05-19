<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class StoreReelReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reel_no' => 'required|string|exists:reels,reel_no',
            'challan_no' => 'nullable|string|max:50',
            'return_date' => 'required|date',
            'remaining_weight' => 'required|numeric|min:0',
            'returned_to' => 'required|in:stock,supplier',
            'return_location' => 'nullable|string|in:GoDown,Factory',
            'condition' => 'required|in:good,damaged,qc_required',
            'vehicle_number' => 'nullable|string|max:50',
            'return_to_supplier_id' => 'nullable|exists:suppliers,id',
            'remarks' => 'nullable|string',
        ];
    }
}
