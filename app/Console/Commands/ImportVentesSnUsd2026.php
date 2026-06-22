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

class ImportVentesSnUsd2026 extends Command
{
    protected $signature = 'ventes:import-sn-usd-2026 {--dry : Simule sans rien enregistrer} {--brouillon : Enregistre en brouillon au lieu de valider}';

    protected $description = 'Importe les ventes SN (706103) en USD dans le journal VT-SN-USD (exercice 2026)';

    private int $societeId = 1;

    private int $exerciceId = 1;

    private int $journalSn = 13;          // VT-SN-USD

    private string $compteRevSn = '706103';   // VENTES SERVICES SN

    /** @var array<string,int> code section => id */
    private array $sectionIds = [];

    /** @var array<string,int> num_compte => tiers_id */
    private array $tiersCache = [];

    /** @var array<string,string> nom client à créer => num_compte résolu */
    private array $comptesCrees = [];

    /** Sections analytiques manquantes à créer : code => libellé (axe CHANTIERS = CH) */
    private array $sectionsACreer = [
        'TATELIE' => 'IMMEUBLE TAT\'ELIE',
        'COMIMMO' => 'COMIMMO',
        'RSH' => 'RS HOLDING',
        'DFSN' => 'DFSN',
        'MAGDIPLO' => 'MAGASIN DIPLOMATIQUE',
        'KAWELE' => 'RESIDENCE KAWELE',
        'THOMAS' => 'MONSIEUR THOMAS',
        'KIMPEVILLE' => 'PROJET KIMPEVILLE',
        'DEBOTTER' => 'MR PETER DEBOTTER',
    ];

    /** Clients à créer (compte 4116xx) : nom => null (rempli à l'exécution) */
    private array $clientsACreer = [
        'RESIDENCE KAWELE',
        'MONSIEUR THOMAS',
        'PROJET KIMPEVILLE',
        'MR PETER DEBOTTER',
    ];

    public function handle(SaisieComptableService $saisie): int
    {
        $dry = (bool) $this->option('dry');
        $valider = ! (bool) $this->option('brouillon');

        if (! $dry) {
            $this->ensureSections();
            foreach ($this->clientsACreer as $nom) {
                $this->comptesCrees[$nom] = $this->ensureClient($nom);
            }
        }
        $this->loadSectionIds();

        $rules = $this->rules();
        $rows = $this->rows();

        // Totaux de contrôle (colonne PRODUITS) par mois
        $expected = [
            '2026-01' => 11375.20,
            '2026-02' => 6587.40,
            '2026-03' => 24447.28,
            '2026-04' => 69835.30,
            '2026-05' => 11732.50,
        ];

        $sommes = [];
        $unresolvedClient = [];
        $unresolvedSection = [];
        $created = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $r) {
            [$date, $label, $ref, $total] = $r;
            $mois = substr($date, 0, 7);
            $sommes[$mois] = ($sommes[$mois] ?? 0) + $total;

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
                $errors[] = "Ligne {$ref} : client introuvable pour « {$label} »";

                continue;
            }

            // Idempotence : ne pas recréer une facture déjà importée
            if (Ecriture::where('societe_id', $this->societeId)->where('reference_facture', $ref)->exists()) {
                $skipped++;

                continue;
            }

            $tiersId = $this->tiersId($compte);
            $sectionId = $sectionCode ? ($this->sectionIds[$sectionCode] ?? null) : null;

            $lignes = [
                [ // Créance client (débit)
                    'num_compte' => $compte,
                    'libelle' => $label,
                    'debit' => $total,
                    'credit' => 0,
                    'tiers_id' => $tiersId,
                ],
                [ // Produit SN (crédit) + analytique
                    'num_compte' => $this->compteRevSn,
                    'libelle' => $label,
                    'debit' => 0,
                    'credit' => $total,
                    'tiers_id' => $tiersId,
                    'section_analytique_id' => $sectionId,
                ],
            ];

