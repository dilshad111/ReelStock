@php
    $snapshot = $version->snapshot_data ?? [];
    $card = $snapshot['job_card'] ?? [];
    $layers = $snapshot['layers'] ?? [];
    $pieces = $snapshot['pieces'] ?? [];
    $changes = $version->changeLogs ?? collect();
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Job Card Version - {{ $jobCard->job_card_no }} V{{ $version->version_no }}</title>
    <style>
        @page { size: A4 portrait; margin: 8mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #000; font-family: Arial, Helvetica, sans-serif; font-size: 10px; }
        .sheet { border: 2px solid #000; min-height: 280mm; padding: 8px; }
        .header { border: 1px solid #000; display: grid; grid-template-columns: 90px 1fr 140px; align-items: stretch; }
        .logo, .meta { display: grid; place-items: center; border-right: 1px solid #000; font-weight: 900; }
        .meta { border-left: 1px solid #000; border-right: 0; padding: 6px; text-align: center; }
        .title { padding: 10px; text-align: center; }
        .title h1 { margin: 0; color: #002060; font-size: 18px; text-transform: uppercase; }
        .title p { margin: 4px 0 0; font-weight: 800; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #000; padding: 5px; vertical-align: top; }
        th { background: #e6e6e6; font-weight: 900; text-transform: uppercase; }
        .section-title { background: #111; color: #fff; font-weight: 900; padding: 5px 8px; margin-top: 8px; text-transform: uppercase; }
        .badge { border: 1px solid #000; display: inline-block; font-weight: 900; padding: 2px 6px; }
        .muted { color: #555; }
        .right { text-align: right; }
        .center { text-align: center; }
    </style>
</head>
<body>
<div class="sheet">
    <div class="header">
        <div class="logo">QC</div>
        <div class="title">
            <h1>Job Card Version History</h1>
            <p>{{ $jobCard->job_card_no }} | V{{ $version->version_no }} | {{ $version->change_request_no ?: 'Initial Version' }}</p>
        </div>
        <div class="meta">
            <div>Date<br><strong>{{ optional($version->created_at)->format('d/m/Y') }}</strong></div>
        </div>
    </div>

    <div class="section-title">Basic Specification</div>
    <table>
        <tr>
            <th>Customer</th><td>{{ $card['customer_name'] ?? '-' }}</td>
            <th>Item Code</th><td>{{ $card['item_code'] ?? '-' }}</td>
        </tr>
        <tr>
            <th>Item Name</th><td>{{ $card['item_name'] ?? '-' }}</td>
            <th>Status</th><td><span class="badge">{{ $version->version_status }}</span></td>
        </tr>
        <tr>
            <th>Size</th><td>{{ $card['length_mm'] ?? '-' }} x {{ $card['width_mm'] ?? '-' }} x {{ $card['height_mm'] ?? '-' }} {{ $card['uom'] ?? 'mm' }}</td>
            <th>Carton Type</th><td>{{ $card['carton_type'] ?? '-' }}</td>
        </tr>
        <tr>
            <th>Deckle</th><td>{{ $card['deckle_size'] ?? '-' }}"</td>
            <th>Sheet Length</th><td>{{ $card['sheet_length'] ?? '-' }}"</td>
        </tr>
        <tr>
            <th>Printing</th><td>{{ $card['printing_colors_count'] ?? 0 }} Color(s)</td>
            <th>Joinery</th><td>{{ $card['pasting_closure'] ?? '-' }}</td>
        </tr>
    </table>

    <div class="section-title">Paper Construction</div>
    @if(count($pieces))
        @foreach($pieces as $piece)
            <table>
                <tr><th colspan="4">{{ $piece['piece_name'] ?? 'Component' }} - {{ $piece['length_mm'] ?? '-' }} x {{ $piece['width_mm'] ?? '-' }} x {{ $piece['height_mm'] ?? '-' }} mm</th></tr>
                <tr><th>Layer</th><th>Paper</th><th>GSM</th><th>Flute</th></tr>
                @foreach(($piece['layers'] ?? []) as $layer)
                    <tr>
                        <td>{{ $layer['layer_type'] ?? '-' }}</td>
                        <td>{{ $layer['paper_name'] ?? '-' }}</td>
                        <td class="center">{{ $layer['gsm'] ?? '-' }}</td>
                        <td class="center">{{ $layer['flute_profile'] ?? '-' }}</td>
                    </tr>
                @endforeach
            </table>
        @endforeach
    @else
        <table>
            <tr><th>Layer</th><th>Paper</th><th>GSM</th><th>Flute</th></tr>
            @forelse($layers as $layer)
                <tr>
                    <td>{{ $layer['layer_type'] ?? '-' }}</td>
                    <td>{{ $layer['paper_name'] ?? '-' }}</td>
                    <td class="center">{{ $layer['gsm'] ?? '-' }}</td>
                    <td class="center">{{ $layer['flute_profile'] ?? '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="center muted">No layer data available.</td></tr>
            @endforelse
        </table>
    @endif

    <div class="section-title">Change Log</div>
    <table>
        <tr><th>Field</th><th>Old Value</th><th>New Value</th><th>Modified By</th></tr>
        @forelse($changes as $change)
            <tr>
                <td>{{ $change->field_name }}</td>
                <td>{{ $change->old_value }}</td>
                <td>{{ $change->new_value }}</td>
                <td>{{ $change->modifier->name ?? '-' }}</td>
            </tr>
        @empty
            <tr><td colspan="4" class="center">Initial version snapshot.</td></tr>
        @endforelse
    </table>
</div>
<script>window.onload = function () { window.print(); };</script>
</body>
</html>
