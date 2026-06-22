<?php

namespace App\Console\Commands;

use App\Models\AxeAnalytique;
use App\Models\Ecriture;
use App\Models\PlanComptable;
use App\Models\SectionAnalytique;
use App\Models\Tiers;
use App\Services\SaisieComptableService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ImportVentesUsd2026 extends Command
{
    protected $signature = 'ventes:import-usd-2026 {--dry : Simule sans rien enregistrer} {--brouillon : Enregistre en brouillon au lieu de valider}';

    protected $description = 'Importe les ventes CLIM (706101) et ASCENSEUR (706102) en USD dans les journaux VT-CLIM-US / VT-ASC-USD (exercice 2026)';

    private int $societeId = 1;

    private int $exerciceId = 1;

    private int $journalClim = 22;   // VT-CLIM-US

    private int $journalAsc = 23;    // VT-ASC-USD

    private string $compteRevClim = '706101';   // VENTES SERVICES CLIM

    private string $compteRevAsc = '706102';    // VENTES SERVICES ASCENSEUR

    private string $compteTva = '443200';        // TVA facturé sur les services fournis

    /** @var array<string,int> code section => id */
    private array $sectionIds = [];

    /** @var array<string,int> num_compte => tiers_id */
    private array $tiersCache = [];

    /** Sections analytiques manquantes à créer : code => libellé (axe CHANTIERS = 1) */
    private array $sectionsACreer = [
        'DEVTRAD' => 'DEVIMCO-TRADITION',
        'MIDEMA' => 'MIDEMA',
        'MONUSCO' => 'MONUSCO',
        'EBATA' => 'EBATA',
    ];

    /** Tiers à forcer (compte => tiers_id) quand le rapprochement automatique échoue */
    private array $tiersOverride = [
        '411169' => 82, // IMMOKIN (tiers nom != libellé compte)
    ];

    public function handle(SaisieComptableService $saisie): int
    {
        $dry = (bool) $this->option('dry');
        $valider = ! (bool) $this->option('brouillon');

        // 1) Clients manquants -> on récupère/crée leur compte
        $compteJapan = $dry ? '411172?' : $this->ensureClient('JAPAN MOTORS');
        $compteEcole = $dry ? '411173?' : $this->ensureClient('ECOLE FRANCAISE');

        // 2) Sections analytiques manquantes
        if (! $dry) {
            $this->ensureSections();
        }
        $this->loadSectionIds();

        $rules = $this->rules($compteJapan, $compteEcole);
        $rows = $this->rows();

        $expected = [
            '2026-01' => ['ASC' => 18739.23, 'CLIM' => 42114.94],
            '2026-02' => ['ASC' => 21558.18, 'CLIM' => 141888.13],
            '2026-03' => ['ASC' => 15370.23, 'CLIM' => 233055.09],
            '2026-04' => ['ASC' => 9847.28, 'CLIM' => 46656.74],
            '2026-05' => ['ASC' => 61551.75, 'CLIM' => 41393.22],
        ];

        $sommes = [];
        $unresolvedClient = [];
        $unresolvedSection = [];
        $created = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $idx => $r) {
            [$date, $label, $refExt, $refFact, $total, $tva] = $r;
            $type = (strpos($refExt, '/DA/') !== false) ? 'ASC' : 'CLIM';
            $mois = substr($date, 0, 7);
            $sommes[$mois][$type] = ($sommes[$mois][$type] ?? 0) + $total;

            [$compte, $sectionCode] = $this->resolve($rules, $label);
            if (! $compte) {
                $unresolvedClient[$label] = true;
            }
            if ($sectionCode && ! isset($this->sectionIds[$sectionCode])) {
                $unresolvedSection[$sectionCode] = true;
            }

            if ($dry) {
                continue;
            }

            if (! $compte) {
                $errors[] = "Ligne {$refFact} : client introuvable pour « {$label} »";
                continue;
            }

            // Idempotence : ne pas recréer une facture déjà importée
            if (Ecriture::where('societe_id', $this->societeId)->where('reference_facture', $refFact)->exists()) {
                $skipped++;

                continue;
            }

            $ht = round($total - $tva, 2);
            $tiersId = $this->tiersId($compte);
            $journalId = $type === 'CLIM' ? $this->journalClim : $this->journalAsc;
            $compteRev = $type === 'CLIM' ? $this->compteRevClim : $this->compteRevAsc;
            $sectionId = $sectionCode ? ($this->sectionIds[$sectionCode] ?? null) : null;

            $lignes = [
                [ // Compte 1 : créance client (débit, TTC)
                    'num_compte' => $compte,
                    'libelle' => $label,
                    'debit' => $total,
                    'credit' => 0,
                    'tiers_id' => $tiersId,
                ],
                [ // Compte 2 : produit (crédit, HT) + analytique
                    'num_compte' => $compteRev,
                    'libelle' => $label,
                    'debit' => 0,
                    'credit' => $ht,
                    'tiers_id' => $tiersId,
                    'section_analytique_id' => $sectionId,
                ],
            ];

            if ($tva > 0) { // Compte 3 : TVA collectée (crédit)
                $lignes[] = [
                    'num_compte' => $this->compteTva,
                    'libelle' => $label,
                    'debit' => 0,
                    'credit' => round($tva, 2),
                    'tiers_id' => $tiersId,
                ];
            }

            try {
                $saisie->enregistrer($this->societeId, [
                    'exercice_id' => $this->exerciceId,
                    'journal_id' => $journalId,
                    'date_ecriture' => $date,
                    'date_piece' => $date,
                    'date_echeance' => Carbon::parse($date)->addMonth()->toDateString(),
                    'libelle' => $label,
                    'type_ecriture' => 'normale',
                    'reference_externe' => $refExt ?: null,
                    'reference_facture' => $refFact,
                    'devise' => 'USD',
                ], $lignes, $valider);
                $created++;
            } catch (\Throwable $e) {
                $errors[] = "Ligne {$refFact} : ".$e->getMessage();
            }
        }

        // ---- Rapport ----
        $this->info($dry ? '=== SIMULATION (dry-run) ===' : '=== IMPORT ===');
        $this->line('Lignes traitées : '.count($rows));
        if (! $dry) {
            $this->line("Écritures créées : {$created} | ignorées (déjà présentes) : {$skipped}");
        }

        $this->newLine();
        $this->line('--- Réconciliation mensuelle (TTC) ---');
        foreach ($sommes as $mois => $byType) {
            foreach (['CLIM', 'ASC'] as $t) {
                $val = round($byType[$t] ?? 0, 2);
                $exp = $expected[$mois][$t] ?? null;
                $flag = $exp === null ? '' : (abs($val - $exp) < 0.01 ? ' OK' : ' >>> ATTENDU '.$exp.' <<<');
                $this->line(sprintf('%s %-4s : %12s%s', $mois, $t, number_format($val, 2), $flag));
            }
        }

        if ($unresolvedClient) {
            $this->newLine();
            $this->warn('Clients NON résolus :');
            foreach (array_keys($unresolvedClient) as $c) {
                $this->line('  - '.$c);
            }
        }
        if ($unresolvedSection) {
            $this->newLine();
            $this->warn('Sections analytiques NON résolues : '.implode(', ', array_keys($unresolvedSection)));
        }
        if ($errors) {
            $this->newLine();
            $this->error('Erreurs :');
            foreach ($errors as $e) {
                $this->line('  - '.$e);
            }
        }

        return self::SUCCESS;
    }

    private function ensureSections(): void
    {
        $axe = AxeAnalytique::where('societe_id', $this->societeId)->where('code', 'CH')->value('id') ?? 1;
        foreach ($this->sectionsACreer as $code => $libelle) {
            SectionAnalytique::firstOrCreate(
                ['societe_id' => $this->societeId, 'code' => $code],
                ['axe_analytique_id' => $axe, 'libelle' => $libelle, 'actif' => true]
            );
        }
    }

    private function loadSectionIds(): void
    {
        $this->sectionIds = SectionAnalytique::where('societe_id', $this->societeId)
            ->pluck('id', 'code')->toArray();
    }

    private function ensureClient(string $nom): string
    {
        $tiers = Tiers::where('societe_id', $this->societeId)->where('nom', $nom)->first();
        if ($tiers && $tiers->num_compte_collectif) {
            $this->tiersCache[$tiers->num_compte_collectif] = $tiers->id;

            return $tiers->num_compte_collectif;
        }

        // Compte 411 suivant disponible
        $max = (int) PlanComptable::where('num_compte', 'like', '4111%')
            ->whereRaw('LENGTH(num_compte) = 6')
            ->max('num_compte');
        $compte = (string) ($max + 1);

        PlanComptable::firstOrCreate(
            ['societe_id' => $this->societeId, 'num_compte' => $compte],
            [
                'libelle' => $nom, 'classe' => 4, 'type_compte' => 'bilan',
                'sens_normal' => 'debiteur', 'niveau' => 4,
                'est_compte_detail' => true, 'est_compte_tiers' => true,
                'est_lettrable' => true, 'actif' => true,
            ]
        );

        $tiers = Tiers::firstOrCreate(
            ['societe_id' => $this->societeId, 'nom' => $nom],
            ['code' => 'T-'.$compte, 'type' => 'client', 'num_compte_collectif' => $compte, 'actif' => true]
        );
        if (! $tiers->num_compte_collectif) {
            $tiers->update(['num_compte_collectif' => $compte]);
        }
        $this->tiersCache[$compte] = $tiers->id;

        return $compte;
    }

    private function tiersId(string $compte): int
    {
        if (isset($this->tiersCache[$compte])) {
            return $this->tiersCache[$compte];
        }
        if (isset($this->tiersOverride[$compte])) {
            return $this->tiersCache[$compte] = $this->tiersOverride[$compte];
        }

        $tiers = Tiers::where('societe_id', $this->societeId)->where('num_compte_collectif', $compte)->first();
        if (! $tiers) {
            $tiers = Tiers::where('societe_id', $this->societeId)->where('code', 'T-'.$compte)->first();
        }
        if (! $tiers) {
            $libelle = PlanComptable::where('num_compte', $compte)->value('libelle');
            if ($libelle) {
                $tiers = Tiers::where('societe_id', $this->societeId)->where('nom', $libelle)->first();
            }
        }
        if (! $tiers) {
            $libelle = $libelle ?? $compte;
            $tiers = Tiers::create([
                'societe_id' => $this->societeId, 'code' => 'T-'.$compte, 'nom' => $libelle,
                'type' => 'client', 'num_compte_collectif' => $compte, 'actif' => true,
            ]);
        }

        return $this->tiersCache[$compte] = $tiers->id;
    }

    private function normalize(string $s): string
    {
        $s = strtoupper($s);
        $s = strtr($s, [
            'É' => 'E', 'È' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'À' => 'A', 'Â' => 'A',
            'Î' => 'I', 'Ï' => 'I', 'Ô' => 'O', 'Û' => 'U', 'Ù' => 'U', 'Ç' => 'C',
        ]);

        return $s;
    }

    /** @return array{0:?string,1:?string} [num_compte, code_section] */
    private function resolve(array $rules, string $label): array
    {
        $l = $this->normalize($label);
        foreach ($rules as $r) {
            $needles = $r['any'];
            foreach ($needles as $n) {
                if (strpos($l, $n) !== false) {
                    return [$r['compte'], $r['section']];
                }
            }
        }

        return [null, null];
    }

    /** Règles ordonnées : les plus spécifiques d'abord. */
    private function rules(string $compteJapan, string $compteEcole): array
    {
        $C = fn (array $any, string $compte, ?string $section) => ['any' => $any, 'compte' => $compte, 'section' => $section];

        return [
            // Conflits prioritaires
            $C(['PETIT PONT'], '411147', 'PETIT-PONT'),
            $C(['CONCESSION', 'IAG'], '411119', 'EBCDC'),
            $C(['AGL', 'BOLLORE'], '411107', 'AGLDG'),
            $C(['COTEX'], '411114', 'COTEX'),
            $C(['ECOLE FRANCAISE'], $compteEcole, 'EFRD'),
            $C(['JAPAN MOTORS'], $compteJapan, 'JMP'),

            // SOGIC
            $C(['BAUDOUIN'], '411131', 'SORB'),
            $C(['SOGIC'], '411131', 'SOBLD'),

            // DEVIMCO (sites)
            $C(['AMBASSADEUR'], '411115', 'DEVAMB'),
            $C(['CONCORDE'], '411115', 'DEVCON'),
            $C(['TRADITION'], '411115', 'DEVTRAD'),
            $C(['PRESTIGE'], '411115', 'DVPG'),
            $C(['DEVIMCO'], '411115', 'DVPG'),

            // TMB
            $C(['VICTOIRE'], '411132', 'TMBV'),
            $C(['TMB'], '411132', 'TMBDG'),

            // BELTEXCO
            $C(['BELTEXCO 1'], '411106', 'BT1'),
            $C(['BELTEXCO 2'], '411106', 'BT2'),
            $C(['BELTEXCO'], '411106', 'BT1'),

            // UTEXAFRICA (sites)
            $C(['BOIS NOBLES'], '411135', 'BN'),
            $C(['BCECO', 'BAT 128', '128'], '411135', 'UTEX-BAT128'),
            $C(['IZOMB', 'KOTIBE'], '411135', 'UTEX-IZ'),
            $C(['UTEXAFRICA', 'UTEX'], '411135', 'UTEX'),

            // RAWBANK (sites)
            $C(['KIALA', 'RESIDENCE', 'PERRAZONE'], '411129', 'RAW-RES'),
            $C(['RAWBANK'], '411129', 'RAW-ATRI'),

            // LFRD
            $C(['APPARTEMENT'], '411124', 'EFRD-AP'),
            $C(['LFRD'], '411124', 'LFRD'),

            // EBCDC / EQUITY
            $C(['EBCDC', 'EQUITY'], '411117', 'EBCDC'),

            // Ascenseur / clim divers
            $C(['MAJESTIC'], '411150', 'MAJ-RIV'),
            $C(['SUCRIERE'], '411148', 'CSKN'),
            $C(['PGHK'], '411143', 'GHC'),
            $C(['PRIMMO'], '411171', 'PRIM'),
            $C(['EJCSDJ'], '411118', 'EJCKIN'),
            $C(['VLISCO'], '411162', 'VLISCO'),
            $C(['CONNEXAFRICA'], '411141', 'CNX'),
            $C(['LPL'], '411125', 'LPL-LYCEE'),
            $C(['UNICEF'], '411133', 'UNICEF-KIN'),
            $C(['TCK'], '411159', 'TCK-KIN'),
            $C(['TALGARTH'], '411168', 'TALG'),
            $C(['IMMOKIN'], '411169', 'IMMO'),
            $C(['IMMOTEX'], '411170', 'IMMOT'),
            $C(['MONUSCO'], '411127', 'MONUSCO'),
            $C(['MIDEMA'], '411126', 'MIDEMA'),
            $C(['EBATA'], '411116', 'EBATA'),
            $C(['WAGENIA'], '411145', 'WG'),
            $C(['IMANI'], '411120', 'IMAN'),
            $C(['AFRICANA'], '411100', 'PAL'),
            $C(['EBALE'], '411157', 'EBALE'),
            $C(['SERKAS'], '411122', 'SERKAS'),
            $C(['HELENIQUE', 'HELLENIQUE'], '411167', 'HEL'),
            $C(['BANQUE MONDIALE'], '411105', 'BIRD'),
            $C(['DELPHIN'], '411144', 'DEL'),
            $C(['MONKOLE'], '411108', 'MONK'),
            $C(['AMBABEL', 'BELGIQUE'], '411139', 'AMBABEL'),
            $C(['BRITANNIQUE', 'BRITISH'], '411140', 'AMBRITISH'),
            $C(['US EMBASSY'], '411134', 'US'),
            $C(['EXCELLERATE', 'CITIBANK', 'CITIGROUP'], '411110', 'EXCEL'),
            $C(['SILIKIN'], '411166', 'SILKN'),
        ];
    }

    /** @return array<int, array{0:string,1:string,2:string,3:string,4:float,5:float}> [date, libelle, refExterne, refFacture, totalTTC, tva] */
    private function rows(): array
    {
        return [
            // ===== JANVIER =====
            ['2026-01-20', 'IMMEUBLE DELPHIN - Redevance 12/2025', '2026/DA/01/4', '136/1-1/2026', 232.00, 32.00],
            ['2026-01-20', 'C.H.MONKOLE - Redevance 12/2025', '2026/DA/01/3', '136/1-2/2026', 700.00, 0],
            ['2026-01-20', 'AMBABEL - Redevance 12/2025', '2026/DA/01/1', '136/1-3/2026', 750.00, 0],
            ['2026-01-20', 'Ambassade BRITANNIQUE - Redevance 12/2025', '2026/DA/01/2', '136/1-4/2026', 150.00, 0],
            ['2026-01-20', 'BANQUE MONDIALE - Redevance 12/2025', '2026/DA/01/5', '136/1-5/2026', 400.00, 0],
            ['2026-01-20', 'IMMEUBLE SERKAS - Redevance 12/2025', '2026/DA/01/6', '136/1-6/2026', 200.45, 27.65],
            ['2026-01-20', 'COMMUNAUTE HELENIQUE - Redevance 12/2025', '2026/DA/01/7', '136/1-7/2026', 139.20, 19.20],
            ['2026-01-21', 'EBALE RESIDENCE - Redevance 12/2025', '2026/DA/01/8', '136/1-8/2026', 192.85, 26.60],
            ['2026-01-21', 'SOGIC - Redevance BOULEVARD 12/2025', '2026/DA/01/9', '136/1-9/2026', 212.28, 29.28],
            ['2026-01-21', 'AFRICANA PALACE - Redevance 12/2025', '2026/DA/01/10', '136/1-10/2026', 208.80, 28.80],
            ['2026-01-21', 'LFRD - Redevance 12/2025', '2026/DA/01/11', '136/1-11/2026', 500.00, 0],
            ['2026-01-21', 'SOGIC - Redevance ROI BAUDOUIN 12/2025', '2026/DA/01/12', '136/1-12/2026', 212.28, 29.28],
            ['2026-01-21', 'IMMEUBLE IMANI - Redevance 12/2025', '2026/DA/01/13', '136/1-13/2026', 232.00, 32.00],
            ['2026-01-22', 'EBCDC - Revance escalators 12/2025', '2026/DA/01/14', '136/1-14/2026', 174.00, 24.00],
            ['2026-01-22', 'UTEXAFRICA - Redevance BOIS NOBLES 2B 12/2025 (522€)', '2026/DA/01/15', '136/1-15/2026', 611.76, 84.38],
            ['2026-01-23', 'EXCELLERATE - Entretien 4eTRIM2025', '2026/DT/01/1', '136/1-16/2026', 2987.00, 412.00],
            ['2026-01-23', 'AGL - Fo&po split 12000btu Bureau KINGABWA', '2026/DT/01/3', '136/1-17/2026', 986.00, 136.00],
            ['2026-01-23', 'AGL - Fo&po split 24000btu VILLA 150 UTEX', '2026/DT/01/4', '136/1-19/2026', 1334.00, 184.00],
            ['2026-01-23', 'TCK - Entretien 12/2025', '2026/DT/01/5', '136/1-20/2026', 445.44, 61.44],
            ['2026-01-23', 'EBCDC/IAG - Redevance CONCESSION 12/2025', '2026/DA/01/17', '136/1-22/2026', 1392.00, 192.00],
            ['2026-01-23', 'TMB - Redevance SIEGE 12/2025', '2026/DA/01/18', '136/1-23/2026', 1539.90, 212.40],
            ['2026-01-28', 'LPL - Entretien clim 1erQUAD2026', '2026/DT/01/7', '136/1-24/2026', 2610.00, 360.00],
            ['2026-01-28', 'RAWBANK SARL - Entretien clim 1eTRIM.2026 (corrigé 4eTRIM.2025)', '2026/DT/01/8', '136/1-25/2026', 14620.18, 2016.58],
            ['2026-01-28', 'UNICEF - Maintenance HUB/KINKOLE 12/2025', '2026/DT/01/2', '136/1-26/2026', 13000.00, 0],
            ['2026-01-28', 'MAJESTIC RIVER - Depannage CF 12/2025', '2026/DT/01/9', '136/1-27/2026', 206.48, 28.48],
            ['2026-01-28', 'LFRD - Entretien clim 1erTRIM.2026', '2026/DT/01/10', '136/1-28/2026', 2940.00, 0],
            ['2026-01-28', 'CONNEXAFRICA RDC - Entretien clim 1erTRIM.2026', '2026/DT/01/11', '136/1-29/2026', 2985.84, 411.84],
            ['2026-01-28', 'EBCDC - Redevance semestrielle ESCALATORS', '2026/DA/01/16', '136/1-30/2026', 1856.00, 256.00],
            ['2026-01-28', 'C.H. MONKOLE - Fo&po 3 courroies de traction', '2026/DA/01/20', '136/1-31/2026', 4000.00, 0],
            ['2026-01-28', 'US EMBASSY - Fo&po 3 Remote ASC', '2026/DA/01/21', '136/1-32/2026', 674.45, 0],
            ['2026-01-28', 'C.H. MONKOLE - Fo&po 4 Galets porte cabine', '2026/DA/01/19', '136/1-33/2026', 780.32, 0],
            ['2026-01-28', 'US EMBASSY - Redevance 122025 - 2026.01/DA/22', '2026/DA/01/22', '136/1-34/2026', 250.00, 0],
            ['2026-01-28', 'DEVIMCO / PRESTIGE - Redevance 122025 - 2026.01/DA/24', '2026/DA/01/24', '136/1-35/2026', 150.80, 20.80],
            ['2026-01-28', 'WAGENIA - Redevance 122025 - 2026.01/DA/23', '2026/DA/01/23', '136/1-36/2026', 290.00, 40.00],
            ['2026-01-28', 'MIDEMA - Redevance 112025 - 2026.01/DA/25', '2026/DA/01/25', '136/1-37/2026', 319.00, 44.00],
            ['2026-01-28', 'MAJESTIC RIVER - Redevance 112025 - 2026.01/DA/26', '2026/DA/01/26', '136/1-38/2026', 406.00, 56.00],
            ['2026-01-28', 'MAJESTIC RIVER - Redevance 122025 - 2026.01/DA/27', '2026/DA/01/27', '136/1-39/2026', 406.00, 56.00],
            ['2026-01-28', 'DEVIMCO / AMBASSADEUR - Redevance 122025 - 2026.01/DA/29', '2026/DA/01/29', '136/1-40/2026', 116.00, 16.00],
            ['2026-01-28', 'MIDEMA - Redevance 122025 - 2026.01/DA/28', '2026/DA/01/28', '136/1-41/2026', 319.00, 44.00],
            ['2026-01-28', 'DEVIMCO / TRADITION - Redevance 122025 - 2026.01/DA/30', '2026/DA/01/30', '136/1-42/2026', 278.40, 38.40],
            ['2026-01-28', 'DEVIMCO / CONCORDE - Redevance 122025 - 2026.01/DA/31', '2026/DA/01/31', '136/1-43/2026', 464.00, 64.00],
            ['2026-01-28', 'TMB / VICTOIRE - Redevance 122025 - 2026.01/DA/32', '2026/DA/01/32', '136/1-44/2026', 261.00, 36.00],
            ['2026-01-28', 'BELTEXCO 1 - Redevance 122025 - 2026.01/DA/33', '2026/DA/01/33', '136/1-45/2026', 150.22, 20.72],
            ['2026-01-28', 'BELTEXCO 2 - Redevance 122025 - 2026.01/DA/34', '2026/DA/01/34', '136/1-46/2026', 170.52, 23.52],

            // ===== FEVRIER =====
            ['2026-02-03', 'AGL - Entretien clim 4eTRIM.2025 - 2026.01/DT/12', '2026/DT/01/12', '136/1-47/2026', 10458.56, 1442.56],
            ['2026-02-04', 'AGL - Dépannage clim 4eTRIM.2025 - 2026.01/DT/13', '2026/DT/01/13', '136/1-48/2026', 259.84, 35.84],
            ['2026-02-04', 'COMPAGNIE SUCRIERE - Entretien 4eTRIM.2025', '2026/DT/01/14', '136/1-49/2026', 15387.40, 2122.40],
            ['2026-02-04', 'COMPAGNIE SUCRIERE - Dépannage 4eTRIM.2025', '2026/DT/01/15', '136/1-50/2026', 2279.40, 314.40],
            ['2026-02-11', 'RAWBANK - 20% Travaux reamenagement 2026.01/DT/16', '2026/DT/01/16', '136/1-51/2026', 80968.00, 11168.00],
            ['2026-02-12', 'DEVIMCO - Redevance PRESTIGE 01/2026', '2026.01/DA/35', '136/1-52/2026', 158.34, 21.84],
            ['2026-02-12', 'DEVIMCO - Redevance AMBASSADEUR 01/2026', '2026.01/DA/36', '136/1-53/2026', 121.80, 16.80],
            ['2026-02-12', 'DEVIMCO - Redevance TRADITION 01/2026', '2026.01/DA/37', '136/1-54/2026', 292.32, 40.32],
            ['2026-02-12', 'DEVIMCO - Redevance CONCORDE 01/2026', '2026.01/DA/38', '136/1-55/2026', 487.20, 67.20],
            ['2026-02-12', 'TMB - Redevance VICTOIRE 01/2026', '2026.01/DA/39', '136/1-56/2026', 274.05, 37.80],
            ['2026-02-12', 'TMB - Redevance DG 01/2026', '2026.01/DA/40', '136/1-57/2026', 1616.90, 223.02],
            ['2026-02-12', 'IMANI - Redevance 01/2026', '2026.01/DA/41', '136/1-58/2026', 243.60, 33.60],
            ['2026-02-12', 'SOGIC - Redevance ROI BAUDOUIN 01/2026', '2026.01/DA/42', '136/1-59/2026', 222.89, 30.74],
            ['2026-02-12', 'BELTEXCO 1 - Redevance 01/2026', '2026.01/DA/43', '136/1-60/2026', 157.74, 21.76],
            ['2026-02-12', 'BELTEXCO 2 - Redevance 01/2026', '2026.01/DA/44', '136/1-61/2026', 179.05, 24.70],
            ['2026-02-12', 'IAG - Redevance CONCESSION 01/2026', '2026.01/DA/45', '136/1-62/2026', 1392.00, 192.00],
            ['2026-02-12', 'COMMUNAUTE HELLENIQUE - Redevance 01/2026', '2026.01/DA/46', '136/1-63/2026', 146.16, 20.16],
            ['2026-02-12', 'WAGENIA - Redevance 01/2026', '2026.01/DA/47', '136/1-64/2026', 304.50, 42.00],
            ['2026-02-17', 'Monsieur EBATA - Redevance annuelle 2026', '2026.01/DA/48', '136/1-65/2026', 3480.00, 480.00],
            ['2026-02-17', 'EBALE RESIDENCE - Redevance mensuelle 01/2026', '2026.01/DA/49', '136/1-66/2026', 202.49, 27.93],
            ['2026-02-17', 'LFRD - Redevance mensuelle 01/2026', '2026.01/DA/50', '136/1-67/2026', 500.00, 0],
            ['2026-02-17', 'C.H. MONKOLE - Redevance 01/2026', '2026.01/DA/51', '136/1-68/2026', 700.00, 0],
            ['2026-02-17', 'US EMBASSY - Redevance PANORAMA 01/2026', '2026.01/DA/52', '136/1-69/2026', 250.00, 0],
            ['2026-02-17', 'AMBABEL - Redevance mensuelle 01/2025', '2026.01/DA/53', '136/1-70/2026', 750.00, 0],
            ['2026-02-17', 'MONUSCO - Redevance 1er TRIM.2026', '2026.01/DA/54', '136/1-71/2026', 1200.00, 0],
            ['2026-02-17', 'BRITISH EMBASSY - Redevance mensuelle 01/2026', '2026.01/DA/55', '136/1-72/2026', 157.50, 0],
            ['2026-02-17', 'BANQUE MONDIALE - Redevance mensuelle 01/2026', '2026.01/DA/56', '136/1-73/2026', 400.00, 0],
            ['2026-02-17', 'UTEXAFRICA - Redevance BOIS NOBLES 2B 01/2026', '2026.01/DA/57', '136/1-74/2026', 619.60, 85.46],
            ['2026-02-17', 'EBCDC - Redevance & surveillance escalators 01/2026', '2026.01/DA/58', '136/1-75/2026', 696.00, 96.00],
            ['2026-02-17', 'TALGARTH HOLDING INC - Redevance BAMBOU 01-02/2026', '2026.01/DA/59', '136/1-76/2026', 580.00, 80.00],
            ['2026-02-17', 'TALGARTH HOLDING INC - Redevance EQUATEUR 01-02/2026', '2026.01/DA/60', '136/1-77/2026', 290.00, 40.00],
            ['2026-02-17', 'IMMOKIN - Redevance TILAPIA 01-02/2026', '2026.01/DA/61', '136/1-78/2026', 290.00, 40.00],
            ['2026-02-17', 'MIDEMA - Redevance mensuelle 01/2026', '2026.01/DA/62', '136/1-79/2026', 319.00, 44.00],
            ['2026-02-17', 'MAJESTIC RIVER - Redevance mensuelle 01/2026', '2026.01/DA/63', '136/1-80/2026', 406.00, 56.00],
            ['2026-02-17', 'SOGIC - Redevance ROI BOULEVARD 01/2026', '2026.01/DA/64', '136/1-81/2026', 222.89, 30.74],
            ['2026-02-18', 'EJCSDJ - Fo&po 2 fans moteur chiller 2026.01/DT/18', '2026/DT/01/18', '136/1-82/2026', 10152.61, 1400.36],
            ['2026-02-18', 'EJCSDJ - Entretien clim 1er TRIM. 2026.01/DT/19', '2026/DT/01/19', '136/1-83/2026', 5582.50, 770.00],
            ['2026-02-18', 'RAWBANK - Entretien clim 1erTOUR  2026.01/DT/16', '2026/DT/01/20', '136/1-84/2026', 1136.80, 156.80],
            ['2026-02-20', 'Immeuble SERKAS - Redevance 01/2026', '2026.01/DA/65', '136/1-85/2026', 210.47, 29.03],
            ['2026-02-20', 'AFRICANA PALACE - Redevance 01/2026', '2026.01/DA/67', '136/1-86/2026', 219.24, 30.24],
            ['2026-02-20', 'Immeuble DELPHIN (LA GRACE DE DIEU) - Redevance 01/2026', '2026.01/DA/66', '136/1-87/2026', 243.60, 33.60],
            ['2026-02-24', 'DEVIMCO - Redevance AMBASSADEUR 02/2026', '2026.01/DA/68', '136/1-88/2026', 121.80, 16.80],
            ['2026-02-24', 'DEVIMCO - Redevance TRADITION 02/2026', '2026.01/DA/69', '136/1-89/2026', 292.32, 40.32],
            ['2026-02-24', 'DEVIMCO - Redevance CONCORDE 02/2026', '2026.01/DA/70', '136/1-90/2026', 487.20, 67.20],
            ['2026-02-24', 'TMB - Redevance DG 02/2026', '2026.01/DA/71', '136/1-91/2026', 1616.90, 223.02],
            ['2026-02-24', 'SOGIC - Redevance BOULEVARD 02/2026', '2026.02/DA/72', '136/1-92/2026', 222.89, 30.74],
            ['2026-02-24', 'SOGIC - Redevance ROI BAUDOUIN 02/2026', '2026.02/DA/73', '136/1-93/2026', 222.89, 30.74],
            ['2026-02-24', 'BELTEXCO 1 - Redevance 02/2026', '2026.01/DA/74', '136/1-94/2026', 157.74, 21.76],
            ['2026-02-24', 'BELTEXCO 2 - Redevance 02/2026', '2026.01/DA/75', '136/1-95/2026', 179.05, 24.70],
            ['2026-02-24', 'COMPAGNIE SUCRIERE - Supplment Dépannage 4eTRIM.2025', '2026/DT/01/21', '136/1-96/2026', 739.62, 102.02],
            ['2026-02-24', 'UTEXAFRICA - Entretien clim 3eTRIM.2025', '2026/DT/01/22', '136/1-97/2026', 9378.60, 1293.60],
            ['2026-02-24', 'COTEX - Entretien clim 3eQUATRIM.2025', '2026/DT/01/23', '136/1-98/2026', 1745.80, 240.80],
            ['2026-02-24', 'COMPAGNIE SUCRIERE - Entretien 1eTOUR.2026', '2026/DT/01/24', '136/1-99/2026', 2012.60, 277.60],
            ['2026-02-25', 'COMPAGNIE SUCRIERE - Fo&po 1 split LG 18000BTU / Salle DC', '2026/DT/01/25', '136/1-100/2026', 1322.40, 182.40],
            ['2026-02-25', 'TMB - Entretien 2 cassettes VERTI 1erTOUR2026', '2026/DT/01/26', '136/1-101/2026', 464.00, 64.00],
            ['2026-02-25', 'TMB - Redevance VICTOIRE 02/2026', '2026.01/DA/76', '136/1-102/2026', 274.05, 37.80],
            ['2026-02-25', 'US EMBASSY - Redevance PANORAMA 02/2026', '2026.01/DA/77', '136/1-103/2026', 250.00, 0],
            ['2026-02-25', 'BANQUE MONDIALE - Redevance 02/2026', '2026.01/DA/78', '136/1-104/2026', 400.00, 0],

            // ===== MARS =====
            ['2026-03-02', 'UNICEF - Maintenance & surveillance HUB KINKOLE 01/2026', '2026/DT/01/27', '136/1-105/2026', 22005.00, 0],
            ['2026-03-02', 'UTEXAFRICA - Demontage&montage spli GREE ZINGANA 16', '2026/DT/01/28', '136/1-106/2026', 1044.00, 144.00],
            ['2026-03-02', 'UTEXAFRICA - Depannages divers KOTIBE 3-4 &IZOMBE 2', '2026/DT/01/29', '136/1-107/2026', 1642.56, 226.56],
            ['2026-03-02', 'MAJESTIC RIVER - Entretien & depannage cuisine', '2026/DT/01/30', '136/1-108/2026', 417.60, 57.60],
            ['2026-03-03', 'PRIMMO - Avancement travaux clim n°1', '2026/DT/01/17', '136/1-109/2026', 17362.87, 2394.88],
            ['2026-03-03', 'TALGARTH HOLDING INC - Divers depannage BAMBOU', '2026.01/DA/79', '136/1-110/2026', 58.00, 8.00],
            ['2026-03-03', 'UTEXAFRICA /BOIS NOBLES - Redevance 02/2026', '2026.01/DA/80', '136/1-111/2026', 605.57, 83.53],
            ['2026-03-03', 'IMMEUBLE WAGENIA - Redevance 02/2026', '2026.01/DA/81', '136/1-112/2026', 304.50, 42.00],
            ['2026-03-03', 'DEVIMCO - Redevance PRESTIGE 02/2026', '2026.01/DA/82', '136/1-113/2026', 158.34, 21.84],
            ['2026-03-03', 'LFRD - Redevance 02/2026', '2026.01/DA/83', '136/1-114/2026', 500.00, 0],
            ['2026-03-03', 'IAG - Redevance CONCESSION 02/2026', '2026.01/DA/84', '136/1-115/2026', 1392.00, 192.00],
            ['2026-03-03', 'COMMUNAUTE HELLENIQUE - Redevance 02/2026', '2026.01/DA/85', '136/1-116/2026', 146.16, 20.16],
            ['2026-03-03', 'C.H. MONKOLE - Redevance 02/2026', '2026.01/DA/86', '136/1-117/2026', 700.00, 0],
            ['2026-03-03', 'JAPAN MOTORS - Entretien 1erTOUR 02/2026', '2026/DT/01/31', '136/1-118/2026', 464.00, 64.00],
            ['2026-03-07', 'EXCELLERATE PROPERTY - Dépannage cassette', '2026/DT/01/32', '136/1-119/2026', 156.60, 21.60],
            ['2026-03-07', 'EXCELLERATE PROPERTY - Dépannage cassette VRV', '2026/DT/01/33', '136/1-120/2026', 429.20, 59.20],
            ['2026-03-07', 'EXCELLERATE PROPERTY - Récharge freon R410', '2026/DT/01/34', '136/1-121/2026', 150.80, 20.80],
            ['2026-03-07', 'COMPAGNIE SUCRIERE - Fo&po 1 split LG 18000BTU, 1 disjoncteur CV2 Mson Phillippe', '2026/DT/01/35', '136/1-122/2026', 1554.40, 214.40],
            ['2026-03-07', 'MAJESTIC RIVER - Redevance mensuelle 02/2026', '2026.01/DA/87', '136/1-123/2026', 406.00, 56.00],
            ['2026-03-07', 'TALGARTH HOLDING INC - Raccoucissement cables & Fo, po 6 serre-cables BAMBOU', '2026.01/DA/88', '136/1-124/2026', 696.00, 96.00],
            ['2026-03-07', 'EBALE RESIDENCE - Redevance mensuelle 02/2026', '2026.01/DA/89', '136/1-125/2026', 202.49, 27.93],
            ['2026-03-07', 'AFRICANA PALACE - Redevance mensuelle 02/2026', '2026.01/DA/90', '136/1-126/2026', 219.24, 30.24],
            ['2026-03-07', 'IMMEUBLE DELPHIN - Redevance mensuelle 02/2026', '2026.01/DA/91', '136/1-127/2026', 243.60, 33.60],
            ['2026-03-07', 'IMMEUBLE SERKAS - Redevance mensuelle 02/2026', '2026.01/DA/92', '136/1-128/2026', 210.47, 29.03],
            ['2026-03-07', 'MIDEMA - Redevance mensuelle 02/2026', '2026.01/DA/93', '136/1-129/2026', 319.00, 44.00],
            ['2026-03-07', 'AMBASSADE BRITANNIQUE - Redevance mensuelle 02/2026', '2026.01/DA/94', '136/1-130/2026', 157.50, 0],
            ['2026-03-07', 'AMBASSADE DE BELGIQUE - Redevance mensuelle 02/2026', '2026.01/DA/95', '136/1-131/2026', 750.00, 0],
            ['2026-03-12', 'UTEXAFRICA - Charge freon R22', '2026/DT/01/37', '136/1-132/2026', 46.40, 6.40],
            ['2026-03-12', 'COTEX - Demontage&montage split LG 24000BTU 3C', '2026/DT/01/38', '136/1-133/2026', 1392.00, 192.00],
            ['2026-03-12', 'COTEX - Diverses interventions machine VRV SILIKIN', '2026/DT/01/39', '136/1-134/2026', 238.96, 32.96],
            ['2026-03-12', 'EJCSDJ - Fo&po 2flexibles+accessoires BUANDERIE & vanne à Baptisroom', '2026/DT/01/40', '136/1-135/2026', 293.48, 40.48],
            ['2026-03-12', 'Immeuble WAGENIA - Entretien split Local SERVEUR', '2026/DT/01/41', '136/1-136/2026', 46.40, 6.40],
            ['2026-03-17', 'COTEX - Entretien clim 12/2025', '2026/DT/01/6', '136/1-138/2026', 7609.60, 1049.60],
            ['2026-03-17', 'RAWBANK - Démontage&montage 5 splits Residence KIALA', '2026/DT/01/42', '136/1-139/2026', 1265.56, 174.56],
            ['2026-03-17', 'RAWBANK - Montage split Bureau DISPATCHING CHAROOI/SIEGE', '2026/DT/01/43', '136/1-140/2026', 476.53, 65.73],
            ['2026-03-17', 'LFRD - Diverses interventions LYCEE & Apt BATIMENT', '2026/DT/01/44', '136/1-141/2026', 360.00, 0],
            ['2026-03-17', 'LFRD - Entretien 59 splits 1erQUADRIMESTRE 2026 APPARTEMENTS', '2026/DT/01/45', '136/1-142/2026', 2065.00, 0],
            ['2026-03-21', 'TMB - Démontage valve de sécurité MACHINE/VERTIV DG', '2026/DT/01/46', '136/1-143/2026', 232.00, 32.00],
            ['2026-03-21', 'UTEXAFRICA - Vérification fonctionnement extracteur cuisine IZOMBI 1', '2026/DT/01/47', '136/1-144/2026', 69.60, 9.60],
            ['2026-03-21', 'MAJESTIC RIVER - Depannage Chambre froide négative', '2026/DT/01/48', '136/1-145/2026', 357.28, 49.28],
            ['2026-03-21', 'LPL -Dépannage clim splits Local B14&Technique / LYCEE', '2026/DT/01/49', '136/1-146/2026', 127.60, 17.60],
            ['2026-03-21', 'DEVIMCO - Redevance AMBASSADEUR 03/2026', '2026.01/DA/96', '136/1-147/2026', 121.80, 16.80],
            ['2026-03-21', 'BELTEXCO 2 - Redevance 03/2026', '2026.01/DA/97', '136/1-148/2026', 179.05, 24.70],
            ['2026-03-21', 'MAJESTIC RIVER - Fo&po 1 contact à clé complet', '2026.01/DA/98', '136/1-149/2026', 754.00, 104.00],
            ['2026-03-21', 'UTEXAFRICA/BOIS NOBLES - Redevance 03/2026', '2026.01/DA/100', '136/1-150/2026', 610.74, 84.24],
            ['2026-03-21', 'EBCDC - Redevance & surveillance mensuelle 03/2026', '2026.01/DA/101', '136/1-151/2026', 696.00, 96.00],
            ['2026-03-21', 'TMB - Redevance mensuelle DG 03/2026', '2026.01/DA/102', '136/1-152/2026', 1616.90, 223.02],
            ['2026-03-21', 'IAG - Redevance CONCESSION 03/2026', '2026.01/DA/103', '136/1-153/2026', 1392.00, 192.00],
            ['2026-03-21', 'BELTEXCO 1 - Redevance 03/2026', '2026.01/DA/104', '136/1-154/2026', 157.74, 21.76],
            ['2026-03-21', 'DEVIMCO - Redevance mensuelle PRESTIGE 03/2026', '2026.01/DA/105', '136/1-155/2026', 158.34, 21.84],
            ['2026-03-21', 'AFRICANA PALACE - Redevance mensuelle 03/2026', '2026.01/DA/99', '136/1-156/2026', 219.24, 30.24],
            ['2026-03-25', 'LFRD - Redevance 03/2026', '2026.01/DA/106', '136/1-157/2026', 500.00, 0],
            ['2026-03-25', 'Immeuble WAGENIA - Redevance 03/2026', '2026.01/DA/107', '136/1-158/2026', 304.50, 42.00],
            ['2026-03-25', 'PGHK - Reception provisoire travaux (87%)', '2026/DT/01/50', '136/1-159/2026', 115778.15, 15969.40],
            ['2026-03-25', 'UTEXAFRICA - Fo&po 2 splits GREE 18000BTU Bat 128', '2026/DT/01/51', '136/1-160/2026', 2436.00, 336.00],
            ['2026-03-25', 'UTEXAFRICA - Fo 10 antivols condenseurs BCECO', '2026/DT/01/52', '136/1-161/2026', 1100.84, 151.84],
            ['2026-03-25', 'TCK - Entretien 14 splits 1er TRIM.2026', '2026/DT/01/53', '136/1-162/2026', 519.68, 71.68],
            ['2026-03-25', 'RAWBANK - Entretien general clim ATRIUM 1er TRIM.2026', '2026/DT/01/54', '136/1-163/2026', 13125.40, 1810.40],
            ['2026-03-25', 'PGHK - Avancement travaux n°8', '2026/DT/01/55', '136/1-164/2026', 15507.00, 2138.90],
            ['2026-03-28', 'AMBASSADE BRITANNIQUE - Demontage&pose 3 splits', '', '136/1-167/2026', 5740.00, 0],
            ['2026-03-28', 'PRIMMO - Avancement travaux clim n°2', '2026/DT/01/58', '136/1-169/2026', 18982.58, 2618.29],
            ['2026-03-28', 'RAWBANK - Debouchage écoulement condenseur split Apt 3 PERRAZONE', '2026/DT/01/59', '136/1-170/2026', 58.00, 8.00],
            ['2026-03-28', 'TMB - Redevance mensuelle VICTOIRE 03/2026', '2026.01/DA/108', '136/1-171/2026', 274.05, 37.80],
            ['2026-03-28', 'US EMBASSY - Redevance PANORAMA 03/2026', '2026.01/DA/109', '136/1-172/2026', 250.00, 0],
            ['2026-03-28', 'BANQUE MONDIALE - Redevance 03/2026', '2026.01/DA/110', '136/1-173/2026', 400.00, 0],
            ['2026-03-28', 'MAJESTIC RIVER - Redevance mensuelle 03/2026', '2026.01/DA/111', '136/1-174/2026', 406.00, 56.00],
            ['2026-03-28', 'TMB - Redressement porte monte charges 1er NIVEAU', '2026.01/DA/112', '136/1-175/2026', 174.00, 24.00],
            ['2026-03-28', 'DEVIMCO - Fo&po 1 bouton appel cabine BLOC A / CONCORDE', '2026.01/DA/113', '136/1-176/2026', 87.00, 12.00],

            // ===== AVRIL =====
            ['2026-04-10', 'IMMEUBLE DELPHIN - Redevance mensuelle 03/2026', '2026.01/DA/114', '136/1-177/2026', 243.60, 33.60],
            ['2026-04-10', 'DEVIMCO - Redevance CONCORDE 03/2026', '2026.01/DA/115', '136/1-178/2026', 487.20, 67.20],
            ['2026-04-10', 'AMBASSADE BRITANNIQUE - Redevance mensuelle 03/2026', '2026.01/DA/117', '136/1-179/2026', 157.50, 0],
            ['2026-04-10', 'DEVIMCO - Redevance TRADITIONE 03/2026', '2026.01/DA/116', '136/1-180/2026', 292.32, 40.32],
            ['2026-04-10', 'AMBABEL - Redevance 03/2026', '2026.01/DA/118', '136/1-181/2026', 750.00, 0],
            ['2026-04-10', 'MIDEMA - Redevance 03/2026', '2026.01/DA/119', '136/1-182/2026', 319.00, 44.00],
            ['2026-04-10', 'SOGIC - Redevance BOULEVARD 03/2026', '2026.01/DA/120', '136/1-183/2026', 222.89, 30.74],
            ['2026-04-10', 'SOGIC - Redevance ROI BAUDOUIN 03/2026', '2026.01/DA/121', '136/1-184/2026', 222.89, 30.74],
            ['2026-04-10', 'IMMEUBLE SERKAS - Redevance 03/2026', '2026.01/DA/123', '136/1-185/2026', 210.47, 29.03],
            ['2026-04-10', 'EBALE RESIDENCE - Redevance 03/2026', '2026.01/DA/124', '136/1-186/2026', 202.49, 27.93],
            ['2026-04-10', 'C.H. MONKOLE - Redevance ASC&MONTE CHARGES 03/2026', '2026.01/DA/125', '136/1-187/2026', 700.00, 0],
            ['2026-04-10', 'COMMUNAUTE HELLENIQUE - Redevance 03/2026', '2026.01/DA/126', '136/1-188/2026', 146.16, 20.16],
            ['2026-04-10', 'TALGARTH HOLDING INC - Redevance BAMBOU 03-04/2026', '2026.01/DA/127', '136/1-189/2026', 580.00, 80.00],
            ['2026-04-10', 'TALGARTH HOLDING INC - Redevance EQUATEUR 03-04/2026', '2026.01/DA/128', '136/1-190/2026', 290.00, 40.00],
            ['2026-04-10', 'IMMOKIN - Redevance bimensuelle TILAPIA 03-04/2026', '2026.01/DA/129', '136/1-191/2026', 290.00, 40.00],
            ['2026-04-10', 'EBCDC - Redevance & surveillance escalators 03/2026', '2026.01/DA/130', '136/1-192/2026', 696.00, 96.00],
            ['2026-04-16', 'DEVIMCO - Fo&po 1 remote RS14 PRESTIGE', '2026.03/DA/122', '136/1-193/2026', 212.28, 29.28],
            ['2026-04-16', 'IMMO PETIT PONT - Depannage gainable VRV - VLISCO', '2026.03/DT/60', '136/1-194/2026', 58.00, 8.00],
            ['2026-04-16', 'COTEX - Depannage clim bat5B & 3C SILIKIN', '2026.03/DT/61', '136/1-195/2026', 34.80, 4.80],
            ['2026-04-16', 'PGHK - Remise en état circuit hydrau CTA/SALON CONGO PHASE 1', '2026.03/DT/62', '136/1-196/2026', 5800.00, 800.00],
            ['2026-04-17', 'IMMO PETIT PONT - Entretien general clim 4eTRIM.2025 (CORRIGEE)', '2026.03/DT/56', '136/1-198/2026', 6536.60, 901.60],
            ['2026-04-21', 'COMPAGNIE SUCRIERE - Entretien general clim 1erTRIM.2026', '2026.03/DT/63', '136/1-199/2026', 16158.80, 2228.80],
            ['2026-04-21', 'COMPAGNIE SUCRIERE - Dépannage clim 1erTRIM.2026', '2026.03/DT/64', '136/1-200/2026', 2177.60, 300.36],
            ['2026-04-21', 'COMPAGNIE SUCRIERE - Montage 2 splits GREE 24000BTU Salle reunion', '2026.03/DT/65', '136/1-201/2026', 3132.00, 432.00],
            ['2026-04-21', 'TMB - Entretien general 2 cassettes VERTIV 2eTOUR 2026', '2026.03/DT/66', '136/1-202/2026', 464.00, 64.00],
            ['2026-04-21', 'COTEX - Entretien general clim 1er TRIM.2026', '2026.03/DT/67', '136/1-203/2026', 7482.00, 1032.00],
            ['2026-04-21', 'ECOLE FRANCAISE - Entretien general clim 1er TOUR 2026', '2026.03/DT/68', '136/1-204/2026', 2170.00, 0],
            ['2026-04-21', 'VLISCO - Dépannage VRV / VLISCO', '2026.03/DT/69', '136/1-205/2026', 81.20, 11.20],
            ['2026-04-21', 'JAPAN MOTORS AFRICA - Travaux depannage CHAMBRE FROIDE', '2026.03/DT/70', '136/1-206/2026', 357.74, 49.34],
            ['2026-04-23', 'TMB - Demontage 2 machines VERTIV - DG', '2026.03/DT/71', '136/1-207/2026', 2088.00, 288.00],
            ['2026-04-23', 'IMMEUBLE WAGENIA - Redevance mensuelle 04/2026', '2026.04/DA/131', '136/1-208/2026', 304.50, 42.00],
            ['2026-04-23', 'BANQUE MONDIALE - Redevance mensuelle 04/2026', '2026.04/DA/132', '136/1-209/2026', 400.00, 0],
            ['2026-04-23', 'LFRD - Redevance mensuelle 04/2026', '2026.04/DA/133', '136/1-210/2026', 500.00, 0],
            ['2026-04-23', 'IAG - Redevance CONCESSION 04/2026', '2026.04/DA/134', '136/1-211/2026', 1392.00, 192.00],
            ['2026-04-23', 'UTEXAFRICA/BOIS NOBLES - Redevance mensuelle 04/2026', '2026.04/DA/135', '136/1-212/2026', 611.05, 84.28],
            ['2026-04-29', 'BELTEXCO 2 - Redevance mensuelle 04/2026', '2026.04/DA/136', '136/1-213/2026', 179.05, 24.70],
            ['2026-04-29', 'BELTEXCO 1 - Redevance mensuelle 04/2026', '2026.04/DA/137', '136/1-214/2026', 157.74, 21.76],
            ['2026-04-29', 'DEVIMCO - Redevance AMBASSADEUR 04/2026', '2026.04/DA/138', '136/1-215/2026', 121.80, 16.80],
            ['2026-04-29', 'DEVIMCO - Redevance PRESTIGE 04/2026', '2026.04/DA/139', '136/1-216/2026', 158.34, 21.84],
            ['2026-04-29', 'VLISCO - Deplecement & mise en service VRV / GRANDE SALLE', '2026.04/DT/73', '136/1-217/2026', 116.00, 16.00],

            // ===== MAI =====
            ['2026-05-06', 'IMMEUBLE DELPHIN - Redevance mensuelle 04/2026', '2026.04/DA/140', '136/1-218/2026', 243.60, 33.60],
            ['2026-05-06', 'IMMEUBLE SERKAS - Redevance 04/2026', '2026.04/DA/141', '136/1-219/2026', 210.47, 29.03],
            ['2026-05-06', 'COMMUNAUTE HELLENIQUE - Redevance 04/2026', '2026.04/DA/142', '136/1-220/2026', 146.16, 20.16],
            ['2026-05-12', 'VLISCO - Remplacement controleur de phase VRV', '2026.04/DT/76', '136/1-221/2026', 348.00, 48.00],
            ['2026-05-12', 'LFRD - Entretien general cli 1erTRIM.2026', '2026.04/DT/78', '136/1-222/2026', 2905.00, 0],
            ['2026-05-15', 'AGL - Entretien general clim 1erTRIM.2026', '2026.04/DT/77', '136/1-223/2026', 11312.32, 1560.32],
            ['2026-05-15', 'AGL - Depannage clim 1erTRIM.2026', '2026.04/DT/79', '136/1-224/2026', 403.10, 55.60],
            ['2026-05-21', 'TMB - Redevance DG  04/20236', '2026.04/DA/143', '136/1-225/2026', 1616.90, 223.02],
            ['2026-05-21', 'TMB - Redevance VICTOIRE  04/20236', '2026.04/DA/144', '136/1-226/2026', 274.05, 37.80],
            ['2026-05-21', 'SOGIC - Redevance BOULEVARD 04/2026', '2026.04/DA/145', '136/1-227/2026', 222.89, 30.74],
            ['2026-05-21', 'SOGIC - Redevance ROI BAUDOUIN 04/2026', '2026.04/DA/146', '136/1-228/2026', 222.89, 30.74],
            ['2026-05-21', 'AMBASSADE BRITANNIQUE - Redevance mensuelle 04/2026', '2026.04/DA/147', '136/1-229/2026', 157.50, 0],
            ['2026-05-21', 'C.H. MONKOLE - Redevance ASC&MONTE CHARGES 04/2026', '2026.04/DA/148', '136/1-230/2026', 700.00, 0],
            ['2026-05-21', 'AMBASSADE DE Belgique - Redevance mensuelle 04/2026', '2026.04/DA/150', '136/1-231/2026', 750.00, 0],
            ['2026-05-21', 'US EMBASSY - Redevance PANORAMA 04/2026', '2026.04/DA/151', '136/1-232/2026', 250.00, 0],
            ['2026-05-21', 'AFRICANA PALACE - Redevance mensuelle 04/2026', '2026.04/DA/152', '136/1-233/2026', 219.24, 30.24],
            ['2026-05-21', 'DEVIMCO - Redevance TRADITION 04/2026', '2026.04/DA/153', '136/1-234/2026', 292.32, 40.32],
            ['2026-05-21', 'DEVIMCO - Redevance CONCORDE 04/2026', '2026.04/DA/154', '136/1-235/2026', 487.20, 67.20],
            ['2026-05-21', 'MIDEMA - Redevance 04/2026', '2026.04/DA/155', '136/1-236/2026', 319.00, 44.00],
            ['2026-05-21', 'MAJESTIC RIVER - Redevance 04/2026', '2026.04/DA/156', '136/1-237/2026', 406.00, 56.00],
            ['2026-05-21', 'EBALE RESIDENCE - Redevance 04/2026', '2026,04/DA/157', '136/1-238/2026', 202.49, 27.93],
            ['2026-05-21', 'COMPAGNIE SUCRIERE - Fo&po 2 splits GREE BRX DICKSON& ACHATS', '2026.04/DT/80', '136/1-239/2026', 2708.60, 373.60],
            ['2026-05-21', 'EXCELLERATE - Entretien general clim 1erTRIM.2026 CITIBANK', '2026.04/DT/81', '136/1-240/2026', 2511.40, 346.40],
            ['2026-05-21', 'CONNEXAFRICA - Entretien general clim 2eTRIM.2026', '2026.04/DT/83', '136/1-241/2026', 2985.84, 411.84],
            ['2026-05-21', 'COMPAGNIE SUCRIERE - Fo&po 7 splits GREE / USINE', '2026.04/DT/84', '136/1-242/2026', 16629.76, 2293.76],
            ['2026-05-22', 'EJCSDJ - Investigation & travaux correctifs immédiats TEMPLE/KIN', '2026.04/DT/82', '136/1-243/2026', 1589.20, 219.20],
            ['2026-05-22', 'IMMOTEX - Acpte ASC Otis Gen3 CORE+Onduleur Q/DES PARCS', '2026,04/DA/158', '136/1-244/2026', 46653.45, 6434.96],
            ['2026-05-22', 'TALGARTH HOLDING INC - Remplacement pces détachées BAMBOU 1', '2026,04/DA/159', '136/1-245/2026', 266.80, 36.80],
            ['2026-05-22', 'EBCDC - Redevance semestrielle + surveillance 05/2026', '2026,04/DA/160', '136/1-246/2026', 2378.00, 328.00],
            ['2026-05-27', 'TMB - Redevance DG  05/20236', '2026,04/DA/161', '136/1-247/2026', 1616.90, 223.02],
            ['2026-05-27', 'TMB - Redevance VICTOIRE  05/20236', '2026,04/DA/162', '136/1-248/2026', 274.05, 37.80],
            ['2026-05-27', 'SOGIC - Redevance BOILEVARD 05/2026', '2026,04/DA/163', '136/1-249/2026', 222.89, 30.74],
            ['2026-05-27', 'DEVIMCO - Redevance AMBASSADEUR 05/2026', '2026,04/DA/164', '136/1-250/2026', 121.80, 16.80],
            ['2026-05-27', 'DEVIMCO - Redevance PRESTIGE 05/2026', '2026,04/DA/165', '136/1-251/2026', 158.34, 21.84],
            ['2026-05-27', 'BELTEXCO 2 - Redevance mensuelle 05/2026', '2026,04/DA/166', '136/1-252/2026', 179.05, 24.70],
            ['2026-05-27', 'BELTEXCO 1 - Redevance mensuelle 05/2026', '2026,04/DA/167', '136/1-253/2026', 157.74, 21.76],
            ['2026-05-27', 'IAG - Redevance CONCESSION 05/2026', '2026,04/DA/168', '136/1-254/2026', 1392.00, 192.00],
            ['2026-05-27', 'LFRD - Redevance mensuelle 05/2026', '2026,04/DA/169', '136/1-255/2026', 500.00, 0],
            ['2026-05-27', 'IMMEUBLE WAGENIA - Redevance mensuelle 05/2026', '2026,04/DA/170', '136/1-256/2026', 304.50, 42.00],
            ['2026-05-27', 'UTEXAFRICA/BOIS NOBLES - Redevance mensuelle 05/2026', '2026,04/DA/171', '136/1-257/2026', 605.52, 83.52],

            // ===== JUIN =====
            ['2026-06-01', 'MONUSCO - Redevance 2e TRIM.2026', '2026,04/DA/149', '136/1-258/2026', 1200.00, 0],
            ['2026-06-01', 'UNICEF - Maintenance HUB/KINKOLE 02/2026', '2026.04/DT/72', '136/1-259/2026', 4000.00, 0],
            ['2026-06-01', 'UNICEF - Maintenance HUB/KINKOLE 12/2025', '2026.04/DT/74', '136/1-260/2026', 4000.00, 0],
            ['2026-06-01', 'UNICEF - Maintenance HUB/KINKOLE 12/2025', '2026.04/DT/75', '136/1-261/2026', 13000.00, 0],
            ['2026-06-01', 'MAJESTIC RIVER - Fo&po 2 led cabine', '2026,04/DA/172', '136/1-262/2026', 69.60, 9.60],
            ['2026-06-01', 'TALGARTH HOLDING INC - Fo&po 2 remotes RS 14 BAMBOU', '2026,04/DA/173', '136/1-263/2026', 424.56, 58.56],
            ['2026-06-01', 'IMMOKIN - Divers interventions TILAPIA', '2026,04/DA/174', '136/1-264/2026', 174.00, 24.00],
            ['2026-06-04', 'IMMO PETIT PONT - Entretien clim 2eTRIM.2026', '2026.04/DT/85', '136/1-265/2026', 6397.40, 882.40],
            ['2026-06-04', 'PRIMMO SARL - Avancement travaux n°3 CLIM', '2026.04/DT/86', '136/1-266/2026', 26458.16, 3649.40],
            ['2026-06-04', 'MAJESTIC RIVER - Redevance 05/2026', '2026,04/DA/175', '136/1-267/2026', 406.00, 56.00],
            ['2026-06-04', 'DEVIMCO - Redevance TRADITION 05/2026', '2026,04/DA/176', '136/1-268/2026', 146.16, 20.16],
            ['2026-06-04', 'DEVIMCO - Redevance CONCORDE 05/2026', '2026,04/DA/177', '136/1-269/2026', 487.20, 67.20],
            ['2026-06-04', 'LA GRACE DE DIEU/Immeuble DELPHIN - Redevance 05/2026', '2026,04/DA/178', '136/1-270/2026', 243.60, 33.60],
            ['2026-06-04', 'AMBASSADE DE Belgique - Redevance mensuelle 05/2026', '2026,04/DA/179', '136/1-271/2026', 750.00, 0],
            ['2026-06-04', 'AMBASSADE BRITANNIQUE - Redevance mensuelle 05/2026', '2026,04/DA/180', '136/1-272/2026', 157.50, 0],
            ['2026-06-04', 'EBALE RESIDENCE - Redevance 05/2026', '2026,04/DA/181', '136/1-273/2026', 202.49, 27.93],
            ['2026-06-06', 'C.H. MONKOLE - Redevance ASC&MONTE CHARGES 05/2026', '2026,04/DA/182', '136/1-274/2026', 700.00, 0],
            ['2026-06-06', 'BANQUE MONDIALE - Redevance mensuelle  05/2026', '2026,04/DA/183', '136/1-275/2026', 400.00, 0],
            ['2026-06-06', 'US EMBASSY - Redevance PANORAMA 05/2026', '2026,04/DA/184', '136/1-276/2026', 250.00, 0],
            ['2026-06-06', 'IMMEUBLE SERKAS - Redevance 05/2026', '2026,04/DA/185', '136/1-277/2026', 210.47, 29.03],
            ['2026-06-06', 'COMMUNAUTE HELLENIQUE - Redevance 05/2026', '2026,04/DA/186', '136/1-278/2026', 146.16, 20.16],
            ['2026-06-06', 'SOGIC - Redevance ROI BAUDOUIN 05/2026', '2026,04/DA/187', '136/1-279/2026', 222.89, 30.74],
            ['2026-06-06', 'LPL - Depannage divers clim LYCEE', '2026.04/DT/87', '136/1-280/2026', 504.60, 69.60],
            ['2026-06-06', 'LPL - Entretien general clim 2eQUADRIMESTRE 2026', '2026.04/DT/89', '136/1-281/2026', 2531.70, 349.20],
            ['2026-06-06', 'TMB - Demontage 2 machines VERTIV - DG', '2026.04/DT/90', '136/1-282/2026', 2088.00, 288.00],
        ];
    }
}
