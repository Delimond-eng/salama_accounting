<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page {
            margin: 0.8cm;
            footer: html_footer;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 8px;
            color: #1a1a1a;
            line-height: 1.2;
        }
        /* Header style Institutionnel */
        .header-table {
            width: 100%;
            border-bottom: 3px solid #800000; /* Bordeaux Comptable */
            padding-bottom: 8px;
            margin-bottom: 15px;
        }
        .company-name {
            font-size: 15px;
            font-weight: bold;
            color: #800000;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .company-details {
            font-size: 7.5px;
            color: #444;
            line-height: 1.3;
        }
        .doc-info {
            text-align: right;
            vertical-align: top;
        }
        .doc-title {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
            text-transform: uppercase;
            margin-bottom: 2px;
        }
        .meta-container {
            font-size: 9px;
            color: #333;
            background: #fcfcfc;
            border: 1px solid #ddd;
            padding: 4px 8px;
            display: inline-block;
            text-align: left;
        }

        /* Data Table Style Pro */
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

        /* Lignes de Section (###) */
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

        /* Lignes de Total (===) */
        .row-total {
            background-color: #fff9f9 !important;
            font-weight: bold;
            color: #000;
        }
        .row-total td {
            border-top: 2px solid #800000 !important;
            border-bottom: 3px double #800000 !important; /* Double ligne de pied */
            font-size: 9px;
        }

        .text-right {
            text-align: right;
            font-family: "Courier New", Courier, monospace;
            font-weight: bold;
            white-space: nowrap;
        }
        .text-center { text-align: center; }

        /* Signature block */
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
        .watermark {
            margin-top: 15px;
            text-align: right;
            font-style: italic;
            font-size: 7px;
            color: #bbb;
        }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td style="width: 55%;">
                @if(isset($societe))
                    <div class="company-name">{{ $societe->raison_sociale }}</div>
                    <div class="company-details">
                        <strong>{{ $societe->sigle ?? $societe->forme_juridique }}</strong> | {{ $societe->regime_fiscal }}<br>
                        {{ $societe->adresse }} {{ $societe->ville }}<br>
                        @if($societe->rccm) RCCM: {{ $societe->rccm }} @endif
                        @if($societe->num_contribuable) | NUI: {{ $societe->num_contribuable }} @endif<br>
                        @if($societe->telephone) Tél: {{ $societe->telephone }} @endif
                        @if($societe->email) | Email: {{ $societe->email }} @endif
                    </div>
                @else
                    <div class="company-name">SALAMA ACCOUNTING</div>
                @endif
            </td>
            <td class="doc-info">
                <div class="doc-title">{{ $title }}</div>
                <div class="meta-container">
                    @foreach ($meta as $k => $v)
                        <div><strong>{{ $k }}:</strong> {{ $v }}</div>
                    @endforeach
                </div>
            </td>
        </tr>
    </table>

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

    <div class="watermark">Document généré électroniquement par {{ config('brand.name') }} le {{ $generated_at }}</div>

    <htmlpagefooter name="html_footer">
        <div class="footer">
            {{ config('brand.name') }} — Page {PAGENO} / {TOTALPAGES} — Document à caractère confidentiel
        </div>
    </htmlpagefooter>

</body>
</html>
