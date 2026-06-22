<?php

namespace App\Support;

/**
 * Modes d'affichage devise unifiés pour le dashboard, les livres et les états financiers.
 *
 * Un seul paramètre (`mode_devise`) pilote tout l'affichage. Chaque mode se résout en :
 *  - devise_affichage : devise dans laquelle les montants sont présentés
 *  - scope_devise     : jeton de filtrage des écritures
 *        'natif:USD' / 'natif:CDF' -> n'inclut que les écritures saisies dans cette devise
 *        'consolide'               -> inclut toutes les écritures (converties)
 *  - mode_conversion  : toujours 'origine' (taux enregistré lors de chaque saisie)
 *
 * La conversion vise toujours `devise_affichage` au taux d'origine de chaque écriture.
 */
class DeviseMode
{
    /**
     * Définition canonique des 6 modes (source unique de vérité).
     *
     * @return array<int, array{id:string,label:string,note:string,devise_affichage:string,scope_devise:string,mode_conversion:string}>
     */
    public static function all(): array
    {
        return [
            [
                'id' => 'usd_natif',
                'label' => 'USD natif',
                'note' => 'Affiche uniquement les opérations saisies en USD, sans conversion.',
                'devise_affichage' => 'USD',
                'scope_devise' => 'natif:USD',
                'mode_conversion' => 'origine',
            ],
            [
                'id' => 'cdf_natif',
                'label' => 'CDF natif',
                'note' => 'Affiche uniquement les opérations saisies en CDF, sans conversion.',
                'devise_affichage' => 'CDF',
                'scope_devise' => 'natif:CDF',
                'mode_conversion' => 'origine',
            ],
            [
                'id' => 'usd_en_cdf',
                'label' => 'USD natif en CDF',
                'note' => "Affiche les opérations saisies en USD, converties en CDF avec leur taux d'origine.",
                'devise_affichage' => 'CDF',
                'scope_devise' => 'natif:USD',
                'mode_conversion' => 'origine',
            ],
            [
                'id' => 'cdf_en_usd',
                'label' => 'CDF natif en USD',
                'note' => "Affiche les opérations saisies en CDF, converties en USD avec leur taux d'origine.",
                'devise_affichage' => 'USD',
                'scope_devise' => 'natif:CDF',
                'mode_conversion' => 'origine',
            ],
            [
                'id' => 'usd_consolide',
                'label' => 'USD consolidé',
                'note' => "Toutes les opérations sont regroupées et présentées en USD après conversion selon le taux d'origine de chaque transaction.",
                'devise_affichage' => 'USD',
                'scope_devise' => 'consolide',
                'mode_conversion' => 'origine',
            ],
            [
                'id' => 'cdf_consolide',
                'label' => 'CDF consolidé',
                'note' => "Toutes les opérations sont regroupées et présentées en CDF après conversion selon le taux d'origine de chaque transaction.",
                'devise_affichage' => 'CDF',
                'scope_devise' => 'consolide',
                'mode_conversion' => 'origine',
            ],
        ];
    }

    /** Liste des identifiants valides. @return array<int,string> */
    public static function ids(): array
    {
        return array_column(self::all(), 'id');
    }

    /** Mode par défaut selon la devise principale de la société. */
    public static function defaut(string $devisePrincipale = 'CDF'): string
    {
        return strtoupper($devisePrincipale) === 'USD' ? 'usd_consolide' : 'cdf_consolide';
    }

    /**
     * Résout un identifiant de mode en contexte complet.
     *
     * @return array{id:string,label:string,note:string,devise_affichage:string,scope_devise:string,mode_conversion:string}
     */
    public static function resolve(?string $id, string $devisePrincipale = 'CDF'): array
    {
        $id = $id ? strtolower(trim($id)) : null;
        foreach (self::all() as $mode) {
            if ($mode['id'] === $id) {
                return $mode;
            }
        }

        return self::resolve(self::defaut($devisePrincipale), $devisePrincipale);
    }

    /**
     * Déduit l'identifiant de mode à partir des anciens paramètres séparés
     * (devise_affichage + scope_devise) pour la rétro-compatibilité.
     */
    public static function fromLegacy(string $deviseAffichage, string $scopeDevise): string
    {
        $affichage = strtoupper($deviseAffichage);

        if (str_starts_with($scopeDevise, 'natif')) {
            $parts = explode(':', $scopeDevise, 2);
            $source = isset($parts[1]) && $parts[1] !== '' ? strtoupper($parts[1]) : $affichage;

            if ($source === $affichage) {
                return $source === 'USD' ? 'usd_natif' : 'cdf_natif';
            }

            // Source filtrée différente de la devise d'affichage (natif converti).
            return $source === 'USD' ? 'usd_en_cdf' : 'cdf_en_usd';
        }

        return $affichage === 'USD' ? 'usd_consolide' : 'cdf_consolide';
    }
}
