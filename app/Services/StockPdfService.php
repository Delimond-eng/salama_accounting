<?php

namespace App\Services;

use App\Models\MouvementStock;
use App\Models\Societe;
use Barryvdh\DomPDF\Facade\Pdf;

class StockPdfService
{
    public function bon(MouvementStock $mouvement): \Barryvdh\DomPDF\PDF
    {
        $mouvement->load(['produit', 'user']);
        $societe = Societe::with('banques')->find($mouvement->societe_id);

        $titre = match ($mouvement->type_mouvement) {
            MouvementStock::TYPE_SORTIE => 'BON DE SORTIE DE STOCK',
            MouvementStock::TYPE_INVENTAIRE => 'BON D\'INVENTAIRE',
            MouvementStock::TYPE_AJUSTEMENT => 'BON D\'AJUSTEMENT',
            default => 'BON D\'ENTRÉE DE STOCK',
        };

        return Pdf::loadView('pdf.bon-stock', [
            'mouvement' => $mouvement,
            'societe' => $societe,
            'titre' => $titre,
        ])
            ->setPaper('a4')
            ->setOption('defaultFont', 'DejaVu Sans')
            ->setOption('isHtml5ParserEnabled', true);
    }
}
