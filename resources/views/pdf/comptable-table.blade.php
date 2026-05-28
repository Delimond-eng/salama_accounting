<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page {
            margin: 1cm 1cm 2cm 1cm; /* Marge suffisante en bas pour le footer */
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 8px;
            color: #1a1a1a;
            line-height: 1.2;
        }

        /* Pied de page fixe pour DomPDF */
        footer {
            position: fixed;
            bottom: -0.8cm;
            left: 0cm;
            right: 0cm;
            height: 1cm;
            text-align: center;
            font-size: 7.5px;
            color: #666;
            border-top: 1.5px solid #800000; /* Ligne rouge foncée */
            padding-top: 8px;
        }

        /* Numérotation automatique */
        .pagenum:before {
            content: counter(page);
        }

        /* En-tête : Société à gauche, Infos à droite */
        .header-container {
            width: 100%;
            border-bottom: 2px solid #800000;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .header-table {
            width: 100%;
            border: none;
        }
        .header-table td {
            border: none !important;
            vertical-align: top;
            padding: 0;
        }
        .company-info { width: 60%; text-align: left; }
        .company-name { font-size: 18px; font-weight: bold; color: #800000; text-transform: uppercase; margin-bottom: 2px; }
        .company-details { font-size: 9px; color: #444; line-height: 1.3; }
        .meta-info { width: 40%; text-align: right; }
        .doc-title { font-size: 16px; font-weight: bold; color: #2c3e50; text-transform: uppercase; margin-bottom: 5px; }
        .meta-item { font-size: 9px; color: #555; margin-bottom: 1px; }

        /* Style de la table */
        table.data-table { width: 100%; border-collapse: collapse; margin-top: 5px; }
        table.data-table th {
            background-color: #800000;
            color: #ffffff;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 7px;
            border: 1px solid #600000;
            padding: 6px 4px;
            text-align: center;
        }
        table.data-table td { border: 1px solid #d1d1d1; padding: 4px 4px; vertical-align: middle; }
        table.data-table tr:nth-child(even) { background-color: #fafafa; }

        .row-section { background-color: #efefef !important; font-weight: bold; text-transform: uppercase; }
        .row-section td { border-top: 1.5px solid #800000 !important; font-size: 8px; color: #800000; }

        .row-total { background-color: #fff9f9 !important; font-weight: bold; color: #000; }
        .row-total td { border-top: 2px solid #800000 !important; border-bottom: 3px double #800000 !important; font-size: 8.5px; }

        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .signature-table { width: 100%; margin-top: 30px; border: none; }
        .signature-table td { border: none !important; width: 50%; text-align: center; font-weight: bold; text-decoration: underline; font-size: 9px; color: #333; }
    </style>
</head>
<body>

    <footer>
        {{ $societe->raison_sociale ?? config('brand.name', 'Salama Accounting') }} —
        Page <span class="pagenum"></span> —
        Document confidentiel généré le {{ $generated_at }}
    </footer>

    <div class="header-container">
        <table class="header-table">
            <tr>
                <td class="company-info">
                    @if(isset($societe))
                        <div class="company-name">{{ $societe->raison_sociale }}</div>
                        <div class="company-details">
                            <strong>{{ $societe->sigle ?? $societe->forme_juridique }}</strong> | {{ $societe->regime_fiscal }}<br>
                            {{ $societe->adresse }} {{ $societe->ville }}<br>
                            RCCM: {{ $societe->rccm }} | ID Nat: {{ $societe->num_contribuable }}
                        </div>
                    @endif
                </td>
                <td class="meta-info">
                    <div class="doc-title">{{ $title }}</div>
                    @foreach ($meta as $k => $v)
                        <div class="meta-item"><strong>{{ $k }}:</strong> {{ $v }}</div>
                    @endforeach
                </td>
            </tr>
        </table>
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

</body>
</html>
