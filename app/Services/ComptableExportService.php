<?php

namespace App\Services;

use App\Models\Societe;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ComptableExportService
{
    public function downloadExcel(array $headers, array $rows, string $filename, ?string $title = null, ?Societe $societe = null, array $meta = []): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $rowIdx = 1;
        $numCols = count($headers);
        $lastCol = Coordinate::stringFromColumnIndex(max($numCols, 1));

        $this->applyHeaderStyle($sheet, $societe, $title ?? 'Export', $lastCol, $rowIdx);

        foreach ($meta as $label => $value) {
            $sheet->setCellValue('A' . $rowIdx, "{$label} :");
            $sheet->setCellValue('B' . $rowIdx, $value);
            $sheet->getStyle('A' . $rowIdx)->getFont()->setBold(true);
            $rowIdx++;
        }
        $rowIdx += 1;

        $this->writeTableToSheet($sheet, $headers, $rows, $rowIdx);

        return $this->streamSpreadsheet($spreadsheet, $filename);
    }

    public function downloadExcelMulti(array $sections, string $filename, ?Societe $societe = null, array $meta = []): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        foreach ($sections as $index => $section) {
            $sheet = $spreadsheet->createSheet();
            $sheetTitle = substr(preg_replace('/[^A-Za-z0-9 ]/', '', $section['title']), 0, 30);
            $sheet->setTitle($sheetTitle ?: 'Feuille ' . ($index + 1));

            $rowIdx = 1;
            $numCols = count($section['headers']);
            $lastCol = Coordinate::stringFromColumnIndex(max($numCols, 2));

            $this->applyHeaderStyle($sheet, $societe, $section['title'], $lastCol, $rowIdx);

            foreach ($meta as $label => $value) {
                $sheet->setCellValue('A' . $rowIdx, "{$label} :");
                $sheet->setCellValue('B' . $rowIdx, $value);
                $sheet->getStyle('A' . $rowIdx)->getFont()->setBold(true);
                $rowIdx++;
            }
            $rowIdx += 1;

            $this->writeTableToSheet($sheet, $section['headers'], $section['rows'], $rowIdx);
        }

        $spreadsheet->setActiveSheetIndex(0);
        return $this->streamSpreadsheet($spreadsheet, $filename);
    }

    protected function applyHeaderStyle($sheet, ?Societe $societe, string $title, string $lastCol, int &$rowIdx)
    {
        if ($societe) {
            // Société en haut à gauche
            $sheet->setCellValue('A' . $rowIdx, strtoupper($societe->raison_sociale));
            $sheet->getStyle('A' . $rowIdx)->applyFromArray([
                'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '800000']],
            ]);

            // Titre du document en haut à droite
            $sheet->setCellValue($lastCol . $rowIdx, strtoupper($title));
            $sheet->getStyle($lastCol . $rowIdx)->applyFromArray([
                'font' => ['bold' => true, 'size' => 12],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]
            ]);
            $rowIdx++;

            $sheet->setCellValue('A' . $rowIdx, $societe->adresse);
            $sheet->getStyle('A' . $rowIdx)->getFont()->setSize(9)->setItalic(true);

            // Date de génération à droite
            $sheet->setCellValue($lastCol . $rowIdx, "Généré le: " . now()->format('d/m/Y H:i'));
            $sheet->getStyle($lastCol . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $rowIdx += 2;
        }
    }

    protected function writeTableToSheet($sheet, array $headers, array $rows, int &$rowIdx)
    {
        $startHeaderRow = $rowIdx;
        $numCols = count($headers);
        if ($numCols === 0) return;
        $lastCol = Coordinate::stringFromColumnIndex($numCols);

        // Header Style (Bandeau Bordeaux - Design Ancien revisité)
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . $rowIdx, $h);
            $col++;
        }

        $sheet->getStyle('A' . $rowIdx . ':' . $lastCol . $rowIdx)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '800000']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension($rowIdx)->setRowHeight(25); // Plus d'espace pour l'entête

        $rowIdx++;
        $rowCount = 0;
        foreach ($rows as $row) {
            $col = 'A';
            $isTotal = false; $isTitle = false;

            foreach (array_slice($row, 0, $numCols) as $cell) {
                $val = (string)$cell;
                if (str_starts_with($val, '### ')) { $isTitle = true; $val = substr($val, 4); }
                if (str_starts_with($val, '=== ')) { $isTotal = true; $val = substr($val, 4); }

                $sheet->setCellValue($col . $rowIdx, $val);

                // Alignement automatique des nombres
                $numVal = str_replace([' ', ','], ['', '.'], $val);
                if (is_numeric($numVal) && strlen($val) > 0) {
                    $sheet->getStyle($col . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    // Format monétaire soft
                    $sheet->getStyle($col . $rowIdx)->getNumberFormat()->setFormatCode('#,##0.00');
                }
                $col++;
            }

            if ($isTotal) {
                $sheet->getStyle('A'.$rowIdx.':'.$lastCol.$rowIdx)->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8F9FA']],
                    'borders' => [
                        'top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '800000']],
                        'bottom' => ['borderStyle' => Border::BORDER_DOUBLE, 'color' => ['rgb' => '800000']]
                    ]
                ]);
            } elseif ($isTitle) {
                $sheet->getStyle('A'.$rowIdx.':'.$lastCol.$rowIdx)->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => '800000']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EEEEEE']]
                ]);
            } else {
                // Alternance de couleurs (Zebra stripes) pour les lignes standards
                if ($rowCount % 2 === 0) {
                    $sheet->getStyle('A'.$rowIdx.':'.$lastCol.$rowIdx)->getFill()
                        ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FCFCFC');
                }
                // Bordure très légère entre les lignes
                $sheet->getStyle('A'.$rowIdx.':'.$lastCol.$rowIdx)->getBorders()->getBottom()
                    ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('F1F1F1');
            }

            $rowIdx++;
            $rowCount++;
        }

        // Auto-size des colonnes pour un rendu propre
        for ($i = 1; $i <= $numCols; $i++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
        }
    }

    protected function streamSpreadsheet(Spreadsheet $spreadsheet, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $this->safeFilename($filename, 'xlsx'), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function downloadPdfMulti(array $sections, string $filename, string $title, array $meta = [], ?Societe $societe = null): \Illuminate\Http\Response
    {
        return Pdf::loadView('pdf.etats-financiers-multi', [
            'societe' => $societe,
            'title' => $title,
            'sections' => $sections,
            'meta' => $meta,
            'generated_at' => now()->format('d/m/Y H:i'),
        ])->setPaper('a4', 'portrait')->download($this->safeFilename($filename, 'pdf'));
    }

    public function respond(string $format, array $headers, array $rows, string $filename, string $title, array $meta = [], ?Societe $societe = null)
    {
        return match ($format) {
            'excel', 'xlsx' => $this->downloadExcel($headers, $rows, $filename, $title, $societe, $meta),
            'pdf' => Pdf::loadView('pdf.comptable-table', array_merge(
                compact('headers', 'rows', 'title', 'societe', 'meta'),
                ['generated_at' => now()->format('d/m/Y H:i')]
            ))->setPaper('a4', 'landscape')->download($this->safeFilename($filename, 'pdf')),
            'csv' => $this->downloadCsv($headers, $rows, $filename),
            default => abort(422),
        };
    }

    protected function downloadCsv(array $headers, array $rows, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, $headers, ';');
            foreach ($rows as $r) fputcsv($out, array_map(fn($v) => preg_replace('/^(### |=== )/', '', (string)$v), $r), ';');
            fclose($out);
        }, $this->safeFilename($filename, 'csv'));
    }

    public function safeFilename(string $name, string $ext): string { return (preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $name) ?: 'export') . '.' . $ext; }
    public function formatNum(mixed $v): string { return ($v === null || $v === '') ? '' : number_format((float) $v, 2, ',', ' '); }
}
