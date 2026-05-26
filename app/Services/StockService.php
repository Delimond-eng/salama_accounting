<?php

namespace App\Services;

use App\Models\BonCommande;
use App\Models\BonCommandeLigne;
use App\Models\BonLivraison;
use App\Models\BonLivraisonLigne;
use App\Models\MouvementStock;
use App\Models\Produit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class StockService
{
    public function genererNumeroBc(int $societeId): string
    {
        return $this->nextNumero($societeId, 'BC', BonCommande::class);
    }

    public function genererNumeroBl(int $societeId): string
    {
        return $this->nextNumero($societeId, 'BL', BonLivraison::class);
    }

    protected function nextNumero(int $societeId, string $prefix, string $modelClass): string
    {
        $year = now()->format('Y');
        $last = $modelClass::parSociete($societeId)
            ->where('numero', 'like', "{$prefix}-{$year}-%")
            ->orderByDesc('id')
            ->value('numero');
        $seq = 1;
        if ($last && preg_match('/-(\d+)$/', $last, $m)) {
            $seq = (int) $m[1] + 1;
        }

        return sprintf('%s-%s-%04d', $prefix, $year, $seq);
    }

    /**
     * @param  array<string, mixed>  $entete
     * @param  array<int, array<string, mixed>>  $lignes
     */
    public function enregistrerBonCommande(int $societeId, array $entete, array $lignes): BonCommande
    {
        return DB::transaction(function () use ($societeId, $entete, $lignes) {
            $ht = 0.0;
            foreach ($lignes as $l) {
                $ht += round((float) ($l['quantite'] ?? 1) * (float) ($l['prix_unitaire'] ?? 0), 2);
            }

            $bc = BonCommande::create([
                'societe_id' => $societeId,
                'tiers_id' => $entete['tiers_id'],
                'numero' => $entete['numero'] ?? $this->genererNumeroBc($societeId),
                'date_commande' => $entete['date_commande'] ?? now()->toDateString(),
                'date_livraison_prevue' => $entete['date_livraison_prevue'] ?? null,
                'statut' => $entete['statut'] ?? 'brouillon',
                'devise' => strtoupper($entete['devise'] ?? 'CDF'),
                'montant_ht' => $ht,
                'montant_ttc' => $ht,
                'notes' => $entete['notes'] ?? null,
                'cree_par' => Auth::id(),
            ]);

            foreach ($lignes as $i => $l) {
                BonCommandeLigne::create([
                    'bon_commande_id' => $bc->id,
                    'produit_id' => $l['produit_id'] ?? null,
                    'ordre' => $i + 1,
                    'libelle' => $l['libelle'],
                    'quantite' => $l['quantite'] ?? 1,
                    'prix_unitaire' => $l['prix_unitaire'] ?? 0,
                    'montant_ht' => round((float) ($l['quantite'] ?? 1) * (float) ($l['prix_unitaire'] ?? 0), 2),
                ]);
            }

            return $bc->fresh(['lignes', 'tiers']);
        });
    }

    /**
     * Réception marchandise : bon de livraison + entrée stock.
     *
     * @param  array<string, mixed>  $entete
     * @param  array<int, array<string, mixed>>  $lignes
     */
    public function recevoirLivraison(int $societeId, array $entete, array $lignes): BonLivraison
    {
        return DB::transaction(function () use ($societeId, $entete, $lignes) {
            $bl = BonLivraison::create([
                'societe_id' => $societeId,
                'bon_commande_id' => $entete['bon_commande_id'] ?? null,
                'tiers_id' => $entete['tiers_id'],
                'entrepot_id' => $entete['entrepot_id'] ?? null,
                'numero' => $entete['numero'] ?? $this->genererNumeroBl($societeId),
                'date_livraison' => $entete['date_livraison'] ?? now()->toDateString(),
                'statut' => 'valide',
                'devise' => strtoupper($entete['devise'] ?? 'CDF'),
                'notes' => $entete['notes'] ?? null,
                'cree_par' => Auth::id(),
            ]);

            foreach ($lignes as $i => $l) {
                BonLivraisonLigne::create([
                    'bon_livraison_id' => $bl->id,
                    'produit_id' => $l['produit_id'] ?? null,
                    'ordre' => $i + 1,
                    'libelle' => $l['libelle'],
                    'quantite' => $l['quantite'] ?? 1,
                    'prix_unitaire' => $l['prix_unitaire'] ?? 0,
                ]);

                if (! empty($l['produit_id'])) {
                    $this->mouvement(
                        $societeId,
                        (int) $l['produit_id'],
                        MouvementStock::TYPE_ENTREE,
                        (float) ($l['quantite'] ?? 1),
                        'Réception '.$bl->numero,
                        $bl->date_livraison->format('Y-m-d'),
                        BonLivraison::class,
                        $bl->id,
                        $entete['entrepot_id'] ?? null
                    );
                }
            }

            if (! empty($entete['bon_commande_id'])) {
                BonCommande::parSociete($societeId)
                    ->where('id', $entete['bon_commande_id'])
                    ->update(['statut' => 'livree']);
            }

            return $bl->fresh(['lignes', 'tiers']);
        });
    }

    public function mouvement(
        int $societeId,
        int $produitId,
        string $type,
        float $quantite,
        string $libelle,
        string $date,
        ?string $refType = null,
        ?int $refId = null,
        ?int $entrepotId = null
    ): MouvementStock {
        $produit = Produit::parSociete($societeId)->findOrFail($produitId);
        if (! $produit->gestion_stock && $type !== MouvementStock::TYPE_AJUSTEMENT) {
            throw new InvalidArgumentException("Le produit « {$produit->libelle} » n'a pas la gestion de stock activée.");
        }

        $q = abs($quantite);
        if ($q <= 0) {
            throw new InvalidArgumentException('Quantité invalide.');
        }

        $avant = (float) $produit->stock_actuel;
        $apres = match ($type) {
            MouvementStock::TYPE_INVENTAIRE => $q,
            MouvementStock::TYPE_SORTIE => $avant - $q,
            default => $avant + $q,
        };

        if ($apres < 0) {
            throw new InvalidArgumentException("Stock insuffisant pour « {$produit->libelle} » (disponible : {$avant}).");
        }

        $mouvement = MouvementStock::create([
            'societe_id' => $societeId,
            'numero' => $this->genererNumeroMouvement($societeId, $type),
            'produit_id' => $produitId,
            'entrepot_id' => $entrepotId,
            'type_mouvement' => $type,
            'quantite' => $q,
            'stock_avant' => $avant,
            'stock_apres' => $apres,
            'reference_type' => $refType,
            'reference_id' => $refId,
            'libelle' => $libelle,
            'date_mouvement' => $date,
            'user_id' => Auth::id(),
        ]);

        $produit->update(['stock_actuel' => $apres]);

        return $mouvement;
    }

    public function genererNumeroMouvement(int $societeId, string $type): string
    {
        $prefix = match ($type) {
            MouvementStock::TYPE_SORTIE => 'BS',
            MouvementStock::TYPE_INVENTAIRE => 'INV',
            MouvementStock::TYPE_AJUSTEMENT => 'AJ',
            default => 'BE',
        };
        $year = now()->format('Y');
        $last = MouvementStock::parSociete($societeId)
            ->where('numero', 'like', "{$prefix}-{$year}-%")
            ->orderByDesc('id')
            ->value('numero');
        $seq = 1;
        if ($last && preg_match('/-(\d+)$/', $last, $m)) {
            $seq = (int) $m[1] + 1;
        }

        return sprintf('%s-%s-%04d', $prefix, $year, $seq);
    }
}
