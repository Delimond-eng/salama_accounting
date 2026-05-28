<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page {
            margin: 1.2cm 1.2cm 2cm 1.2cm;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 9px;
            color: #1a1a1a;
            line-height: 1.4;
        }

        /* Footer DomPDF */
        footer {
            position: fixed;
            bottom: -1cm;
            left: 0cm;
            right: 0cm;
            height: 1cm;
            text-align: center;
            font-size: 8px;
            color: #999;
            border-top: 1px solid #eee;
            padding-top: 5px;
        }
        .pagenum:before {
            content: counter(page);
        }

        /* Header Layout */
        .header-container {
            width: 100%;
            border-bottom: 3px solid #800000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header-table {
            width: 100%;
            border: none;
        }
        .header-table td {
            border: none !important;
            vertical-align: bottom;
            padding: 0;
        }

        .company-info {
            width: 50%;
            text-align: left;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #800000;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .company-details {
            font-size: 11px;
            color: #444;
            line-height: 1.5;
        }

        .meta-info {
            width: 50%;
            text-align: right;
        }
        .doc-title {
            font-size: 22px;
            font-weight: bold;
            color: #2c3e50;
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        .meta-item {
            font-size: 12px;
            color: #333;
            margin-bottom: 2px;
        }

        .section-title {
            font-size: 15px;
            font-weight: bold;
            color: #800000;
            background: #f8f9fa;
            padding: 8px 12px;
            margin-top: 30px;
            margin-bottom: 15px;
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
            text-align: center;
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
            border-top: 2px solid #800000 !important;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
    </style>
</head>
<body>

    <footer>
        {{ $societe->raison_sociale ?? 'Salama Accounting' }} — États Financiers — Page <span class="pagenum"></span> — Document généré le {{ $generated_at }}
    </footer>

    <div class="header-container">
        <table class="header-table">
            <tr>
                <td class="company-info">
                    @if(isset($societe))
                        <div class="company-name">{{ $societe->raison_sociale }}</div>
                        <div class="company-details">
                            @if($societe->sigle) <strong>{{ $societe->sigle }}</strong><br> @endif
                            {{ $societe->adresse }} {{ $societe->ville }}<br>
                            RCCM: {{ $societe->rccm }} | ID Nat: {{ $societe->num_contribuable }}<br>
                            Tél: {{ $societe->telephone }}
                        </div>
                    @endif
                </td>
                <td class="meta-info">
                    <div class="doc-title">{{ $title }}</div>
                    <div class="meta-item"><strong>EXERCICE :</strong> {{ $meta['Exercice'] ?? '' }}</div>
                    @foreach ($meta as $k => $v)
                        @if($k !== 'Exercice')
                            <div class="meta-item"><strong>{{ $k }}:</strong> {{ $v }}</div>
                        @endif
                    @endforeach
                </td>
            </tr>
        </table>
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

</body>
</html>
