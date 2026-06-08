@php
    $special = is_array($jobCard->special_details) ? $jobCard->special_details : [];
    $product = $jobCard->product;
    $customer = $jobCard->customer;
    $cartonQuality = $special['carton_quality'] ?? 'normal';
    $jobType = $special['job_type'] ?? 'Repeat';
    $priority = $special['quality_priority'] ?? 'Normal';
    $slotType = $special['slot_type'] ?? 'Simple';
    $inkCoverage = $special['ink_coverage'] ?? null;
    $tolerance = $jobCard->pieces_count == 1 && $jobCard->layers->count() >= 5 ? 5 : 3;
    $printDate = now()->format('d/m/Y');
    $itemCode = $product->item_code ?? $product->code ?? '-';
    $cartonCode = $special['carton_type_code'] ?? null;

    if (!$cartonCode && preg_match('/(\d{4}(?:-[A-Za-z0-9]+)?)/', (string) $jobCard->carton_type, $matches)) {
        $cartonCode = $matches[1];
    }

    $cartonPreviewDataUri = null;
    $cartonPreviewCandidates = array_filter([
        $cartonCode ? public_path('images/fefco/' . $cartonCode . '.png') : null,
        $cartonCode ? public_path('images/fefco/' . strtoupper($cartonCode) . '.png') : null,
        $cartonCode ? public_path('images/fefco/' . substr($cartonCode, 0, 4) . '.png') : null,
        public_path('images/fefco/0201.png'),
    ]);

    foreach ($cartonPreviewCandidates as $candidate) {
        if (is_file($candidate)) {
            $cartonPreviewDataUri = 'data:image/png;base64,' . base64_encode(file_get_contents($candidate));
            break;
        }
    }
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Card - {{ $jobCard->job_card_no }}</title>
    <style>
        @page { size: A4 portrait; margin: 3mm 5mm 3mm 10mm; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 0;
            background: #fff;
            color: #000;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9pt;
            line-height: 1.25;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .print-container {
            border: 2.5px solid #000;
            min-height: 285mm;
            padding: 4px;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 3px 5px; vertical-align: middle; }
        th { background: #e6e6e6; font-weight: 800; text-transform: uppercase; }
        .doc-table td { border-width: 2px; }
        .logo-cell {
            width: 10%;
            text-align: center;
            font-size: 11px;
            font-weight: 800;
            background: #f2f2f2;
        }
        .title-cell {
            position: relative;
            text-align: center;
            padding: 5px;
        }
        .title-cell h1 {
            margin: 0;
            color: #0000cd;
            font-size: 16px;
            font-weight: 900;
        }
        .print-date {
            position: absolute;
            right: 6px;
            top: 6px;
            font-size: 9px;
            color: #000;
        }
        .section-box {
            border: 1.5px solid #000;
            page-break-inside: avoid;
        }
        .section-title {
            margin: 1px;
            padding: 3px 10px;
            border: 1px solid #000;
            border-radius: 4px;
            background: linear-gradient(to bottom, #4d4d4d 0%, #000 100%);
            color: #fff;
            font-weight: 900;
            font-size: 10pt;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .job-info {
            display: grid;
            grid-template-columns: 1fr 0.95in;
            gap: 6px;
            padding: 5px;
            align-items: stretch;
        }
        .customer-name {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 3px;
            background: #e6e6e6;
            font-size: 14pt;
            font-weight: 900;
        }
        .info-line {
            margin-top: 4px;
            text-align: center;
            font-size: 11pt;
        }
        .sub-meta {
            display: flex;
            justify-content: center;
            gap: 14px;
            margin-top: 5px;
            padding-top: 3px;
            border-top: 1px dashed #aaa;
            font-size: 8.5pt;
        }
        .carton-preview-box {
            border: 2px solid #000;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 5px;
            min-height: 1.22in;
            background: #fff;
        }
        .carton-preview-box img {
            display: block;
            width: 100%;
            max-width: 1.08in;
            max-height: 0.82in;
            object-fit: contain;
        }
        .speed-strip {
            display: flex;
            justify-content: space-around;
            gap: 10px;
            border-top: 1.5px solid #000;
            background: #f7f7f7;
            padding: 3px 8px;
            font-size: 8.5pt;
        }
        .corrugation-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4px;
            padding: 4px;
        }
        .piece-box {
            border: 1.5px solid #000;
            background: #fafafa;
            padding: 4px;
        }
        .piece-head {
            display: flex;
            justify-content: space-between;
            gap: 6px;
            padding: 2px 4px;
            background: #e6e6e6;
            font-weight: 900;
            border: 1px solid #999;
            margin-bottom: 3px;
        }
        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: 900; }
        .muted { color: #555; }
        .warning-note {
            margin: 3px;
            padding: 4px;
            border: 1.5px solid #d4a017;
            background: #fff8c5;
            font-weight: 700;
        }
        .info-note {
            margin: 3px;
            padding: 4px;
            border: 1.5px solid #1976d2;
            background: #dbeafe;
            font-weight: 700;
        }
        .finish-note {
            margin: 3px;
            padding: 4px;
            border: 1.5px solid #28a745;
            background: #dcfce7;
            font-weight: 700;
        }
        .pantone-chip {
            display: inline-block;
            border: 1px solid #000;
            background: #eee;
            border-radius: 2px;
            padding: 1px 4px;
            margin: 1px;
            font-size: 8px;
            font-weight: 800;
            text-transform: uppercase;
        }
        .signature-row {
            margin-top: auto;
        }
        .signature-row td {
            height: 44px;
            width: 33.33%;
            vertical-align: bottom;
            text-align: center;
            font-size: 9pt;
            font-weight: 800;
        }
        .signature-line {
            border-top: 1.5px solid #000;
            padding-top: 3px;
            margin: 0 22px;
        }
    </style>
</head>
<body>
<div class="print-container">
    <table class="doc-table">
        <tr>
            <td rowspan="2" class="logo-cell">QC</td>
            <td colspan="8" class="title-cell">
                <h1>Job Card</h1>
                <div class="print-date"><strong><u>Printing Date:</u></strong> {{ $printDate }}</div>
            </td>
        </tr>
        <tr>
            <td class="center"><strong>Document ID No</strong></td>
            <td class="center"><strong>QC/DI3A/025</strong></td>
            <td class="center"><strong>Rev. #</strong></td>
            <td class="center bold">00</td>
            <td class="center"><strong>Rev. Date</strong></td>
            <td class="center"><strong>-</strong></td>
            <td class="center"><strong>Page #</strong></td>
            <td class="center"><strong>1 of 1</strong></td>
        </tr>
    </table>

    <div class="section-box">
        <div class="section-title">1. Job Information</div>
        <div class="job-info">
            <div class="center">
                <div><span class="customer-name">{{ $customer->name ?? '' }}</span></div>
                <div class="info-line">
                    <strong>Item Name:</strong> {{ $product->item_name ?? '-' }}
                    <strong style="margin-left: 18px;">Item Code:</strong> {{ $itemCode }}
                </div>
                <div class="info-line">
                    <strong>Size:</strong>
                    <span class="bold">
                        {{ number_format($jobCard->length_mm, $jobCard->uom === 'mm' ? 0 : 2) }} x
                        {{ number_format($jobCard->width_mm, $jobCard->uom === 'mm' ? 0 : 2) }} x
                        {{ number_format($jobCard->height_mm, $jobCard->uom === 'mm' ? 0 : 2) }}
                        {{ $jobCard->uom }}
                    </span>
                    <span style="color:#c00; font-style:italic; font-weight:900;">(Tolerance: +/-{{ $tolerance }} mm)</span>
                </div>
                <div class="sub-meta">
                    <span><strong>Job Type:</strong> {{ $jobType }}</span>
                    <span><strong>Quality:</strong> {{ ucfirst(str_replace('_', ' ', $cartonQuality)) }}</span>
                    <span><strong>Priority:</strong> {{ $priority }}</span>
                    @if($inkCoverage !== null)
                        <span><strong>Ink Coverage:</strong> {{ $inkCoverage }}%</span>
                    @endif
                </div>
            </div>
            <div class="carton-preview-box">
                @if($cartonPreviewDataUri)
                    <img src="{{ $cartonPreviewDataUri }}" alt="FEFCO carton preview">
                @endif
            </div>
        </div>
    </div>

    <div class="section-box">
        <div class="section-title">2. Corrugation</div>
        @if($jobCard->pieces_count > 1 && $jobCard->pieces->count() > 0)
            <div class="corrugation-grid">
                @foreach($jobCard->pieces as $piece)
                    <div class="piece-box">
                        <div class="piece-head">
                            <span>{{ $piece->piece_name }}</span>
                            <span>{{ number_format($piece->length_mm, 0) }}x{{ number_format($piece->width_mm, 0) }}x{{ number_format($piece->height_mm, 0) }} mm</span>
                        </div>
                        <table>
                            <tr>
                                <th>Deckle</th>
                                <th>Sheet Length</th>
                                <th>In MM</th>
                                <th>UPS</th>
                                <th>Weight</th>
                            </tr>
                            <tr>
                                <td class="center bold">{{ number_format($piece->deckle_size, 0) }}"</td>
                                <td class="center bold">{{ number_format($piece->sheet_length, 2) }}"</td>
                                <td class="center bold">{{ number_format($piece->sheet_length * 25.4, 0) }}</td>
                                <td class="center bold">{{ $piece->ups }}</td>
                                <td class="center bold">{{ number_format($piece->est_unit_weight, 3) }}kg</td>
                            </tr>
                        </table>
                        <table style="margin-top:3px;">
                            <tr><th>Layer</th><th>Paper</th><th>GSM</th><th>Flute</th></tr>
                            @foreach($piece->layers as $layer)
                                <tr>
                                    <td class="bold">{{ $layer->layer_type }}</td>
                                    <td>{{ $layer->paper_name }}</td>
                                    <td class="center">{{ $layer->gsm }}</td>
                                    <td class="center">{{ $layer->flute_profile }}</td>
                                </tr>
                            @endforeach
                        </table>
                        @if($piece->instructions)
                            <div class="warning-note">{{ $piece->instructions }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="center" style="padding:5px; font-size:13pt;">
                <strong>Size (Inner):</strong>
                <strong>{{ number_format($jobCard->length_mm, 0) }} x {{ number_format($jobCard->width_mm, 0) }} x {{ number_format($jobCard->height_mm, 0) }} {{ $jobCard->uom }}</strong>
                <span style="margin-left:16px;"><strong>Ply Type:</strong> {{ $jobCard->layers->count() ?: '-' }}-Ply</span>
            </div>
            <table>
                <tr>
                    <th>Deckle Size</th>
                    <th>Sheet Length (In)</th>
                    <th>Sheet Length (MM)</th>
                    <th>UPS</th>
                    <th>Est. Unit Weight</th>
                </tr>
                <tr>
                    <td class="center bold">{{ number_format($jobCard->deckle_size, 0) }}"</td>
                    <td class="center bold">{{ number_format($jobCard->sheet_length, 2) }}"</td>
                    <td class="center bold">{{ number_format($jobCard->sheet_length * 25.4, 0) }} mm</td>
                    <td class="center bold">{{ $jobCard->ups }}</td>
                    <td class="center bold">{{ number_format($jobCard->est_unit_weight, 3) }} kg</td>
                </tr>
            </table>
            <table style="margin-top:3px;">
                <tr><th>Layer</th><th>Paper Structure</th><th>GSM</th><th>Flute</th></tr>
                @forelse($jobCard->layers as $layer)
                    <tr>
                        <td class="bold">{{ $layer->layer_type }}</td>
                        <td>{{ $layer->paper_name }}</td>
                        <td class="center">{{ $layer->gsm }}</td>
                        <td class="center">{{ $layer->flute_profile }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="center muted">No paper structure configured.</td></tr>
                @endforelse
            </table>
        @endif
        @if(!empty($special['corrugation_instruction']))
            <div class="warning-note"><strong>Plant Instruction:</strong> {{ $special['corrugation_instruction'] }}</div>
        @endif
    </div>

    <div class="section-box">
        <div class="section-title">3. Printing &amp; Finishing</div>
        <table>
            <tr>
                <th style="width: 18%;">Process Type</th>
                <td style="width: 32%;">{{ $jobCard->printing_process ?: 'FLEXOGRAPHIC' }}</td>
                <th style="width: 18%;">Pasting Type</th>
                <td style="width: 32%;">{{ $jobCard->pasting_closure ?: 'GLUING' }}</td>
            </tr>
            <tr>
                <th>Printing</th>
                <td>{{ $jobCard->printing_colors_count > 0 ? $jobCard->printing_colors_count . ' Color Printing' : 'Un-Printed' }}</td>
                <th>Slot Type</th>
                <td>{{ $slotType }}</td>
            </tr>
            <tr>
                <th>Pantone / Ink</th>
                <td colspan="3">
                    @forelse(($jobCard->pantone_colors ?? []) as $color)
                        @if($color)
                            <span class="pantone-chip">{{ $color }}</span>
                        @endif
                    @empty
                        <span class="muted">No Pantone colors specified</span>
                    @endforelse
                </td>
            </tr>
        </table>
        @if(!empty($special['printing_instruction']))
            <div class="info-note"><strong>Print:</strong> {{ $special['printing_instruction'] }}</div>
        @endif
        @if(!empty($special['finishing_instruction']))
            <div class="finish-note"><strong>Finish:</strong> {{ $special['finishing_instruction'] }}</div>
        @endif
    </div>

    <div class="section-box">
        <div class="section-title">4. Special Add-ons</div>
        <table>
            <tr>
                <th>Honeycomb</th>
                <td>{{ !empty($special['honeycomb']) ? 'Required' : 'N/A' }}</td>
                <th>Separator</th>
                <td>{{ !empty($special['separators']) ? 'Required' : 'N/A' }}</td>
            </tr>
        </table>
    </div>

    <div class="section-box">
        <div class="section-title">5. Floor Processing &amp; Quality Control Instructions</div>
        <div style="min-height:45px; padding:5px; font-size:10pt;">
            {{ $jobCard->notes ?: '1. Ensure correct flute heights are maintained on the corrugator. 2. Check pasting strength and glue coverage across flaps. 3. Print registration must be inspected every 500 sheets. 4. Maintain structural pressure tolerances at delivery stackers.' }}
        </div>
    </div>

    <table class="signature-row">
        <tr>
            <td><div class="signature-line">Prepared By</div></td>
            <td><div class="signature-line">Checked By</div></td>
            <td><div class="signature-line">Approved By</div></td>
        </tr>
    </table>
</div>

<script>
    window.onload = function () {
        window.print();
    };
</script>
</body>
</html>
