<!DOCTYPE html>
<html lang="fr">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $facture->numero }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9.5px; color: #0f172a; margin: 0; padding: 20px 24px 16px; line-height: 1.4; }
        .accent { color: #1d4ed8; }
        .header { border-bottom: 3px solid #1d4ed8; padding-bottom: 14px; margin-bottom: 16px; }
        .header table { width: 100%; border-collapse: collapse; }
        .header td { border: none; vertical-align: top; padding: 0; }
        .logo-img { max-height: 52px; max-width: 140px; margin-bottom: 6px; }
        .brand { font-size: 17px; font-weight: bold; color: #1e3a8a; letter-spacing: 0.3px; }
        .brand-sub { font-size: 8.5px; color: #64748b; margin-top: 3px; }
        .doc-title { font-size: 20px; font-weight: bold; text-align: right; color: #1e3a8a; letter-spacing: 0.5px; }
        .doc-meta { text-align: right; font-size: 10px; color: #475569; margin-top: 5px; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 8px; font-weight: bold; text-transform: uppercase; }
        .badge-brouillon { background: #fef3c7; color: #92400e; }
        .badge-validee { background: #dbeafe; color: #1e40af; }
        .badge-payee { background: #d1fae5; color: #065f46; }
        .badge-annulee { background: #fee2e2; color: #991b1b; }
        .parties { margin-bottom: 14px; }
        .parties table { width: 100%; border-collapse: collapse; }
        .parties td { border: none; width: 50%; vertical-align: top; padding: 0 6px 0 0; }
        .box { background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%); border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px 12px; }
        .box h4 { margin: 0 0 6px; font-size: 8px; text-transform: uppercase; color: #1d4ed8; letter-spacing: 0.6px; font-weight: bold; }
        .box p { margin: 2px 0; font-size: 9.5px; }
        .objet { background: #eff6ff; border-left: 4px solid #2563eb; padding: 8px 12px; margin-bottom: 12px; font-size: 9.5px; border-radius: 0 6px 6px 0; }
        table.lignes { width: 100%; border-collapse: collapse; margin-top: 4px; }
        table.lignes thead th { background: #1e3a8a; color: #fff; padding: 8px 6px; font-size: 8.5px; text-align: left; font-weight: bold; }
        table.lignes tbody td { border-bottom: 1px solid #e2e8f0; padding: 7px 6px; vertical-align: top; }
        table.lignes tbody tr:nth-child(even) td { background: #f8fafc; }
        table.lignes td.num { text-align: right; white-space: nowrap; }
        .rubrique { font-size: 8px; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 2px; }
        .totaux-wrap { margin-top: 14px; }
        .totaux-wrap table { width: 100%; border: none; }
        .totaux-wrap td { border: none; vertical-align: top; }
        .commentaires { width: 58%; padding-right: 12px; font-size: 9px; color: #334155; }
        .commentaires-box { background: #fffbeb; border: 1px solid #fde68a; border-radius: 6px; padding: 10px; min-height: 60px; }
        .commentaires-box strong { color: #92400e; display: block; margin-bottom: 4px; font-size: 8px; text-transform: uppercase; }
        .totaux { width: 42%; }
        .totaux table.inner { width: 100%; border-collapse: collapse; background: #f8fafc; border-radius: 8px; overflow: hidden; border: 1px solid #e2e8f0; }
        .totaux table.inner td { padding: 7px 12px; border: none; }
        .totaux .label { color: #64748b; }
        .totaux .val { text-align: right; font-weight: bold; }
        .totaux .ttc td { background: #1e3a8a; color: #fff; font-size: 12px; padding: 10px 12px; }
        .totaux .ttc .label { color: #bfdbfe; }
        .footer { margin-top: 18px; padding-top: 10px; border-top: 2px solid #1d4ed8; font-size: 7.5px; color: #475569; }
        .footer-grid { width: 100%; border-collapse: collapse; }
        .footer-grid td { border: none; vertical-align: top; padding: 3px 8px 3px 0; width: 33%; }
        .footer h5 { margin: 0 0 4px; font-size: 7.5px; text-transform: uppercase; color: #1d4ed8; letter-spacing: 0.4px; }
        .legal { text-align: center; margin-top: 8px; color: #94a3b8; font-size: 7px; }
        .bank-line { margin: 1px 0; }
    </style>
</head>
<body>
    <div class="header">
        <table>
            <tr>
                <td style="width:52%">
                    @if($societe?->logo_url)
                    <img src="{{ $societe->logo_url }}" class="logo-img" alt="Logo">
                    @endif
                    <div class="brand">{{ $societe?->raison_sociale ?? 'Société' }}</div>
                    @if($societe?->forme_juridique || $societe?->sigle)
                    <div class="brand-sub">{{ trim(($societe->forme_juridique ?? '').' '.($societe->sigle ? '— '.$societe->sigle : '')) }}</div>
                    @endif
                    @if($societe?->adresse)<div class="brand-sub">{{ $societe->adresse }}@if($societe->ville), {{ $societe->ville }}@endif@if($societe->pays), {{ $societe->pays }}@endif</div>@endif
                    @if($societe?->telephone)<div class="brand-sub">Tél : {{ $societe->telephone }}@if($societe->email) — {{ $societe->email }}@endif</div>@endif
                </td>
                <td>
                    <div class="doc-title">{{ $titre }}</div>
                    <div class="doc-meta"><strong>N°</strong> {{ $facture->numero }}</div>
                    <div class="doc-meta"><strong>Date</strong> {{ $facture->date_facture->format('d/m/Y') }}</div>
                    @if($facture->date_echeance)<div class="doc-meta"><strong>Échéance</strong> {{ $facture->date_echeance->format('d/m/Y') }}</div>@endif
                    <div class="doc-meta" style="margin-top:6px;"><span class="badge badge-{{ $facture->statut }}">{{ strtoupper($facture->statut) }}</span></div>
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
                        <p><strong style="font-size:11px;">{{ $facture->tiers?->nom ?? '' }}</strong></p>
                        @if($facture->tiers?->code)<p>Code : {{ $facture->tiers->code }}</p>@endif
                        @if($facture->tiers?->adresse)<p>{{ $facture->tiers->adresse }}</p>@endif
                        @if($facture->tiers?->ville)<p>{{ $facture->tiers->ville }}</p>@endif
                    </div>
                </td>
                <td>
                    <div class="box">
                        <h4>Détails facture</h4>
                        <p>Devise : <strong>{{ $facture->devise }}</strong></p>
                        @if($facture->factureOrigine)<p>Réf. avoir / origine : <strong>{{ $facture->factureOrigine->numero }}</strong></p>@endif
                        @if($facture->tva_active)<p>TVA : <strong>{{ number_format($facture->taux_tva, 0) }} %</strong></p>@else<p>TVA : <strong>Non applicable</strong></p>@endif
                    </div>
                </td>
            </tr>
        </table>
    </div>

    @if($facture->objet)
    <div class="objet"><strong>Objet :</strong> {{ $facture->objet }}</div>
    @endif

    <table class="lignes">
        <thead>
        <tr>
            <th style="width:28px;">#</th>
            <th style="width:70px;">Rubrique</th>
            <th>Désignation</th>
            <th style="width:52px;text-align:right;">Qté</th>
            <th style="width:72px;text-align:right;">P.U.</th>
            <th style="width:82px;text-align:right;">Montant HT</th>
        </tr>
        </thead>
        <tbody>
        @foreach($facture->lignes as $l)
        <tr>
            <td>{{ $l->ordre }}</td>
            <td>@if($l->rubrique)<span class="rubrique">{{ $l->rubrique }}</span>@else<span style="color:#cbd5e1;">—</span>@endif</td>
            <td>{{ $l->libelle }}</td>
            <td class="num">{{ number_format($l->quantite, 2, ',', ' ') }}</td>
            <td class="num">{{ number_format($l->prix_unitaire, 2, ',', ' ') }}</td>
            <td class="num">{{ number_format($l->montant_ht, 2, ',', ' ') }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>

    <div class="totaux-wrap">
        <table>
            <tr>
                <td class="commentaires">
                    @if($facture->notes)
                    <div class="commentaires-box">
                        <strong>Commentaires</strong>
                        {!! nl2br(e($facture->notes)) !!}
                    </div>
                    @endif
                </td>
                <td class="totaux">
                    <table class="inner">
                        <tr><td class="label">Total HT</td><td class="val">{{ number_format($facture->montant_ht, 2, ',', ' ') }} {{ $facture->devise }}</td></tr>
                        @if($facture->tva_active)
                        <tr><td class="label">TVA ({{ number_format($facture->taux_tva, 0) }} %)</td><td class="val">{{ number_format($facture->montant_tva, 2, ',', ' ') }}</td></tr>
                        @endif
                        <tr class="ttc"><td class="label">Total TTC</td><td class="val">{{ number_format($facture->montant_ttc, 2, ',', ' ') }} {{ $facture->devise }}</td></tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <table class="footer-grid">
            <tr>
                <td>
                    <h5>Informations légales</h5>
                    @if($societe?->rccm)<div>RCCM : {{ $societe->rccm }}</div>@endif
                    @if($societe?->num_contribuable)<div>N° Impôt : {{ $societe->num_contribuable }}</div>@endif
                    @if($societe?->identification_nationale)<div>ID. nationale : {{ $societe->identification_nationale }}</div>@endif
                    @if($societe?->num_cnps)<div>CNPS : {{ $societe->num_cnps }}</div>@endif
                    @if($societe?->regime_fiscal)<div>Régime : {{ $societe->regime_fiscal }}</div>@endif
                </td>
                <td>
                    <h5>Coordonnées</h5>
                    @if($societe?->telephone)<div>Tél : {{ $societe->telephone }}</div>@endif
                    @if($societe?->email)<div>{{ $societe->email }}</div>@endif
                    @if($societe?->site_web)<div>{{ $societe->site_web }}</div>@endif
                    @if($societe?->adresse)<div>{{ $societe->adresse }}</div>@endif
                </td>
                <td>
                    <h5>Comptes bancaires</h5>
                    @forelse($societe?->banques ?? [] as $b)
                    <div class="bank-line"><strong>{{ $b->banque }}</strong> — {{ $b->numero_compte }} ({{ $b->devise }})</div>
                    @empty
                    <div style="color:#94a3b8;">—</div>
                    @endforelse
                </td>
            </tr>
        </table>
        <div class="legal">
            Document généré le {{ now()->format('d/m/Y à H:i') }} — {{ $societe?->raison_sociale ?? '' }} — Conforme SYSCOHADA / OHADA
        </div>
    </div>
</body>
</html>
