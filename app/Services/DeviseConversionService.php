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

        if ($taux) {
            return (float) $taux;
        }

        // Aucune cotation à cette date (ex. opération antérieure au 1er taux saisi) :
        // on prend le taux connu le plus proche dans le temps plutôt que 1.0,
        // qui assimilerait à tort 1 unité étrangère à 1 unité de devise principale.
        $tauxProche = TauxChange::where('societe_id', $societeId)
            ->where('devise_code', $devise)
            ->orderBy('date_taux')
            ->value('taux');

        return $tauxProche ? (float) $tauxProche : 1.0;
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

        // En mode origine : taux enregistré sur l'écriture pour la devise source ;
        // pour la devise cible, taux du jour de l'écriture (cohérent avec la saisie).
        $tauxSrc = $mode === 'actuel'
            ? $this->tauxJournalier($societeId, $deviseSource, $dateEcriture)
            : ($deviseSource === $this->devisePrincipale
                ? 1.0
                : ($tauxOrigine > 1 ? $tauxOrigine : $this->tauxJournalier($societeId, $deviseSource, $dateEcriture)));

        $tauxDst = $mode === 'actuel'
            ? $this->tauxJournalier($societeId, $deviseCible, $dateEcriture)
            : ($deviseCible === $this->devisePrincipale
                ? 1.0
                : $this->tauxJournalier($societeId, $deviseCible, $dateEcriture));

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

    /**
     * Devises proposées dans les sélecteurs (saisie, livres, états, dashboard).
     * Inclut la devise principale + les devises de travail : USD, EUR, FCFA (XAF).
     */
    public function devisesPourAffichage(): array
    {
        $codes = array_values(array_unique(array_merge(
            [$this->devisePrincipale],
            ['CDF', 'USD', 'EUR', 'XAF']
        )));

        $ordre = "'".implode("','", $codes)."'";

        return Devise::actif()
            ->whereIn('code_iso', $codes)
            ->orderByRaw("FIELD(code_iso, {$ordre})")
            ->get(['code_iso', 'libelle', 'symbole', 'nb_decimales'])
            ->all();
    }

    public function parseDate(string $date): string
    {
        return Carbon::parse($date)->toDateString();
    }
}
