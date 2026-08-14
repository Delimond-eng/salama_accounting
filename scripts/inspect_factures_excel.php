<?php

require __DIR__.'/../vendor/autoload.php';

$path = __DIR__.'/../BDD/SALAMA - SUIVI FACTURES NORMALISEES 2026(1).xlsx';
$reader = new PhpOffice\PhpSpreadsheet\Reader\Xlsx();
$spreadsheet = $reader->load($path);

foreach (['FACTURES NORMALISEES', 'FACTURES SN'] as $name) {
    echo "=== {$name} ===\n";
    $sh = $spreadsheet->getSheetByName($name);
    $maxCol = $name === 'FACTURES SN' ? 'P' : 'J';
    for ($row = 1; $row <= 8; $row++) {
        $cells = [];
        for ($col = 'A'; $col <= $maxCol; $col++) {
            $v = $sh->getCell($col.$row)->getCalculatedValue();
            if ($v !== null && $v !== '') {
                $cells[] = $col.':'.$v;
            }
        }
        echo 'R'.$row.' '.implode(' | ', $cells)."\n";
    }
    echo "\n";
}
