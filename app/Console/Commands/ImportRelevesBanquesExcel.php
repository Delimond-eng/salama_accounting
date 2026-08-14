<?php

namespace App\Console\Commands;

use App\Models\Ecriture;
use App\Models\Journal;
use App\Models\LigneEcriture;
use App\Models\PlanComptable;
use App\Models\Tiers;
use App\Services\SaisieComptableService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

class ImportRelevesBanquesExcel extends Command
{
    protected $signature = 'banque:import-releves-excel
                            {--dry : Simule sans rien enregistrer}
                            {--brouillon : Enregistre en brouillon au lieu de valider}
                            {--banque= : equity|rawbank|sofibanque|tmb (toutes si omis)}
                            {--devise= : USD|CDF|EUR (toutes si omis)}
                            {--no-purge : Ne pas supprimer les écritures banque existantes}';

    protected $description = 'Purge puis réimporte les relevés Excel Equity / Rawbank / Sofibanque / TMB (soldes d\'ouverture inclus)';

    private int $societeId = 1;

    private int $exerciceId = 1;

    /** @var array<string,int> */
    private array $journalIds = [];

    /** @var array<string,int|null> */
    private array $tiersCache = [];

    private array $comptesBanque = [
        '521010' => 'EQUITY BCDC - USD',
        '521011' => 'EQUITY BCDC / CDF',
        '521012' => 'EQUITY BCDC - EUR',
        '522020' => 'RAWBANK USD',
        '522021' => 'RAWBANK CDF',
        '522022' => 'RAWBANK EUR',
        '523030' => 'TMB USD',
        '524040' => 'SOFIBANQUE USD',
        '524042' => 'SOFIBANQUE EUR',
        '57006' => 'CAISSE EN MONNAIE ETRANGERE EUR',
    ];

    private array $comptesAnnexes = [
        '521002' => "Compte d'attente de la banque",
        '585000' => 'Transferts de fonds',
        '631800' => 'Autres frais bancaires',
        '658101' => 'INDEMNITES & AUTRES IVAN YSEBOOT',
        '632500' => 'Frais juridiques et de contentieux',
        '445400' => 'TVA récupérable sur les services externes et autres dépenses',
        '447110' => 'DGI - IRPP',
        '447210' => 'CONTRIBUTION ONEM',
        '447310' => 'COTISATION INPP',
        '447410' => 'COTISATION CNSS',
        '442200' => 'Taxes pour les autorités publiques',
        '622200' => "Location d'immeubles",
        '674500' => 'Intérêts bancaires et de financement (escompte, etc.)',
        '628100' => 'Frais de téléphone',
        '638800' => 'Charges externes diverses',
        '471100' => 'Débiteurs divers',
        '401108' => 'BRICO KIN',
        '411100' => 'Clients',
    ];

    private array $journauxDef = [
        'BQ10' => ['libelle' => 'EQUITY-BCDC USD', 'compte' => '521010', 'devise' => 'USD', 'ordre' => 10],
        'BQ11' => ['libelle' => 'EQUITY-BCDC CDF', 'compte' => '521011', 'devise' => 'CDF', 'ordre' => 11],
        'BQ12' => ['libelle' => 'EQUITY-BCDC EUR', 'compte' => '521012', 'devise' => 'EUR', 'ordre' => 12],
        'BQ20' => ['libelle' => 'RAWBANK USD', 'compte' => '522020', 'devise' => 'USD', 'ordre' => 20],
        'BQ21' => ['libelle' => 'RAWBANK CDF', 'compte' => '522021', 'devise' => 'CDF', 'ordre' => 21],
        'BQ22' => ['libelle' => 'RAWBANK EUR', 'compte' => '522022', 'devise' => 'EUR', 'ordre' => 22],
        'BQ30' => ['libelle' => 'TMB USD', 'compte' => '523030', 'devise' => 'USD', 'ordre' => 30],
        'BQ40' => ['libelle' => 'SOFIBANQUE USD', 'compte' => '524040', 'devise' => 'USD', 'ordre' => 40],
        'BQ42' => ['libelle' => 'SOFIBANQUE EUR', 'compte' => '524042', 'devise' => 'EUR', 'ordre' => 42],
    ];

