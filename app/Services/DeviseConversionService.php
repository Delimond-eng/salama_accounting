<?php

namespace App\Services;

use App\Models\Devise;
use App\Models\TauxChange;
use Carbon\Carbon;

/**
 * Conversion via la devise principale (CDF par défaut).
 * taux = unités de devise principale pour 1 unité de devise étrangère (ex. 1 USD = 2200 CDF).
 */
class DeviseConversionService
{
    protected string $devisePrincipale = 'CDF';

    public function setDevisePrincipale(string $code): self
    {
        $this->devisePrincipale = strtoupper($code);

        return $this;
    }

    public function getDevisePrincipale(): string
    {
        return $this->devisePrincipale;
    }

    public function tauxJournalier(int $societeId, string $devise, string $date): float
    {
        $devise = strtoupper($devise);
        if ($devise === $this->devisePrincipale) {
            return 1.0;
        }

        $taux = TauxChange::where('societe_id', $societeId)
            ->where('devise_code', $devise)
            ->where('date_taux', '<=', $date)
            ->orderByDesc('date_taux')
            ->value('taux');

        return $taux ? (float) $taux : 1.0;
    }

    public function versDevisePrincipale(float $montant, string $deviseSource, float $taux): float
    {
        $deviseSource = strtoupper($deviseSource);
        if ($montant == 0.0 || $deviseSource === $this->devisePrincipale) {
            return round($montant, 2);
        }

        return round($montant * max($taux, 0.000001), 2);
    }

    public function depuisDevisePrincipale(float $montantPrincipal, string $deviseCible, float $tauxCible): float
    {
        $deviseCible = strtoupper($deviseCible);
        if ($montantPrincipal == 0.0 || $deviseCible === $this->devisePrincipale) {
            return round($montantPrincipal, 2);
        }

        return round($montantPrincipal / max($tauxCible, 0.000001), 2);
    }

    /**
     * @param  string  $mode  origine|actuel
     */
    public function convertir(
        float $montant,
        string $deviseSource,
        string $deviseCible,
        float $tauxOrigine,
        int $societeId,
        string $dateEcriture,
        string $mode = 'origine'
    ): float {
        $deviseSource = strtoupper($deviseSource);
        $deviseCible = strtoupper($deviseCible);

        if ($deviseSource === $deviseCible) {
            return round($montant, 2);
        }

        $tauxSrc = $mode === 'actuel'
            ? $this->tauxJournalier($societeId, $deviseSource, $dateEcriture)
            : ($deviseSource === $this->devisePrincipale ? 1.0 : ($tauxOrigine > 0 ? $tauxOrigine : $this->tauxJournalier($societeId, $deviseSource, $dateEcriture)));

        $tauxDst = $mode === 'actuel'
            ? $this->tauxJournalier($societeId, $deviseCible, $dateEcriture)
            : $this->tauxJournalier($societeId, $deviseCible, $dateEcriture);

        $principal = $this->versDevisePrincipale($montant, $deviseSource, $tauxSrc);

        return $this->depuisDevisePrincipale($principal, $deviseCible, $tauxDst);
    }

    public function libelleTaux(string $devise, float $taux): string
    {
        $devise = strtoupper($devise);
        if ($devise === $this->devisePrincipale || $taux <= 0) {
            return "1 {$this->devisePrincipale}";
        }

        return '1 '.$devise.' = '.number_format($taux, 2, ',', ' ').' '.$this->devisePrincipale;
    }

    public function devisesPourAffichage(): array
    {
        return Devise::actif()
            ->whereIn('code_iso', ['CDF', 'USD'])
            ->orderByRaw("FIELD(code_iso, 'CDF', 'USD')")
            ->get(['code_iso', 'libelle', 'symbole', 'nb_decimales'])
            ->all();
    }

    public function parseDate(string $date): string
    {
        return Carbon::parse($date)->toDateString();
    }
}