            try {
                $saisie->enregistrer($this->societeId, [
                    'exercice_id' => $this->exerciceId,
                    'journal_id' => $this->journalSn,
                    'date_ecriture' => $date,
                    'date_piece' => $date,
                    'date_echeance' => Carbon::parse($date)->addMonth()->toDateString(),
                    'libelle' => $label,
                    'type_ecriture' => 'normale',
                    'reference_externe' => $ref,
                    'reference_facture' => $ref,
                    'devise' => 'USD',
                ], $lignes, $valider);
                $created++;
            } catch (\Throwable $e) {
                $errors[] = "Ligne {$ref} : ".$e->getMessage();
            }
        }

        // ---- Rapport ----
        $this->info($dry ? '=== SIMULATION (dry-run) ===' : '=== IMPORT VENTES SN USD ===');
        $this->line('Lignes traitées : '.count($rows));
        if (! $dry) {
            $this->line("Écritures créées : {$created} | ignorées (déjà présentes) : {$skipped}");
        }

        $this->newLine();
        $this->line('--- Réconciliation mensuelle (PRODUITS) ---');
        ksort($sommes);
        foreach ($sommes as $mois => $val) {
            $val = round($val, 2);
            $exp = $expected[$mois] ?? null;
            $flag = $exp === null ? '' : (abs($val - $exp) < 0.01 ? ' OK' : ' >>> ATTENDU '.$exp.' <<<');
            $this->line(sprintf('%s : %12s%s', $mois, number_format($val, 2), $flag));
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

    /** Crée (ou récupère) un compte client dans la série 4116xx + son tiers. */
    private function ensureClient(string $nom): string
    {
        $tiers = Tiers::where('societe_id', $this->societeId)->where('nom', $nom)->first();
        if ($tiers && $tiers->num_compte_collectif) {
            $this->tiersCache[$tiers->num_compte_collectif] = $tiers->id;

            return $tiers->num_compte_collectif;
        }

        $max = (int) PlanComptable::where('num_compte', 'like', '4116%')
            ->whereRaw('LENGTH(num_compte) = 6')
            ->max('num_compte');
        $compte = (string) (max($max, 411610) + 1);

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

        return strtr($s, [
            'É' => 'E', 'È' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'À' => 'A', 'Â' => 'A',
            'Î' => 'I', 'Ï' => 'I', 'Ô' => 'O', 'Û' => 'U', 'Ù' => 'U', 'Ç' => 'C',
        ]);
    }

    /** @return array{0:?string,1:?string} [num_compte, code_section] */
    private function resolve(array $rules, string $label): array
    {
        $l = $this->normalize($label);
        foreach ($rules as $r) {
            foreach ($r['any'] as $n) {
                if (strpos($l, $n) !== false) {
                    $compte = $r['compte'];
                    // Résolution tardive des comptes clients créés à l'exécution
                    if (isset($this->comptesCrees[$compte])) {
                        $compte = $this->comptesCrees[$compte];
                    }

                    return [$compte, $r['section']];
                }
            }
        }

        return [null, null];
    }

    /**
     * Règles ordonnées : sites spécifiques d'abord, puis génériques.
     * Le « compte » peut être un num_compte existant OU un nom de client à créer (résolu via comptesCrees).
     */
    private function rules(): array
    {
        $C = fn (array $any, string $compte, ?string $section) => ['any' => $any, 'compte' => $compte, 'section' => $section];

        return [
            // SHEHENAJ L.M. (411608) — sites PACIFIQUE / COMMERCE-HARMONIE
            $C(['PACIFIQUE'], '411608', 'MBUJIMAYI'),
            $C(['HARMONIE', 'COMMERCE'], '411608', 'HARMONIE'),
            $C(['SHEHENAJ'], '411608', 'HARMONIE'),

            // ESPACE SHADARY (411605) — CITE VERTE / LUBEFU / NYEMBO
            $C(['CITE VERTE'], '411605', 'CIVERT'),
            $C(['LUBEFU'], '411605', 'LUB'),
            $C(['NYEMBO'], '411605', 'NYE'),
            $C(['SHADARY'], '411605', 'CIVERT'),

            // LYS SARL (411603) — TOWER / CENTER
            $C(['LYS TOWER'], '411603', 'TOW'),
            $C(['LYS CENTER'], '411603', 'CENTER'),
            $C(['LYS'], '411603', 'CENTER'),

            // A ONE CONSTRUCTION (411101) — PANORAMIQUE / ESTORIL
            $C(['PANORAMIQUE', 'ESTORIL', 'A ONE'], '411101', 'AONE'),

            // DEV GROUP (411606) — DEV SOLAIRE
            $C(['DEV GROUP', 'DEV SOLAIRE'], '411606', 'DEV'),

            // LIBERTY (411610)
            $C(['LIBERTY'], '411610', 'LIB'),

            // Immeubles & résidences
            $C(['MARIE YVETTE'], '411604', 'MARYV'),
            $C(['LEGREY'], '411609', 'LEG'),
            $C(['MALAIKA'], '411607', 'MAL'),
            $C(['IMANI'], '411120', 'IMAN'),
            $C(['TATELIE', 'TAT\'ELIE'], '411123', 'TATELIE'),
            $C(['EBATA'], '411116', 'EBATA'),
            $C(['EMERAUDE'], '411152', 'JES'),

            // Divers clients
            $C(['ZHENDRE'], '411601', 'ZHE'),
            $C(['KETELEER'], '411602', 'KET'),
            $C(['COMIMMO'], '411112', 'COMIMMO'),
            $C(['RS HOLDING'], '411158', 'RSH'),
            $C(['DFSN'], '411164', 'DFSN'),
            $C(['DIPLOMATIQUE'], '411165', 'MAGDIPLO'),

            // Clients créés (compte = nom, résolu via comptesCrees)
            $C(['KAWELE'], 'RESIDENCE KAWELE', 'KAWELE'),
            $C(['THOMAS'], 'MONSIEUR THOMAS', 'THOMAS'),
            $C(['KIMPEVILLE'], 'PROJET KIMPEVILLE', 'KIMPEVILLE'),
            $C(['DEBOTTER'], 'MR PETER DEBOTTER', 'DEBOTTER'),
        ];
    }

    /** @return list<array{0:string,1:string,2:string,3:float}> [date ISO, libellé, réf, montant PRODUITS USD] */
    private function rows(): array
    {
        return [
            // ===== JANVIER 2026 =====
            ['2026-01-14', 'RESIDENCE EMERAUDE - Réinstallation liaisons frigo APT 2 cfr 2eNIVEAU', '2026.01/SN/1', 296.00],
            ['2026-01-14', 'RESIDENCE EMERAUDE - Depannage clim APT 2-4 cfr 2eNIVEAU', '2026.01/SN/2', 220.00],
            ['2026-01-14', 'SN ZHENDRE - Frais dédouanement colis AERO/LUANO', '2026.01/SN/3', 3800.00],
            ['2026-01-14', 'A ONE CONSTRUCTION - Redevance mensuelle PANORAMIQUE 12/2025', '2026.01/SN/4', 400.00],
            ['2026-01-14', 'Monsieur KETELEER PRINCE - Entretien clim 12/2025', '2026.01/SN/5', 175.00],
            ['2026-01-14', 'LYS SARL - Redevance LYS TOWER 12/2025', '2026.01/SN/6', 200.00],
            ['2026-01-14', 'IMMEUBLE MARIE YVETTE - Redevance mensuelle 12/2025', '2026.01/SN/7', 174.00],
            ['2026-01-14', 'LYS SARL - Redevance LYS CENTER 11/2025', '2026.01/SN/8', 600.00],
            ['2026-01-14', 'IMMEUBLE LEGREY - Redevance mensuelle 12/2025', '2026.01/SN/9', 400.00],
            ['2026-01-14', 'ESPACE SHADARY - Redevance CITE VERTE 12/2025', '2026.01/SN/10', 200.00],
            ['2026-01-25', 'LIBERTY - Redevance mensuelle LUBUMBASHI 01/2026', '2026.01/SN/11', 577.50],
            ['2026-01-25', 'DEV GROUP - Redevance mensuelle DEV SOLAIRE LUSHI 01/2026', '2026.01/SN/12', 288.75],
            ['2026-01-25', 'IMMEUBLE MALAIKA - Redevance 1er TRIM.2026', '2026.01/SN/13', 450.00],
            ['2026-01-25', 'IMMEUBLE MARIE YVETTE - Redevance mensuelle 01/2026', '2026.01/SN/14', 182.70],
            ['2026-01-28', 'ESPACE SHADARY - Redevance CITE VERTE 01/2026', '2026.01/SN/15', 210.00],
            ['2026-01-28', 'ESPACE SHADARY - Redevance LUBEFU 01/2026', '2026.01/SN/16', 210.00],
            ['2026-01-28', 'SHEHENAJ L.M. - Redevance COMMERCE (HARMONIE) 01/2026', '2026.01/SN/17', 183.75],
            ['2026-01-28', 'SHEHENAJ L.M. - Redevance PACIFIQUE 01/2026', '2026.01/SN/18', 367.50],
            ['2026-01-28', 'A ONE CONSTRUCTION - Redevance mensuelle PANORAMIQUE 01/2026', '2026.01/SN/19', 210.00],
            ['2026-01-28', 'IMMEUBLE LEGREY - Redevance mensuelle 01/2026', '2026.01/SN/20', 420.00],
            ['2026-01-31', 'LYS SARL - Redevance LYS CENTER 12/2025', '2026.01/SN/21', 600.00],
            ['2026-01-31', 'LYS SARL - Redevance LYS TOWER 01/2026', '2026.01/SN/22', 400.00],
            ['2026-01-31', 'LYS SARL - Redevance LYS CENTER 01/2026', '2026.01/SN/23', 600.00],
            ['2026-01-31', 'ESPACE SHADARY - Redevance NYEMBO 01/2026', '2026.01/SN/24', 210.00],

            // ===== FEVRIER 2026 =====
            ['2026-02-16', 'SHEHENAJ L.M. - Redevance PACIFIQUE 02/2026', '2026.01/SN/25', 367.50],
            ['2026-02-16', 'A ONE CONSTRUCTION - Redevance PANORAMIQUE (ESTORIL) 02/2026', '2026.01/SN/26', 420.00],
            ['2026-02-16', 'LYS SARL - Redevance LYS TOWER 02/2026', '2026.01/SN/27', 400.00],
            ['2026-02-16', 'IMMEUBLE TATELIE - Redevance mensuelle 01/2026 - DECEMBRE', '2026.01/SN/28', 174.00],
            ['2026-02-16', 'LYS SARL - Entretien condenseur VRV+8 Evapo EUNICE/VILLA 5', '2026.02/SN/29', 225.00],
            ['2026-02-16', 'IMMEUBLE IMANI - Redevance mensuelle 02/2026', '2026.01/SN/30', 243.60],
            ['2026-02-16', 'RESIDENCE EMERAUDE - Entretien general clim 1er TOUR 2026', '2026.01/SN/31', 700.00],
            ['2026-02-16', 'LYS SARL - Dépannage clim BOUTIQUE COULOIR LYS CENTER', '2026.01/SN/32', 846.00],
            ['2026-02-16', 'LYS SARL - Dépannage machines VRV 2é&3é Etage LYS CENTER', '2026.01/SN/33', 194.60],
            ['2026-02-25', 'Monsieur THOMAS - Entretien clim 12/2025', '2026.01/SN/34', 210.00],
            ['2026-02-25', 'SHEHENAJ L.M. - Redevance COMMERCE (HARMONIE) 02/2026', '2026.01/SN/35', 183.75],
            ['2026-02-25', 'LYS SARL - Fo&po 4 garnitures de PRS / LYS CENTER', '2026.01/SN/36', 100.00],
            ['2026-02-25', 'RESIDENCE KAWELE - Redevance mensuelle 02/2026', '2026.01/SN/37', 250.00],
            ['2026-02-27', 'IMMEUBLE MARIE YVETTE - Redevance mensuelle 02/2026', '2026.01/SN/38', 182.70],
            ['2026-02-27', 'ESPACE SHADARY - Redevance CITE VERTE 02/2026', '2026.01/SN/39', 210.00],
            ['2026-02-28', 'LIBERTY - Redevance 02/2026', '2026.01/SN/40', 577.50],
            ['2026-02-28', 'DEV GROUP - Redevance DEV SOLAIRE 02/2026', '2026.01/SN/41', 288.75],
            ['2026-02-28', 'IMMEUBLE TATELIE - Redevance mensuelle 01/2026', '2026.01/SN/42', 174.00],
            ['2026-02-28', 'IMMEUBLE LEGREY - Redevance 02/2026', '2026.01/SN/43', 420.00],
            ['2026-02-28', 'ESPACE SHADARY - Redevance LUBEFU 02/2026', '2026.01/SN/44', 210.00],
            ['2026-02-28', 'ESPACE SHADARY - Redevance NYEMBO 02/2026', '2026.01/SN/45', 210.00],

            // ===== MARS 2026 =====
            ['2026-03-12', 'PROJET KIMPEVILLE - Installation & mise en service ASC/KIMPEVILLE', '2026.01/SN/46', 7640.00],
            ['2026-03-12', 'SHEHENAJ L.M. - Redevance PACIFIQUE 03/2026', '2026.01/SN/47', 367.50],
            ['2026-03-12', 'A ONE CONSTRUCTION - Redevance PANORAMIQUE (ESTORIL) 03/2026', '2026.01/SN/48', 420.00],
            ['2026-03-12', 'LIBERTY - Redevance SUPER MARCHE LUSHI 03/2026', '2026.01/SN/49', 577.50],
            ['2026-03-12', 'DEV GROUP - Redevance mensuelle 03/2026', '2026.01/SN/50', 288.75],
            ['2026-03-12', 'A ONE CONSTRUCTION - Fo&po 1 Crate GECB-AP / PANORAMIQUE', '2026.01/SN/51', 3418.08],
            ['2026-03-13', 'MR THOMAS - Chargement freon R22 split DAIKIN/BUREAU', '2026.01/SN/52', 140.00],
            ['2026-03-20', 'SHEHENAJ L.M. - Redevance COMMERCE (HARMONIE) 03/2026', '2026.01/SN/53', 183.75],
            ['2026-03-23', 'IMMEUBLE LEGREY - Fo&po 4 galets porte cabine & 4 glissieres ASC', '2026.01/SN/54', 553.00],
            ['2026-03-23', 'SHEHENAJ L.M. - Fo&po support detecteur ASC/PACIFIQUE', '2026.01/SN/55', 100.00],
            ['2026-03-26', 'IMMEUBLE LEGREY - Redevance 03/2026', '2026.01/SN/56', 420.00],
            ['2026-03-29', 'RESIDENCE KAWELE - Redevance 03/2026', '2026.01/SN/57', 250.00],
            ['2026-03-29', 'LYS SARL - Redevance LYS CENTER 02/2026', '2026.01/SN/58', 600.00],
            ['2026-03-29', 'IMMEUBLE MARIE YVETTE - Redevance mensuelle 03/2026', '2026.01/SN/59', 182.70],
            ['2026-03-29', 'ESPACE SHADARY - Redevance NYEMBO 03/2026', '2026.01/SN/60', 210.00],
            ['2026-03-31', 'LYS SARL - Fo&po 12 garnitures cabine ASC / LYS TOWER', '2026.01/SN/61', 1308.00],
            ['2026-03-31', 'COMIMMO - Entretien 95 cassettes VRV avec 8 condenseurs & 19 splits', '2026.01/SN/62', 6281.40],
            ['2026-03-31', 'ESPACE SHADARY - Redevance CITE VERTE 03/2026', '2026.01/SN/63', 210.00],
            ['2026-03-31', 'IMMEUBLE TATELIE - Redevance mensuelle 03/2026', '2026.01/SN/64', 174.00],
            ['2026-03-31', 'IMMEUBLE IMANI - Redevance mensuelle 03/2026', '2026.01/SN/65', 243.60],
            ['2026-03-31', 'SHEHENAJ L.M. - Fo&po 1 garniture cabine IMM.PACIFIQUE', '2026.01/SN/66', 109.00],
            ['2026-03-31', 'DFSN - Entretien general clim 1erTRIM.2026', '2026.01/SN/67', 245.00],
            ['2026-03-31', 'MAGASIN DIPLOMATIQUE - Entretien general clim 1erTRIM.2026', '2026.01/SN/68', 525.00],

            // ===== AVRIL 2026 =====
            ['2026-04-10', 'Mr PETER DEBOTTER - Entretien general clim AVRIL 2026', '2026.01/SN/69', 405.00],
            ['2026-04-29', 'RS HOLDING - Restitution retenue reception definitive', '2026.01/SN/70', 56900.00],
            ['2026-04-29', 'SHEHENAJ L.M. - Redevance PACIFIQUE 04/2026', '2026.01/SN/71', 367.50],
            ['2026-04-29', 'SHEHENAJ L.M. - Redevance COMMERCE (HARMONIE) 04/2026', '2026.01/SN/72', 183.75],
            ['2026-04-30', 'LIBERTY - Redevance SUPER MARCHE LUSHI 04/2026', '2026.01/SN/73', 577.50],
            ['2026-04-30', 'DEV GROUP - Redevance mensuelle 04/2026', '2026.01/SN/74', 288.75],
            ['2026-04-30', 'A ONE CONSTRUCTION - Redevance PANORAMIQUE (ESTORIL) 04/2026', '2026.01/SN/75', 420.00],
            ['2026-04-30', 'IMMEUBLE LEGREY - Redevance mensuelle 04/2026', '2026.01/SN/76', 420.00],
            ['2026-04-30', 'LYS SARL - Entretien general clim LYS CENTER 1erTRIM.2026', '2026.01/SN/77', 6562.50],
            ['2026-04-30', 'LYS SARL - Redevance LYS TOWER 03/2026', '2026.01/SN/78', 400.00],
            ['2026-04-30', 'LYS SARL - Redevance LYS TOWER 04/2026', '2026.01/SN/79', 400.00],
            ['2026-04-30', 'LYS SARL - Redevance LYS CENTER 03/2026', '2026.01/SN/80', 600.00],
            ['2026-04-30', 'LYS SARL - Redevance LYS CENTER 04/2026', '2026.01/SN/81', 600.00],
            ['2026-04-30', 'IMMEUBLE MALAIKA - Redevance trimestrielle 1erTRIM.2026', '2026.01/SN/82', 450.00],
            ['2026-04-30', 'RESIDENCE KAWELE - Redevance 04/2026', '2026.01/SN/83', 250.00],
            ['2026-04-30', 'IMMEUBLE TATELIE - Redevance mensuelle 04/2026', '2026.01/SN/84', 174.00],
            ['2026-04-30', 'ESPACE SHADARY - Redevance NYEMBO 04/2026', '2026.01/SN/85', 200.00],
            ['2026-04-30', 'ESPACE SHADARY - Redevance CITE VERTE 04/2026', '2026.01/SN/86', 210.00],
            ['2026-04-30', 'IMMEUBLE IMANI - Redevance mensuelle 04/2026', '2026.01/SN/87', 243.60],
            ['2026-04-30', 'IMMEUBLE MARIE YVETTE - Redevance mensuelle 04/2026', '2026.01/SN/88', 182.70],

            // ===== MAI 2026 =====
            ['2026-05-09', 'COMIMMO 1 - Entretien general clim 1erTRIM.2026', '2026.01/SN/89', 6281.40],
            ['2026-05-09', 'COMIMMO 4 - Fo 12m cable 50mm²', '2026.01/SN/90', 748.80],
            ['2026-05-09', 'LIBERTY - Redevance SUPER MARCHE LUSHI 05/2026', '2026.01/SN/91', 577.50],
            ['2026-05-09', 'A ONE CONSTRUCTION - Redevance PANORAMIQUE (ESTORIL) 05/2026', '2026.01/SN/92', 577.50],
            ['2026-05-09', 'SHEHENAJ L.M. - Redevance PACIFIQUE 05/2026', '2026.01/SN/93', 183.75],
            ['2026-05-09', 'SHEHENAJ L.M. - Redevance COMMERCE (HARMONIE) 05/2026', '2026.01/SN/94', 367.50],
            ['2026-05-20', 'DEV GROUP - Redevance mensuelle 05/2026', '2026.01/SN/95', 288.75],
            ['2026-05-20', 'RESIDENCE EMERAUDE - Entretien general 27 splits 2eTOUR2026', '2026.01/SN/96', 675.00],
            ['2026-05-20', 'IMMEUBLE EBATA - Depannage groupe electrogene', '2026.01/SN/97', 116.00],
            ['2026-05-20', 'IMMEUBLE LEGREY - Redevance 05/2026', '2026.01/SN/98', 420.00],
            ['2026-05-30', 'LYS SARL - Redevance LYS TOWER 05/2026', '2026.01/SN/99', 400.00],
            ['2026-05-30', 'RESIDENCE KAWELE - Redevance 05/2026', '2026.01/SN/100', 250.00],
            ['2026-05-30', 'ESPACE SHADARY - Redevance NYEMBO 05/2026', '2026.01/SN/101', 210.00],
            ['2026-05-30', 'ESPACE SHADARY - Redevance CITE VERTE 05/2026', '2026.01/SN/102', 210.00],
            ['2026-05-30', 'IMMEUBLE IMANI - Redevance mensuelle 05/2026', '2026.01/SN/103', 243.60],
            ['2026-05-30', 'IMMEUBLE MARIE YVETTE - Redevance mensuelle 05/2026', '2026.01/SN/104', 182.70],

            // ===== JUIN 2026 =====
            ['2026-06-05', 'SHEHENAJ L.M. - Redevance PACIFIQUE 06/2026', '2026.01/SN/105', 367.50],
            ['2026-06-05', 'COMIMMO - Fo&raccordement inverseur groupe 800kva cfr TRADEXPORT', '2026.01/SN/106', 45269.00],
        ];
    }
}
