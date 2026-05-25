<?php

namespace App\Services;

use App\Models\Societe;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ComptableExportService
{
    public function downloadExcel(array $headers, array $rows, string $filename, ?string $title = null, ?Societe $societe = null): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $rowIdx = 1;

        if ($societe) {
            $sheet->setCellValue('A1', $societe->raison_sociale);
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('d32f2f'));
            $rowIdx++;
            $sheet->setCellValue('A2', ($societe->sigle ? $societe->sigle . ' - ' : '') . $societe->adresse . ' ' . $societe->ville);
            $sheet->getStyle('A2')->getFont()->setSize(10)->setItalic(true);
            $rowIdx = 4;
        }

        if ($title) {
            $sheet->setCellValue('A'.$rowIdx, strtoupper($title));
            $sheet->getStyle('A'.$rowIdx)->getFont()->setBold(true)->setSize(16);
            $rowIdx += 2;
        }

        $startHeaderRow = $rowIdx;
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col.$rowIdx, $h);
            $col++;
        }

        $lastCol = $sheet->getHighestColumn();
        $headerRange = 'A' . $startHeaderRow . ':' . $lastCol . $startHeaderRow;
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'd32f2f']
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);

        $rowIdx++;
        foreach ($rows as $row) {
            $col = 'A';
            $isTotalRow = false;
            $isSectionRow = false;

            foreach ($row as $cell) {
                $cleanValue = (string)$cell;
                if (str_starts_with($cleanValue, '### ')) {
                    $isSectionRow = true;
                    $cleanValue = substr($cleanValue, 4);
                }
                if (str_starts_with($cleanValue, '=== ')) {
                    $isTotalRow = true;
                    $cleanValue = substr($cleanValue, 4);
                }

                $sheet->setCellValue($col.$rowIdx, $cleanValue);

                if (is_numeric(str_replace([' ', ','], ['', '.'], $cleanValue)) && strlen($cleanValue) > 0) {
                    $sheet->getStyle($col.$rowIdx)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }
                $col++;
            }

            if ($isTotalRow) {
                $rowRange = 'A' . $rowIdx . ':' . $lastCol . $rowIdx;
                $sheet->getStyle($rowRange)->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FDF2F2']],
                    'borders' => [
                        'top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => 'd32f2f']],
                        'bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => 'd32f2f']],
                    ]
                ]);
            } elseif ($isSectionRow) {
                $rowRange = 'A' . $rowIdx . ':' . $lastCol . $rowIdx;
                $sheet->getStyle($rowRange)->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F1F1']],
                ]);
            }

            $rowIdx++;
        }

        foreach (range('A', $lastCol) as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $this->safeFilename($filename, 'xlsx'), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
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

    public function downloadCsv(array $headers, array $rows, string $filename): StreamedResponse {
        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, $headers, ';');
            foreach ($rows as $row) {
                $cleanRow = array_map(function($v) {
                    $v = (string)$v;
                    if (str_starts_with($v, '### ')) return substr($v, 4);
                    if (str_starts_with($v, '=== ')) return substr($v, 4);
                    return $v;
                }, $row);
                fputcsv($out, $cleanRow, ';');
            }
            fclose($out);
        }, $this->safeFilename($filename, 'csv'), [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function respond(string $format, array $headers, array $rows, string $filename, string $title, array $meta = [], ?Societe $societe = null): StreamedResponse|\Illuminate\Http\Response {
        return match ($format) {
            'excel', 'xlsx' => $this->downloadExcel($headers, $rows, $filename, $title, $societe),
            'pdf' => $this->downloadPdf($headers, $rows, $filename, $title, $meta, $societe),
            'csv' => $this->downloadCsv($headers, $rows, $filename),
            default => abort(422, 'Format d\'export non supporté.'),
        };
    }

    public function safeFilename(string $name, string $ext): string {
        return (preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $name) ?: 'export') . '.' . $ext;
    }

    public function formatNum(mixed $v): string {
        return ($v === null || $v === '') ? '' : number_format((float) $v, 2, ',', ' ');
    }
}
