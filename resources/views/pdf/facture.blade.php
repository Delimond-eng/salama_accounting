<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $facture->numero }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1a1a2e; margin: 0; padding: 24px; }
        .header { border-bottom: 3px solid #2563eb; padding-bottom: 16px; margin-bottom: 20px; }
        .header table { width: 100%; border: none; }
        .header td { border: none; vertical-align: top; padding: 0; }
        .logo { font-size: 18px; font-weight: bold; color: #2563eb; }
        .doc-title { font-size: 22px; font-weight: bold; text-align: right; color: #1e3a5f; letter-spacing: 1px; }
        .doc-num { text-align: right; font-size: 12px; color: #64748b; margin-top: 4px; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 4px; font-size: 9px; font-weight: bold; text-transform: uppercase; }
        .badge-brouillon { background: #fef3c7; color: #92400e; }
        .badge-validee { background: #dbeafe; color: #1e40af; }
        .badge-payee { background: #d1fae5; color: #065f46; }
        .parties { margin-bottom: 20px; }
        .parties table { width: 100%; border: none; }
        .parties td { border: none; width: 50%; vertical-align: top; padding: 0 8px 0 0; }
        .box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px; }
        .box h4 { margin: 0 0 8px; font-size: 9px; text-transform: uppercase; color: #64748b; letter-spacing: .5px; }
        .box p { margin: 2px 0; font-size: 10px; }
        table.lignes { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.lignes th { background: #1e3a5f; color: #fff; padding: 8px 6px; font-size: 9px; text-align: left; }
        table.lignes td { border-bottom: 1px solid #e2e8f0; padding: 7px 6px; }
        table.lignes td.num { text-align: right; }
        .totaux { margin-top: 16px; width: 280px; margin-left: auto; }
        .totaux table { width: 100%; border: none; }
        .totaux td { border: none; padding: 5px 0; }
        .totaux .label { color: #64748b; }
        .totaux .val { text-align: right; font-weight: bold; }
        .totaux .ttc td { border-top: 2px solid #2563eb; padding-top: 8px; font-size: 13px; color: #2563eb; }
        .footer { margin-top: 32px; padding-top: 12px; border-top: 1px solid #e2e8f0; font-size: 8px; color: #94a3b8; text-align: center; }
        .notes { margin-top: 16px; font-size: 9px; color: #475569; }
    </style>
</head>
<body>
    <div class="header">
        <table>
            <tr>
                <td>
                    <div class="logo">{{ $societe->raison_sociale ?? 'Société' }}</div>
                    @if($societe->adresse)<div style="font-size:9px;color:#64748b;margin-top:4px;">{{ $societe->adresse }}</div>@endif
                    @if($societe->nif)<div style="font-size:9px;">NIF : {{ $societe->nif }}</div>@endif
                </td>
                <td>
                    <div class="doc-title">{{ $titre }}</div>
                    <div class="doc-num">{{ $facture->numero }}</div>
                    <div style="text-align:right;margin-top:8px;">
                        <span class="badge badge-{{ $facture->statut }}">{{ strtoupper($facture->statut) }}</span>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="parties">
        <table>
            <tr>
                <td>
                    <div class="box">
                        <h4>{{ $facture->estClient() ? 'Client' : 'Fournisseur' }}</h4>
                        <p><strong>{{ $facture->tiers->nom ?? '' }}</strong></p>
                        @if($facture->tiers->code)<p>Code : {{ $facture->tiers->code }}</div>@endif
                        @if($facture->tiers->adresse)<p>{{ $facture->tiers->adresse }}</div>@endif
                    </div>
                </td>
                <td>
                    <div class="box">
                        <h4>Informations document</h4>
                        <p>Date : <strong>{{ $facture->date_facture->format('d/m/Y') }}</strong></p>
                        @if($facture->date_echeance)<p>Echéance : <strong>{{ $facture->date_echeance->format('d/m/Y') }}</strong></div>@endif
                        <p>Devise : <strong>{{ $facture->devise }}</strong></p>
                        @if($facture->factureOrigine)<p>Réf. origine : {{ $facture->factureOrigine->numero }}</div>@endif
                    </div>
                </td>
            </tr>
        </table>
    </div>

    @if($facture->objet)<p style="margin-bottom:12px;"><strong>Objet :</strong> {{ $facture->objet }}</div>@endif

    <table class="lignes">
        <thead>
        <tr>
            <th style="width:40px;">#</th>
            <th>Désignation</th>
            <th style="width:60px;text-align:right;">Qté</th>
            <th style="width:80px;text-align:right;">P.U.</th>
            <th style="width:90px;text-align:right;">Montant HT</th>
        </tr>
        </thead>
        <tbody>
        @foreach($facture->lignes as $l)
        <tr>
            <td>{{ $l->ordre }}</td>
            <td>{{ $l->libelle }}</td>
            <td class="num">{{ number_format($l->quantite, 2, ',', ' ') }}</td>
            <td class="num">{{ number_format($l->prix_unitaire, 2, ',', ' ') }}</td>
            <td class="num">{{ number_format($l->montant_ht, 2, ',', ' ') }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>

    <div class="totaux">
        <table>
            <tr><td class="label">Total HT</td><td class="val">{{ number_format($facture->montant_ht, 2, ',', ' ') }} {{ $facture->devise }}</td></tr>
            @if($facture->tva_active)
            <tr><td class="label">TVA ({{ number_format($facture->taux_tva, 0) }}%)</td><td class="val">{{ number_format($facture->montant_tva, 2, ',', ' ') }}</td></tr>
            @endif
            <tr class="ttc"><td class="label">Total TTC</td><td class="val">{{ number_format($facture->montant_ttc, 2, ',', ' ') }} {{ $facture->devise }}</td></tr>
        </table>
    </div>

    @if($facture->notes)<div class="notes"><strong>Notes :</strong> {{ $facture->notes }}</div>@endif

    <div class="footer">
        Document généré le {{ now()->format('d/m/Y H:i') }} â€” {{ $societe->raison_sociale ?? '' }} â€” SYSCOHADA
    </div>
</body>
</html>

