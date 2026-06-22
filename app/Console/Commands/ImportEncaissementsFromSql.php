<?php

namespace App\Console\Commands;

use App\Models\Ecriture;
use App\Models\Journal;
use App\Models\PlanComptable;
use App\Models\SectionAnalytique;
use App\Models\Tiers;
use App\Services\SaisieComptableService;
use App\Support\SqlDumpReader;
use Illuminate\Console\Command;

class ImportEncaissementsFromSql extends Command
{
    protected $signature = 'encaissements:import-sql
                            {--file= : Chemin du dump SQL (défaut : BDD/skkvjute_compta-electrocool(3).sql)}
                            {--dry : Simule sans rien enregistrer}
                            {--brouillon : Enregistre en brouillon au lieu de valider}';

    protected $description = 'Importe les encaissements bancaires (BQ10/BQ20/BQ30…) depuis le dump SQL comptable';

    private int $societeId = 1;

    private int $exerciceId = 1;

    /** @var array<int,string> id journal source => code */
    private array $journalSrcCodes = [];

    /** @var array<string,int> code journal => id cible */
    private array $journalTargetIds = [];

    /** @var array<int,array{nom:string,collectif:string}> */
    private array $tiersSrc = [];

    /** @var array<int,string> id section source => code */
    private array $sectionsSrc = [];

    /** @var array<string,int> code section => id cible */
    private array $sectionIds = [];

    /** @var array<string,int|null> num_compte => tiers_id cible */
    private array $tiersCache = [];

    /** Journaux banque encaissements dans le dump. */
    private array $journalCodesEnc = ['BQ10', 'BQ11', 'BQ20', 'BQ21', 'BQ30', 'BQ40'];

