<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Job Card Blueprint - {{ $jobCard->job_card_no }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 3mm 5mm 3mm 10mm;
        }
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 11px;
            color: #000;
            line-height: 1.2;
            margin: 0;
            padding: 0;
            -webkit-print-color-adjust: exact;
        }
        .container {
            width: 100%;
            max-width: 195mm; /* Max printable width */
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 285mm; /* Perfect A4 height minus margins */
            box-sizing: border-box;
        }
        .border-heavy {
            border: 2px solid #000;
        }
        .border-medium {
            border: 1.5px solid #000;
        }
        .border-light {
            border: 1px solid #000;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .fw-bold { font-weight: bold; }
        .text-uppercase { text-transform: uppercase; }
        
        /* Split Banner Header */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 3px;
        }
        .header-table td {
            border: 2px solid #000;
            padding: 4px;
            vertical-align: middle;
        }
        .logo-cell {
            width: 25%;
            font-size: 16px;
            letter-spacing: 1px;
        }
        .title-cell {
            width: 50%;
        }
        .title-cell h1 {
            margin: 0;
            font-size: 18px;
            font-weight: 900;
            letter-spacing: 2px;
        }
        .meta-cell {
            width: 25%;
            font-size: 9px;
            line-height: 1.3;
        }

        /* Summary Box layout */
        .summary-box {
            display: flex;
            width: 100%;
            border: 2px solid #000;
            margin-bottom: 3px;
        }
        .summary-details {
            width: 65%;
            border-right: 2px solid #000;
            padding: 4px;
        }
        .summary-details table {
            width: 100%;
            border-collapse: collapse;
        }
        .summary-details td {
            padding: 2px 4px;
            vertical-align: top;
        }
        .summary-cad {
            width: 35%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 4px;
            background: #fff;
        }

        /* Technical specs grid */
        .section-title {
            background: #000;
            color: #fff;
            font-weight: bold;
            text-align: center;
            padding: 2px 0;
            font-size: 10px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .tech-table, .substrate-table, .ops-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 3px;
        }
        .tech-table th, .tech-table td,
        .substrate-table th, .substrate-table td,
        .ops-table th, .ops-table td {
            border: 1px solid #000;
            padding: 3px 5px;
            vertical-align: middle;
        }
        .tech-table th, .substrate-table th, .ops-table th {
            background-color: #f2f2f2;
            font-weight: bold;
            font-size: 9px;
            text-transform: uppercase;
        }

        /* Pantone visual chips */
        .pantone-container {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        .pantone-chip {
            border: 1px solid #000;
            padding: 1px 4px;
            font-size: 8px;
            font-weight: bold;
            border-radius: 2px;
            background-color: #eee;
        }

        /* Slotting matrix styles */
        .slotting-matrix {
            display: flex;
            justify-content: space-around;
            border: 1px solid #000;
            padding: 4px;
            background: #fafafa;
            margin-bottom: 3px;
        }
        .slotting-col {
            text-align: center;
            border-right: 1px dashed #000;
            flex: 1;
        }
        .slotting-col:last-child {
            border-right: none;
        }
        .slotting-val {
            font-size: 12px;
            font-weight: bold;
            margin-top: 2px;
        }

        /* Notes & Instructions */
        .instruction-box {
            border: 1.5px solid #000;
            padding: 4px;
            min-height: 45px;
            margin-bottom: 3px;
            font-size: 10px;
        }

        /* Multi piece configuration */
        .pieces-container {
            border: 1.5px solid #000;
            margin-bottom: 3px;
            padding: 3px;
        }
        .piece-item {
            border: 1px solid #000;
            margin-bottom: 3px;
            padding: 3px;
        }
        .piece-item:last-child {
            margin-bottom: 0;
        }

        /* Footer signatures */
        .footer-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: auto;
        }
        .footer-table td {
            border: 2px solid #000;
            width: 33.33%;
            padding: 8px 4px 2px 4px;
            vertical-align: bottom;
        }
        .signature-line {
            border-top: 1px dashed #000;
            margin-top: 20px;
            font-size: 9px;
            text-align: center;
            text-transform: uppercase;
        }

        /* SVG Box style */
        .box-svg {
            stroke: #000;
            stroke-width: 1.5px;
            fill: none;
            stroke-dasharray: 4 2;
        }
        .box-solid {
            stroke: #000;
            stroke-width: 1.8px;
            fill: none;
        }
    </style>
</head>
<body>

