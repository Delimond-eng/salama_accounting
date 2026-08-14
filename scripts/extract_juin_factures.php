<?php

require __DIR__.'/../vendor/autoload.php';

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

$path = __DIR__.'/../BDD/SALAMA - SUIVI FACTURES NORMALISEES 2026(1).xlsx';
$reader = new PhpOffice\PhpSpreadsheet\Reader\Xlsx();
$spreadsheet = $reader->load($path);

function cellDate($sh, string $col, int $row): ?string
{
    $v = $sh->getCell($col.$row)->getCalculatedValue();
    if ($v === null || $v === '') {
        return null;
    }
    if (is_numeric($v)) {
        return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $v))->format('Y-m-d');
    }

    return Carbon::parse((string) $v)->format('Y-m-d');
}

function num($v): float
{
    if ($v === null || $v === '') {
        return 0.0;
    }

    return round((float) str_replace(',', '.', (string) $v), 2);
}

// Normalisées
$sh = $spreadsheet->getSheetByName('FACTURES NORMALISEES');
$norm = [];
for ($row = 6; $row <= $sh->getHighestRow(); $row++) {
    $date = cellDate($sh, 'A', $row);
    if (! $date || ! str_starts_with($date, '2026-06')) {
        continue;
    }
    $label = trim((string) $sh->getCell('B'.$row)->getCalculatedValue());
    if ($label === '') {
        continue;
    }
    $refExt = trim((string) $sh->getCell('C'.$row)->getCalculatedValue());
    $refFact = trim((string) $sh->getCell('D'.$row)->getCalculatedValue());
    $e = num($sh->getCell('E'.$row)->getCalculatedValue());
    $f = num($sh->getCell('F'.$row)->getCalculatedValue());
    $g = num($sh->getCell('G'.$row)->getCalculatedValue());
    $h = num($sh->getCell('H'.$row)->getCalculatedValue());
    $i = num($sh->getCell('I'.$row)->getCalculatedValue());
    $ttc = $h > 0 ? $h : ($i > 0 ? $i : max($e + $f + $g, $e));
    $tva = $g > 0 && $h > $e ? $g : max(0, $ttc - $e - $f);
    if ($ttc <= 0) {
        continue;
    }
    $norm[] = compact('date', 'label', 'refExt', 'refFact', 'e', 'f', 'g', 'h', 'i', 'ttc', 'tva');
}

echo '=== JUIN NORMALISEES ('.count($norm).") ===\n";
$sumAsc = $sumClim = 0;
foreach ($norm as $r) {
    $type = str_contains($r['refExt'], '/DA/') ? 'ASC' : 'CLIM';
    if ($type === 'ASC') {
        $sumAsc += $r['ttc'];
    } else {
        $sumClim += $r['ttc'];
    }
    echo "{$r['date']} | {$r['refFact']} | {$type} | TTC={$r['ttc']} TVA={$r['tva']} | {$r['label']}\n";
}
echo "Total ASC: {$sumAsc} | Total CLIM: {$sumClim}\n\n";

// SN
$sh = $spreadsheet->getSheetByName('FACTURES SN');
$sn = [];
for ($row = 7; $row <= $sh->getHighestRow(); $row++) {
    $date = cellDate($sh, 'A', $row);
    if (! $date || ! str_starts_with($date, '2026-06')) {
        continue;
    }
    $label = trim((string) $sh->getCell('B'.$row)->getCalculatedValue());
    $ref = trim((string) $sh->getCell('C'.$row)->getCalculatedValue());
    $produits = num($sh->getCell('D'.$row)->getCalculatedValue());
    $total = num($sh->getCell('I'.$row)->getCalculatedValue());
    $montant = $total > 0 ? $total : $produits;
    if ($montant <= 0 || $label === '') {
        continue;
    }
    $sn[] = compact('date', 'label', 'ref', 'montant');
}

echo '=== JUIN SN ('.count($sn).") ===\n";
$sumSn = 0;
foreach ($sn as $r) {
    $sumSn += $r['montant'];
    echo "{$r['date']} | {$r['ref']} | {$r['montant']} | {$r['label']}\n";
}
echo "Total SN: {$sumSn}\n";
