<?php

namespace App\Services;

use App\Exceptions\BilanDesequilibreException;
use App\Models\Ecriture;
use App\Models\Exercice;
use App\Models\Journal;
use App\Models\Societe;
use App\Support\ParametreSysteme;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ExerciceComptableService
{
    public function __construct(
        protected LivresComptablesService $livres,
        protected SaisieComptableService $saisie,
        protected BilanComptableService $bilan
    ) {}

    public function exerciceCourant(int $societeId): ?Exercice
    {
        return Exercice::where('societe_id', $societeId)->where('est_courant', true)->first();
    }

    /** @return Collection<int, Exercice> */
    public function lister(int $societeId): Collection
    {
        return Exercice::where('societe_id', $societeId)
            ->orderByDesc('date_debut')
            ->get()
            ->map(function (Exercice $ex) use ($societeId) {
                $ex->setAttribute('nb_ecritures', Ecriture::where('societe_id', $societeId)
                    ->where('exercice_id', $ex->id)
                    ->where('statut', 'validee')
                    ->count());
                $ex->setAttribute('nb_brouillons', Ecriture::where('societe_id', $societeId)
                    ->where('exercice_id', $ex->id)
                    ->where('statut', 'brouillon')
                    ->count());

                return $ex;
            });
    }

    public function controlesCloture(int $societeId, int $exerciceId, ?string $dateFin = null): array
    {
        $exercice = $this->findExercice($societeId, $exerciceId);
        $dateFin = $dateFin ?: $exercice->date_fin->format('Y-m-d');

        $brouillons = Ecriture::where('societe_id', $societeId)
            ->where('exercice_id', $exercice->id)
            ->where('statut', 'brouillon')
            ->count();

        $resultatNet = $this->bilan->resultatNetExercice($societeId, $exercice, $dateFin);

        $erreurs = [];
        $avertissements = [];

        if ($exercice->statut === 'cloture' || $exercice->statut === 'archive') {
            $erreurs[] = 'Cet exercice est déjà clôturé ou archivé.';
        }

        if ($brouillons > 0) {
            $erreurs[] = "{$brouillons} écriture(s) en brouillon doivent être validées ou supprimées avant la clôture.";
        }

        if (Ecriture::where('societe_id', $societeId)
            ->where('exercice_id', $exercice->id)
            ->where('type_ecriture', 'cloture')
            ->where('statut', 'validee')
            ->exists()) {
            $avertissements[] = 'Une écriture de clôture existe déjà pour cet exercice.';
        }

        $bilanOk = true;
        try {
            $this->bilan->generer($societeId, $exercice, $dateFin);
        } catch (BilanDesequilibreException $e) {
            $bilanOk = false;
            $erreurs[] = 'Bilan déséquilibré : '.$e->getMessage();
        }

        return [
            'exercice' => $exercice,
            'date_fin' => $dateFin,
            'brouillons' => $brouillons,
            'resultat_net' => round($resultatNet, 2),
            'bilan_equilibre' => $bilanOk,
            'erreurs' => $erreurs,
            'avertissements' => $avertissements,
            'pret' => $erreurs === [],
        ];
    }

    public function controlesMensuels(int $societeId, int $exerciceId, int $annee, int $mois): array
    {
        $exercice = $this->findExercice($societeId, $exerciceId);
        $debut = Carbon::create($annee, $mois, 1)->startOfMonth();
        $fin = $debut->copy()->endOfMonth();

        if ($debut->lt($exercice->date_debut) || $fin->gt($exercice->date_fin)) {
            throw new InvalidArgumentException('Le mois sélectionné est hors de la période de l\'exercice.');
        }

        $brouillons = Ecriture::where('societe_id', $societeId)
            ->where('exercice_id', $exercice->id)
            ->where('statut', 'brouillon')
            ->whereBetween('date_ecriture', [$debut->toDateString(), $fin->toDateString()])
            ->count();

        $validees = Ecriture::where('societe_id', $societeId)
            ->where('exercice_id', $exercice->id)
            ->where('statut', 'validee')
            ->whereBetween('date_ecriture', [$debut->toDateString(), $fin->toDateString()])
            ->count();

        $erreurs = [];
        if ($brouillons > 0) {
            $erreurs[] = "{$brouillons} brouillon(s) sur la période.";
        }
        if (! $exercice->accepteEcritures()) {
            $erreurs[] = 'L\'exercice n\'accepte plus de saisie.';
        }

        return [
            'periode' => $debut->translatedFormat('F Y'),
            'date_debut' => $debut->toDateString(),
            'date_fin' => $fin->toDateString(),
            'ecritures_validees' => $validees,
            'brouillons' => $brouillons,
            'erreurs' => $erreurs,
            'pret' => $erreurs === [],
            'message' => $erreurs === []
                ? 'Contrôles mensuels OK — vous pouvez poursuivre la clôture annuelle lorsque l\'exercice est achevé.'
                : null,
        ];
    }

    public function passerPreCloture(int $societeId, int $exerciceId): Exercice
    {
        $controles = $this->controlesCloture($societeId, $exerciceId);
        if (! $controles['pret']) {
            throw new InvalidArgumentException(implode(' ', $controles['erreurs']));
        }

        $exercice = $controles['exercice'];
        $exercice->update(['statut' => 'pre_cloture']);

        return $exercice->fresh();
    }

    public function cloturerExercice(int $societeId, int $exerciceId, ?string $notes = null): array
    {
        return DB::transaction(function () use ($societeId, $exerciceId, $notes) {
            $exercice = $this->findExercice($societeId, $exerciceId);

            if ($exercice->statut === 'cloture') {
                throw new InvalidArgumentException('Exercice déjà clôturé.');
            }

            if ($exercice->statut !== 'pre_cloture') {
                $this->passerPreCloture($societeId, $exerciceId);
                $exercice->refresh();
            }

            $controles = $this->controlesCloture($societeId, $exerciceId);
            if (! $controles['pret']) {
                throw new InvalidArgumentException(implode(' ', $controles['erreurs']));
            }

            $ecritureCloture = null;
            if (! Ecriture::where('societe_id', $societeId)
                ->where('exercice_id', $exercice->id)
                ->where('type_ecriture', 'cloture')
                ->where('statut', 'validee')
                ->exists()) {
                $ecritureCloture = $this->genererEcritureClotureResultat($societeId, $exercice);
            }

            $exercice->update([
                'statut' => 'cloture',
                'date_cloture' => now()->toDateString(),
                'cloture_par' => Auth::id(),
                'notes_cloture' => $notes,
                'est_courant' => false,
            ]);

            return [
                'exercice' => $exercice->fresh(),
                'ecriture_cloture' => $ecritureCloture,
                'resultat_net' => $controles['resultat_net'],
            ];
        });
    }

    public function creerExerciceSuivant(int $societeId, int $exerciceSourceId, bool $definirCourant = true): Exercice
    {
        return DB::transaction(function () use ($societeId, $exerciceSourceId, $definirCourant) {
            $source = $this->findExercice($societeId, $exerciceSourceId);

            if ($source->statut !== 'cloture' && $source->statut !== 'archive') {
                throw new InvalidArgumentException('L\'exercice source doit être clôturé avant d\'ouvrir le suivant.');
            }

            $anneeSuivante = (int) $source->annee + 1;
            if (Exercice::where('societe_id', $societeId)->where('annee', $anneeSuivante)->exists()) {
                throw new InvalidArgumentException("Un exercice existe déjà pour l'année {$anneeSuivante}.");
            }

            $dateDebut = $source->date_fin->copy()->addDay();
            $dateFin = $dateDebut->copy()->addYear()->subDay();

            if ($definirCourant) {
                Exercice::where('societe_id', $societeId)->update(['est_courant' => false]);
            }

            return Exercice::create([
                'societe_id' => $societeId,
                'libelle' => 'Exercice '.$anneeSuivante,
                'annee' => $anneeSuivante,
                'date_debut' => $dateDebut,
                'date_fin' => $dateFin,
                'statut' => 'ouvert',
                'est_courant' => $definirCourant,
                'date_ouverture' => now()->toDateString(),
                'report_a_nouveau_genere' => false,
                'bilan_ouverture_genere' => false,
            ]);
        });
    }

    public function genererBilanOuverture(int $societeId, int $exerciceCibleId, ?int $exerciceSourceId = null): array
    {
        return DB::transaction(function () use ($societeId, $exerciceCibleId, $exerciceSourceId) {
            $cible = $this->findExercice($societeId, $exerciceCibleId);

            if ($cible->bilan_ouverture_genere) {
                throw new InvalidArgumentException('Le bilan d\'ouverture a déjà été généré pour cet exercice.');
            }

            if (! $cible->accepteEcritures('ouverture')) {
                throw new InvalidArgumentException('L\'exercice cible n\'accepte pas les écritures d\'ouverture.');
            }

            $source = $exerciceSourceId
                ? $this->findExercice($societeId, $exerciceSourceId)
                : $this->exercicePrecedent($societeId, $cible);

            if (! $source || ! in_array($source->statut, ['cloture', 'archive'], true)) {
                throw new InvalidArgumentException('Aucun exercice clôturé précédent trouvé.');
            }

            if (Ecriture::where('societe_id', $societeId)
                ->where('exercice_id', $cible->id)
                ->where('type_ecriture', 'ouverture')
                ->exists()) {
                throw new InvalidArgumentException('Des écritures d\'ouverture existent déjà sur cet exercice.');
            }

            $lignes = $this->lignesBilanOuverture($societeId, $source);
            if ($lignes === []) {
                throw new InvalidArgumentException('Aucun solde à reporter pour le bilan d\'ouverture.');
            }

            $journal = $this->journalParCode($societeId, 'AN');
            $result = $this->saisie->enregistrer($societeId, [
                'exercice_id' => $cible->id,
                'journal_id' => $journal->id,
                'date_ecriture' => $cible->date_debut->format('Y-m-d'),
                'libelle' => 'Bilan d\'ouverture — reprise '.$source->libelle,
                'type_ecriture' => 'ouverture',
                'reference_externe' => 'BO-'.$source->annee,
            ], $lignes, true);

            $cible->update(['bilan_ouverture_genere' => true]);

            return [
                'exercice' => $cible->fresh(),
                'exercice_source' => $source,
                'ecriture' => $result['ecriture'],
                'nb_lignes' => count($lignes),
            ];
        });
    }

    public function genererReportANouveau(int $societeId, int $exerciceId): array
    {
        return DB::transaction(function () use ($societeId, $exerciceId) {
            $exercice = $this->findExercice($societeId, $exerciceId);

            if ($exercice->report_a_nouveau_genere) {
                throw new InvalidArgumentException('Le report à nouveau a déjà été généré.');
            }

            if (! $exercice->accepteEcritures('ouverture')) {
                throw new InvalidArgumentException('L\'exercice n\'accepte pas les écritures d\'ouverture.');
            }

            $source = $this->exercicePrecedent($societeId, $exercice);
            if (! $source) {
                throw new InvalidArgumentException('Exercice précédent introuvable.');
            }

            $compteBenefice = $this->compteParam('compte_resultat_benefice', '131000');
            $comptePerte = $this->compteParam('compte_resultat_perte', '139000');
            $compteRanB = $this->compteParam('compte_report_nouveau_B', '121000');
            $compteRanP = $this->compteParam('compte_report_nouveau_P', '129000');

            $soldes = $this->soldesFinParCompte($societeId, $source);
            $lignes = [];
            $libelle = 'Report à nouveau — '.$source->libelle;

            $solde131 = $this->soldeNetCompte($soldes, $compteBenefice);
            if (abs($solde131) >= 0.01) {
                if ($solde131 > 0) {
                    $lignes[] = ['num_compte' => $compteBenefice, 'libelle' => $libelle, 'debit' => $solde131, 'credit' => 0];
                    $lignes[] = ['num_compte' => $compteRanB, 'libelle' => $libelle, 'debit' => 0, 'credit' => $solde131];
                } else {
                    $montant = abs($solde131);
                    $lignes[] = ['num_compte' => $compteBenefice, 'libelle' => $libelle, 'debit' => 0, 'credit' => $montant];
                    $lignes[] = ['num_compte' => $compteRanP, 'libelle' => $libelle, 'debit' => $montant, 'credit' => 0];
                }
            }

            $solde139 = $this->soldeNetCompte($soldes, $comptePerte);
            if (abs($solde139) >= 0.01) {
                if ($solde139 < 0) {
                    $montant = abs($solde139);
                    $lignes[] = ['num_compte' => $comptePerte, 'libelle' => $libelle, 'debit' => $montant, 'credit' => 0];
                    $lignes[] = ['num_compte' => $compteRanP, 'libelle' => $libelle, 'debit' => 0, 'credit' => $montant];
                } else {
                    $lignes[] = ['num_compte' => $comptePerte, 'libelle' => $libelle, 'debit' => 0, 'credit' => $solde139];
                    $lignes[] = ['num_compte' => $compteRanB, 'libelle' => $libelle, 'debit' => $solde139, 'credit' => 0];
                }
            }

            if ($lignes === []) {
                throw new InvalidArgumentException('Aucun solde sur les comptes de résultat (131/139) à reporter.');
            }

            $journal = $this->journalParCode($societeId, 'AN');
            $result = $this->saisie->enregistrer($societeId, [
                'exercice_id' => $exercice->id,
                'journal_id' => $journal->id,
                'date_ecriture' => $exercice->date_debut->format('Y-m-d'),
                'libelle' => $libelle,
                'type_ecriture' => 'ouverture',
                'reference_externe' => 'RAN-'.$source->annee,
            ], $lignes, true);

            $exercice->update(['report_a_nouveau_genere' => true]);

            return [
                'exercice' => $exercice->fresh(),
                'ecriture' => $result['ecriture'],
                'nb_lignes' => count($lignes),
            ];
        });
    }

    public function definirExerciceCourant(int $societeId, int $exerciceId): Exercice
    {
        return DB::transaction(function () use ($societeId, $exerciceId) {
            $exercice = $this->findExercice($societeId, $exerciceId);
            Exercice::where('societe_id', $societeId)->update(['est_courant' => false]);
            $exercice->update(['est_courant' => true]);

            return $exercice->fresh();
        });
    }

    protected function genererEcritureClotureResultat(int $societeId, Exercice $exercice): Ecriture
    {
        $lignes = [];
        $libelle = 'Clôture exercice '.$exercice->libelle;
        $date = $exercice->date_fin->format('Y-m-d');

        foreach ([6, 7] as $classe) {
            foreach ($this->soldesFinParCompte($societeId, $exercice, $classe) as $row) {
                $net = round((float) $row['solde_fin_debiteur'] - (float) $row['solde_fin_crediteur'], 2);
                if (abs($net) < 0.01) {
                    continue;
                }

                if ($classe === 6) {
                    if ($net > 0) {
                        $lignes[] = ['num_compte' => $row['num_compte'], 'libelle' => $libelle, 'debit' => 0, 'credit' => $net];
                    } else {
                        $lignes[] = ['num_compte' => $row['num_compte'], 'libelle' => $libelle, 'debit' => abs($net), 'credit' => 0];
                    }
                } else {
                    if ($net < 0) {
                        $lignes[] = ['num_compte' => $row['num_compte'], 'libelle' => $libelle, 'debit' => abs($net), 'credit' => 0];
                    } else {
                        $lignes[] = ['num_compte' => $row['num_compte'], 'libelle' => $libelle, 'debit' => 0, 'credit' => $net];
                    }
                }
            }
        }

        $totaux = $this->saisie->calculerTotaux($lignes);
        $soldeResultat = round($totaux['debit'] - $totaux['credit'], 2);

        if (abs($soldeResultat) < 0.01 && $lignes === []) {
            $resultatNet = $this->bilan->resultatNetExercice($societeId, $exercice, $date);
            $soldeResultat = round(-$resultatNet, 2);
        }

        if (abs($soldeResultat) >= 0.01) {
            if ($soldeResultat > 0) {
                $compte = $this->compteParam('compte_resultat_benefice', '131000');
                $lignes[] = ['num_compte' => $compte, 'libelle' => $libelle, 'debit' => 0, 'credit' => $soldeResultat];
            } else {
                $compte = $this->compteParam('compte_resultat_perte', '139000');
                $lignes[] = ['num_compte' => $compte, 'libelle' => $libelle, 'debit' => abs($soldeResultat), 'credit' => 0];
            }
        }

        if ($lignes === []) {
            throw new InvalidArgumentException('Aucune ligne de clôture à générer (comptes 6 et 7 déjà soldés).');
        }

        $journal = $this->journalParCode($societeId, 'CL');

        return $this->saisie->enregistrer($societeId, [
            'exercice_id' => $exercice->id,
            'journal_id' => $journal->id,
            'date_ecriture' => $date,
            'libelle' => $libelle,
            'type_ecriture' => 'cloture',
            'reference_externe' => 'CL-'.$exercice->annee,
        ], $lignes, true)['ecriture'];
    }

    /** @return array<int, array<string, mixed>> */
    protected function lignesBilanOuverture(int $societeId, Exercice $source): array
    {
        $lignes = [];
        $libelle = 'Reprise solde — '.$source->libelle;

        foreach ($this->soldesFinParCompte($societeId, $source) as $row) {
            $classe = (int) substr((string) $row['num_compte'], 0, 1);
            if ($classe < 1 || $classe > 5) {
                continue;
            }

            $net = round((float) $row['solde_fin_debiteur'] - (float) $row['solde_fin_crediteur'], 2);
            if (abs($net) < 0.01) {
                continue;
            }

            if ($net > 0) {
                $lignes[] = ['num_compte' => $row['num_compte'], 'libelle' => $libelle, 'debit' => $net, 'credit' => 0];
            } else {
                $lignes[] = ['num_compte' => $row['num_compte'], 'libelle' => $libelle, 'debit' => 0, 'credit' => abs($net)];
            }
        }

        return $lignes;
    }

    /** @return array<int, array<string, mixed>> */
    protected function soldesFinParCompte(int $societeId, Exercice $exercice, ?int $classe = null): array
    {
        $balance = $this->livres->balanceGenerale(
            $societeId,
            $exercice->id,
            $exercice->date_debut->format('Y-m-d'),
            $exercice->date_fin->format('Y-m-d'),
            Societe::find($societeId)?->devise_principale ?? 'CDF',
            'origine',
            $classe
        );

        return $balance['lignes']->all();
    }

    protected function soldeNetCompte(array $soldes, string $prefixe): float
    {
        $prefixe = preg_replace('/\D/', '', $prefixe) ?: $prefixe;
        $total = 0.0;
        foreach ($soldes as $row) {
            $num = preg_replace('/\D/', '', (string) $row['num_compte']);
            if (! str_starts_with($num, $prefixe)) {
                continue;
            }
            $total += (float) $row['solde_fin_debiteur'] - (float) $row['solde_fin_crediteur'];
        }

        return round($total, 2);
    }

    protected function exercicePrecedent(int $societeId, Exercice $cible): ?Exercice
    {
        return Exercice::where('societe_id', $societeId)
            ->where('date_fin', '<', $cible->date_debut)
            ->orderByDesc('date_fin')
            ->first();
    }

    protected function journalParCode(int $societeId, string $code): Journal
    {
        $journal = Journal::where('societe_id', $societeId)->where('code', $code)->where('actif', true)->first();
        if (! $journal) {
            throw new InvalidArgumentException("Journal {$code} introuvable ou inactif pour cette société.");
        }

        return $journal;
    }

    protected function compteParam(string $cle, string $defaut): string
    {
        $raw = (string) ParametreSysteme::get($cle, $defaut);
        $digits = preg_replace('/\D/', '', $raw) ?: $defaut;

        return str_pad($digits, 6, '0', STR_PAD_RIGHT);
    }

    protected function findExercice(int $societeId, int $exerciceId): Exercice
    {
        return Exercice::where('societe_id', $societeId)->findOrFail($exerciceId);
    }
}
