<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSampleSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            // Master fields
            'customer_id'        => 'required|exists:customers,id',
            'sample_date'        => 'required|date',
            'length'             => 'required|numeric|min:0.01',
            'width'              => 'required|numeric|min:0.01',
            'height'             => 'required|numeric|min:0.01',
            'uom'                => 'required|in:mm,cm,inch',
            'quantity'           => 'required|integer|min:1',
            'print_type'         => 'required|in:printed,un-print',
            'ply'                => 'required|in:3,5,7',
            'size_approval_only' => 'required|boolean',
            'remarks'            => 'nullable|string|max:1000',

            // Paper layers — required only when NOT size-approval-only
            'paper_layers'                 => 'exclude_if:size_approval_only,true|required|array|min:1',
            'paper_layers.*.layer_sequence' => 'required_with:paper_layers|integer|min:1',
            'paper_layers.*.paper_type'     => 'required_with:paper_layers|string|max:100',
            'paper_layers.*.paper_quality_id' => 'required_with:paper_layers|exists:paper_qualities,id',
            'paper_layers.*.gsm'            => 'nullable|integer|min:1',

            // Add-ons (optional array)
            'addons'            => 'nullable|array',
            'addons.*.type'     => 'required|in:honeycomb,separator',
            'addons.*.length'   => 'nullable|numeric|min:0',
            'addons.*.width'    => 'nullable|numeric|min:0',
            'addons.*.height'   => 'nullable|numeric|min:0',
            'addons.*.ply'      => 'required|in:3,5,7',
            'addons.*.source'   => 'required|in:in-house,outsource',

            // Addon paper layers (conditional)
            'addons.*.paper_layers'                 => 'exclude_if:size_approval_only,true|nullable|array',
            'addons.*.paper_layers.*.layer_sequence' => 'required_with:addons.*.paper_layers|integer|min:1',
            'addons.*.paper_layers.*.paper_type'     => 'required_with:addons.*.paper_layers|string|max:100',
            'addons.*.paper_layers.*.paper_quality_id' => 'required_with:addons.*.paper_layers|exists:paper_qualities,id',
            'addons.*.paper_layers.*.gsm'            => 'nullable|integer|min:1',
        ];

        return $rules;
    }

    public function messages(): array
    {
        return [
            'paper_layers.required' => 'Paper construction layers are required when "Size Approval Only" is not checked.',
            'customer_id.required'  => 'Please select a customer.',
            'customer_id.exists'    => 'The selected customer does not exist.',
        ];
    }
}
