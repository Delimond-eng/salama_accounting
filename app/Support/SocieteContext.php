<?php

namespace App\Support;

use App\Models\Societe;

class SocieteContext
{
    public static function id(): ?int
    {
        if ($id = session('societe_id')) {
            return (int) $id;
        }

        $firstId = Societe::query()->where('statut', 'active')->value('id');
        if ($firstId) {
            session(['societe_id' => $firstId]);

            return (int) $firstId;
        }

        return null;
    }

    public static function societe(): ?Societe
    {
        $id = self::id();

        return $id ? Societe::find($id) : null;
    }

    public static function set(int $societeId): void
    {
        session(['societe_id' => $societeId]);
    }

    public static function requireId(): int
    {
        $id = self::id();
        if (! $id) {
            abort(422, 'Aucune société active. Créez une société dans Paramètres > Société & exercice.');
        }

        return $id;
    }
}
