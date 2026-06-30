<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFGDamageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => 'required|date',
            'customer_id' => 'required|exists:customers,id',
            'product_id' => 'required|exists:products,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'job_card_id' => 'nullable|exists:job_cards,id',
            'job_number' => 'nullable|string|max:100',
            'quantity' => 'required|numeric|min:0.01',
            'reason' => 'required|in:Water Damage,Compression,Printing Defect,Pest Damage,Expired,Handling Damage,Other',
            'remarks' => 'nullable|string|max:1000',
            'approved_by' => 'nullable|exists:users,id',
        ];
    }
}
