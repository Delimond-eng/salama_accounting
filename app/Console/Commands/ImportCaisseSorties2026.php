<?php

namespace App\Console\Commands;

use App\Models\Ecriture;
use App\Models\SectionAnalytique;
use App\Models\Tiers;
use App\Services\SaisieComptableService;
use Illuminate\Console\Command;

class ImportCaisseSorties2026 extends Command
{
    protected $signature = 'caisse:import-sorties-2026 {--dry : Simule sans rien enregistrer} {--brouillon : Enregistre en brouillon au lieu de valider}';

    protected $description = 'Importe les sorties de caisse (USD -> CA-USD/57004, CDF -> CA-CDF/57005), exercice 2026';

    private int $societeId = 1;

    private int $exerciceId = 1;

    private int $journalUsd = 24;   // CA-USD
    private int $journalCdf = 25;   // CA-CDF
    private string $caisseUsd = '57004';
    private string $caisseCdf = '57005';

    /** @var array<string,int> code section => id */
    private array $sectionIds = [];

    /** @var array<string,int> cache clé => tiers id */
    private array $tiersCache = [];

    /** Code du mois (utilisé dans les références d'idempotence). */
    protected function moisCode(): string
    {
        return '01';
    }

    public function handle(SaisieComptableService $saisie): int
    {
        $dry = (bool) $this->option('dry');
        $valider = ! (bool) $this->option('brouillon');

        $this->sectionIds = SectionAnalytique::where('societe_id', $this->societeId)
            ->pluck('id', 'code')->toArray();

        $rows = $this->rows();
        $created = 0;
        $skipped = 0;
        $vides = 0;
        $errors = [];
        $parCompte = [];
        $totUsd = 0.0;
        $totCdf = 0.0;

        foreach ($rows as $i => $r) {
            [$date, $label, $usd, $cdf] = $r;

            if (($usd === null || $usd <= 0) && ($cdf === null || $cdf <= 0)) {
                $vides++;
                continue;
            }

            $compteCharge = $this->resolveCharge($label);
            $parCompte[$compteCharge] = ($parCompte[$compteCharge] ?? 0) + 1;

            // Analytique : seulement sur les charges (classe 6) dont le libellé cite un chantier connu
            $sectionId = null;
            if (str_starts_with($compteCharge, '6')) {
                $code = $this->resolveSectionCode($label);
                $sectionId = $code ? ($this->sectionIds[$code] ?? null) : null;
            }

            $seq = sprintf('CSE/2026/%s/%03d', $this->moisCode(), $i + 1);

            if ($usd !== null && $usd > 0) {
                $totUsd += $usd;
                if (! $dry) {
                    $errors = array_merge($errors, $this->enregistrer(
                        $saisie, $valider, $date, $label, $seq.'-USD', 'USD',
                        $this->journalUsd, $compteCharge, $this->caisseUsd, $usd, $created, $skipped, $sectionId
                    ));
                }
            }

            if ($cdf !== null && $cdf > 0) {
                $totCdf += $cdf;
                if (! $dry) {
                    $errors = array_merge($errors, $this->enregistrer(
                        $saisie, $valider, $date, $label, $seq.'-CDF', 'CDF',
                        $this->journalCdf, $compteCharge, $this->caisseCdf, $cdf, $created, $skipped, $sectionId
                    ));
                }
            }
        }

        // ---- Rapport ----
        $this->info($dry ? '=== SIMULATION (dry-run) ===' : '=== IMPORT SORTIES CAISSE ===');
        $this->line('Lignes source : '.count($rows).' | vides (ignorées) : '.$vides);
        if (! $dry) {
            $this->line("Écritures créées : {$created} | ignorées (déjà présentes) : {$skipped}");
        }
        $this->line('Total sorties USD : '.number_format($totUsd, 2).' $');
        $this->line('Total sorties CDF : '.number_format($totCdf, 2).' FC');

        $this->newLine();
        $this->line('--- Répartition par compte de charge ---');
        ksort($parCompte);
        foreach ($parCompte as $compte => $n) {
            $this->line(sprintf('  %s : %d ligne(s)', $compte, $n));
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

    private function enregistrer(
        SaisieComptableService $saisie,
        bool $valider,
        string $date,
        string $label,
        string $ref,
        string $devise,
        int $journalId,
        string $compteCharge,
        string $caisse,
        float $montant,
        int &$created,
        int &$skipped,
        ?int $sectionId = null
    ): array {
        if (Ecriture::where('societe_id', $this->societeId)->where('reference_externe', $ref)->exists()) {
            $skipped++;
            return [];
        }

        // Les comptes de tiers (classe 4 : avances 421/409) exigent un tiers.
        $tiersId = $this->tiersPourCharge($compteCharge, $label);

        $lignes = [
            array_filter([ // Charge (débit)
                'num_compte' => $compteCharge,
                'libelle' => $label,
                'debit' => $montant,
                'credit' => 0,
                'tiers_id' => $tiersId,
                'section_analytique_id' => $sectionId,
            ], fn ($v) => $v !== null),
            [ // Sortie de caisse (crédit)
                'num_compte' => $caisse,
                'libelle' => $label,
                'debit' => 0,
                'credit' => $montant,
            ],
        ];

        try {
            $saisie->enregistrer($this->societeId, [
                'exercice_id' => $this->exerciceId,
                'journal_id' => $journalId,
                'date_ecriture' => $date,
                'date_piece' => $date,
                'libelle' => $label,
                'type_ecriture' => 'normale',
                'reference_externe' => $ref,
                'devise' => $devise,
            ], $lignes, $valider);
            $created++;
        } catch (\Throwable $e) {
            return ["Ligne {$ref} : ".$e->getMessage()];
        }

        return [];
    }

    /**
     * Résout (ou crée) le tiers d'une charge sur compte de classe 4 (avances 421/409),
     * à partir du nom en tête de libellé. Retourne null pour les comptes sans tiers.
     */
    private function tiersPourCharge(string $compte, string $label): ?int
    {
        if (! str_starts_with($compte, '4')) {
            return null;
        }

        $nom = trim(explode(' - ', $label, 2)[0]);
        if ($nom === '') {
            $nom = 'DIVERS CAISSE';
        }

        $cacheKey = $compte.'|'.$nom;
        if (isset($this->tiersCache[$cacheKey])) {
            return $this->tiersCache[$cacheKey];
        }

        $type = match ($compte) {
            '421100' => 'salarie',
            '409100' => 'fournisseur',
            default => 'autre',
        };

        $tiers = Tiers::where('societe_id', $this->societeId)->where('nom', $nom)->first();
        if (! $tiers) {
            $base = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $nom));
            $code = 'T-CSE-'.substr($base, 0, 12).'-'.substr(md5($cacheKey), 0, 4);
            $tiers = Tiers::create([
                'societe_id' => $this->societeId,
                'code' => $code,
                'nom' => $nom,
                'type' => $type,
                'num_compte_collectif' => $compte,
                'actif' => true,
            ]);
        }

        return $this->tiersCache[$cacheKey] = $tiers->id;
    }