<div class="container">
    <div>
        <!-- 1. SPLIT BANNER HEADER -->
        <table class="header-table">
            <tr>
                <td class="logo-cell text-center fw-bold">
                    <div style="font-size: 18px; line-height: 1;">QUALITY</div>
                    <div style="font-size: 10px; font-family: sans-serif; color: #555;">CARTONS</div>
                </td>
                <td class="title-cell text-center">
                    <h1>MANUFACTURING SPECIFICATION</h1>
                    <div style="font-size: 10px; font-weight: bold; margin-top: 2px;">JOB CARD: {{ $jobCard->job_card_no }}</div>
                </td>
                <td class="meta-cell">
                    <strong>DOC ID :</strong> QC-JC-{{ str_pad($jobCard->id, 5, '0', STR_PAD_LEFT) }}<br>
                    <strong>REV NO :</strong> 02 / ISO 9001<br>
                    <strong>DATE   :</strong> {{ \Carbon\Carbon::parse($jobCard->planned_date)->format('d-M-Y') }}<br>
                    <strong>PAGE   :</strong> 1 OF 1
                </td>
            </tr>
        </table>

        <!-- 2. JOB SUMMARY BOX -->
        <div class="summary-box">
            <div class="summary-details">
                <table>
                    <tr>
                        <td class="fw-bold" style="width: 25%;">CUSTOMER:</td>
                        <td class="fw-bold text-uppercase">{{ $jobCard->customer->name }}</td>
                    </tr>
                    <tr>
                        <td class="fw-bold">FG ITEM:</td>
                        <td class="text-uppercase">{{ $jobCard->product->item_name }} ({{ $jobCard->product->item_code }})</td>
                    </tr>
                    <tr>
                        <td class="fw-bold">BOX TYPE:</td>
                        <td>{{ $jobCard->carton_type }}</td>
                    </tr>
                    <tr>
                        <td class="fw-bold">PLANNED QTY:</td>
                        <td class="fw-bold" style="font-size: 12px;">{{ number_format($jobCard->planned_qty) }} PCS</td>
                    </tr>
                    <tr>
                        <td class="fw-bold">OUTER SIZE:</td>
                        <td class="fw-bold" style="font-size: 12px; letter-spacing: 0.5px;">
                            {{ number_format($jobCard->length_mm, 1) }} x {{ number_format($jobCard->width_mm, 1) }} x {{ number_format($jobCard->height_mm, 1) }} {{ strtoupper($jobCard->uom) }}
                        </td>
                    </tr>
                </table>
            </div>
            <div class="summary-cad">
                <!-- Inline SVG CAD schematic for FEFCO 0201 Standard Box -->
                <svg width="100%" height="80" viewBox="0 0 200 90">
                    <!-- Flaps & Body unfolding structure -->
                    <rect x="10" y="5" width="40" height="20" class="box-svg" />
                    <rect x="50" y="5" width="45" height="20" class="box-svg" />
                    <rect x="95" y="5" width="40" height="20" class="box-svg" />
                    <rect x="135" y="5" width="45" height="20" class="box-svg" />

                    <!-- Main Body Panel -->
                    <rect x="10" y="25" width="40" height="40" class="box-solid" />
                    <rect x="50" y="25" width="45" height="40" class="box-solid" />
                    <rect x="95" y="25" width="40" height="40" class="box-solid" />
                    <rect x="135" y="25" width="45" height="40" class="box-solid" />
                    <!-- Joint flap -->
                    <polygon points="180,25 186,28 186,62 180,65" class="box-solid" />

                    <!-- Bottom Flaps -->
                    <rect x="10" y="65" width="40" height="20" class="box-svg" />
                    <rect x="50" y="65" width="45" height="20" class="box-svg" />
                    <rect x="95" y="65" width="40" height="20" class="box-svg" />
                    <rect x="135" y="65" width="45" height="20" class="box-svg" />

                    <!-- Dimensions labels -->
                    <text x="72" y="87" font-size="6" font-family="monospace" text-anchor="middle">L ({{ number_format($jobCard->length_mm, 0) }})</text>
                    <text x="115" y="87" font-size="6" font-family="monospace" text-anchor="middle">W ({{ number_format($jobCard->width_mm, 0) }})</text>
                    <text x="187" y="47" font-size="6" font-family="monospace" text-anchor="middle" transform="rotate(90,187,47)">H ({{ number_format($jobCard->height_mm, 0) }})</text>
                </svg>
            </div>
        </div>

        <!-- 3. PRODUCTION TECHNICAL PARAMETERS -->
        <div class="section-title">Production Technical Parameters</div>
        <table class="tech-table">
            <thead>
                <tr>
                    <th>Deckle Size (in)</th>
                    <th>Sheet Length (in)</th>
                    <th>Ups (Outs)</th>
                    <th>Est. Unit Weight (kg)</th>
                    <th>Est. Total Weight (kg)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="text-center fw-bold" style="font-size: 12px;">{{ number_format($jobCard->deckle_size, 2) }}"</td>
                    <td class="text-center fw-bold" style="font-size: 12px;">{{ number_format($jobCard->sheet_length, 2) }}"</td>
                    <td class="text-center" style="font-size: 12px;">{{ $jobCard->ups }}</td>
                    <td class="text-center fw-bold" style="font-size: 12px;">{{ number_format($jobCard->est_unit_weight, 4) }}</td>
                    <td class="text-center fw-bold" style="font-size: 12px;">{{ number_format($jobCard->est_unit_weight * $jobCard->planned_qty, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <!-- 4. RSC ONLINE FLUTES SLOTTING MATRIX -->
        <div class="section-title">RSC Flutes &amp; Slotting Matrix (mm)</div>
        <div class="slotting-matrix">
            <div class="slotting-col">
                <div>FLAP 1 (W/2 + Trim)</div>
                <div class="slotting-val">{{ number_format($jobCard->width_mm / 2 + 5, 1) }}</div>
            </div>
            <div class="slotting-col">
                <div>HEIGHT PROFILE</div>
                <div class="slotting-val">{{ number_format($jobCard->height_mm + 3, 1) }}</div>
            </div>
            <div class="slotting-col">
                <div>FLAP 2 (W/2 + Trim)</div>
                <div class="slotting-val">{{ number_format($jobCard->width_mm / 2 + 5, 1) }}</div>
            </div>
        </div>

        <!-- 5. CARDBOARD PAPER SUBSTRATE STRUCTURE (PLY TABLE) -->
        <div class="section-title">Ply &amp; Cardboard Substrate Composition</div>
        
        @if($jobCard->pieces_count == 1)
            <!-- Single Piece ply configuration -->
            <table class="substrate-table">
                <thead>
                    <tr>
                        <th>Ply No / Layer Type</th>
                        <th>Paper Grade / Quality</th>
                        <th class="text-right">GSM (g/m²)</th>
                        <th>Flute Profile</th>
                        <th>Factor</th>
                    </tr>
                </thead>
                <tbody>
                    @php $sumGsm = 0; @endphp
                    @forelse($jobCard->layers as $layer)
                        @php 
                            $factor = 1.0;
                            if (stripos($layer->flute_profile, 'B') !== false) $factor = 1.35;
                            elseif (stripos($layer->flute_profile, 'C') !== false) $factor = 1.45;
                            elseif (stripos($layer->flute_profile, 'E') !== false) $factor = 1.25;
                            $sumGsm += $layer->gsm * $factor;
                        @endphp
                        <tr>
                            <td class="fw-bold">{{ $layer->layer_type }}</td>
                            <td>{{ $layer->paper_name ?? 'N/A' }}</td>
                            <td class="text-right fw-bold">{{ $layer->gsm }}</td>
                            <td class="text-center">{{ $layer->flute_profile }}</td>
                            <td class="text-center">{{ number_format($factor, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">No ply structures configured for this Job Card.</td>
                        </tr>
                    @endforelse
                    @if($jobCard->layers->isNotEmpty())
                        <tr style="background: #fafafa;">
                            <td colspan="2" class="fw-bold text-right">TOTAL MATERIAL STRENGTH:</td>
                            <td class="text-right fw-bold" style="font-size: 11px;">{{ number_format($jobCard->layers->sum('gsm')) }} g/m²</td>
                            <td colspan="2" class="text-center fw-bold">Effective: {{ number_format($sumGsm, 1) }} g/m²</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        @else
            <!-- Multi-piece Config -->
            <div class="pieces-container">
                <div style="font-weight: bold; margin-bottom: 2px; text-decoration: underline;">Multi-Piece Configuration ({{ $jobCard->pieces_count }} components):</div>
                @foreach($jobCard->pieces as $piece)
                    <div class="piece-item">
                        <div style="font-weight: bold; display: flex; justify-content: space-between; border-bottom: 1px solid #000; margin-bottom: 2px;">
                            <span>{{ $piece->piece_name }}</span>
                            <span>{{ $piece->length_mm }}x{{ $piece->width_mm }}x{{ $piece->height_mm }} mm | Deckle: {{ $piece->deckle_size }}" | Length: {{ $piece->sheet_length }}"</span>
                        </div>
                        <table class="substrate-table" style="margin-bottom: 0;">
                            <thead>
                                <tr>
                                    <th>Layer Type</th>
                                    <th>Paper Grade</th>
                                    <th class="text-right">GSM</th>
                                    <th>Flute</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($piece->layers as $layer)
                                    <tr>
                                        <td>{{ $layer->layer_type }}</td>
                                        <td>{{ $layer->paper_name }}</td>
                                        <td class="text-right fw-bold">{{ $layer->gsm }}</td>
                                        <td class="text-center">{{ $layer->flute_profile }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @if($piece->instructions)
                            <div style="font-size: 8px; font-style: italic; margin-top: 1px;">Instructions: {{ $piece->instructions }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        <!-- 6. PROCESSING & INKS -->
        <div class="section-title">Processing, Inks &amp; Pantone Color Passes</div>
        <table class="ops-table">
            <tr>
                <th style="width: 20%;">PRINTING PROCESS</th>
                <td style="width: 30%;" class="fw-bold">{{ $jobCard->printing_process ?? 'FLEXOGRAPHIC' }}</td>
                <th style="width: 20%;">CLOSURE METHOD</th>
                <td style="width: 30%;" class="fw-bold">{{ $jobCard->pasting_closure ?? 'STITCHING / GLUING' }}</td>
            </tr>
            <tr>
                <th>TARGET MACHINE</th>
                <td class="fw-bold">{{ $jobCard->machine_name ?? 'N/A' }} ({{ $jobCard->target_speed }} pcs/hr)</td>
                <th>COLOR PASSES</th>
                <td>
                    <span class="fw-bold" style="font-size: 11px;">{{ $jobCard->printing_colors_count }} Colors</span>
                </td>
            </tr>
            <tr>
                <th>PANTONE MATCHES</th>
                <td colspan="3">
                    <div class="pantone-container">
                        @if(!empty($jobCard->pantone_colors))
                            @foreach($jobCard->pantone_colors as $color)
                                <span class="pantone-chip">{{ strtoupper($color) }}</span>
                            @endforeach
                        @else
                            <span class="text-muted">No Pantone Colors Specified</span>
                        @endif
                    </div>
                </td>
            </tr>
        </table>

        <!-- 7. SPECIAL ADD-ONS & ADD DETAILS -->
        @php 
            $special = $jobCard->special_details; 
            $honeycomb = isset($special['honeycomb']) && $special['honeycomb'];
            $separators = isset($special['separators']) && $special['separators'];
        @endphp
        @if($honeycomb || $separators)
            <div class="section-title">Special Packaging Add-ons</div>
            <table class="ops-table">
                <tr>
                    <th style="width: 25%;">HONEYCOMB BLOCK</th>
                    <td style="width: 25%;" class="fw-bold">{{ $honeycomb ? 'REQUIRED [YES]' : 'NONE' }}</td>
                    <th style="width: 25%;">INNER SEPARATORS</th>
                    <td style="width: 25%;" class="fw-bold">{{ $separators ? 'REQUIRED [YES]' : 'NONE' }}</td>
                </tr>
            </table>
        @endif

        <!-- 8. FLOOR INSTRUCTIONS -->
        <div class="section-title">Floor Processing &amp; Quality Control Instructions</div>
        <div class="instruction-box">
            @if($jobCard->notes)
                {{ $jobCard->notes }}
            @else
                1. Ensure correct flute heights are maintained on the corrugator.
                2. Check pasting strength and glue coverage across flaps.
                3. Print registration must be inspected every 500 sheets.
                4. Maintain structural pressure tolerances at delivery stackers.
            @endif
        </div>
    </div>

    <!-- 9. SIGNATURES FOOTER -->
    <table class="footer-table">
        <tr>
            <td>
                <div class="signature-line">PREPARED BY: CAD ENGINEER</div>
            </td>
            <td>
                <div class="signature-line">VERIFIED BY: QC INSPECTOR</div>
            </td>
            <td>
                <div class="signature-line">APPROVED BY: PLANT HEAD</div>
            </td>
        </tr>
    </table>
</div>

<script>
    window.onload = function() {
        window.print();
    }
</script>
</body>
</html>
