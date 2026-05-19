<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class StoreReelIssueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reel_no' => 'required|string|exists:reels,reel_no',
            'issue_date' => 'required|date',
            'quantity_issued' => 'required|numeric|min:0.01',
            'return_to_stock_weight' => 'nullable|numeric|min:0',
            'return_location' => 'nullable|string|in:GoDown,Factory',
            'issued_to' => 'required|string',
            'remarks' => 'nullable|string',
        ];
    }
}
