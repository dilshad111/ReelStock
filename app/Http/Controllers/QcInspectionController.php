<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\QcInspection;
use App\Models\QcInspectionDetail;
use App\Models\ReelReceipt;
use App\Models\PaperQuality;
use App\Models\Reel;
use App\Models\Supplier;
use App\Models\Setting;

class QcInspectionController extends Controller
{
    /**
     * List all QC inspections with filters
     */
    public function index(Request $request)
    {
        try {
            $query = QcInspection::with(['paperQuality', 'supplier', 'inspector', 'details.reel']);

            if ($request->filled('lot_number')) {
                $query->where('lot_number', 'like', '%' . $request->lot_number . '%');
            }

            if ($request->filled('qc_status')) {
                $query->where('qc_status', $request->qc_status);
            }

            if ($request->filled('supplier_id')) {
                $query->where('supplier_id', $request->supplier_id);
            }

            if ($request->filled('paper_quality_id')) {
                $query->where('paper_quality_id', $request->paper_quality_id);
            }

            if ($request->filled('date_from') && $request->filled('date_to')) {
                $query->whereBetween('inspection_date', [$request->date_from, $request->date_to]);
            }

            $inspections = $query->orderBy('id', 'desc')->paginate(50);

            return response()->json($inspections);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get open lots pending QC inspection
     */
    public function openLots(Request $request)
    {
        try {
            $query = ReelReceipt::with(['reel.paperQuality', 'reel.supplier'])
                ->whereNotNull('lot_number')
                ->where('lot_number', '!=', '');

            if ($request->filled('lot_number')) {
                $query->where('lot_number', 'like', '%' . $request->lot_number . '%');
            }

            // Group by lot_number to get unique lots
            $receipts = $query->orderBy('id', 'desc')->get();

            $latestInspections = QcInspection::orderByDesc('id')->get()->keyBy('lot_number');

            $lotGroups = $receipts->groupBy('lot_number')->map(function ($group) use ($latestInspections) {
                $first = $group->first();
                $reel = $first->reel;
                $quality = $reel ? $reel->paperQuality : null;
                $supplier = $reel ? $reel->supplier : null;
                $inspection = $latestInspections->get($first->lot_number);
                $statusMap = [
                    'pending' => 'Pending',
                    'approved' => 'Completed',
                    'rejected' => 'Rejected',
                ];
                $statusCode = $inspection ? $inspection->qc_status : 'pending';
                $statusLabel = $statusMap[$statusCode] ?? ucfirst($statusCode);

                return [
                    'lot_number' => $first->lot_number,
                    'po_number' => $inspection && $inspection->po_number ? $inspection->po_number : $first->po_number,
                    'grn_number' => $inspection && $inspection->grn_number ? $inspection->grn_number : $first->grn_number,
                    'receiving_date' => $first->receiving_date,
                    'paper_quality_id' => $reel ? $reel->paper_quality_id : null,
                    'paper_quality' => $quality ? ($quality->quality . ' ' . $quality->gsm_range) : 'N/A',
                    'paper_color' => $quality ? $quality->paper_color : null,
                    'supplier_id' => $reel ? $reel->supplier_id : null,
                    'supplier_name' => $supplier ? $supplier->name : 'N/A',
                    'inspection_id' => $inspection ? $inspection->id : null,
                    'qc_status' => $statusCode,
                    'qc_status_label' => $statusLabel,
                    'reel_count' => $group->count(),
                    'total_weight' => $group->sum(function ($r) {
                        return $r->reel ? $r->reel->original_weight : 0;
                    }),
                    'reels' => $group->map(function ($receipt) {
                        return [
                            'receipt_id' => $receipt->id,
                            'reel_id' => $receipt->reel_id,
                            'reel_no' => $receipt->reel ? $receipt->reel->reel_no : 'N/A',
                            'reel_size' => $receipt->reel ? $receipt->reel->reel_size : null,
                            'original_weight' => $receipt->reel ? $receipt->reel->original_weight : null,
                        ];
                    })->values()
                ];
            })->values();

            return response()->json($lotGroups);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get lot details with reels for QC entry
     */
    public function getLotDetails($lotNumber)
    {
        try {
            $receipts = ReelReceipt::with(['reel.paperQuality', 'reel.supplier'])
                ->where('lot_number', $lotNumber)
                ->get();

            if ($receipts->isEmpty()) {
                return response()->json(['error' => 'Lot not found'], 404);
            }

            $first = $receipts->first();
            $reel = $first->reel;
            $quality = $reel ? $reel->paperQuality : null;
            $supplier = $reel ? $reel->supplier : null;

            // Check if inspection already exists
            $existingInspection = QcInspection::with('details')
                ->where('lot_number', $lotNumber)
                ->first();

            $data = [
                'lot_number' => $lotNumber,
                'po_number' => $first->po_number,
                'grn_number' => $first->grn_number,
                'receiving_date' => $first->receiving_date,
                'paper_quality_id' => $reel ? $reel->paper_quality_id : null,
                'paper_quality' => $quality ? ($quality->quality . ' ' . $quality->gsm_range) : 'N/A',
                'paper_color' => $quality ? $quality->paper_color : null,
                'supplier_id' => $reel ? $reel->supplier_id : null,
                'supplier_name' => $supplier ? $supplier->name : 'N/A',
                'criteria' => $quality ? [
                    'min_gsm' => $quality->min_gsm,
                    'max_gsm' => $quality->max_gsm,
                    'min_bursting' => $quality->min_bursting,
                    'max_bursting' => $quality->max_bursting,
                    'min_moisture' => $quality->min_moisture,
                    'max_moisture' => $quality->max_moisture,
                    'min_cobb' => $quality->min_cobb,
                    'max_cobb' => $quality->max_cobb,
                ] : null,
                'reels' => $receipts->map(function ($receipt) {
                    return [
                        'receipt_id' => $receipt->id,
                        'reel_id' => $receipt->reel_id,
                        'reel_no' => $receipt->reel ? $receipt->reel->reel_no : 'N/A',
                        'reel_size' => $receipt->reel ? $receipt->reel->reel_size : null,
                        'original_weight' => $receipt->reel ? $receipt->reel->original_weight : null,
                    ];
                })->values(),
                'existing_inspection' => $existingInspection,
            ];

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a new QC inspection
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'lot_number' => 'required|string',
                'paper_quality_id' => 'required|exists:paper_qualities,id',
                'supplier_id' => 'required|exists:suppliers,id',
                'inspection_date' => 'required|date',
                'inspector_name' => 'required|string',
                'decision_type' => 'nullable|in:lot_accept,lot_reject,temporary_accept,partial_accept',
                'received_date' => 'required|date',
                'details' => 'required|array|min:1',
                'details.*.reel_id' => 'required|exists:reels,id',
                'details.*.reel_size' => 'nullable|numeric|min:0',
                'details.*.reel_weight' => 'nullable|numeric|min:0',
                'details.*.gsm' => 'nullable|numeric|min:0',
                'details.*.bursting' => 'nullable|numeric|min:0',
                'details.*.moisture' => 'nullable|numeric|min:0',
                'details.*.ash' => 'nullable|numeric|min:0',
                'details.*.cobb' => 'nullable|numeric|min:0',
            ]);

            $inspection = DB::transaction(function () use ($request) {
                $criteria = PaperQuality::find($request->paper_quality_id);

                $inspection = QcInspection::create([
                    'lot_number' => $request->lot_number,
                    'paper_quality_id' => $request->paper_quality_id,
                    'supplier_id' => $request->supplier_id,
                    'po_number' => $request->po_number,
                    'grn_number' => $request->grn_number,
                    'received_date' => $request->received_date,
                    'inspection_date' => $request->inspection_date,
                    'inspector_name' => $request->inspector_name,
                    'qc_status' => 'pending',
                    'decision_type' => $request->decision_type ?: 'lot_accept',
                    'remarks' => $request->remarks,
                    'inspected_by' => auth()->id(),
                ]);

                foreach ($request->details as $detailData) {
                    $reel = Reel::find($detailData['reel_id']);
                    $detail = new QcInspectionDetail([
                        'qc_inspection_id' => $inspection->id,
                        'reel_id' => $detailData['reel_id'],
                        'reel_no' => $reel ? $reel->reel_no : null,
                        'reel_size' => $detailData['reel_size'] ?? ($reel ? $reel->reel_size : null),
                        'reel_weight' => $detailData['reel_weight'] ?? ($reel ? $reel->original_weight : null),
                        'gsm' => $detailData['gsm'] ?? null,
                        'bursting' => $detailData['bursting'] ?? null,
                        'moisture' => $detailData['moisture'] ?? null,
                        'ash' => $detailData['ash'] ?? null,
                        'cobb' => $detailData['cobb'] ?? null,
                    ]);

                    // Validate against criteria
                    if ($criteria) {
                        $detail->validateAgainstCriteria($criteria);
                    }

                    $detail->save();
                }

                // Recalculate averages and determine status
                $inspection->recalculateAverages();
                $inspection->determineStatus();

                return $inspection;
            });

            return response()->json(
                $inspection->load(['paperQuality', 'supplier', 'inspector', 'details.reel']),
                201
            );
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Show a single QC inspection
     */
    public function show($id)
    {
        try {
            $inspection = QcInspection::with(['paperQuality', 'supplier', 'inspector', 'details.reel'])
                ->findOrFail($id);

            // Also include criteria from paper quality
            $criteria = $inspection->paperQuality ? [
                'min_gsm' => $inspection->paperQuality->min_gsm,
                'max_gsm' => $inspection->paperQuality->max_gsm,
                'min_bursting' => $inspection->paperQuality->min_bursting,
                'max_bursting' => $inspection->paperQuality->max_bursting,
                'min_moisture' => $inspection->paperQuality->min_moisture,
                'max_moisture' => $inspection->paperQuality->max_moisture,
                'min_cobb' => $inspection->paperQuality->min_cobb,
                'max_cobb' => $inspection->paperQuality->max_cobb,
            ] : null;

            $data = $inspection->toArray();
            $data['criteria'] = $criteria;

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update a QC inspection
     */
    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'inspection_date' => 'sometimes|date',
                'inspector_name' => 'sometimes|string',
                'decision_type' => 'sometimes|in:lot_accept,lot_reject,temporary_accept,partial_accept',
                'details' => 'sometimes|array',
                'details.*.reel_id' => 'required_with:details|exists:reels,id',
                'details.*.reel_size' => 'nullable|numeric|min:0',
                'details.*.reel_weight' => 'nullable|numeric|min:0',
                'details.*.gsm' => 'nullable|numeric|min:0',
                'details.*.bursting' => 'nullable|numeric|min:0',
                'details.*.moisture' => 'nullable|numeric|min:0',
                'details.*.ash' => 'nullable|numeric|min:0',
                'details.*.cobb' => 'nullable|numeric|min:0',
            ]);

            $inspection = DB::transaction(function () use ($request, $id) {
                $inspection = QcInspection::findOrFail($id);
                $criteria = PaperQuality::find($inspection->paper_quality_id);

                // Update header fields
                $headerFields = ['inspection_date', 'inspector_name', 'decision_type', 'remarks', 'po_number', 'grn_number'];
                $inspection->update($request->only($headerFields));

                // Update details if provided
                if ($request->has('details')) {
                    // Delete old details
                    $inspection->details()->delete();

                    foreach ($request->details as $detailData) {
                        $reel = Reel::find($detailData['reel_id']);
                        $detail = new QcInspectionDetail([
                            'qc_inspection_id' => $inspection->id,
                            'reel_id' => $detailData['reel_id'],
                            'reel_no' => $reel ? $reel->reel_no : null,
                            'reel_size' => $detailData['reel_size'] ?? ($reel ? $reel->reel_size : null),
                            'reel_weight' => $detailData['reel_weight'] ?? ($reel ? $reel->original_weight : null),
                            'gsm' => $detailData['gsm'] ?? null,
                            'bursting' => $detailData['bursting'] ?? null,
                            'moisture' => $detailData['moisture'] ?? null,
                            'ash' => $detailData['ash'] ?? null,
                            'cobb' => $detailData['cobb'] ?? null,
                        ]);

                        if ($criteria) {
                            $detail->validateAgainstCriteria($criteria);
                        }

                        $detail->save();
                    }

                    // Recalculate
                    $inspection->recalculateAverages();
                    $inspection->determineStatus();
                }

                return $inspection;
            });

            return response()->json(
                $inspection->load(['paperQuality', 'supplier', 'inspector', 'details.reel'])
            );
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete a QC inspection
     */
    public function destroy($id)
    {
        try {
            $inspection = QcInspection::findOrFail($id);
            $inspection->details()->delete();
            $inspection->delete();

            return response()->json(['message' => 'QC Inspection deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get report data for a QC inspection (for printing)
     */
    public function report($id)
    {
        try {
            $inspection = QcInspection::with(['paperQuality', 'supplier', 'inspector', 'details.reel'])
                ->findOrFail($id);

            $criteria = $inspection->paperQuality ? [
                'min_gsm' => $inspection->paperQuality->min_gsm,
                'max_gsm' => $inspection->paperQuality->max_gsm,
                'min_bursting' => $inspection->paperQuality->min_bursting,
                'max_bursting' => $inspection->paperQuality->max_bursting,
                'min_moisture' => $inspection->paperQuality->min_moisture,
                'max_moisture' => $inspection->paperQuality->max_moisture,
                'min_cobb' => $inspection->paperQuality->min_cobb,
                'max_cobb' => $inspection->paperQuality->max_cobb,
            ] : null;

            $data = $inspection->toArray();
            $data['criteria'] = $criteria;
            $data['paper_color'] = $inspection->paperQuality ? $inspection->paperQuality->paper_color : null;

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Download QC inspection report as PDF
     */
    public function reportPdf($id)
    {
        try {
            $inspection = QcInspection::with(['paperQuality', 'supplier', 'inspector', 'details.reel'])
                ->findOrFail($id);
            $approvedBy = Setting::where('key', 'qc_default_approved_by')->value('value') ?: '-';

            $criteria = $inspection->paperQuality ? [
                'min_gsm' => $inspection->paperQuality->min_gsm,
                'max_gsm' => $inspection->paperQuality->max_gsm,
                'min_bursting' => $inspection->paperQuality->min_bursting,
                'max_bursting' => $inspection->paperQuality->max_bursting,
                'min_moisture' => $inspection->paperQuality->min_moisture,
                'max_moisture' => $inspection->paperQuality->max_moisture,
                'min_cobb' => $inspection->paperQuality->min_cobb,
                'max_cobb' => $inspection->paperQuality->max_cobb,
            ] : null;

            $pdf = Pdf::loadView('reports.qc-inspection-pdf', [
                'inspection' => $inspection,
                'criteria' => $criteria,
                'approvedBy' => $approvedBy,
            ])->setPaper('a4', 'portrait');

            return $pdf->download('qc_inspection_' . $inspection->lot_number . '.pdf');
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