    public function handle(SaisieComptableService $saisie): int
    {
        $dry = (bool) $this->option('dry');
        $valider = ! (bool) $this->option('brouillon');
        $filtreBanque = $this->option('banque') ? strtolower(trim((string) $this->option('banque'))) : null;
        $filtreDevise = $this->option('devise') ? strtoupper(trim((string) $this->option('devise'))) : null;
        $purge = ! (bool) $this->option('no-purge');

        if (! $dry) {
            $this->ensureReferentiels();
            if ($purge) {
                $this->purgeEcrituresBanque($filtreBanque, $filtreDevise);
            }
        } else {
            $this->loadJournalIds();
            $this->warn('Mode dry : aucune suppression / écriture.');
        }

        $sources = $this->sources();
        if ($filtreBanque) {
            $sources = array_values(array_filter($sources, fn ($s) => $s['banque'] === $filtreBanque));
            if (! $sources) {
                $this->error("Banque inconnue : {$filtreBanque}");

                return self::FAILURE;
            }
        }
        if ($filtreDevise) {
            if (! in_array($filtreDevise, ['USD', 'CDF', 'EUR'], true)) {
                $this->error("Devise inconnue : {$filtreDevise}");

                return self::FAILURE;
            }
            $sources = array_values(array_filter($sources, fn ($s) => $s['devise'] === $filtreDevise));
            if (! $sources) {
                $this->error("Aucune source pour la devise {$filtreDevise}".($filtreBanque ? " / banque {$filtreBanque}" : ''));

                return self::FAILURE;
            }
        }

        $created = 0;
        $skipped = 0;
        $errors = [];
        $parJournal = [];
        $parCompte = [];
        $soldesFin = [];

        foreach ($sources as $src) {
            if (! is_file($src['file'])) {
                $errors[] = 'Fichier introuvable : '.$src['file'];
                continue;
            }

            $this->info('--- '.$src['banque'].' / '.$src['devise'].' / '.basename($src['file']).' ---');
            $parsed = $this->lireFeuille($src);
            $mouvements = $parsed['mouvements'];
            $ouverture = $parsed['ouverture'];

            $this->line(sprintf(
                '  Ouverture : %s | mouvements : %d | clôture Excel : %s',
                $ouverture !== null ? number_format($ouverture, 2, '.', ' ') : 'n/a',
                count($mouvements),
                $parsed['cloture'] !== null ? number_format($parsed['cloture'], 2, '.', ' ') : 'n/a'
            ));

            if ($ouverture !== null && abs($ouverture) >= 0.005) {
                $parJournal[$src['journal']] = ($parJournal[$src['journal']] ?? 0) + 1;
                $parCompte['521002'] = ($parCompte['521002'] ?? 0) + 1;

                if (! $dry) {
                    try {
                        $this->enregistrerOuverture($saisie, $src, $ouverture, $valider);
                        $created++;
                    } catch (Throwable $e) {
                        $errors[] = "OUVERTURE {$src['journal']} : ".$e->getMessage();
                    }
                }
            }

            foreach ($mouvements as $idx => $mvt) {
                $montant = $mvt['debit'] > 0 ? $mvt['debit'] : $mvt['credit'];
                $sens = $mvt['debit'] > 0 ? 'debit' : 'credit';
                if ($montant < 0.005) {
                    continue;
                }

                $contrepartie = $this->resoudreContrepartie($mvt['libelle'], $sens);
                $parJournal[$src['journal']] = ($parJournal[$src['journal']] ?? 0) + 1;
                $parCompte[$contrepartie] = ($parCompte[$contrepartie] ?? 0) + 1;
                $ref = $this->referenceExterne($src['banque'], $src['devise'], $mvt, $idx);

                if ($dry) {
                    $this->line(sprintf(
                        '  %s | %s %s | %s → %s | %s',
                        $mvt['date'],
                        $sens === 'debit' ? 'D' : 'C',
                        number_format($montant, 2, '.', ' '),
                        $src['devise'],
                        $contrepartie,
                        mb_substr($mvt['libelle'], 0, 70)
                    ));
                    continue;
                }

                $journalId = $this->journalIds[$src['journal']] ?? null;
                if (! $journalId) {
                    $errors[] = "{$ref} : journal {$src['journal']} absent";
                    continue;
                }

                if (Ecriture::where('societe_id', $this->societeId)->where('reference_externe', $ref)->exists()) {
                    $skipped++;
                    continue;
                }

                try {
                    foreach ([$src['compte'], $contrepartie] as $num) {
                        $saisie->resolveCompte($this->societeId, $num);
                    }

                    $tiersId = $this->tiersPourCompte($contrepartie, $mvt['libelle']);
                    $lignes = $this->construireLignes($src['compte'], $contrepartie, $mvt['libelle'], $montant, $sens, $tiersId);

                    $saisie->enregistrer($this->societeId, [
                        'exercice_id' => $this->exerciceId,
                        'journal_id' => $journalId,
                        'date_ecriture' => $mvt['date'],
                        'date_piece' => $mvt['date'],
                        'libelle' => mb_substr($mvt['libelle'], 0, 255),
                        'type_ecriture' => 'normale',
                        'reference_externe' => $ref,
                        'devise' => $src['devise'],
                    ], $lignes, $valider);

                    $created++;
                } catch (Throwable $e) {
                    $errors[] = "{$ref} (".mb_substr($mvt['libelle'], 0, 60).') : '.$e->getMessage();
                }
            }

            if (! $dry) {
                $soldeCompta = $this->soldeCompteBanque($src['compte'], $src['devise']);
                $soldesFin[$src['journal']] = [
                    'compte' => $src['compte'],
                    'devise' => $src['devise'],
                    'excel' => $parsed['cloture'],
                    'compta' => $soldeCompta,
                ];
            }
        }

        $this->newLine();
        $this->info($dry ? '=== SIMULATION RELEVÉS BANQUES ===' : '=== IMPORT RELEVÉS BANQUES ===');
        if (! $dry) {
            $this->line("Créées : {$created} | déjà présentes : {$skipped}");
        }

        $this->newLine();
        $this->line('--- Par journal ---');
        ksort($parJournal);
        foreach ($parJournal as $code => $n) {
            $this->line("  {$code} : {$n}");
        }

        $this->newLine();
        $this->line('--- Par compte de contrepartie ---');
        ksort($parCompte);
        foreach ($parCompte as $compte => $n) {
            $this->line(sprintf('  %s : %d', $compte, $n));
        }

        if ($soldesFin) {
            $this->newLine();
            $this->line('--- Contrôle soldes fin (Excel vs compta) ---');
            foreach ($soldesFin as $code => $s) {
                $excel = $s['excel'];
                $delta = $excel === null ? null : round($s['compta'] - $excel, 2);
                $ok = $delta !== null && abs($delta) < 0.05;
                $this->line(sprintf(
                    '  %s (%s %s) : Excel=%s | Compta=%s | Δ=%s %s',
                    $code,
                    $s['compte'],
                    $s['devise'],
                    $excel === null ? 'n/a' : number_format($excel, 2, '.', ' '),
                    number_format($s['compta'], 2, '.', ' '),
                    $delta === null ? 'n/a' : number_format($delta, 2, '.', ' '),
                    $ok ? 'OK' : 'ÉCART'
                ));
            }
        }

        if ($errors) {
            $this->newLine();
            $this->error('Erreurs ('.count($errors).') :');
            foreach (array_slice($errors, 0, 50) as $e) {
                $this->line('  - '.$e);
            }
            if (count($errors) > 50) {
                $this->line('  … '.(count($errors) - 50).' autres');
            }

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /** @return list<array{banque:string,file:string,sheet:?string,devise:string,journal:string,compte:string,layout:string}> */
    private function sources(): array
    {
        return [
            [
                'banque' => 'equity',
                'file' => base_path('BDD/EQUITY/Releve_EquityBCDC_ELECTRO_COOL_USD (1).xlsx'),
                'sheet' => null,
                'devise' => 'USD',
                'journal' => 'BQ10',
                'compte' => '521010',
                'layout' => 'equity',
            ],
            [
                'banque' => 'equity',
                'file' => base_path('BDD/EQUITY/Releve_EquityBCDC_ELECTRO_COOL_CDF (2).xlsx'),
                'sheet' => null,
                'devise' => 'CDF',
                'journal' => 'BQ11',
                'compte' => '521011',
                'layout' => 'equity',
            ],
            [
                'banque' => 'equity',
                'file' => base_path('BDD/EQUITY/Releve_EquityBCDC_ELECTRO_COOL_EUR.xlsx'),
                'sheet' => null,
                'devise' => 'EUR',
                'journal' => 'BQ12',
                'compte' => '521012',
                'layout' => 'equity',
            ],
            [
                'banque' => 'rawbank',
                'file' => base_path('BDD/RAWBANK/Releve_RAWBANK_ELECTRO_COOL_USD_officiel.xlsx'),
                'sheet' => null,
                'devise' => 'USD',
                'journal' => 'BQ20',
                'compte' => '522020',
                'layout' => 'officiel',
            ],
            [
                'banque' => 'rawbank',
                'file' => base_path('BDD/RAWBANK/Releve_RAWBANK_ELECTRO_COOL_CDF_officiel.xlsx'),
                'sheet' => null,
                'devise' => 'CDF',
                'journal' => 'BQ21',
                'compte' => '522021',
                'layout' => 'officiel',
            ],
            [
                'banque' => 'rawbank',
                'file' => base_path('BDD/RAWBANK/Releve_RAWBANK_ELECTRO_COOL_EUR_officiel.xlsx'),
                'sheet' => null,
                'devise' => 'EUR',
                'journal' => 'BQ22',
                'compte' => '522022',
                'layout' => 'officiel',
            ],
            [
                'banque' => 'sofibanque',
                'file' => base_path('BDD/SOFIBANQUE/Releve_SOFIBANQUE_ELECTRO_COOL_USD_officiel.xlsx'),
                'sheet' => null,
                'devise' => 'USD',
                'journal' => 'BQ40',
                'compte' => '524040',
                'layout' => 'officiel',
            ],
            [
                'banque' => 'sofibanque',
                'file' => base_path('BDD/SOFIBANQUE/Releve_SOFIBANQUE_ELECTRO_COOL_EUR_officiel.xlsx'),
                'sheet' => null,
                'devise' => 'EUR',
                'journal' => 'BQ42',
                'compte' => '524042',
                'layout' => 'officiel',
            ],
            [
                'banque' => 'tmb',
                'file' => base_path('BDD/TMB/Releve_TMB_ELECTRO_COOL_USD_officiel (1).xlsx'),
                'sheet' => null,
                'devise' => 'USD',
                'journal' => 'BQ30',
                'compte' => '523030',
                'layout' => 'officiel',
            ],
        ];
    }

    private function purgeEcrituresBanque(?string $filtreBanque, ?string $filtreDevise = null): void
    {
        $codes = array_keys($this->journauxDef);
        if ($filtreBanque) {
            $map = [
                'equity' => ['BQ10', 'BQ11', 'BQ12'],
                'rawbank' => ['BQ20', 'BQ21', 'BQ22'],
                'sofibanque' => ['BQ40', 'BQ42'],
                'tmb' => ['BQ30'],
            ];
            $codes = $map[$filtreBanque] ?? $codes;
        }

        if ($filtreDevise) {
            $codes = array_values(array_filter($codes, function ($code) use ($filtreDevise) {
                $meta = $this->journauxDef[$code] ?? null;

                return $meta && $meta['devise'] === $filtreDevise;
            }));
        }

        if ($codes === []) {
            $this->warn('Aucun journal correspondant à purger.');

            return;
        }

        $journalIds = Journal::where('societe_id', $this->societeId)
            ->whereIn('code', $codes)
            ->pluck('id');

        if ($journalIds->isEmpty()) {
            $this->warn('Aucun journal banque à purger.');

            return;
        }

        $ecritureIds = Ecriture::withTrashed()
            ->where('societe_id', $this->societeId)
            ->whereIn('journal_id', $journalIds)
            ->pluck('id');

        $nb = $ecritureIds->count();
        if ($nb === 0) {
            $this->line('Aucune écriture banque à supprimer.');

            return;
        }

        DB::transaction(function () use ($ecritureIds, $journalIds) {
            DB::table('paiements')->whereIn('ecriture_id', $ecritureIds)->update(['ecriture_id' => null]);
            LigneEcriture::whereIn('ecriture_id', $ecritureIds)->delete();
            Ecriture::withTrashed()->whereIn('id', $ecritureIds)->forceDelete();

            $extra = Ecriture::withTrashed()->whereIn('journal_id', $journalIds)->pluck('id');
            if ($extra->isNotEmpty()) {
                LigneEcriture::whereIn('ecriture_id', $extra)->delete();
                Ecriture::withTrashed()->whereIn('id', $extra)->forceDelete();
            }
        });

        $this->warn('Purge : '.$nb.' écriture(s) banque ('.implode(', ', $codes).') supprimée(s).');
    }

    private function ensureReferentiels(): void
    {
        foreach ($this->comptesBanque + $this->comptesAnnexes as $num => $libelle) {
            $num = (string) $num;
            if (PlanComptable::parSociete($this->societeId)->where('num_compte', $num)->exists()) {
                continue;
            }

            $classe = (int) substr($num, 0, 1);
            PlanComptable::create([
                'societe_id' => $this->societeId,
                'num_compte' => $num,
                'libelle' => $libelle,
                'classe' => $classe,
                'niveau' => strlen($num),
                'type_compte' => $classe <= 5 ? 'bilan' : 'gestion',
                'type_compte_detail' => str_starts_with($num, '52') ? 'BANQUE' : null,
                'sens_normal' => in_array($classe, [1, 2, 3, 5, 6], true) ? 'debiteur' : 'crediteur',
                'categorie_bilan' => 'non_applicable',
                'est_compte_detail' => true,
                'est_compte_tiers' => str_starts_with($num, '401') || str_starts_with($num, '411'),
                'est_lettrable' => str_starts_with($num, '401') || str_starts_with($num, '411'),
                'est_rapprochable' => str_starts_with($num, '52'),
                'actif' => true,
            ]);
            $this->line("Compte créé : {$num} — {$libelle}");
        }

        foreach ($this->journauxDef as $code => $meta) {
            $journal = Journal::firstOrCreate(
                ['societe_id' => $this->societeId, 'code' => $code],
                [
                    'libelle' => $meta['libelle'],
                    'type' => 'banque',
                    'compte_contrepartie' => $meta['compte'],
                    'devise_defaut' => $meta['devise'],
                    'format_numerotation' => 'annuel',
                    'padding_numero' => 5,
                    'prochain_numero' => 1,
                    'actif' => true,
                    'ordre_affichage' => $meta['ordre'],
                ]
            );

            $dirty = false;
            foreach ([
                'compte_contrepartie' => $meta['compte'],
                'devise_defaut' => $meta['devise'],
                'type' => 'banque',
                'libelle' => $meta['libelle'],
                'actif' => true,
            ] as $field => $value) {
                if ($journal->{$field} != $value) {
                    $journal->{$field} = $value;
                    $dirty = true;
                }
            }
            if ($dirty) {
                $journal->save();
                $this->line("Journal mis à jour : {$code} → {$meta['compte']} ({$meta['devise']})");
            }

            $this->journalIds[$code] = $journal->id;
        }

        $this->ensureJournauxEuroAnnexes();
        $this->ensureTauxEur();
    }

    private function ensureJournauxEuroAnnexes(): void
    {
        $extras = [
            'CA-EUR' => [
                'libelle' => 'JOURNAL CAISSE EUR',
                'type' => 'caisse',
                'compte' => '57006',
                'devise' => 'EUR',
                'ordre' => 26,
            ],
            'OD-EUR' => [
                'libelle' => 'OPERATIONS DIVERSES - EUR',
                'type' => 'operations_diverses',
                'compte' => null,
                'devise' => 'EUR',
                'ordre' => 36,
            ],
        ];

        foreach ($extras as $code => $meta) {
            $journal = Journal::firstOrCreate(
                ['societe_id' => $this->societeId, 'code' => $code],
                [
                    'libelle' => $meta['libelle'],
                    'type' => $meta['type'],
                    'compte_contrepartie' => $meta['compte'],
                    'devise_defaut' => $meta['devise'],
                    'format_numerotation' => 'annuel',
                    'padding_numero' => 5,
                    'prochain_numero' => 1,
                    'actif' => true,
                    'ordre_affichage' => $meta['ordre'],
                ]
            );

            $dirty = false;
            foreach ([
                'libelle' => $meta['libelle'],
                'type' => $meta['type'],
                'compte_contrepartie' => $meta['compte'],
                'devise_defaut' => $meta['devise'],
                'actif' => true,
            ] as $field => $value) {
                if ($journal->{$field} != $value) {
                    $journal->{$field} = $value;
                    $dirty = true;
                }
            }
            if ($dirty) {
                $journal->save();
                $this->line("Journal mis à jour : {$code} ({$meta['devise']})");
            } elseif ($journal->wasRecentlyCreated) {
                $this->line("Journal créé : {$code} ({$meta['devise']})");
            }
        }

        // Aligner aussi les contreparties caisse USD/CDF si vides.
        $caisseAlign = [
            'CA-USD' => '57004',
            'CA-CDF' => '57005',
        ];
        foreach ($caisseAlign as $code => $compte) {
            $j = Journal::where('societe_id', $this->societeId)->where('code', $code)->first();
            if ($j && empty($j->compte_contrepartie)) {
                $j->update(['compte_contrepartie' => $compte]);
                $this->line("Journal {$code} : contrepartie {$compte}");
            }
        }
    }

    /**
     * Complète les taux EUR manquants à partir des taux USD (ratio ≈ 1,15).
     */
    private function ensureTauxEur(): void
    {
        $usdRows = DB::table('taux_change')
            ->where('societe_id', $this->societeId)
            ->where('devise_code', 'USD')
            ->where('date_taux', '>=', '2026-01-01')
            ->orderBy('date_taux')
            ->get(['date_taux', 'taux']);

        if ($usdRows->isEmpty()) {
            return;
        }

        $created = 0;
        foreach ($usdRows as $row) {
            $exists = DB::table('taux_change')
                ->where('societe_id', $this->societeId)
                ->where('devise_code', 'EUR')
                ->where('date_taux', $row->date_taux)
                ->exists();
            if ($exists) {
                continue;
            }

            $tauxEur = round((float) $row->taux * 1.15, 6);
            DB::table('taux_change')->insert([
                'societe_id' => $this->societeId,
                'devise_code' => 'EUR',
                'date_taux' => $row->date_taux,
                'taux' => $tauxEur,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $created++;
        }

        if ($created > 0) {
            $this->line("Taux EUR complétés : {$created} cotation(s) (≈ USD × 1,15).");
        }
    }

    private function loadJournalIds(): void
    {
        $this->journalIds = Journal::where('societe_id', $this->societeId)
            ->whereIn('code', array_keys($this->journauxDef))
            ->pluck('id', 'code')
            ->toArray();
    }

    /**
     * @param  array{file:string,sheet:?string,layout:string,devise:string}  $src
     * @return array{ouverture:?float,cloture:?float,mouvements:list<array{date:string,libelle:string,debit:float,credit:float}>}
     */
    private function lireFeuille(array $src): array
    {
        $ss = IOFactory::load($src['file']);
        $sheet = $src['sheet'] ? $ss->getSheetByName($src['sheet']) : $ss->getSheet(0);
        if (! $sheet) {
            throw new \RuntimeException('Onglet introuvable dans '.basename($src['file']));
        }

        if ($src['layout'] === 'equity') {
            return $this->parseEquity($sheet);
        }

        return $this->parseOfficiel($sheet);
    }

    private function parseEquity($sheet): array
    {
        $ouverture = $this->toFloat($sheet->getCell('F3')->getCalculatedValue());
        $cloture = $this->toFloat($sheet->getCell('F4')->getCalculatedValue());

        $mouvements = [];
        $highest = $sheet->getHighestRow();
        for ($r = 11; $r <= $highest; $r++) {
            $libelle = $this->cellText($sheet->getCellByColumnAndRow(3, $r)->getValue());
            $libelle = trim(preg_replace('/\s+/u', ' ', $libelle) ?? '');
            if ($libelle === '' || stripos($libelle, 'Total') === 0 || stripos($libelle, 'Contrôle') === 0 || stripos($libelle, 'Note') === 0 || stripos($libelle, 'Remarque') === 0) {
                continue;
            }

            $date = $this->parseDate(
                $sheet->getCellByColumnAndRow(1, $r)->getValue(),
                $sheet->getCellByColumnAndRow(1, $r)->getFormattedValue()
            );
            if (! $date) {
                continue;
            }

            $debit = $this->toFloat($sheet->getCellByColumnAndRow(5, $r)->getCalculatedValue());
            $credit = $this->toFloat($sheet->getCellByColumnAndRow(6, $r)->getCalculatedValue());
            if ($debit < 0.005 && $credit < 0.005) {
                continue;
            }

            $mouvements[] = [
                'date' => $date,
                'libelle' => $libelle,
                'debit' => round($debit, 2),
                'credit' => round($credit, 2),
            ];
        }

        return compact('ouverture', 'cloture', 'mouvements');
    }

    private function parseOfficiel($sheet): array
    {
        $ouvertureHeader = $this->parseSoldeHeader($this->cellText($sheet->getCell('B8')->getValue()).' '.$this->cellText($sheet->getCell('B8')->getFormattedValue()));
        // Softibanque / Rawbank : Period Opening Balance is B8; Closing F8 or B/F
        $ouvertureHeaderAlt = $this->parseSoldeHeader($this->cellText($sheet->getCell('F8')->getFormattedValue()));
        // Actually opening is B8, closing F8 for officiel layout
        $clotureHeader = $this->parseSoldeHeader(
            $this->cellText($sheet->getCell('F8')->getValue()).' '.$this->cellText($sheet->getCell('F8')->getFormattedValue())
        );

        $ouverture = null;
        $cloture = $clotureHeader;
        $mouvements = [];
        $prevSolde = null;
        $highest = $sheet->getHighestRow();

        for ($r = 11; $r <= $highest; $r++) {
            $libelle = $this->cellText($sheet->getCellByColumnAndRow(3, $r)->getValue());
            $libelle = trim(preg_replace('/\s+/u', ' ', $libelle) ?? '');
            if ($libelle === '' || stripos($libelle, 'Total') === 0 || stripos($libelle, 'Contrôle') === 0) {
                continue;
            }

            $soldeCell = $this->toFloat($sheet->getCellByColumnAndRow(6, $r)->getCalculatedValue());
            $sensFlag = strtoupper(trim($this->cellText($sheet->getCellByColumnAndRow(7, $r)->getValue())));
            $soldeSigne = $this->soldeSigne($soldeCell, $sensFlag);

            if ($this->estReportSolde($libelle)) {
                $ouverture = $soldeSigne;
                $prevSolde = $ouverture;
                continue;
            }

            $date = $this->parseDate(
                $sheet->getCellByColumnAndRow(1, $r)->getValue(),
                $sheet->getCellByColumnAndRow(1, $r)->getFormattedValue()
            );
            if (! $date) {
                continue;
            }

            $debit = $this->toFloat($sheet->getCellByColumnAndRow(4, $r)->getCalculatedValue());
            $credit = $this->toFloat($sheet->getCellByColumnAndRow(5, $r)->getCalculatedValue());

            // Capture de solde TMB (sans débit/crédit mais avec saut de solde)
            if ($debit < 0.005 && $credit < 0.005) {
                if ($this->estCaptureSolde($libelle) && $prevSolde !== null) {
                    $delta = round($soldeSigne - $prevSolde, 2);
                    if (abs($delta) >= 0.005) {
                        $mouvements[] = [
                            'date' => $date,
                            'libelle' => $libelle,
                            'debit' => $delta < 0 ? abs($delta) : 0.0,
                            'credit' => $delta > 0 ? $delta : 0.0,
                        ];
                    }
                    $prevSolde = $soldeSigne;
                    $cloture = $soldeSigne;
                }
                continue;
            }

            $mouvements[] = [
                'date' => $date,
                'libelle' => $libelle,
                'debit' => round($debit, 2),
                'credit' => round($credit, 2),
            ];

            $prevSolde = $soldeSigne;
            $cloture = $soldeSigne;
        }

        if ($ouverture === null) {
            $ouverture = $ouvertureHeader;
            // Softibanque header "97 593,15 DR"
            $rawB8 = strtoupper($this->cellText($sheet->getCell('B8')->getValue()).' '.$this->cellText($sheet->getCell('B8')->getFormattedValue()));
            if ($ouverture !== null && str_contains($rawB8, 'DR') && $ouverture > 0) {
                $ouverture = -$ouverture;
            }
        }

        if ($cloture === null) {
            $cloture = $clotureHeader;
            $rawF8 = strtoupper($this->cellText($sheet->getCell('F8')->getValue()).' '.$this->cellText($sheet->getCell('F8')->getFormattedValue()));
            if ($cloture !== null && str_contains($rawF8, 'DR') && $cloture > 0) {
                $cloture = -$cloture;
            }
        }

        // Softibanque : forcer signe découvert si header DR
        $rawB8 = strtoupper($this->cellText($sheet->getCell('B8')->getValue()).' '.$this->cellText($sheet->getCell('B8')->getFormattedValue()));
        $rawF8 = strtoupper($this->cellText($sheet->getCell('F8')->getValue()).' '.$this->cellText($sheet->getCell('F8')->getFormattedValue()));
        if (str_contains($rawB8, 'DR') && $ouverture !== null && $ouverture > 0) {
            $ouverture = -$ouverture;
        }
        if (str_contains($rawF8, 'DR') && $cloture !== null && $cloture > 0) {
            $cloture = -$cloture;
        }

        return compact('ouverture', 'cloture', 'mouvements');
    }

    private function parseSoldeHeader(string $raw): ?float
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }
        // "97 593,15 DR" / "-41 767,79" / "552 264,54"
        if (preg_match('/-?[0-9]+(?:[ \x{00A0}]?[0-9]{3})*(?:[.,][0-9]+)?/u', $raw, $m)) {
            $n = $this->toFloat($m[0]);
            if (str_contains(strtoupper($raw), 'DR') && $n > 0) {
                return -$n;
            }

            return $n;
        }

        return $this->toFloat($raw) ?: null;
    }

    private function enregistrerOuverture(SaisieComptableService $saisie, array $src, float $ouverture, bool $valider): void
    {
        $ref = sprintf('REL/%s/%s/OUV/%s', strtoupper($src['banque']), $src['devise'], str_replace('-', '', '20260101'));
        if (Ecriture::where('societe_id', $this->societeId)->where('reference_externe', $ref)->exists()) {
            return;
        }

        $date = '2026-01-01';
        $montant = abs($ouverture);
        $libelle = 'Solde d\'ouverture '.$src['banque'].' '.$src['devise'];

        // Solde positif (avoir) : D banque / C 521002
        // Solde négatif (découvert) : D 521002 / C banque
        if ($ouverture >= 0) {
            $lignes = [
                ['num_compte' => $src['compte'], 'libelle' => $libelle, 'debit' => $montant, 'credit' => 0],
                ['num_compte' => '521002', 'libelle' => $libelle, 'debit' => 0, 'credit' => $montant],
            ];
        } else {
            $lignes = [
                ['num_compte' => '521002', 'libelle' => $libelle, 'debit' => $montant, 'credit' => 0],
                ['num_compte' => $src['compte'], 'libelle' => $libelle, 'debit' => 0, 'credit' => $montant],
            ];
        }

        $saisie->enregistrer($this->societeId, [
            'exercice_id' => $this->exerciceId,
            'journal_id' => $this->journalIds[$src['journal']],
            'date_ecriture' => $date,
            'date_piece' => $date,
            'libelle' => $libelle,
            'type_ecriture' => 'ouverture',
            'reference_externe' => $ref,
            'devise' => $src['devise'],
        ], $lignes, $valider);
    }

    private function soldeCompteBanque(string $numCompte, string $devise): float
    {
        $rows = DB::table('lignes_ecritures as l')
            ->join('ecritures as e', 'e.id', '=', 'l.ecriture_id')
            ->where('l.num_compte', $numCompte)
            ->where('e.societe_id', $this->societeId)
            ->whereNull('e.deleted_at')
            ->where('e.devise', $devise)
            ->selectRaw('COALESCE(SUM(l.debit),0) - COALESCE(SUM(l.credit),0) as solde')
            ->value('solde');

        return round((float) $rows, 2);
    }

    private function cellText(mixed $value): string
    {
        if ($value instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
            return $value->getPlainText();
        }

        return (string) ($value ?? '');
    }

    private function toFloat(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }
        if (is_numeric($value)) {
            return (float) $value;
        }
        $s = (string) $value;
        $s = str_replace(["\xc2\xa0", ' '], '', $s);
        $s = str_replace(',', '.', $s);
        $s = preg_replace('/[^0-9.\-]/', '', $s) ?? '';

        return is_numeric($s) ? (float) $s : 0.0;
    }

    private function parseDate(mixed $raw, mixed $formatted): ?string
    {
        if ($raw instanceof \DateTimeInterface) {
            return Carbon::instance(\DateTimeImmutable::createFromInterface($raw))->format('Y-m-d');
        }

        if (is_numeric($raw) && (float) $raw > 20000 && (float) $raw < 80000) {
            try {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $raw))->format('Y-m-d');
            } catch (Throwable) {
            }
        }

