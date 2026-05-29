<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Incoming Paper Inspection Standard</title>
    <style>
        * { box-sizing: border-box; }
        @page { size: A4 portrait; margin: 8mm 8mm; }
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #000; font-size: 10px; margin: 0; padding-bottom: 0; }
        .header-table, .data-table { width: 100%; border-collapse: collapse; }
        .header-table td, .header-table th, .data-table td, .data-table th { border: 1px solid #000; }
        .header-logo { width: 118px; text-align: center; vertical-align: middle; padding: 5px; }
        .header-logo img { max-width: 100px; max-height: 62px; }
        .header-title { text-align: center; font-size: 18px; font-weight: 700; color: #001f5f; padding: 8px 4px; white-space: nowrap; letter-spacing: 0.2px; }
        .meta-label { width: 92px; text-align: center; font-size: 9px; padding: 5px 3px; white-space: nowrap; }
        .meta-value { width: 82px; text-align: center; font-size: 10px; font-weight: 700; padding: 5px 3px; white-space: nowrap; }
        .spacer { height: 8px; }
        .data-table th { background: #efefef; text-align: center; font-size: 9px; padding: 4px 3px; }
        .data-table td { padding: 3px 4px; font-size: 10px; text-align: center; }
        .left { text-align: left !important; }
        .footer {
            margin-top: 18px;
            padding: 6px 2px 0 2px;
            font-size: 8px;
            color: #4b5563;
            page-break-inside: avoid;
        }
        .footer-wrap {
            display: table;
            width: 100%;
        }
        .footer-left, .footer-right {
            display: table-cell;
            vertical-align: bottom;
        }
        .footer-right { text-align: right; width: 170px; }
        .approved-line { border-top: 1px solid #000; margin-bottom: 2px; }
        .approved-label { font-size: 9.6px; font-weight: 700; color: #000; text-transform: uppercase; }
    </style>
</head>
<body>
    @php
        $hasCobb = $qualities->contains(function ($q) {
            foreach (['min_cobb', 'standard_cobb', 'max_cobb'] as $field) {
                $value = $q->{$field};
                if ($value !== null && $value !== '' && $value !== '-' && (float) $value != 0.0) {
                    return true;
                }
            }
            return false;
        });
        $emptyColspan = $hasCobb ? 14 : 11;
    @endphp
    <table class="header-table">
        <tr>
            <td class="header-logo" rowspan="2">
                <img src="{{ public_path('images/quality-cartons-logo.png') }}" alt="Quality Cartons">
            </td>
            <td class="header-title" colspan="8">INCOMING PAPER INSPECTION STANDARD</td>
        </tr>
        <tr>
            <td class="meta-label">Document I.D. #</td>
            <td class="meta-value">{{ $documentId }}</td>
            <td class="meta-label">Rev. #</td>
            <td class="meta-value">{{ $revisionNo }}</td>
            <td class="meta-label">Rev. Date</td>
            <td class="meta-value">{{ $revisionDate }}</td>
            <td class="meta-label">Page #</td>
            <td class="meta-value">{{ $pageText }}</td>
        </tr>
    </table>

    <div class="spacer"></div>

    <table class="data-table">
        <thead>
            <tr>
                <th rowspan="2">S#</th>
                <th rowspan="2">Quality Name</th>
                <th colspan="3">GSM</th>
                <th colspan="3">Bursting</th>
                <th colspan="3">Moisture</th>
                @if($hasCobb)
                    <th colspan="3">Cobb</th>
                @endif
            </tr>
            <tr>
                <th>Min</th><th>Std</th><th>Max</th>
                <th>Min</th><th>Std</th><th>Max</th>
                <th>Min</th><th>Std</th><th>Max</th>
                @if($hasCobb)
                    <th>Min</th><th>Std</th><th>Max</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse ($qualities as $index => $q)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="left">{{ $q->quality }}{{ $q->gsm_range ? ' (' . $q->gsm_range . ')' : '' }}</td>
                    <td>{{ $q->min_gsm ?? '-' }}</td>
                    <td>{{ $q->standard_gsm ?? '-' }}</td>
                    <td>{{ $q->max_gsm ?? '-' }}</td>
                    <td>{{ $q->min_bursting ?? '-' }}</td>
                    <td>{{ $q->standard_bursting ?? '-' }}</td>
                    <td>{{ $q->max_bursting ?? '-' }}</td>
                    <td>{{ $q->min_moisture ?? '-' }}</td>
                    <td>{{ $q->standard_moisture ?? '-' }}</td>
                    <td>{{ $q->max_moisture ?? '-' }}</td>
                    @if($hasCobb)
                        <td>{{ $q->min_cobb ?? '-' }}</td>
                        <td>{{ $q->standard_cobb ?? '-' }}</td>
                        <td>{{ $q->max_cobb ?? '-' }}</td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $emptyColspan }}">No paper quality records found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <div class="footer-wrap">
            <div class="footer-right">
                <div class="approved-line"></div>
                <div class="approved-label">Approved By</div>
            </div>
        </div>
    </div>
</body>
</html>
