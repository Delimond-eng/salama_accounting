<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>ReÃ§u {{ $paiement->numero }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1a1a2e; margin: 0; padding: 20px; }
        .header { text-align: center; border-bottom: 2px solid #059669; padding-bottom: 12px; margin-bottom: 16px; }
        .header h1 { margin: 0; font-size: 16px; color: #059669; letter-spacing: 2px; }
        .header p { margin: 4px 0 0; color: #64748b; font-size: 9px; }
        .amount { text-align: center; background: #ecfdf5; border: 2px solid #059669; border-radius: 8px; padding: 16px; margin: 16px 0; }
        .amount .label { font-size: 9px; color: #64748b; text-transform: uppercase; }
        .amount .value { font-size: 22px; font-weight: bold; color: #059669; margin-top: 4px; }
        table.info { width: 100%; border-collapse: collapse; }
        table.info td { padding: 6px 0; border-bottom: 1px dotted #e2e8f0; }
        table.info td:first-child { color: #64748b; width: 40%; }
        .footer { margin-top: 20px; text-align: center; font-size: 8px; color: #94a3b8; }
        .stamp { margin-top: 24px; text-align: center; border: 1px dashed #cbd5e1; padding: 12px; color: #64748b; font-size: 9px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>REÃ‡U DE PAIEMENT</h1>
        <p>{{ $societe->raison_sociale ?? '' }}</p>
        <p>{{ $paiement->numero }}</p>
    </div>

    <div class="amount">
        <div class="label">Montant reÃ§u</div>
        <div class="value">{{ number_format($paiement->montant, 2, ',', ' ') }} {{ $paiement->devise }}</div>
    </div>

    <table class="info">
        <tr><td>Date paiement</td><td><strong>{{ $paiement->date_paiement->format('d/m/Y') }}</strong></td></tr>
        <tr><td>Méthode</td><td>{{ ucfirst($paiement->methode) }} â€” {{ $paiement->compte_tresorerie }}</td></tr>
        @if($facture)
        <tr><td>Facture</td><td><strong>{{ $facture->numero }}</strong></td></tr>
        <tr><td>Tiers</td><td>{{ $facture->tiers->nom ?? '' }}</td></tr>
        @endif
        <tr><td>Enregistré par</td><td>{{ $paiement->user->name ?? 'â€”' }}</td></tr>
    </table>

    <div class="stamp">Document comptable validé â€” écriture nÂ° {{ $paiement->ecriture_id ?? 'â€”' }}</div>

    <div class="footer">Généré le {{ now()->format('d/m/Y H:i') }} â€” Conservez ce reÃ§u</div>
</body>
</html>

