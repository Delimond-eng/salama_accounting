<?php

require __DIR__.'/../vendor/autoload.php';

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

$path = __DIR__.'/../BDD/SALAMA - SUIVI FACTURES NORMALISEES 2026(1).xlsx';
$reader = new PhpOffice\PhpSpreadsheet\Reader\Xlsx();
$spreadsheet = $reader->load($path);
$sh = $spreadsheet->getSheetByName('FACTURES NORMALISEES');

$refs = ['136/1-1/2026', '136/1-259/2026', '136/1-266/2026', '136/1-280/2026'];
for ($row = 6; $row <= $sh->getHighestRow(); $row++) {
    $ref = trim((string) $sh->getCell('D'.$row)->getCalculatedValue());
    if (! in_array($ref, $refs, true)) {
        continue;
    }
    $cols = [];
    foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'] as $c) {
        $cols[$c] = $sh->getCell($c.$row)->getCalculatedValue();
    }
    echo $ref.' '.json_encode($cols, JSON_UNESCAPED_UNICODE)."\n";
}
