<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreSampleSubmissionRequest;
use App\Models\SampleSubmission;
use App\Models\SampleAddon;
use Illuminate\Support\Facades\DB;

class SampleSubmissionController extends Controller
{
    /**
     * Paginated listing with filters: customer_id, date_from, date_to.
     */
    public function index(Request $request)
    {
        try {
            $query = SampleSubmission::with([
                'customer',
                'creator',
                'addons',
                'paperLayers.paperQuality',
                'addons.paperLayers.paperQuality',
            ]);

            if ($request->filled('customer_id')) {
                $query->where('customer_id', $request->customer_id);
            }

            if ($request->filled('date_from') && $request->filled('date_to')) {
                $query->whereBetween('sample_date', [$request->date_from, $request->date_to]);
            } elseif ($request->filled('date_from')) {
                $query->where('sample_date', '>=', $request->date_from);
            } elseif ($request->filled('date_to')) {
                $query->where('sample_date', '<=', $request->date_to);
            }

            $perPage = $request->input('per_page', 50);
            $results = $query->orderBy('id', 'desc')->paginate($perPage);

            return response()->json($results);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Create a new sample submission with nested layers and addons.
     */
    public function store(StoreSampleSubmissionRequest $request)
    {
        return DB::transaction(function () use ($request) {
            // 1. Create master record
            $submission = SampleSubmission::create([
                'customer_id'        => $request->customer_id,
                'sample_date'        => $request->sample_date,
                'length'             => $request->length,
                'width'              => $request->width,
                'height'             => $request->height,
                'uom'                => $request->uom,
                'quantity'           => $request->quantity,
                'print_type'         => $request->print_type,
                'ply'                => $request->ply,
                'size_approval_only' => $request->size_approval_only,
                'remarks'            => $request->remarks,
                'sample_made_by'     => $request->sample_made_by,
                'joinery_technique'  => $request->joinery_technique,
                'created_by'         => $request->user()->id,
            ]);

            // 2. Create paper layers for the main submission (if not size-approval-only)
            if (!$request->size_approval_only && $request->has('paper_layers')) {
                foreach ($request->paper_layers as $layer) {
                    $submission->paperLayers()->create([
                        'layer_sequence' => $layer['layer_sequence'],
                        'paper_type'     => $layer['paper_type'],
                        'paper_quality_id' => $layer['paper_quality_id'],
                        'gsm'            => $layer['gsm'] ?? null,
                    ]);
                }
            }

            // 3. Create add-ons (honeycomb / separator) with their own paper layers
            if ($request->has('addons') && is_array($request->addons)) {
                foreach ($request->addons as $addonData) {
                    $addon = SampleAddon::create([
                        'sample_submission_id' => $submission->id,
                        'type'    => $addonData['type'],
                        'length'  => $addonData['length'] ?? null,
                        'width'   => $addonData['width'] ?? null,
                        'height'  => $addonData['height'] ?? null,
                        'ply'     => $addonData['ply'],
                        'source'  => $addonData['source'],
                    ]);

                    // Addon paper layers
                    if (!$request->size_approval_only
                        && isset($addonData['paper_layers'])
                        && is_array($addonData['paper_layers'])) {
                        foreach ($addonData['paper_layers'] as $layer) {
                            $addon->paperLayers()->create([
                                'layer_sequence' => $layer['layer_sequence'],
                                'paper_type'     => $layer['paper_type'],
                                'paper_quality_id' => $layer['paper_quality_id'],
                                'gsm'            => $layer['gsm'] ?? null,
                            ]);
                        }
                    }
                }
            }

            return response()->json(
                $submission->load([
                    'customer',
                    'creator',
                    'paperLayers.paperQuality',
                    'addons.paperLayers.paperQuality',
                ]),
                201
            );
        });
    }

    /**
     * Show a single sample submission with all relationships.
     */
    public function show($id)
    {
        return response()->json(
            SampleSubmission::with([
                'customer',
                'creator',
                'paperLayers.paperQuality',
                'addons.paperLayers.paperQuality',
            ])->findOrFail($id)
        );
    }

    /**
     * Delete a sample submission.
     */
    public function destroy($id)
    {
        try {
            $submission = SampleSubmission::findOrFail($id);
            // Cascading delete handled by foreign key constraints
            $submission->delete();

            return response()->json(['success' => true, 'message' => 'Sample submission deleted.']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
