<?php

namespace App\Support;

use App\Models\Societe;
use App\Services\LivresComptablesService;

/**
 * Préférences d'affichage multi-devises (societes.parametres JSON).
 * Délègue au système unifié à 6 modes (DeviseMode).
 */
class DeviseAffichage
{
    public static function options(?Societe $societe = null): array
    {
        $societe = $societe ?? SocieteContext::societe();
        if (! $societe) {
            $defaut = DeviseMode::defaut('CDF');
            $ctx = DeviseMode::resolve($defaut, 'CDF');

            return [
                'devise_principale' => 'CDF',
                'devise_affichage' => $ctx['devise_affichage'],
                'mode_conversion' => $ctx['mode_conversion'],
                'scope_devise' => $ctx['scope_devise'],
                'mode_devise' => $ctx['id'],
                'modes_devise' => DeviseMode::all(),
                'devises' => [],
                'libelle_mode' => $ctx['label'],
                'note_mode' => $ctx['note'],
            ];
        }

        $options = app(LivresComptablesService::class)->optionsDefaut($societe);
        $ctx = DeviseMode::resolve($options['mode_devise'], $options['devise_principale']);

        return $options + [
            'libelle_mode' => $ctx['label'],
            'note_mode' => $ctx['note'],
        ];
    }

    /** Enregistre le mode unifié (prioritaire). */
    public static function saveMode(Societe $societe, string $modeDevise): array
    {
        $resolved = app(LivresComptablesService::class)->resoudreFiltresDevise($societe, [
            'mode_devise' => $modeDevise,
        ]);

        $params = $societe->parametres ?? [];
        $params['mode_devise'] = $resolved['mode_devise'];
        $params['devise_affichage'] = $resolved['devise_affichage'];
        $params['scope_devise'] = $resolved['scope_devise'];
        $params['mode_conversion'] = $resolved['mode_conversion'];
        $societe->update(['parametres' => $params]);

        return self::options($societe->fresh());
    }

    /**
     * @deprecated Utiliser saveMode() avec mode_devise.
     */
    public static function save(Societe $societe, string $deviseAffichage, string $modeConversion, string $scopeDevise): array
    {
        $modeDevise = DeviseMode::fromLegacy($deviseAffichage, $scopeDevise);

        return self::saveMode($societe, $modeDevise);
    }

    public static function libelleMode(string $devise, string $scope): string
    {
        return DeviseMode::resolve(DeviseMode::fromLegacy($devise, $scope))['label'];
    }
}
