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
        $lastCol = Coordinate::stringFromColumnIndex($numCols);

        // Header Style: Society Left, Meta Right
        if ($societe) {
            $sheet->setCellValue('A' . $rowIdx, strtoupper($societe->raison_sociale));
            $sheet->getStyle('A' . $rowIdx)->applyFromArray([
                'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => '800000']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]
            ]);

            // Meta on the right (same row)
            $sheet->setCellValue($lastCol . $rowIdx, strtoupper($title ?? 'EXPORT'));
            $sheet->getStyle($lastCol . $rowIdx)->applyFromArray([
                'font' => ['bold' => true, 'size' => 14],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]
            ]);
            $rowIdx++;

            $sheet->setCellValue('A' . $rowIdx, ($societe->sigle ? $societe->sigle . ' | ' : '') . $societe->adresse);
            $sheet->getStyle('A' . $rowIdx)->getFont()->setItalic(true);

            // Date on the right
            $sheet->setCellValue($lastCol . $rowIdx, "Généré le: " . now()->format('d/m/Y H:i'));
            $sheet->getStyle($lastCol . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $rowIdx++;

            $sheet->setCellValue('A' . $rowIdx, "RCCM: {$societe->rccm} | ID Nat: {$societe->num_contribuable}");
            $rowIdx += 2;
        }

        // Additional Meta
        foreach ($meta as $label => $value) {
            $sheet->setCellValue('A' . $rowIdx, "{$label} :");
            $sheet->setCellValue('B' . $rowIdx, $value);
            $sheet->getStyle('A' . $rowIdx)->getFont()->setBold(true);
            $rowIdx++;
        }
        $rowIdx++;

        $this->writeTableToSheet($sheet, $headers, $rows, $rowIdx);

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $this->safeFilename($filename, 'xlsx'), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    protected function writeTableToSheet($sheet, array $headers, array $rows, int &$rowIdx)
    {
        $startHeaderRow = $rowIdx;
        $numCols = count($headers);
        $lastCol = Coordinate::stringFromColumnIndex($numCols);

        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . $rowIdx, $h);
            $col++;
        }

        $headerRange = 'A' . $startHeaderRow . ':' . $lastCol . $startHeaderRow;
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '800000'] // Retour au Rouge Foncé
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '600000']]
            ]
        ]);

        $rowIdx++;
        foreach ($rows as $row) {
            $col = 'A';
            $isTotalRow = false;
            $isSectionRow = false;

            foreach (array_slice($row, 0, $numCols) as $cell) {
                $cleanValue = (string)$cell;
                if (str_starts_with($cleanValue, '### ')) {
                    $isSectionRow = true;
                    $cleanValue = substr($cleanValue, 4);
                }
                if (str_starts_with($cleanValue, '=== ') || str_contains(strtoupper($cleanValue), 'TOTAL')) {
                    $isTotalRow = true;
                    $cleanValue = substr($cleanValue, 4);
                }

                $sheet->setCellValue($col . $rowIdx, $cleanValue);
                if (is_numeric(str_replace([' ', ','], ['', '.'], $cleanValue)) && strlen($cleanValue) > 0) {
                    $sheet->getStyle($col . $rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }
                $col++;
            }

            if ($isTotalRow) {
                $sheet->getStyle('A' . $rowIdx . ':' . $lastCol . $rowIdx)->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF9F9']],
                    'borders' => [
                        'top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '800000']],
                        'bottom' => ['borderStyle' => Border::BORDER_DOUBLE, 'color' => ['rgb' => '800000']],
                    ]
                ]);
            } elseif ($isSectionRow) {
                $sheet->getStyle('A' . $rowIdx . ':' . $lastCol . $rowIdx)->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => '800000']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F1F1']],
                ]);
            }
            $rowIdx++;
        }

        for ($i = 1; $i <= $numCols; $i++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
        }
    }

    public function downloadPdf(array $headers, array $rows, string $filename, string $title, array $meta = [], ?Societe $societe = null): \Illuminate\Http\Response {
        $pdf = Pdf::loadView('pdf.comptable-table', [
            'societe' => $societe,
            'title' => $title,
            'headers' => $headers,
            'rows' => $rows,
            'meta' => $meta,
            'generated_at' => now()->format('d/m/Y H:i'),
        ])->setPaper('a4', 'landscape');

        return $pdf->download($this->safeFilename($filename, 'pdf'));
    }

    public function downloadPdfMulti(array $sections, string $filename, string $title, array $meta = [], ?Societe $societe = null): \Illuminate\Http\Response {
        $pdf = Pdf::loadView('pdf.etats-financiers-multi', [
            'societe' => $societe,
            'title' => $title,
            'sections' => $sections,
            'meta' => $meta,
            'generated_at' => now()->format('d/m/Y H:i'),
        ])->setPaper('a4', 'portrait');

        return $pdf->download($this->safeFilename($filename, 'pdf'));
    }

    public function downloadCsv(array $headers, array $rows, string $filename): StreamedResponse {
        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, $headers, ';');
            foreach ($rows as $row) {
                $cleanRow = array_map(fn($v) => preg_replace('/^(### |=== )/', '', (string)$v), $row);
                fputcsv($out, $cleanRow, ';');
            }
            fclose($out);
        }, $this->safeFilename($filename, 'csv'), ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function respond(string $format, array $headers, array $rows, string $filename, string $title, array $meta = [], ?Societe $societe = null): StreamedResponse|\Illuminate\Http\Response {
        return match ($format) {
            'excel', 'xlsx' => $this->downloadExcel($headers, $rows, $filename, $title, $societe, $meta),
            'pdf' => $this->downloadPdf($headers, $rows, $filename, $title, $meta, $societe),
            'csv' => $this->downloadCsv($headers, $rows, $filename),
            default => abort(422, 'Format non supporté.'),
        };
    }

    public function safeFilename(string $name, string $ext): string {
        return (preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $name) ?: 'export') . '.' . $ext;
    }

    public function formatNum(mixed $v): string {
        return ($v === null || $v === '') ? '' : number_format((float) $v, 2, ',', ' ');
    }
}
