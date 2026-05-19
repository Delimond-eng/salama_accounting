<?php

namespace App\Services;

use App\Models\Facture;
use App\Models\Paiement;
use App\Models\Societe;
use Barryvdh\DomPDF\Facade\Pdf;

class FacturationPdfService
{
    public function facture(Facture $facture): \Barryvdh\DomPDF\PDF
    {
        $facture->load(['lignes', 'tiers', 'societe', 'factureOrigine']);

        return Pdf::loadView('pdf.facture', [
            'facture' => $facture,
            'societe' => $facture->societe,
            'titre' => $this->titreFacture($facture),
        ])->setPaper('a4');
    }

    public function recuPaiement(Paiement $paiement): \Barryvdh\DomPDF\PDF
    {
        $paiement->load(['facture.tiers', 'facture.lignes', 'user']);
        $societe = Societe::find($paiement->societe_id);

        return Pdf::loadView('pdf.recu-paiement', [
            'paiement' => $paiement,
            'facture' => $paiement->facture,
            'societe' => $societe,
        ])->setPaper('a5', 'portrait');
    }

    protected function titreFacture(Facture $facture): string
    {
        return match ($facture->type_document) {
            Facture::TYPE_VENTE_CLIENT => 'FACTURE CLIENT',
            Facture::TYPE_ACHAT_FOURNISSEUR => 'FACTURE FOURNISSEUR',
            Facture::TYPE_AVOIR_CLIENT => 'AVOIR CLIENT',
            Facture::TYPE_AVOIR_FOURNISSEUR => 'AVOIR FOURNISSEUR',
            default => 'DOCUMENT',
        };
    }
}
