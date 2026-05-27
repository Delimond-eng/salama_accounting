<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page {
            margin: 1cm;
            footer: html_footer;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 8px;
            color: #1a1a1a;
            line-height: 1.2;
        }
        .header-centered {
            text-align: center;
            border-bottom: 2px solid #800000;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #800000;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .company-details {
            font-size: 10px;
            color: #555;
            margin-bottom: 10px;
        }
        .doc-title {
            font-size: 20px;
            font-weight: bold;
            color: #2c3e50;
            text-transform: uppercase;
        }
        .meta-container {
            margin: 15px auto;
            text-align: center;
            background: #fafafa;
            border: 1px solid #ddd;
            padding: 8px;
            width: 90%;
        }
        .meta-item {
            display: inline-block;
            margin: 0 15px;
            font-size: 11px;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }
        table.data-table th {
            background-color: #800000;
            color: #ffffff;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 7px;
            border: 1px solid #600000;
            padding: 6px 3px;
            text-align: center;
        }
        table.data-table td {
            border: 1px solid #d1d1d1;
            padding: 4px 3px;
            vertical-align: middle;
        }
        table.data-table tr:nth-child(even) {
            background-color: #fafafa;
        }

        .row-section {
            background-color: #efefef !important;
            font-weight: bold;
            text-transform: uppercase;
        }
        .row-section td {
            border-top: 1.5px solid #800000 !important;
            font-size: 8.5px;
            color: #800000;
        }

        .row-total {
            background-color: #fff9f9 !important;
            font-weight: bold;
            color: #000;
        }
        .row-total td {
            border-top: 2px solid #800000 !important;
            border-bottom: 3px double #800000 !important;
            font-size: 9px;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .signature-table {
            width: 100%;
            margin-top: 40px;
            border: none;
        }
        .signature-table td {
            border: none !important;
            width: 50%;
            text-align: center;
            font-weight: bold;
            text-decoration: underline;
            font-size: 10px;
            color: #333;
        }

        .footer {
            text-align: center;
            font-size: 7px;
            color: #999;
            border-top: 1px solid #eee;
            padding-top: 4px;
        }
    </style>
</head>
<body>

    <div class="header-centered">
        @if(isset($societe))
            <div class="company-name">{{ $societe->raison_sociale }}</div>
            <div class="company-details">
                <strong>{{ $societe->sigle ?? $societe->forme_juridique }}</strong> | {{ $societe->regime_fiscal }}<br>
                {{ $societe->adresse }} {{ $societe->ville }}<br>
                RCCM: {{ $societe->rccm }} | ID Nat: {{ $societe->num_contribuable }}
            </div>
        @endif
        <div class="doc-title">{{ $title }}</div>
    </div>

    <div class="meta-container">
        @foreach ($meta as $k => $v)
            <div class="meta-item"><strong>{{ $k }}:</strong> {{ $v }}</div>
        @endforeach
    </div>

    <table class="data-table">
        <thead>
            <tr>
                @foreach ($headers as $h)
                    <th>{{ $h }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                @php
                    $rowClass = '';
                    foreach($row as $cell) {
                        $c = (string)$cell;
                        if (str_starts_with($c, '### ')) { $rowClass = 'row-section'; break; }
                        if (str_starts_with($c, '=== ') || str_contains(strtoupper($c), 'TOTAL') || str_contains(strtoupper($c), 'SOLDE DE CLÔTURE')) {
                            $rowClass = 'row-total'; break;
                        }
                    }
                @endphp
                <tr class="{{ $rowClass }}">
                    @foreach ($row as $cell)
                        @php
                            $cleanCell = (string)$cell;
                            if (str_starts_with($cleanCell, '### ')) $cleanCell = substr($cleanCell, 4);
                            if (str_starts_with($cleanCell, '=== ')) $cleanCell = substr($cleanCell, 4);
                            $isNumeric = is_numeric(str_replace([' ', ','], ['', '.'], $cleanCell)) && strlen($cleanCell) > 0;
                        @endphp
                        <td class="{{ $isNumeric ? 'text-right' : '' }}">
                            {{ $cleanCell }}
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="signature-table">
        <tr>
            <td>Le Comptable</td>
            <td>La Direction</td>
        </tr>
    </table>

    <htmlpagefooter name="html_footer">
        <div class="footer">
            SALAMA ACCOUNTING — Page {PAGENO} / {TOTALPAGES} — Document confidentiel généré le {{ $generated_at }}
        </div>
    </htmlpagefooter>

</body>
</html>