    public function handle(SaisieComptableService $saisie): int
    {
        $dry = (bool) $this->option('dry');
        $valider = ! (bool) $this->option('brouillon');
        $path = $this->option('file') ?: base_path('BDD/skkvjute_compta-electrocool(4).sql');

        if (! is_file($path)) {
            $this->error("Fichier introuvable : {$path}");

            return self::FAILURE;
        }

        $reader = new SqlDumpReader($path);
        $this->chargerMappings($reader);
        $donnees = $this->extraireEncaissements($reader);

        $created = 0;
        $skipped = 0;
        $errors = [];
        $parJournal = [];
        $totUsd = 0.0;
        $totCdf = 0.0;

        foreach ($donnees as $ec) {
            $code = $this->journalSrcCodes[$ec['journal_id_src']] ?? null;
            $journalId = $code ? ($this->journalTargetIds[$code] ?? null) : null;
            if (! $journalId) {
                $errors[] = "Écriture source {$ec['id_src']} : journal inconnu (id {$ec['journal_id_src']})";
                continue;
            }

            $ref = 'DUMP-ENC/'.$ec['id_src'];
            $parJournal[$code] = ($parJournal[$code] ?? 0) + 1;

            if ($ec['devise'] === 'USD') {
                $totUsd += $ec['total'];
            } else {
                $totCdf += $ec['total'];
            }

            if ($dry) {
                continue;
            }

            if (Ecriture::where('societe_id', $this->societeId)->where('reference_externe', $ref)->exists()) {
                $skipped++;
                continue;
            }

            $lignes = [];
            foreach ($ec['lignes'] as $l) {
                $num = $l['num_compte'];
                if (! PlanComptable::where('societe_id', $this->societeId)->where('num_compte', $num)->exists()) {
                    $errors[] = "{$ref} : compte {$num} absent du plan comptable";
                    continue 2;
                }

                $tiersId = null;
                if (str_starts_with($num, '4')) {
                    $tiersId = $this->resoudreTiers($num, $l['tiers_id_src']);
                    if (! $tiersId) {
                        $errors[] = "{$ref} : tiers introuvable pour {$num}";
                        continue 2;
                    }
                }

                $sectionId = $this->resoudreSection($l['section_src']);

                $lignes[] = array_filter([
                    'num_compte' => $num,
                    'libelle' => $l['libelle'],
                    'debit' => $l['debit'],
                    'credit' => $l['credit'],
                    'tiers_id' => $tiersId,
                    'section_analytique_id' => $sectionId,
                ], fn ($v) => $v !== null);
            }

            if (count($lignes) < 2) {
                $errors[] = "{$ref} : moins de 2 lignes comptables";
                continue;
            }

            try {
                $saisie->enregistrer($this->societeId, [
                    'exercice_id' => $this->exerciceId,
                    'journal_id' => $journalId,
                    'date_ecriture' => $ec['date'],
                    'date_piece' => $ec['date'],
                    'libelle' => $ec['libelle'],
                    'type_ecriture' => 'normale',
                    'reference_externe' => $ref,
                    'reference_facture' => $ec['ref_facture'] ?: null,
                    'devise' => $ec['devise'],
                    'taux_change' => $ec['taux'],
                ], $lignes, $valider);
                $created++;
            } catch (\Throwable $e) {
                $errors[] = "{$ref} ({$ec['libelle']}) : ".$e->getMessage();
            }
        }

        $this->info($dry ? '=== SIMULATION ENCAISSEMENTS ===' : '=== IMPORT ENCAISSEMENTS ===');
        $this->line('Source : '.$path);
        $this->line('Écritures encaissement : '.count($donnees));
        if (! $dry) {
            $this->line("Créées : {$created} | ignorées (déjà importées) : {$skipped}");
        }
        $this->line('Total USD : '.number_format($totUsd, 2).' $');
        $this->line('Total CDF : '.number_format($totCdf, 2).' FC');

        $this->newLine();
        $this->line('--- Par journal ---');
        ksort($parJournal);
        foreach ($parJournal as $code => $n) {
            $this->line("  {$code} : {$n}");
        }

        if ($errors) {
            $this->newLine();
            $this->error('Erreurs ('.count($errors).') :');
            foreach ($errors as $e) {
                $this->line('  - '.$e);
            }

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function chargerMappings(SqlDumpReader $reader): void
    {
        foreach ($reader->rows('journaux') as $row) {
            $f = $reader->fields($row);
            $this->journalSrcCodes[(int) $f[0]] = $reader->str($f, 2);
        }

        $this->journalTargetIds = Journal::where('societe_id', $this->societeId)
            ->whereIn('code', $this->journalCodesEnc)
            ->pluck('id', 'code')
            ->toArray();

        foreach ($reader->rows('tiers') as $row) {
            $f = $reader->fields($row);
            $this->tiersSrc[(int) $f[0]] = [
                'nom' => $reader->str($f, 3),
                'collectif' => $reader->str($f, 6),
            ];
        }

        foreach ($reader->rows('sections_analytiques') as $row) {
            $f = $reader->fields($row);
            $this->sectionsSrc[(int) $f[0]] = $reader->str($f, 2);
        }

        $this->sectionIds = SectionAnalytique::where('societe_id', $this->societeId)
            ->pluck('id', 'code')
            ->toArray();
    }

    /**
     * @return list<array{
     *   id_src:int, journal_id_src:int, date:string, libelle:string,
     *   ref_facture:?string, total:float, devise:string, taux:float,
     *   lignes:list<array{num_compte:string, tiers_id_src:?int, libelle:string, debit:float, credit:float, section_src:?int}>
     * }>
     */
    private function extraireEncaissements(SqlDumpReader $reader): array
    {
        $journalSrcIds = [];
        foreach ($this->journalSrcCodes as $id => $code) {
            if (in_array($code, $this->journalCodesEnc, true)) {
                $journalSrcIds[$id] = true;
            }
        }

        $ecritures = [];
        foreach ($reader->rows('ecritures') as $row) {
            $f = $reader->fields($row);
            $id = (int) $f[0];
            $jid = (int) $f[3];
            if (! isset($journalSrcIds[$jid])) {
                continue;
            }
            $lib = $reader->str($f, 10);
            if (! preg_match('/encaissement|avance facture/i', $lib)) {
                continue;
            }

            $ecritures[$id] = [
                'id_src' => $id,
                'journal_id_src' => $jid,
                'num_piece' => $reader->str($f, 4),
                'date' => $reader->str($f, 6),
                'libelle' => $lib,
                'ref_facture' => $reader->str($f, 14) ?: null,
                'total' => max($reader->float($f, 15), $reader->float($f, 16)),
                'devise' => $reader->str($f, 17) ?: 'USD',
                'taux' => $reader->float($f, 18) ?: 1.0,
                'lignes' => [],
            ];
        }

        foreach ($reader->rows('lignes_ecritures') as $row) {
            $f = $reader->fields($row);
            $eid = (int) $f[1];
            if (! isset($ecritures[$eid])) {
                continue;
            }
            $ecritures[$eid]['lignes'][] = [
                'num_compte' => $reader->str($f, 5),
                'tiers_id_src' => $reader->int($f, 7),
                'libelle' => $reader->str($f, 9),
                'debit' => $reader->float($f, 10),
                'credit' => $reader->float($f, 11),
                'section_src' => $reader->int($f, 21),
            ];
        }

        $result = array_values(array_filter($ecritures, fn ($e) => count($e['lignes']) >= 2));
        usort($result, fn ($a, $b) => [$a['date'], $a['id_src']] <=> [$b['date'], $b['id_src']]);

        return $result;
    }

    private function resoudreTiers(string $numCompte, ?int $tiersIdSrc): ?int
    {
        if (isset($this->tiersCache[$numCompte])) {
            return $this->tiersCache[$numCompte];
        }

        $tiers = Tiers::where('societe_id', $this->societeId)
            ->where('num_compte_collectif', $numCompte)
            ->value('id');

        if (! $tiers && $tiersIdSrc && isset($this->tiersSrc[$tiersIdSrc])) {
            $nom = $this->tiersSrc[$tiersIdSrc]['nom'];
            $tiers = Tiers::where('societe_id', $this->societeId)
                ->where('nom', $nom)
                ->value('id');
        }

        $this->tiersCache[$numCompte] = $tiers ? (int) $tiers : null;

        return $this->tiersCache[$numCompte];
    }

    private function resoudreSection(?int $sectionIdSrc): ?int
    {
        if (! $sectionIdSrc) {
            return null;
        }
        $code = $this->sectionsSrc[$sectionIdSrc] ?? null;
        if (! $code) {
            return null;
        }

        return $this->sectionIds[$code] ?? null;
    }
}