    private function normalize(string $s): string
    {
        $s = strtoupper($s);

        return strtr($s, [
            'É' => 'E', 'È' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'À' => 'A', 'Â' => 'A',
            'Î' => 'I', 'Ï' => 'I', 'Ô' => 'O', 'Û' => 'U', 'Ù' => 'U', 'Ç' => 'C',
        ]);
    }

    /** Mappe un libellé vers un compte de charge SYSCOHADA (défaut : 638800 charges diverses). */
    private function resolveCharge(string $label): string
    {
        $l = $this->normalize($label);

        // Opération de change : libellé strictement « CHANGE » (évite « ReCHANGEs »).
        if (trim($l) === 'CHANGE') {
            return '585000';
        }

        foreach ($this->chargeRules() as [$needles, $compte]) {
            foreach ($needles as $n) {
                if (strpos($l, $n) !== false) {
                    return $compte;
                }
            }
        }

        return '638800';
    }

    /**
     * Résout le code de section analytique (axe CHANTIERS) à partir du libellé,
     * uniquement quand un chantier/immeuble connu est cité de façon non ambiguë.
     */
    public function resolveSectionCode(string $label): ?string
    {
        $l = $this->normalize($label);

        foreach ($this->sectionRules() as [$needles, $code]) {
            foreach ($needles as $n) {
                if (strpos($l, $n) !== false) {
                    return $code;
                }
            }
        }

        return null;
    }

    /** Mapping prudent libellé -> code section (ordre : plus spécifique d'abord). */
    private function sectionRules(): array
    {
        return [
            [['AGL/'], 'AGLDG'],                  // AGL/RES.DG, AGL/SIEGE (pas « AGL - tarmac »)
            [['UTEX/BCECO', 'BCECO'], 'UTEX-BAT128'],
            [['UTEXAFRICA', 'UTEX'], 'UTEX'],
            [['LYS CENTER'], 'CENTER'],
            [['ATRIUM'], 'RAW-ATRI'],
            [['COMIMMO', 'COIMIMMO'], 'COMIMMO'],
            [['RS HOLDING'], 'RSH'],
            [['MONKOLE'], 'MONK'],
            [['HELLENIQUE'], 'HEL'],
            [['MARIE YVETTE'], 'MARYV'],
            [['IMANI'], 'IMAN'],
            [['DELPHIN'], 'DEL'],
            [['AFRICANA PALACE'], 'PAL'],
            [['LEGREY'], 'LEG'],
            [['SERKAS'], 'SERKAS'],
            [['SILIKIN'], 'SILKN'],
            [['EBCDC'], 'EBCDC'],
            [['BRITISH EMBASSY', 'AMBASSADE UK', 'UK EMBASSY'], 'BRIT'],
            [['EMERAUDE'], 'JES'],
            [['ZHENDRE'], 'ZHE'],
            [['MAJESTIC'], 'MAJ-RIV'],
            [['LPL'], 'LPL-LYCEE'],
            [['VLISCO'], 'VLISCO'],
            [['EBALE'], 'EBALE'],
            [['EBATA'], 'EBATA'],
            [['TEMPLE'], 'EJCKIN'],
            [['TCK'], 'TCK-KIN'],
        ];
    }

