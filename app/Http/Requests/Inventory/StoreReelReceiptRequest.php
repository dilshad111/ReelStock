<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class StoreReelReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Use Gates/Policies in a real scenario
    }

    public function rules(): array
    {
        return [
            'paper_quality_id'  => 'required|exists:paper_qualities,id',
            'supplier_id'       => 'required|exists:suppliers,id',
            'reel_size'         => 'required|numeric|min:0',
            'reel_weight'       => 'required|numeric|min:0',
            'receiving_date'    => 'required|date',
            'received_by'       => 'nullable|string',
            'gsm'               => 'nullable|numeric|min:0',
            'bursting_strength' => 'nullable|numeric|min:0',
            'rate_per_kg'       => 'nullable|numeric|min:0',
            'qc_status'         => 'required|in:approved,rejected,on_hold',
            'remarks'           => 'nullable|string',
            'po_number'         => 'nullable|string',
            'grn_number'        => 'nullable|string',
        ];
    }
}
