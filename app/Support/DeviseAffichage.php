<?php

namespace App\Support;

use App\Models\Societe;
use App\Services\LivresComptablesService;

/**
 * Préférences d'affichage multi-devises (societes.parametres JSON).
 */
class DeviseAffichage
{
    public static function options(?Societe $societe = null): array
    {
        $societe = $societe ?? SocieteContext::societe();
        if (! $societe) {
            return [
                'devise_principale' => 'CDF',
                'devise_affichage' => 'CDF',
                'mode_conversion' => 'origine',
                'scope_devise' => 'consolide',
                'devises' => [],
                'libelle_mode' => 'Consolidé CDF',
            ];
        }

        return app(LivresComptablesService::class)->optionsDefaut($societe) + [
            'libelle_mode' => self::libelleMode(
                $societe->parametres['devise_affichage'] ?? ($societe->devise_principale ?? 'CDF'),
                $societe->parametres['scope_devise'] ?? 'consolide'
            ),
        ];
    }

    public static function save(Societe $societe, string $deviseAffichage, string $modeConversion, string $scopeDevise): array
    {
        $params = $societe->parametres ?? [];
        $params['devise_affichage'] = strtoupper($deviseAffichage);
        $params['mode_conversion'] = $modeConversion;
        $params['scope_devise'] = $scopeDevise;
        $societe->update(['parametres' => $params]);

        return self::options($societe->fresh());
    }

    public static function libelleMode(string $devise, string $scope): string
    {
        $devise = strtoupper($devise);

        return $scope === 'natif'
            ? "Natif {$devise}"
            : "Consolidé {$devise}";
    }
}