    /** Règles ordonnées (les plus spécifiques d'abord). */
    private function chargeRules(): array
    {
        return [
            // --- Mouvements internes de fonds (retrait/appro/retour caisse) ---
            // NB : motifs spécifiques pour ne pas capter « RETRAITE » (acompte retraite).
            [['RETRAIT CFR', 'RETRAIT CAISSE', 'USD - RETRAIT', 'APPRO CAISSE', 'APPROVISIONNEMENT CAISSE', 'RETOUR CAISSE'], '585000'],

            // --- Personnel & charges sociales ---
            [['CNSS'], '664100'],
            [['IPR'], '641300'],
            [['COTISATION SYNDICALE', 'SYNDICAL'], '668100'],
            [['TEMPORAIRES', 'JOURNALIERS', 'PRESTATIONS AGENTS', 'PRESTATIONS '], '637100'],
            [['AVANCE PAIE', 'SMIG', 'PAIE AGENTS', 'QUINZAINE', 'HS '], '661100'],
            [['SOLDE PRIME', 'PRIME', 'GRATIFICATION'], '661200'],
            [['SOLDE CONGE', 'CONGE'], '661300'],
            [['FUNERAIRE'], '663800'],
            // Avance / acompte pension de retraite au personnel (actif, pas une charge)
            [['PENSION DE RETRAITE', 'PENSION RETRAITE'], '421100'],
            // Avance au personnel (actif, pas une charge) — après AVANCE PAIE ci-dessus
            [['AVANCE'], '421100'],

            // --- Réparation / entretien véhicules ---
            [['REPARATION PNEU', 'PNEU'], '624200'],
            [['ENTRETIEN NISSAN', 'RECHANGES NISSAN', 'PIECES PICKUP', 'BATTERIE', 'AMORTISSEUR',
                'HUILE MOTEUR', 'BOUCHON RADIATEUR', 'VITRE RETROVISEUR'], '624200'],
            [['REPARATION IMPRIMANTE', 'IMPRIMANTE', 'PHOTOCOPIEUSE', 'STABILISATEUR'], '624800'],

            // --- Taxes & frais véhicules / banque ---
            [['FRAIS BANCAIRE', 'FRAIS BANK', 'FRAIS BANCAIRES'], '631800'],
            [['CCT', 'MINISTERE DE TRANSPORT', 'MINISTERE TRANSPORT'], '646300'],

            // --- Communications ---
            [['COMMUNICATION', 'UNITES', 'UNITE'], '628100'],

            // --- Carburant ---
            [['CARBURANT'], '604200'],

            // --- Déplacements / voyages ---
            [['AERO', 'PROTOCOLE', 'PARKING', 'BAGAGES', 'TARMAC', 'BILLET', 'PCR', 'CARTE ROSE'], '618100'],
            [['COURSE DE SERVICE'], '618300'],
            [['TRANSPORT', 'TRFT'], '614000'],

            // --- Énergie / eau ---
            [['PREPAID', 'ELECTRICITE'], '605200'],
            [['REGIDESO', 'ACHAT EAU'], '605100'],

            // --- Entretien immeubles / sous-traitance ---
            [['REBOBINAGE'], '624200'],
            [['PEINTURE', 'TOITURE', 'M.O.', 'ELECTRICIEN'], '624100'],
            [['EVACUATION POUBELLE', 'IMMONDICES'], '624300'],

            // --- Assurance / import ---
            [['ASSURANCE', 'FERI'], '625800'],
            // Acomptes containers = avance fournisseur (actif), pas une charge
            [['ACPTE CONTAINER', 'ACOMPTE CONTAINER'], '409100'],

            // --- Locations ---
            [['LOCATION'], '623200'],

            // --- Bureau / documentation ---
            [['FOURNITURES DE BUREAU', 'FOURNITURE DE BUREAU', 'AGENDA'], '604700'],
            [['IMPRESSION', 'PLANS A0', 'PLAN A0'], '626600'],
            [['AUTOMATISATION', 'FACTURATION'], '632700'],

            // --- Outils / petits équipements ---
            [['KARSHER', 'ECHELLE', 'MANOMETRE', 'CLE A GAZ', 'COUPE-TUBE', 'DIJONNAIRE',
                'ONDULAIRE', 'GILETS', 'OUTIL'], '605600'],

            // --- Produits d'entretien / magasin ---
            [['SAVON'], '604600'],

            // --- Matières & fournitures techniques (HVAC) ---
            [['FREON', 'ARMAFLEX', 'CABLE', 'DISQUE', 'COLLIER', 'RELAIS', 'DISJONCTEUR',
                'TUBE CU', 'TUBES CU', 'NUPPLE', 'CORNIERE', 'ELECTRODE', 'GAZ', 'BOUTEILLE',
                'FUSIBLE', 'LAMPE', 'PROJECTEUR', 'PRESSOSTAT', 'FLOTTEUR', 'COURROIE',
                'SOUDAL', 'COLLE', 'BAGUETTE', 'PLANCHE', 'AZOTE', 'ACETYLENE', 'OXYGENE',
                'PROPANE', 'BUTANE', 'RACCORD', 'AMPOULE', 'ATTACHES-CABLES', 'SACHET',
                'SCOTCH', 'CARTE UNIVERSELLE', 'CARTES UNIVERSELLE', 'BASSIN', 'SCEAU',
                'THINNER', 'EMAIL', 'ACCESSOIRES', 'CHARBON', 'ECLAIRAGE', 'CONDENSEUR',
                'RADIATEUR', 'GAINES', 'PVC'], '602100'],
        ];
    }

