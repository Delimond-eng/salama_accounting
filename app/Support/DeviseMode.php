<?php

namespace App\Support;

/**
 * Modes d'affichage devise unifiés pour le dashboard, les livres et les états financiers.
 *
 * Un seul paramètre (`mode_devise`) pilote tout l'affichage. Chaque mode se résout en :
 *  - devise_affichage : devise dans laquelle les montants sont présentés
 *  - scope_devise     : jeton de filtrage des écritures
 *        'natif:USD' / 'natif:CDF' / 'natif:EUR' -> n'inclut que les écritures saisies dans cette devise
 *        'consolide'                             -> inclut toutes les écritures (converties)
 *  - mode_conversion  : 'origine' = taux_change enregistré à la saisie (ligne par ligne).
 *
 * Si taux_change ≤ 1 (erreur de saisie), le taux applicatif à la date de l'écriture est utilisé.
 */
class DeviseMode
{
    /**
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
                'id' => 'eur_natif',
                'label' => 'EUR natif',
                'note' => 'Affiche uniquement les opérations saisies en EUR, sans conversion.',
                'devise_affichage' => 'EUR',
                'scope_devise' => 'natif:EUR',
                'mode_conversion' => 'origine',
            ],
            [
                'id' => 'usd_en_cdf',
                'label' => 'USD natif en CDF',
                'note' => 'Affiche les opérations saisies en USD, converties en CDF avec le taux enregistré à la saisie.',
                'devise_affichage' => 'CDF',
                'scope_devise' => 'natif:USD',
                'mode_conversion' => 'origine',
            ],
            [
                'id' => 'cdf_en_usd',
                'label' => 'CDF natif en USD',
                'note' => 'Affiche les opérations saisies en CDF, converties en USD avec le taux enregistré à la saisie.',
                'devise_affichage' => 'USD',
                'scope_devise' => 'natif:CDF',
                'mode_conversion' => 'origine',
            ],
            [
                'id' => 'eur_en_cdf',
                'label' => 'EUR natif en CDF',
                'note' => 'Affiche les opérations saisies en EUR, converties en CDF avec le taux enregistré à la saisie.',
                'devise_affichage' => 'CDF',
                'scope_devise' => 'natif:EUR',
                'mode_conversion' => 'origine',
            ],
            [
                'id' => 'eur_en_usd',
                'label' => 'EUR natif en USD',
                'note' => 'Affiche les opérations saisies en EUR, converties en USD avec le taux enregistré à la saisie.',
                'devise_affichage' => 'USD',
                'scope_devise' => 'natif:EUR',
                'mode_conversion' => 'origine',
            ],
            [
                'id' => 'usd_en_eur',
                'label' => 'USD natif en EUR',
                'note' => 'Affiche les opérations saisies en USD, converties en EUR avec le taux enregistré à la saisie.',
                'devise_affichage' => 'EUR',
                'scope_devise' => 'natif:USD',
                'mode_conversion' => 'origine',
            ],
            [
                'id' => 'cdf_en_eur',
                'label' => 'CDF natif en EUR',
                'note' => 'Affiche les opérations saisies en CDF, converties en EUR avec le taux enregistré à la saisie.',
                'devise_affichage' => 'EUR',
                'scope_devise' => 'natif:CDF',
                'mode_conversion' => 'origine',
            ],
            [
                'id' => 'usd_consolide',
                'label' => 'USD consolidé',
                'note' => 'Toutes les opérations sont regroupées et présentées en USD au taux enregistré à la saisie de chaque écriture.',
                'devise_affichage' => 'USD',
                'scope_devise' => 'consolide',
                'mode_conversion' => 'origine',
            ],
            [
                'id' => 'cdf_consolide',
                'label' => 'CDF consolidé',
                'note' => 'Toutes les opérations sont regroupées et présentées en CDF au taux enregistré à la saisie de chaque écriture.',
                'devise_affichage' => 'CDF',
                'scope_devise' => 'consolide',
                'mode_conversion' => 'origine',
            ],
            [
                'id' => 'eur_consolide',
                'label' => 'EUR consolidé',
                'note' => 'Toutes les opérations sont regroupées et présentées en EUR au taux enregistré à la saisie de chaque écriture.',
                'devise_affichage' => 'EUR',
                'scope_devise' => 'consolide',
                'mode_conversion' => 'origine',
            ],
        ];
    }

    /** @return array<int, string> */
    public static function ids(): array
    {
        return array_column(self::all(), 'id');
    }

    public static function defaut(string $devisePrincipale = 'CDF'): string
    {
        return match (strtoupper($devisePrincipale)) {
            'USD' => 'usd_consolide',
            'EUR' => 'eur_consolide',
            default => 'cdf_consolide',
        };
    }

    /**
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

    public static function fromLegacy(string $deviseAffichage, string $scopeDevise): string
    {
        $affichage = strtoupper($deviseAffichage);

        if (str_starts_with($scopeDevise, 'natif')) {
            $parts = explode(':', $scopeDevise, 2);
            $source = isset($parts[1]) && $parts[1] !== '' ? strtoupper($parts[1]) : $affichage;

            if ($source === $affichage) {
                return match ($source) {
                    'USD' => 'usd_natif',
                    'EUR' => 'eur_natif',
                    default => 'cdf_natif',
                };
            }

            $key = strtolower($source).'_en_'.strtolower($affichage);
            foreach (self::all() as $mode) {
                if ($mode['id'] === $key) {
                    return $mode['id'];
                }
            }

            return match ($source) {
                'USD' => 'usd_natif',
                'EUR' => 'eur_natif',
                default => 'cdf_natif',
            };
        }

        return match ($affichage) {
            'USD' => 'usd_consolide',
            'EUR' => 'eur_consolide',
            default => 'cdf_consolide',
        };
    }
}
