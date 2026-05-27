<!DOCTYPE html>
<html lang="fr">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $mouvement->numero ?? 'Bon stock' }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1e293b; margin: 0; padding: 22px; }
        .header { border-bottom: 3px solid #0d9488; padding-bottom: 14px; margin-bottom: 18px; }
        .header table { width: 100%; border: none; border-collapse: collapse; }
        .header td { border: none; vertical-align: top; padding: 0; }
        .brand { font-size: 16px; font-weight: bold; color: #0f766e; }
        .doc-title { font-size: 18px; font-weight: bold; text-align: right; color: #134e4a; }
        .doc-num { text-align: right; font-size: 11px; color: #64748b; margin-top: 4px; }
        .box { background: #f0fdfa; border: 1px solid #99f6e4; border-radius: 6px; padding: 12px; margin-bottom: 14px; }
        .box h4 { margin: 0 0 8px; font-size: 9px; text-transform: uppercase; color: #0f766e; }
        .box p { margin: 3px 0; }
        table.detail { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.detail th { background: #134e4a; color: #fff; padding: 8px; text-align: left; font-size: 9px; }
        table.detail td { border-bottom: 1px solid #e2e8f0; padding: 8px; }
        .footer { margin-top: 28px; padding-top: 10px; border-top: 2px solid #0d9488; font-size: 8px; color: #475569; }
        .footer table { width: 100%; border: none; }
        .footer td { border: none; vertical-align: top; padding: 2px 4px; }
        .sign { margin-top: 40px; }
        .sign table { width: 100%; border: none; }
        .sign td { border: none; width: 33%; text-align: center; padding-top: 30px; border-top: 1px solid #94a3b8; font-size: 9px; }
    </style>
</head>
<body>
    <div class="header">
        <table>
            <tr>
                <td style="width:55%">
                    <div class="brand">{{ $societe?->raison_sociale ?? 'Société' }}</div>
                    @if($societe?->adresse)<div style="font-size:9px;margin-top:4px;">{{ $societe->adresse }}@if($societe->ville), {{ $societe->ville }}@endif</div>@endif
                    @if($societe?->telephone)<div style="font-size:9px;">Tél : {{ $societe->telephone }}</div>@endif
                </td>
                <td>
                    <div class="doc-title">{{ $titre }}</div>
                    <div class="doc-num">N° {{ $mouvement->numero ?? $mouvement->id }}</div>
                    <div class="doc-num">Date : {{ $mouvement->date_mouvement?->format('d/m/Y') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="box">
        <h4>Article concerné</h4>
        <p><strong>{{ $mouvement->produit?->libelle }}</strong>@if($mouvement->produit?->code) — Code : {{ $mouvement->produit->code }}@endif</p>
        <p>Libellé du mouvement : {{ $mouvement->libelle }}</p>
        @if($mouvement->user)<p>Enregistré par : {{ $mouvement->user->name }}</p>@endif
    </div>

    <table class="detail">
        <thead>
            <tr>
                <th>Type</th>
                <th style="text-align:right;">Quantité</th>
                <th style="text-align:right;">Stock avant</th>
                <th style="text-align:right;">Stock après</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="text-transform:uppercase;">{{ str_replace('_', ' ', $mouvement->type_mouvement) }}</td>
                <td style="text-align:right;">{{ number_format($mouvement->quantite, 2, ',', ' ') }}</td>
                <td style="text-align:right;">{{ number_format($mouvement->stock_avant, 2, ',', ' ') }}</td>
                <td style="text-align:right;font-weight:bold;">{{ number_format($mouvement->stock_apres, 2, ',', ' ') }}</td>
            </tr>
        </tbody>
    </table>

    <div class="sign">
        <table>
            <tr>
                <td>Préparé par</td>
                <td>Contrôlé par</td>
                <td>Approuvé par</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <table>
            <tr>
                <td style="width:50%">
                    @if($societe?->rccm)<div>RCCM : {{ $societe->rccm }}</div>@endif
                    @if($societe?->num_contribuable)<div>N° Impôt : {{ $societe->num_contribuable }}</div>@endif
                    @if($societe?->identification_nationale)<div>ID. nationale : {{ $societe->identification_nationale }}</div>@endif
                </td>
                <td>
                    @foreach(($societe?->banques ?? []) as $b)
                    <div>{{ $b->banque }} — {{ $b->numero_compte }} ({{ $b->devise }})</div>
                    @endforeach
                </td>
            </tr>
        </table>
        <div style="text-align:center;margin-top:8px;color:#94a3b8;">
            Document généré le {{ now()->format('d/m/Y H:i') }} — {{ $societe?->raison_sociale ?? '' }}
        </div>
    </div>
</body>
</html>