    /** @return list<array{0:string,1:string,2:?float,3:?float}> [date ISO, libellé, montant USD|null, montant CDF|null] */
    protected function rows(): array
    {
        return [
            ['2026-01-02', 'RESIDENCE IVAN - Paie agents 12/2025', 420.00, null],
            ['2026-01-02', 'RESIDENCE DG - Paie agents 12/2025', 450.00, null],
            ['2026-01-02', 'EGSC - SOLDE PRIME agents 12/2025', 2100.00, null],
            ['2026-01-02', 'WILLY - Prime 2025', 900.00, null],
            ['2026-01-02', 'WILLY - (Frais funeraires)', 600.00, null],
            ['2026-01-02', 'SERGE - TRFT / LUSHI + Frais', 150.00, 10000.00],
            ['2026-01-02', 'CHANGE', 850.00, null],
            ['2026-01-02', 'CDT - Cotisation syndicale s/Gratification ELECTROCOOL', null, 410000.00],
            ['2026-01-02', 'DIDIER - Rechanges NISSAN DG', null, 1449000.00],
            ['2026-01-02', 'MBUTELA Brandon - complement prime 12/2025', 50.00, null],
            ['2026-01-02', 'ATRIUM - 10 Disques c125', null, 19800.00],
            ['2026-01-02', 'FRANCK - Location Véhicule / Transport container colis', 200.00, null],
            ['2026-01-02', 'FRANCK - Frais journaliers colis/ CONTAINER', 100.00, null],
            ['2026-01-02', 'FRANCK - Transport chauffeurs DIDIER&MBENKIE', null, 50000.00],
            ['2026-01-02', 'BENI - Travaux peinture RESIDENCE DG', 55.00, null],
            ['2026-01-02', 'RESIDENCE DG - 1 Relais compact 20A cfr GEIRGES', 25.00, null],
            ['2026-01-03', 'ALBERT - Carburant NISSAN/DG-PRADO-NISSAN PATROL', 150.00, null],
            ['2026-01-03', 'DIDIER - Frais electricien ANCIEN JAC', 150.00, null],
            ['2026-01-03', 'ALBERT - Frais aero, protocole, parking, bagages', 60.00, null],
            ['2026-01-06', 'CHICK - Achat eau DG&BUREAU', 20.00, null],
            ['2026-01-06', 'CLAUDE - Carburant PICKUP L200', 50.00, null],
            ['2026-01-06', 'COMMUNICATIONS willy, georges, urbain', 60.00, null],
            ['2026-01-06', 'HANS - Communications', 20.00, null],
            ['2026-01-06', 'COMMUNICATIONS franck, kusiekita, bangamo', 30.00, null],
            ['2026-01-06', 'NDONA - Communications', 10.00, null],
            ['2026-01-06', 'RESIDENCE IVAN - Electricité prepaid', 100.00, null],
            ['2026-01-06', 'PGHK - 1Lg flexible non isolé 100', 25.00, null],
            ['2026-01-06', 'LYS CENTER - 1 Bouteille freon 410', 140.00, null],
            ['2026-01-06', 'CHANGE', 200.00, null],
            ['2026-01-06', 'CDT - Cotisation syndicale / GRATIFICATION 2025', null, 410000.00],
            ['2026-01-06', 'ATRIUM - 10 Disques c125 (19800fc)', null, 16800.00],
            ['2026-01-06', 'PRIMMO - Frais assurance + feri 2CONTAINERS', 2000.00, null],
            ['2026-01-07', 'MALEWU - Carburant TATA/DELUXE', 50.00, null],
            ['2026-01-07', 'PROSPERE - Reparation pneu NISSAN PATROL', null, 10000.00],
            ['2026-01-07', 'CHICK - Communications', 10.00, null],
            ['2026-01-07', 'CLAUDE - Frais aero, protocole, parking&divers', 40.00, null],
            ['2026-01-07', 'PRIMMO - Impressions 3plans A0', 60.00, null],
            ['2026-01-07', 'LEKO - Carburant IST', 10.00, null],
            ['2026-01-07', 'LOSEMBE 1 - Transport RESIDENCE DG', null, 10000.00],
            ['2026-01-07', 'CHANGE', 50.00, null],
            ['2026-01-07', 'KINKOLE - Frais journaliers entretien CF/HUB', null, 80000.00],
            ['2026-01-08', 'AGL/RES.DG - 1 Disjoncteur GV2 & 1support condenseur', 65.00, null],
            ['2026-01-08', 'MBENKIE - Carburant JAC/CONTAINER', 50.00, null],
            ['2026-01-08', 'MAKANDA - Communications', 10.00, null],
            ['2026-01-08', 'LOSEMBE 1 - Carburant groupe NDOLO', 100.00, null],
            ['2026-01-08', 'PRIMMO - Impression 7 plans A0', 140.00, null],
            ['2026-01-08', 'LEKO - Carburant IST', 10.00, null],
            ['2026-01-08', 'CHANGE', 1800.00, null],
            ['2026-01-08', 'EGSC - CNSS 12/2025', null, 2248500.00],
            ['2026-01-08', 'EGSC - IPR 12/2025', null, 1780062.00],
            ['2026-01-09', 'GEORGES - Carburant NISSAN PATROL', 60.00, null],
            ['2026-01-09', 'DIDIER - Course de service', null, 10000.00],
            ['2026-01-09', 'ATRIUM - 2Lg planches & 2 disques c230', 40.00, null],
            ['2026-01-09', 'RESIDENCE IVAN - Eclairage chambre parents', 21.00, null],
            ['2026-01-09', 'RESIDENCE DG - Eclairage salle de bain', 21.00, null],
            ['2026-01-09', 'PRIMMO - 15m cable souple 25mm²', 105.00, null],
            ['2026-01-09', 'PGHK - 1 Carton colle contact', 60.00, null],
            ['2026-01-09', 'ALVARO - Evacuation poubelle NDOLO', null, 10000.00],
            ['2026-01-09', 'LEKO - Carburant IST', 10.00, null],
            ['2026-01-10', 'CHICK - Carburant HYUNDAI SANTAFE', 50.00, null],
            ['2026-01-10', 'ALBERT - Carburant PRADO/DG', 50.00, null],
            ['2026-01-10', 'CHICK - Frais aero , protocole, parking, bagages, divers', 60.00, null],
            ['2026-01-12', 'ABEL - Rebobinage 2 moteurs condenseurs/AGL', 300.00, null],
            ['2026-01-12', 'PRIMMO - 5 Pqts colliers plastic', 50.00, null],
            ['2026-01-12', 'COMMUNICATIONS willy, georges, urbain', 60.00, null],
            ['2026-01-12', 'MNAOUER - Communications', 20.00, null],
            ['2026-01-12', 'COMMUNICATIONS franck, kusiekita, bangamo', 30.00, null],
            ['2026-01-12', 'MONTASSAR&MAIL - Communications', 40.00, null],
            ['2026-01-12', 'CHANGE', 4500.00, null],
            ['2026-01-12', 'ELECTROCOLL - Quinzaine 01/2026', null, 2920000.00],
            ['2026-01-12', 'EGSC - Quinzaine 01/2026', null, 2225000.00],
            ['2026-01-12', 'TEMPORAIRES - Quinzaine 01/2026', null, 5150000.00],
            ['2026-01-12', 'EUNICE - 1 Boite SOUDAL cfr GEORGES', 15.00, null],
            ['2026-01-13', 'FRANCK - Trft SERGE/LUBUMBASHI', 155.00, null],
            ['2026-01-13', 'MALEWU - Carburant TATA DELUXE', 60.00, null],
            ['2026-01-13', 'CLAUDE - Carburant PICKUP L200', 50.00, null],
            ['2026-01-13', 'PRIMMO - Acpte container 4&5 cfr GEORGES NAWEJ', 20000.00, null],
            ['2026-01-13', 'DIDIER - Carburant groupe NDOLO', 50.00, null],
            ['2026-01-13', 'CHICK - Carburant PRADO/DG', 50.00, null],
            ['2026-01-13', 'FRANCK - Carburant IST', 40.00, null],
            ['2026-01-13', 'DIDIER - Entretien NISSAN PATROL / GEORGES', 315.00, null],
            ['2026-01-14', 'PRIMMO - 2Gaz propane, btes colle tangit, 10pqts baguettes Ag', 250.00, null],
            ['2026-01-14', 'UTEX/BCECO - 3 Disques diamant 230', 75.00, null],
            ['2026-01-14', 'PRIMMO - 1 Bouteille azote, acétylène, oxygene', 316.00, null],
            ['2026-01-14', 'PRIMMO - 1Bte dijonnaire, coupe-tubes; 5pqts nupples---', 160.00, null],
            ['2026-01-14', 'AGL - Frais accès tarmac aero', 133.00, null],
            ['2026-01-14', 'ETS NEW CEPRONAC - Passage immondices IY 12/2025', 100.00, null],
            ['2026-01-14', 'USCT - Reparation imprimante TOSHIBA', 151.00, null],
            ['2026-01-14', 'CHANGE', 100.00, null],
            ['2026-01-14', 'PRIMMO - 8 Journaliers du 06/01 + 4Journaliers du 07/01/2026', null, 240000.00],
            ['2026-01-14', 'LYS CENTER - 2 Fusibles cartouche complet cfr GEORGES', 20.00, null],
            ['2026-01-15', 'RESIDENCE IVAN - Quinzaine agents 01/2025', 250.00, null],
            ['2026-01-15', 'RESIDENCE DG - Quinzaine agents 01/2025', 300.00, null],
            ['2026-01-15', 'KINKOLE - 1 Pressostat hydrofort & 1 flotteur bache eau', 110.00, null],
            ['2026-01-15', 'EUIPE DEPANNAGE - 1 Bouteille freon 410', 140.00, null],
            ['2026-01-15', 'Equipe DEPANNAGE & MAGASIN - 2Bouteilles freon R22', 170.00, null],
            ['2026-01-15', 'UTEX/BCECO - Accessoires dibers cfr bon 3288', 190.00, null],
            ['2026-01-15', 'UTEX/BCECO - 70 Colliers galva 63', 245.00, null],
            ['2026-01-15', 'ALBERT - Carburant NISSAN/DG', 60.00, null],
            ['2026-01-15', 'GEORGES - Course de service du 14/01/2026', null, 20000.00],
            ['2026-01-15', 'LOSEMBE 1 - Transport RESIDENCE IVAN', null, 10000.00],
            ['2026-01-15', 'CDI - Divers/Facture normalisée', 200.00, null],
            ['2026-01-15', 'CHANGE', 100.00, null],
            ['2026-01-15', 'NDOLO - Facture REGIDESO 12/2025', null, 308500.00],
            ['2026-01-15', 'ATRIUM - 1 Clé à gaz 6"', 15.00, null],
            ['2026-01-15', 'SILIKIN - 1 Ondulaire 600VA', 100.00, null],
            ['2026-01-15', 'RESIDENCE IVAN - 2 Relais 30A', 50.00, null],
            ['2026-01-17', 'FRANCK - Achat billet aller simple TURKISH MONTASSAR', 969.00, null],
            ['2026-01-20', 'MALEWU - Carburant TATA/DELUXE', 60.00, null],
            ['2026-01-20', 'MBENKIE - Carburant JAC ANCIEN', 50.00, null],
            ['2026-01-20', 'DIDIER - Carburant JAC/CONTAINER', 50.00, null],
            ['2026-01-20', 'LOSEMBE 1 - Carburant groupe NDOLO', 100.00, null],
            ['2026-01-20', 'CHICK - Carburant PRADO & HYUNDAI', 140.00, null],
            ['2026-01-20', 'YAMAFRET - 2e Acpte container 4-5', 15000.00, null],
            ['2026-01-20', 'URBAIN - Fournitures de bureau', 451.00, null],
            ['2026-01-20', 'DIDIER - 1 Batterie 100A 12V jac:ancien', 147.00, null],
            ['2026-01-20', 'COMMUNICATIONS willy, urbain, georges, nancy', 80.00, null],
            ['2026-01-20', 'MNAOUER - Communications', 20.00, null],
            ['2026-01-20', 'COMMUNICATIONS franck, kusiekita, bangamo', 30.00, null],
            ['2026-01-20', 'MNAOUER - Avance', 1500.00, null],
            ['2026-01-20', 'KINKOLE - Quinzaine maintenanciers HUB 01/2026', 100.00, null],
            ['2026-01-20', 'MAWAMA Exaucé - Transport NDOLO-LIMETE / AGL', null, 5000.00],
            ['2026-01-20', 'DIVERS', 100.00, null],
            ['2026-01-21', 'LOSEMBE URBAIN - Solde congé 2025', 100.00, null],
            ['2026-01-21', 'Jean Grégoire - Frais automatisation fichiers/FACTURATION', 60.00, null],
            ['2026-01-21', 'DIDIER - Transport', null, 10000.00],
            ['2026-01-21', 'PRIMMO - 1 Manometre frigorifique cfr kusiekita', 150.00, null],
            ['2026-01-21', 'AGL/SIEGE - Rlx tubes cu cfr bon 3298', 164.00, null],
            ['2026-01-21', 'DIDIER - 10L huile moteur SAE 40 STOCK', 70.00, null],
            ['2026-01-21', 'LOSEMBE 1 - 1 Relais compact stabilisateur photocopieuse', 25.00, null],
            ['2026-01-21', 'LOSEMBE 1 - Transport menuisier', 20.00, null],
            ['2026-01-22', 'ALVARO - Unités Mme VANESSA', 50.00, null],
            ['2026-01-22', 'LOSEMBE 1 - 1 Paire de charbon souffleur', 12.00, null],
            ['2026-01-22', 'RESIDENCE IVAN - Reparation toiture', 345.00, null],
            ['2026-01-22', 'DIDIER - Pièces PICKUP L200', 638.00, null],
            ['2026-01-22', 'CHANGE', 350.00, null],
            ['2026-01-22', 'KINKOLE - Frais journaliers entretien CF/HUB + Transport', 120.00, 80000.00],
            ['2026-01-22', 'ALI DANANI - Transport Gregory du 14 au 23/01/2026', null, 30000.00],
            ['2026-01-22', 'EQUIPE ENTRETIEN - 2 Bassins GF & 2 sceaux', null, 118000.00],
            ['2026-01-22', 'UTEX/BCECO - 2Rlx sachet bleu + scotch GF', null, 204000.00],
            ['2026-01-22', 'RESIDENCE IVAN - Facture REGIDESO 12/2025', null, 199500.00],
            ['2026-01-22', 'ATRIUM - 6 Journaliers transport gaines au 4e Etage', null, 120000.00],
            ['2026-01-22', 'MONKOLE - Transport Bosako', null, 12500.00],
            ['2026-01-22', 'NANCY - Achat agenda 2026', null, 12500.00],
            ['2026-01-22', 'DIVERS CDI', 200.00, null],
            ['2026-01-23', 'KINKOLE - 1 Raccord union PVC 32', null, 10000.00],
            ['2026-01-23', 'WAPETWA - Transport KINKOLE', null, 20000.00],
            ['2026-01-23', 'ALVARO - Evacuation poubelle NDOLO', null, 10000.00],
            ['2026-01-23', 'RESIDENCE DG - 1 Relais compresseur carte split', 30.00, null],
            ['2026-01-23', 'LOSEMBE 1 - 3Lampes eco/MAGASIN', 36.00, null],
            ['2026-01-23', 'RESIDENCE IVAN - 1Projecteur, ampoule, 2colliers M', 41.00, null],
            ['2026-01-23', 'DIDIER - 1 Amortisseur NISSAN/DG', 40.00, null],
            ['2026-01-23', 'PGHK - HS samedi 11/10/2025 ndona, boko, ego, imbana---', null, 97000.00],
            ['2026-01-23', 'PGHK - HS samedi 18/10/2025 ndona, wapetwa, steve, christian---', null, 133000.00],
            ['2026-01-23', 'PROSPERE - HS mercredi 17/12/2025', null, 35000.00],
            ['2026-01-23', 'IMM.PANORAMA - HS samedi 03/01/2026 lioko', null, 18500.00],
            ['2026-01-23', 'EBCDC - HS 23/12 AU 31/12/2025 mane', null, 35000.00],
            ['2026-01-23', 'CLAUDE - HS du 07 au 08/01/2026 AERO', null, 73460.00],
            ['2026-01-23', 'ATRIUM - 4 Gilets d\'accès à la RAWBANK', null, 40000.00],
            ['2026-01-23', 'NANCY - Carburant IST', 40.00, null],
            ['2026-01-23', 'CHANGE', 50.00, null],
            ['2026-01-23', 'RESIDENCE IVAN - 1Pqt attaches-cables jardin', null, 20000.00],
            ['2026-01-26', 'LOSEMBE 1 - Carburant groupe NDOLO', 100.00, null],
            ['2026-01-26', 'MALEWU - Carburant TATA DELUXE', 50.00, null],
            ['2026-01-26', 'GEORGES - Carburant NISSAN PATROL', 60.00, null],
            ['2026-01-26', 'AGL - Frais accès tarmac aero 3jours', 312.00, null],
            ['2026-01-26', 'PROSPERE - Carburant NISSAN/DG', 100.00, null],
            ['2026-01-26', 'PROSPERE - Communications', 10.00, null],
            ['2026-01-26', 'CLAUDE - Carburant PICKUP L200', 50.00, null],
            ['2026-01-26', 'MINISTERE DE TRANSPORT - CCT Veicules 1er semestre 2026', 800.00, null],
            ['2026-01-27', 'MBENKIE - Carburant JAC/CONTAINER', 50.00, null],
            ['2026-01-27', 'CHICK - Carburant PRADO', 70.00, null],
            ['2026-01-27', 'MAGASIN - 2 Crts savon liquide', 60.00, null],
            ['2026-01-27', 'CHANGE', 17500.00, null],
            ['2026-01-27', 'MAGASIN - 5 Gaz butane GF', 75.00, null],
            ['2026-01-27', 'MAGASIN - 1 Bouteille freon 410a', 140.00, null],
            ['2026-01-27', 'COMMUNICATIONS willy, nancy, urbain, georges', 80.00, null],
            ['2026-01-27', 'MNAOUER - Communications', 20.00, null],
            ['2026-01-27', 'COMMUNICATIONS franck, kusiekita, bangamo', 30.00, null],
            ['2026-01-28', 'ATRIUM - 1Rlx tube cu 1/4-3/8 & 8Lg armaflex 1/4-3/8', 134.00, null],
            ['2026-01-28', 'LYS CENTER - 1 Disjoncteur modulaire 32A', 40.00, null],
            ['2026-01-28', 'RS HOLDING - 4 Courroies dentée A33', 40.00, null],
            ['2026-01-28', 'MALEWU - Reparation pneus TATA DELUXE', null, 15000.00],
            ['2026-01-28', 'RS HOLDING - 2Gaz butane + 1Lg armaflex 7/8', 41.00, null],
            ['2026-01-28', 'MNAOUER - 2 Karshers à pression & 2 echelles', 420.00, null],
            ['2026-01-28', 'CHANGE', 9500.00, null],
            ['2026-01-28', 'MINISTERE TRANSPORT - CCT HYUNDAI+Frais bank', 80.00, 14000.00],
            ['2026-01-28', 'MINISTERE TRANSPORT - Frais bancaires CCT/Charroi', 20.00, null],
            ['2026-01-29', 'RESIDENCE IVAN - M.O. Reparation toiture', 200.00, null],
            ['2026-01-29', 'SUCRIERE/KINSHASA - 1 Disjoncteur GVII 9-14A', 50.00, null],
            ['2026-01-29', 'CHANGE', 2750.00, null],
            ['2026-01-29', 'ELECTROCOOL - AVANCE PAIE/SMIG 01/2026', null, 26423353.00],
            ['2026-01-29', 'Egsc - AVANCE PAIE/SMIG 01/2026', null, 20510311.00],
            ['2026-01-29', 'TEMPORAIRES - PRESTATIONS 01/2026', null, 20297585.00],
            ['2026-01-29', 'CDT - Cotisation syndicale 012026', null, 405176.00],
            ['2026-01-29', 'DIDIER - 1Bouchon radiateur IST & Vitre retroviseur JAC', null, 20000.00],
            ['2026-01-29', 'DIVERS CDI', 200.00, null],
            ['2026-01-30', 'ALBERT - Carburant NISSAN PATROL', 50.00, null],
            ['2026-01-30', 'DIDIER - Carburant TATA DELUXE', 50.00, null],
            ['2026-01-30', 'LEKO - Carburant IST', 40.00, null],
            ['2026-01-30', 'DIDIER - Carburant JAC/ANCIEN', 50.00, null],
            ['2026-01-30', 'SUCRIERE - Prestations agents 01/2026', 840.00, null],
            ['2026-01-30', 'UTEXAFRICA - 1 Bouteille freon 410A - Kotibe 4', 140.00, null],
            ['2026-01-30', 'PRIMMO - 1 Bouteille acetylene, 1 azote', 276.00, null],
            ['2026-01-30', 'PRIMMO - 30Lg cornieres, 2pqts electrodes, email, thinner', 705.00, null],
            ['2026-01-30', 'PRIMMO - 2Disques c115, 30nupples, gaz propane----', 182.00, null],
            ['2026-01-30', 'RESIDENCE IVAN - Paie agents 12/2025', 420.00, null],
            ['2026-01-30', 'RESIDENCE DG - Paie agents 12/2025', 450.00, null],
            ['2026-01-30', 'PRIMMO - Location camion / Transport materiels', 200.00, null],
            ['2026-01-30', 'ALVARO - Evacuation poubelle NDOLO', null, 10000.00],
            ['2026-01-30', 'SUCRIERE - 3 Cartes universelles bleu', 75.00, null],
            ['2026-01-30', 'PRIMMO - 70Lg armaflex 40', 630.00, null],
            ['2026-01-30', 'UTEXAFRICA - Evacuation poubelle BCECO', null, 50000.00],
            ['2026-01-30', 'GEORGES - Frais PCR/Carte rose dupplicata', 35.00, null],
        ];
    }
}
