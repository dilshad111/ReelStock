@php
    $customer = $jobIssue->customer;
    $product = $jobIssue->product;
    $jobCard = $jobIssue->jobCard;
    $routeLabels = [
        'print_only' => 'Print Only',
        'die_cut_only' => 'Die-Cut Only',
        'print_die_cut' => 'Print → Die-Cut',
        'die_cut_print' => 'Die-Cut → Print',
        'corrugation_only' => 'Corrugation → Bundling',
    ];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Production Job Issue - {{ $jobIssue->job_no }}</title>
    <style>
        @page { size: A4 portrait; margin: 8mm; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            color: #000;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .page {
            border: 2px solid #000;
            min-height: 280mm;
            padding: 8px;
        }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 5px 6px; vertical-align: middle; }
        th { background: #e9ecef; font-weight: 900; text-transform: uppercase; }
        .header { margin-bottom: 8px; }
        .logo {
            width: 105px;
            text-align: center;
            font-size: 28px;
            font-weight: 900;
            color: #0b7a3b;
            line-height: 1;
        }
        .logo small { display: block; color: #000; font-size: 9px; margin-top: 2px; }
        h1 {
            margin: 0;
            text-align: center;
            color: #002060;
            font-size: 21px;
            letter-spacing: 0.5px;
        }
        .doc-meta td { text-align: center; font-weight: 700; }
        .section-title {
            background: #111827;
            color: #fff;
            border: 1px solid #000;
            font-weight: 900;
            padding: 5px 8px;
            margin-top: 8px;
            text-transform: uppercase;
        }
        .label { font-weight: 900; text-transform: uppercase; width: 16%; }
        .value { font-weight: 700; }
        .stage-boxes {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 6px;
            margin-top: 8px;
        }
        .stage {
            border: 1px solid #000;
            min-height: 46px;
            padding: 6px;
            text-align: center;
            font-weight: 900;
        }
        .stage span { display: block; margin-top: 8px; font-size: 9px; color: #333; }
        .signatures {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-top: 34px;
        }
        .sign {
            border-top: 1px solid #000;
            padding-top: 4px;
            font-weight: 900;
            text-align: center;
        }
        .no-print { margin-top: 10px; text-align: center; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="page">
        <table class="header">
            <tr>
                <td rowspan="2" class="logo">QC<small>QUALITY CARTONS</small></td>
                <td colspan="6"><h1>PRODUCTION JOB ISSUE DOCUMENT</h1></td>
            </tr>
            <tr class="doc-meta">
                <td>Document I.D. #</td>
                <td>QC/PROD/JI</td>
                <td>Rev. #</td>
                <td>01</td>
                <td>Date</td>
                <td>{{ now()->format('d/m/Y') }}</td>
            </tr>
        </table>

        <div class="section-title">Job Issue Information</div>
        <table>
            <tr>
                <td class="label">Job #</td>
                <td class="value">{{ $jobIssue->job_no }}</td>
                <td class="label">Job Card</td>
                <td class="value">{{ $jobCard->job_card_no ?? '-' }}</td>
                <td class="label">Issue Date</td>
                <td class="value">{{ optional($jobIssue->issued_date)->format('d/m/Y') }}</td>
            </tr>
            <tr>
                <td class="label">Customer</td>
                <td class="value" colspan="3">{{ $customer->name ?? '-' }}</td>
                <td class="label">P.O. #</td>
                <td class="value">{{ $jobIssue->purchase_order_no ?: '-' }}</td>
            </tr>
            <tr>
                <td class="label">Item Code</td>
                <td class="value">{{ $product->item_code ?? '-' }}</td>
                <td class="label">Item Name</td>
                <td class="value" colspan="3">{{ $product->item_name ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Required Qty</td>
                <td class="value">{{ number_format($jobIssue->required_carton_qty, 0) }}</td>
                <td class="label">Route</td>
                <td class="value">{{ $routeLabels[$jobIssue->production_route] ?? $jobIssue->production_route }}</td>
                <td class="label">Status</td>
                <td class="value">{{ $jobIssue->status }}</td>
            </tr>
        </table>

        <div class="section-title">Carton Specifications</div>
        <table>
            <tr>
                <td class="label">Carton Type</td>
                <td>{{ $jobCard->carton_type ?? '-' }}</td>
                <td class="label">Dimensions</td>
                <td>{{ number_format((float) $jobCard->length_mm, 2) }} x {{ number_format((float) $jobCard->width_mm, 2) }} x {{ number_format((float) $jobCard->height_mm, 2) }} mm</td>
            </tr>
            <tr>
                <td class="label">Deckle</td>
                <td>{{ number_format((float) $jobCard->deckle_size, 2) }}"</td>
                <td class="label">Sheet Length</td>
                <td>{{ number_format((float) $jobCard->sheet_length, 2) }}"</td>
            </tr>
            <tr>
                <td class="label">UPS</td>
                <td>{{ $jobCard->ups ?? '-' }}</td>
                <td class="label">Printing Colors</td>
                <td>{{ $jobCard->printing_colors_count ?? 0 }}</td>
            </tr>
        </table>

        <div class="section-title">Workflow Routing</div>
        <div class="stage-boxes">
            <div class="stage">Corrugation Plant<span>Sheets / reels / wastage</span></div>
            <div class="stage">Printing<span>As per selected route</span></div>
            <div class="stage">Die-Cutting<span>As per selected route</span></div>
            <div class="stage">Bundling & FG<span>Final verification</span></div>
        </div>

        <div class="section-title">Production Notes</div>
        <table>
            <tr>
                <th>Department</th>
                <th>Instructions / Remarks</th>
                <th>Checked</th>
            </tr>
            <tr><td>Corrugation</td><td style="height:38px;"></td><td></td></tr>
            <tr><td>Printing</td><td style="height:38px;"></td><td></td></tr>
            <tr><td>Die-Cutting / Finishing</td><td style="height:38px;"></td><td></td></tr>
            <tr><td>Bundling</td><td style="height:38px;"></td><td></td></tr>
        </table>

        <div class="signatures">
            <div class="sign">Issued By</div>
            <div class="sign">Production Supervisor</div>
            <div class="sign">Approved By</div>
        </div>
    </div>
    <div class="no-print">
        <button onclick="window.print()">Print</button>
    </div>
    <script>
        window.addEventListener('load', () => window.print());
    </script>
</body>
</html>
