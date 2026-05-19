<?php

namespace App\Exceptions;

use RuntimeException;

class BilanDesequilibreException extends RuntimeException
{
    public function __construct(
        public readonly float $totalActif,
        public readonly float $totalPassif,
        public readonly float $totalCapitauxPropres,
        public readonly float $ecart,
        public readonly array $context = []
    ) {
        $message = match ($context['type'] ?? '') {
            'comptes_non_affectes' => 'BILAN IMPOSSIBLE — compte(s) non classé(s) dans le plan bilan SYSCOHADA',
            'ecart_bilan', 'ecart_comptable', 'ecart_reporting' => 'BILAN NON ÉQUILIBRÉ — TOTAL ACTIF ≠ TOTAL PASSIF (soldes naturels signés)',
            default => 'BILAN NON ÉQUILIBRÉ — écart actif / passif après classification',
        };

        parent::__construct($message, 422);
    }
}