        foreach ([trim((string) $formatted), trim((string) $raw)] as $s) {
            if ($s === '') {
                continue;
            }
            foreach (['d-m-Y', 'd/m/Y', 'Y-m-d', 'd-m-y', 'd/m/y'] as $fmt) {
                try {
                    $dt = Carbon::createFromFormat($fmt, $s);
                    if ($dt !== false) {
                        return $dt->format('Y-m-d');
                    }
                } catch (Throwable) {
                }
            }
            try {
                return Carbon::parse($s)->format('Y-m-d');
            } catch (Throwable) {
            }
        }

        return null;
    }

    private function normalize(string $s): string
    {
        $s = mb_strtoupper($s);

        return strtr($s, [
            'É' => 'E', 'È' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'À' => 'A', 'Â' => 'A',
            'Î' => 'I', 'Ï' => 'I', 'Ô' => 'O', 'Û' => 'U', 'Ù' => 'U', 'Ç' => 'C',
            '°' => ' ',
        ]);
    }

    private function soldeSigne(float $solde, string $sensFlag): float
    {
        $sensFlag = strtoupper(trim($sensFlag));
        if ($sensFlag === 'DR') {
            return -abs($solde);
        }
        if ($sensFlag === 'CR') {
            return abs($solde);
        }

        return $solde;
    }

    private function estReportSolde(string $libelle): bool
    {
        $l = $this->normalize($libelle);

        return str_contains($l, 'BALANCE B/F') || str_contains($l, 'REPORT DE SOLDE');
    }

    private function estCaptureSolde(string $libelle): bool
    {
        $l = $this->normalize($libelle);

        return str_contains($l, 'CAPTURE') && (str_contains($l, 'SOLDE') || str_contains($l, 'BALANCE'));
    }

    private function resoudreContrepartie(string $libelle, string $sens): string
    {
        $l = $this->normalize($libelle);

        // Yvan / Ivan Yseboot
        if (
            str_contains($l, 'YSEBOOT')
            || preg_match('/\bYVAN\b/', $l)
            || preg_match('/\bIVAN\b/', $l)
            || str_contains($l, 'APPRO COMPTE PRIV')
            || str_contains($l, 'APPRO PRIV')
        ) {
            return '658101';
        }

        if (str_contains($l, 'MIESSI')) {
            return '632500';
        }

        // Frais bancaires (avant RETRAIT : « FRAIS SUR RETRAIT » = frais, pas transfert)
        if (
            str_contains($l, 'FEE FOREX') || str_contains($l, 'FRAIS') || str_contains($l, 'FTC')
            || str_contains($l, 'COMMISSION') || str_contains($l, 'SOUSC') || str_contains($l, 'ALERT')
            || str_contains($l, 'CHEQUIER') || str_contains($l, 'CARNET DE CHEQUE')
            || str_contains($l, 'TENUE DE COMPTE') || str_contains($l, 'GESTION DE COMPTE')
            || str_contains($l, 'CHARGES DE GESTION') || str_contains($l, 'MAINTENANCE')
            || str_contains($l, 'FRAISEVENTTYPECHARGE') || str_contains($l, 'COMPTAGE')
            || str_contains($l, 'FOREX') || str_contains($l, 'RECOUVREMENT')
            || str_contains($l, 'FRAISBANCAIRE') || str_contains($l, 'CCION') || str_contains($l, 'CION')
            || str_contains($l, 'MONTANT DU CALCUL DE RETOUR')
            || str_contains($l, 'RSC SUR')
            || preg_match('/\bFR\b/', $l) || str_contains($l, 'FR VIR') || str_contains($l, 'FR $')
        ) {
            return '631800';
        }

        // Retrait chèque / espèces
        if (
            str_contains($l, 'RETRAIT')
            || str_contains($l, 'NR CHEQUE')
            || str_contains($l, 'PMT CHQ')
            || str_contains($l, 'PYT CHQ')
            || str_contains($l, 'CHQ/')
            || preg_match('/\bCHQ\b/', $l)
            || str_contains($l, 'RETRAIT EN ESPECES')
            || str_contains($l, 'RETRAIT PAR CHEQUE')
            || str_contains($l, 'RETRAIT DEPLACE')
        ) {
            return '585000';
        }

        // Cotisations sociales / impôts (Equity CDF)
        if (str_contains($l, 'CNSS')) {
            return '447410';
        }
        if (str_contains($l, 'ONEM')) {
            return '447210';
        }
        if (str_contains($l, 'INPP')) {
            return '447310';
        }
        if (str_contains($l, 'IPRIER') || str_contains($l, 'CDI-') || str_contains($l, 'DPE1') || str_contains($l, 'DGI')) {
            return '447110';
        }

        // TVA
        if (
            preg_match('/\bT\.?V\.?A\b/', $l)
            || str_contains($l, 'FRAISEVENTTYPETAX')
            || preg_match('/\bTAX\b/', $l)
            || str_contains($l, 'TVA COLLECTEE')
            || str_contains($l, 'PYT RCC')
            || str_contains($l, 'RCC FCT')
        ) {
            return '445400';
        }

        if (str_contains($l, 'INTERET') || str_contains($l, 'CAPITALISATION')) {
            return '674500';
        }

        if (str_contains($l, 'VRT LOGI: 2026')) {
            return '447110';
        }

        if (str_contains($l, 'VILLE DE KINSHASA')) {
            return '442200';
        }

        if (str_contains($l, 'LOYER')) {
            return '622200';
        }

        if (str_contains($l, 'BRICOKIN') || str_contains($l, 'BRICO KIN') || str_contains($l, 'BRICO')) {
            return '401108';
        }

        if (str_contains($l, 'INTERNET') || str_contains($l, 'SMS VOICE') || str_contains($l, 'SUBCRIPTION')) {
            return '628100';
        }

        if (str_contains($l, 'MASTER')) {
            return '471100';
        }

        // Capture de solde / ajustement banque
        if (str_contains($l, 'CAPTURE') && (str_contains($l, 'SOLDE') || str_contains($l, 'BALANCE'))) {
            return '521002';
        }

        // Mouvements de fonds / change / appro interne
        if (
            str_contains($l, 'APPRO COMPTE') || str_contains($l, 'APPROV COMPTE')
            || str_contains($l, 'AAPRO COMPTE') || str_contains($l, 'APPROVISIONNEMENT')
            || str_contains($l, 'APPRO CPTE') || str_contains($l, 'VIREMENT INTERNE')
            || str_contains($l, 'RECUP') || str_contains($l, 'ACHAT/VENTE DEVISES')
            || str_contains($l, 'AC XFR') || str_contains($l, 'XFR FROM')
            || (str_contains($l, 'CHANGE') && ! str_contains($l, 'CHARGE'))
        ) {
            return '585000';
        }

        // Entrées clients : rattacher au compte collectif du tiers si le libellé le nomme
        if ($sens === 'credit') {
            $compteClient = $this->compteClientDepuisLibelle($l);
            if ($compteClient) {
                return $compteClient;
            }

            // Ne pas gonfler 411100 générique : attente identification
            return '471100';
        }

        // TRANSFERT sortant restant
        if (str_contains($l, 'TRANSFERT')) {
            return '585000';
        }

        return '638800';
    }

    /** @return list<array<string,mixed>> */
    private function construireLignes(
        string $compteBanque,
        string $contrepartie,
        string $libelle,
        float $montant,
        string $sens,
        ?int $tiersId
    ): array {
        $lib = mb_substr($libelle, 0, 255);

        if ($sens === 'debit') {
            return [
                array_filter([
                    'num_compte' => $contrepartie,
                    'libelle' => $lib,
                    'debit' => $montant,
                    'credit' => 0,
                    'tiers_id' => $tiersId,
                ], fn ($v) => $v !== null),
                [
                    'num_compte' => $compteBanque,
                    'libelle' => $lib,
                    'debit' => 0,
                    'credit' => $montant,
                ],
            ];
        }

        return [
            [
                'num_compte' => $compteBanque,
                'libelle' => $lib,
                'debit' => $montant,
                'credit' => 0,
            ],
            array_filter([
                'num_compte' => $contrepartie,
                'libelle' => $lib,
                'debit' => 0,
                'credit' => $montant,
                'tiers_id' => $tiersId,
            ], fn ($v) => $v !== null),
        ];
    }

    private function tiersPourCompte(string $compte, string $libelle): ?int
    {
        if (str_starts_with($compte, '411')) {
            return $this->tiersClient($compte);
        }

        if (! str_starts_with($compte, '4') || str_starts_with($compte, '44') || str_starts_with($compte, '47')) {
            if (! str_starts_with($compte, '401')) {
                return $this->tiersSiExige($compte, $libelle);
            }
        }

        if (! str_starts_with($compte, '401')) {
            return null;
        }

        $cacheKey = $compte;
        if (isset($this->tiersCache[$cacheKey])) {
            return $this->tiersCache[$cacheKey];
        }

        $tiers = Tiers::where('societe_id', $this->societeId)
            ->where(function ($q) use ($compte) {
                $q->where('num_compte_collectif', $compte)
                    ->orWhere('code', 'T-'.$compte)
                    ->orWhere('nom', 'like', '%BRICO%');
            })
            ->first();

        if (! $tiers) {
            $nom = $compte === '401108' ? 'BRICO KIN' : 'FOURNISSEUR '.$compte;
            $tiers = Tiers::create([
                'societe_id' => $this->societeId,
                'code' => 'T-'.$compte.'-'.substr(uniqid(), -4),
                'nom' => $nom,
                'type' => 'fournisseur',
                'num_compte_collectif' => $compte,
                'actif' => true,
            ]);
        } elseif ($tiers->num_compte_collectif !== $compte) {
            $tiers->update(['num_compte_collectif' => $compte]);
        }

        return $this->tiersCache[$cacheKey] = $tiers->id;
    }

    private function tiersClient(string $compte): int
    {
        $cacheKey = 'CLI|'.$compte;
        if (isset($this->tiersCache[$cacheKey])) {
            return (int) $this->tiersCache[$cacheKey];
        }

        // Compte client précis → tiers déjà lié à ce collectif
        if ($compte !== '411100') {
            $existant = Tiers::where('societe_id', $this->societeId)
                ->where('num_compte_collectif', $compte)
                ->whereNull('deleted_at')
                ->orderBy('id')
                ->first();
            if ($existant) {
                return $this->tiersCache[$cacheKey] = $existant->id;
            }
        }

        $tiers = Tiers::firstOrCreate(
            ['societe_id' => $this->societeId, 'nom' => 'CLIENTS DIVERS BANQUE'],
            [
                'code' => 'T-CLI-BQ',
                'type' => 'client',
                'num_compte_collectif' => $compte,
                'actif' => true,
            ]
        );

        if ($tiers->num_compte_collectif !== $compte && $compte === '411100') {
            $tiers->update(['num_compte_collectif' => $compte]);
        }

        return $this->tiersCache[$cacheKey] = $tiers->id;
    }

    /**
     * Retrouve le compte client 411xxx si le libellé bancaire contient le nom d'un tiers.
     */
    private function compteClientDepuisLibelle(string $libelleUpper): ?string
    {
        static $clients = null;
        if ($clients === null) {
            $clients = Tiers::where('societe_id', $this->societeId)
                ->where('num_compte_collectif', 'like', '411%')
                ->where('num_compte_collectif', '<>', '411100')
                ->whereNull('deleted_at')
                ->orderByRaw('LENGTH(nom) DESC')
                ->get(['nom', 'num_compte_collectif']);
        }

        $norm = $this->normaliserLibelle($libelleUpper);
        foreach ($clients as $t) {
            $nom = trim((string) $t->nom);
            if (mb_strlen($nom) < 4) {
                continue;
            }
            $nomN = $this->normaliserLibelle($nom);
            if ($nomN !== '' && str_contains($norm, $nomN)) {
                return $t->num_compte_collectif;
            }
            // Premier mot significatif (ex. COTEX, UTEXAFRICA)
            $parts = preg_split('/\s+/', $nomN) ?: [];
            $alias = $parts[0] ?? '';
            if (mb_strlen($alias) >= 5 && preg_match('/\b'.preg_quote($alias, '/').'\b/', $norm)) {
                return $t->num_compte_collectif;
            }
        }

        return null;
    }

    private function normaliserLibelle(string $s): string
    {
        $s = mb_strtoupper($s);
        $s = strtr($s, [
            'É' => 'E', 'È' => 'E', 'Ê' => 'E', 'À' => 'A', 'Â' => 'A',
            'Ô' => 'O', 'Ù' => 'U', 'Û' => 'U', 'Ç' => 'C', 'Ï' => 'I',
        ]);

        return trim((string) preg_replace('/[^A-Z0-9]+/', ' ', $s));
    }

    private function tiersSiExige(string $compte, string $libelle): ?int
    {
        $pc = PlanComptable::parSociete($this->societeId)->where('num_compte', $compte)->first();
        if (! $pc || ! $pc->est_compte_tiers) {
            return null;
        }

        $cacheKey = $compte.'|GEN';
        if (isset($this->tiersCache[$cacheKey])) {
            return $this->tiersCache[$cacheKey];
        }

        $nom = match (true) {
            str_starts_with($compte, '445') => 'TVA BANQUE',
            str_starts_with($compte, '447') => 'ETAT / ORGANISMES',
            str_starts_with($compte, '442') => 'VILLE DE KINSHASA',
            str_starts_with($compte, '471') => 'DEBITTEURS DIVERS BANQUE',
            default => mb_substr($libelle, 0, 80),
        };

        $tiers = Tiers::firstOrCreate(
            ['societe_id' => $this->societeId, 'nom' => $nom, 'num_compte_collectif' => $compte],
            [
                'code' => 'T-'.$compte.'-'.substr(md5($nom), 0, 4),
                'type' => 'autre',
                'actif' => true,
            ]
        );

        return $this->tiersCache[$cacheKey] = $tiers->id;
    }

    /** @param  array{date:string,libelle:string,debit:float,credit:float}  $mvt */
    private function referenceExterne(string $banque, string $devise, array $mvt, int $seq = 0): string
    {
        $sens = $mvt['debit'] > 0 ? 'D' : 'C';
        $montant = $mvt['debit'] > 0 ? $mvt['debit'] : $mvt['credit'];
        $hash = substr(md5($this->normalize($mvt['libelle']).'|'.$sens.'|'.number_format($montant, 2, '.', '').'|'.$seq), 0, 10);

        return sprintf('REL/%s/%s/%s/%s', strtoupper($banque), $devise, str_replace('-', '', $mvt['date']), $hash);
    }
}
