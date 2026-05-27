<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page {
            margin: 1.5cm;
            footer: html_footer;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 9px;
            color: #1a1a1a;
            line-height: 1.4;
        }
        .cover-page {
            text-align: center;
            padding-top: 50px;
            margin-bottom: 50px;
            border-bottom: 3px solid #800000;
            padding-bottom: 50px;
        }
        .company-name {
            font-size: 38px;
            font-weight: bold;
            color: #800000;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        .company-details {
            font-size: 14px;
            color: #555;
            margin-bottom: 40px;
            line-height: 1.6;
        }
        .doc-title {
            font-size: 32px;
            font-weight: bold;
            color: #2c3e50;
            text-transform: uppercase;
            margin-top: 30px;
        }
        .doc-subtitle {
            font-size: 20px;
            font-weight: bold;
            color: #444;
            margin-top: 10px;
            background: #f8f9fa;
            padding: 10px;
            display: inline-block;
        }
        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #800000;
            background: #f8f9fa;
            padding: 10px;
            margin-top: 40px;
            margin-bottom: 20px;
            border-left: 5px solid #800000;
            page-break-before: always;
        }
        .section-title:first-of-type {
            page-break-before: avoid;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
        }
        table.data-table th {
            background-color: #800000;
            color: #ffffff;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8px;
            padding: 10px 5px;
            border: 1px solid #600000;
        }
        table.data-table td {
            border: 1px solid #ddd;
            padding: 7px 5px;
        }
        .row-section {
            background-color: #f1f1f1 !important;
            font-weight: bold;
        }
        .row-total {
            background-color: #fff5f5 !important;
            font-weight: bold;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .footer {
            text-align: center;
            font-size: 8px;
            color: #999;
            border-top: 1px solid #eee;
            padding-top: 5px;
        }
        .meta-container {
            margin-top: 30px;
            text-align: center;
        }
        .meta-item {
            font-size: 14px;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>

    <div class="cover-page">
        @if(isset($societe))
            <div class="company-name">{{ $societe->raison_sociale }}</div>
            <div class="company-details">
                {{ $societe->sigle ? $societe->sigle.' | ' : '' }}
                {{ $societe->adresse }} {{ $societe->ville }}<br>
                RCCM: {{ $societe->rccm }} | ID Nat: {{ $societe->num_contribuable }}<br>
                Tél: {{ $societe->telephone }} | Email: {{ $societe->email }}
            </div>
        @endif

        <div class="doc-title">ÉTATS FINANCIERS ANNUELS</div>
        <div class="doc-subtitle">EXERCICE : {{ $meta['Exercice'] ?? '' }}</div>

        <div class="meta-container">
            @foreach ($meta as $k => $v)
                @if($k !== 'Exercice')
                    <div class="meta-item"><strong>{{ $k }}:</strong> {{ $v }}</div>
                @endif
            @endforeach
        </div>
    </div>

    @foreach ($sections as $section)
        <div class="section-title">{{ $section['title'] }}</div>

        <table class="data-table">
            <thead>
                <tr>
                    @foreach ($section['headers'] as $h)
                        <th>{{ $h }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($section['rows'] as $row)
                    @php
                        $rowClass = '';
                        foreach($row as $cell) {
                            $c = (string)$cell;
                            if (str_starts_with($c, '### ')) { $rowClass = 'row-section'; break; }
                            if (str_starts_with($c, '=== ')) { $rowClass = 'row-total'; break; }
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
    @endforeach

    <htmlpagefooter name="html_footer">
        <div class="footer">
            {{ $societe->raison_sociale }} — États Financiers — Page {PAGENO} / {TOTALPAGES} — Document généré le {{ $generated_at }}
        </div>
    </htmlpagefooter>

</body>
</html>
