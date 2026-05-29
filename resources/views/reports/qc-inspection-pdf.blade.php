<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>QC Inspection Report</title>
    <style>
        @page { size: A4 portrait; margin: 8mm 10mm; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #000; margin: 0; }
        .report-page { min-height: 275mm; display: flex; flex-direction: column; }
        .page-break { page-break-after: always; }
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 6px; page-break-inside: avoid; }
        .header-table td { border: 1px solid #000; }
        .header-logo { width: 140px; text-align: center; vertical-align: middle; padding: 6px; }
        .header-logo img { max-width: 120px; max-height: 68px; }
        .header-title { text-align: center; font-size: 18px; font-weight: 700; color: #000; padding: 9px 6px; white-space: nowrap; border: 1px solid #000; }
        .meta-label { width: 110px; text-align: center; font-size: 10px; padding: 6px 4px; white-space: nowrap; }
        .meta-value { width: 95px; text-align: center; font-size: 11px; font-weight: 700; padding: 6px 4px; white-space: nowrap; }
        .meta { width: 100%; border-collapse: collapse; margin-bottom: 6px; page-break-inside: avoid; }
        .meta td { border: 1px solid #000; padding: 4px 6px; color: #000; font-size: 10px; }
        .meta .label { width: 120px; color: #000; font-weight: 700; background: #f3f4f6; }
        .lot-value { white-space: nowrap; min-width: 130px; font-weight: 700; }
        .criteria-section { margin-bottom: 6px; border: 1px solid #000; page-break-inside: avoid; }
        .criteria-title { background: #e5e7eb; color: #000; font-weight: 700; padding: 4px 8px; font-size: 9px; text-transform: uppercase; text-align: center; border-bottom: 1px solid #000; }
        .criteria-table { width: 100%; border-collapse: collapse; }
        .criteria-table td { border-right: 1px solid #000; padding: 4px 6px; text-align: center; color: #000; font-size: 9px; }
        .criteria-table td:last-child { border-right: 0; }
        .criteria-label { display: block; font-size: 8px; font-weight: 700; text-transform: uppercase; }
        .criteria-value { display: block; font-size: 9.5px; font-weight: 700; margin-top: 1px; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { border: 1px solid #000; padding: 4px; text-align: center; color: #000; font-size: 10.5px; }
        .table th { background: #e5e7eb; color: #000; font-size: 9px; font-weight: 700; }
        .left { text-align: left !important; color: #000; }
        .weight-cell { font-weight: 700; text-align: center; color: #000; }
        .result-cell { font-weight: 700; color: #000; }
        .decision-table { width: 100%; border-collapse: collapse; margin: 6px 0; page-break-inside: avoid; }
        .decision-table td { border: 1px solid #000; text-align: center; font-weight: 500; font-size: 9px; padding: 4px 3px; }
        .decision-table td.selected { font-weight: 700; }
        .decision-check { font-size: 2em; line-height: 0; vertical-align: -0.12em; }
        .remarks-box { border: 1px solid #000; min-height: 30px; padding: 5px 6px; margin-bottom: 6px; color: #000; page-break-inside: avoid; }
        .remarks-text { margin-top: 3px; color: #000; }
        .footer-section { margin-top: 0; page-break-inside: avoid; flex: 1; display: flex; flex-direction: column; min-height: 0; }
        .signature-only { margin-top: 6px; border: 1px solid #111827; padding: 6px 8px; page-break-inside: avoid; }
        .signature-grid { width: 100%; border-collapse: separate; border-spacing: 30px 0; }
        .signature-cell { width: 50%; vertical-align: top; }
        .sig-name { min-height: 16px; font-size: 10px; font-weight: 700; color: #000; }
        .sig-line { border-bottom: 1px solid #111827; height: 12px; }
        .sig-label { margin-top: 2px; font-size: 10px; font-weight: 700; color: #000; }
        .sample-box { height: auto !important; flex: 1; min-height: 180px; position: relative; border: 1px solid #111827; margin-top: 6px; page-break-inside: avoid; overflow: hidden; }
        .sample-text { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; font-size: 42px; font-weight: 800; color: #d1d5db; letter-spacing: 0.5px; text-align: center; line-height: 0.95; width: 90%; margin: 0 auto; white-space: normal; word-break: break-word; }
    </style>
</head>
<body>
@php
    $pages = $inspection->details->chunk(13);
    if ($pages->count() === 0) {
        $pages = collect([collect([])]);
    }
    $totalPages = $pages->count();
    $allCols = collect([
        ['key' => 'gsm', 'label' => 'GSM'],
        ['key' => 'bursting', 'label' => 'Bursting (PSI)'],
        ['key' => 'moisture', 'label' => 'Moisture'],
        ['key' => 'ash', 'label' => 'Ash'],
        ['key' => 'cobb', 'label' => 'Cobb'],
    ]);
    $optionalCols = $allCols->slice(2)->filter(function ($col) use ($inspection) {
        return $inspection->details->contains(function ($detail) use ($col) {
            $value = $detail->{$col['key']};
            return $value !== null && $value !== '' && $value !== '-' && (float) $value != 0.0;
        });
    })->values();
    $activeCols = $allCols->take(2)->concat($optionalCols)->values();
@endphp
@foreach($pages as $pageIndex => $pageRows)
@php
    $rowCount = max($pageRows->count(), 1);
    $sampleHeight = max(180, (int) round(560 - (($rowCount - 1) * 30)));
    $avg = function ($key) use ($pageRows) {
        $vals = $pageRows->map(function ($r) use ($key) {
            return is_numeric($r->{$key}) ? (float) $r->{$key} : null;
        })->filter(function ($v) {
            return $v !== null;
        })->values();
        if ($vals->count() === 0) return '-';
        return number_format($vals->avg(), 2);
    };
    $decision = $inspection->decision_type ?? 'lot_accept';
    $criterion = function ($key) use ($criteria) {
        if (!$criteria || !array_key_exists($key, $criteria)) return '-';
        return $criteria[$key] !== null && $criteria[$key] !== '' ? $criteria[$key] : '-';
    };
    $criteriaMap = [
        'gsm' => ['label' => 'GSM', 'min' => 'min_gsm', 'max' => 'max_gsm'],
        'bursting' => ['label' => 'Bursting (PSI)', 'min' => 'min_bursting', 'max' => 'max_bursting'],
        'moisture' => ['label' => 'Moisture', 'min' => 'min_moisture', 'max' => 'max_moisture'],
        'cobb' => ['label' => 'Cobb', 'min' => 'min_cobb', 'max' => 'max_cobb'],
    ];
    $criteriaCols = $activeCols->filter(function ($col) use ($criteriaMap) {
        return array_key_exists($col['key'], $criteriaMap);
    })->values();
@endphp
<div class="report-page{{ $pageIndex < $totalPages - 1 ? ' page-break' : '' }}">
    <table class="header-table">
        <tr>
            <td class="header-logo" rowspan="2">
                <img src="{{ public_path('images/quality-cartons-logo.png') }}" alt="Quality Cartons">
            </td>
            <td class="header-title" colspan="8">INCOMING MATERIAL (REEL) INSPECTION REPORT</td>
        </tr>
        <tr>
            <td class="meta-label">Document I.D. #</td>
            <td class="meta-value">QC/DI3A/045</td>
            <td class="meta-label">Rev. #</td>
            <td class="meta-value">01</td>
            <td class="meta-label">Rev. Date</td>
            <td class="meta-value">25/10/2016</td>
            <td class="meta-label">Page #</td>
            <td class="meta-value">{{ $pageIndex + 1 }} of {{ $totalPages }}</td>
        </tr>
    </table>

    <table class="meta">
        <tr>
            <td class="label">Lot #</td><td class="lot-value">{{ $inspection->lot_number }}</td>
            <td class="label">Inspection Date</td><td>{{ \Carbon\Carbon::parse($inspection->inspection_date)->format('d/m/Y') }}</td>
            <td class="label">Status</td><td>{{ strtoupper($inspection->qc_status ?? 'pending') }}</td>
        </tr>
        <tr>
            <td class="label">Paper</td><td style="white-space:nowrap;min-width:280px;">{{ $inspection->paperQuality->quality ?? '-' }} {{ $inspection->paperQuality->gsm_range ?? '' }}</td>
            <td class="label">Supplier</td><td>{{ $inspection->supplier->name ?? '-' }}</td>
            <td class="label">PO No</td><td>{{ $inspection->po_number ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Received Date</td><td>{{ $inspection->received_date ? \Carbon\Carbon::parse($inspection->received_date)->format('d/m/Y') : '-' }}</td>
            <td class="label">Inspected</td><td>{{ $inspection->inspection_date ? \Carbon\Carbon::parse($inspection->inspection_date)->format('d/m/Y') : '-' }}</td>
            <td class="label">GRN No</td><td>{{ $inspection->grn_number ?? '-' }}</td>
        </tr>
    </table>

    @if($criteria && $criteriaCols->count())
        <div class="criteria-section">
            <div class="criteria-title">Quality Acceptance Criteria</div>
            <table class="criteria-table">
                <tr>
                    @foreach($criteriaCols as $col)
                        @php $meta = $criteriaMap[$col['key']]; @endphp
                        <td><span class="criteria-label">{{ $meta['label'] }}</span><span class="criteria-value">{{ $criterion($meta['min']) }} - {{ $criterion($meta['max']) }}</span></td>
                    @endforeach
                </tr>
            </table>
        </div>
    @endif

    <table class="table">
        <thead>
            <tr>
                <th style="white-space: nowrap;">S. No.</th>
                <th>Reel No</th>
                <th>Size</th>
                <th>Weight (kg)</th>
                @foreach($activeCols as $col)
                    <th>{{ $col['label'] }}</th>
                @endforeach
                <th>Result</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pageRows as $i => $detail)
                <tr>
                    <td>{{ ($pageIndex * 13) + $i + 1 }}</td>
                    <td class="left">{{ $detail->reel_no }}</td>
                    <td>{{ $detail->reel_size ?? '-' }}</td>
                    <td class="weight-cell">{{ $detail->reel_weight ?? '-' }}</td>
                    @foreach($activeCols as $col)
                        <td>{{ ($detail->{$col['key']} !== null && $detail->{$col['key']} !== '') ? $detail->{$col['key']} : '-' }}</td>
                    @endforeach
                    <td class="result-cell">{{ $detail->is_passed === false ? 'FAIL' : 'PASS' }}</td>
                </tr>
            @endforeach
            <tr>
                <td colspan="4" style="text-align:right;font-weight:700;padding-right:12px">Average</td>
                @foreach($activeCols as $col)
                    <td style="text-align:center;font-weight:700">{{ $avg($col['key']) }}</td>
                @endforeach
                <td></td>
            </tr>
        </tbody>
    </table>

    <table class="decision-table">
        <tr>
            <td class="{{ $decision === 'lot_accept' ? 'selected' : '' }}">Lot Accept{{ $decision === 'lot_accept' ? ' ✓' : '' }}</td>
            <td class="{{ $decision === 'lot_reject' ? 'selected' : '' }}">Lot Reject{{ $decision === 'lot_reject' ? ' ✓' : '' }}</td>
            <td class="{{ $decision === 'temporary_accept' ? 'selected' : '' }}">Temporary Accept{{ $decision === 'temporary_accept' ? ' ✓' : '' }}</td>
            <td class="{{ $decision === 'partial_accept' ? 'selected' : '' }}">Partial Accept{{ $decision === 'partial_accept' ? ' ✓' : '' }}</td>
        </tr>
    </table>

    <div class="remarks-box">
        <div>
            Remarks:
            <span style="font-weight:700;{{ ($inspection->qc_status ?? 'pending') === 'approved' ? 'text-decoration:underline;' : '' }}">
            @if(($inspection->qc_status ?? 'pending') === 'approved')
                APPROVED (LOT ACCEPTED)
            @elseif(($inspection->qc_status ?? 'pending') === 'rejected')
                REJECTED
            @else
                PENDING
            @endif
            </span>
        </div>
        @if($inspection->remarks)
            <div class="remarks-text">{{ $inspection->remarks }}</div>
        @endif
    </div>

    <div class="footer-section">
        <div class="signature-only">
            <table class="signature-grid">
                <tr>
                    <td class="signature-cell">
                        <div class="sig-name">{{ $inspection->inspector_name ?: '-' }}</div>
                        <div class="sig-line"></div>
                        <div class="sig-label">Checked By</div>
                    </td>
                    <td class="signature-cell">
                        <div class="sig-name">{{ $approvedBy ?: '-' }}</div>
                        <div class="sig-line"></div>
                        <div class="sig-label">Approved By</div>
                    </td>
                </tr>
            </table>
        </div>
        <div class="sample-box" style="height: {{ $sampleHeight }}px; min-height: {{ $sampleHeight }}px;">
            <div class="sample-text">SAMPLE ATTACH HERE</div>
        </div>
    </div>
</div>
@endforeach
</body>
</html>
