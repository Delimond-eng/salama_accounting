<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ComptableExportService
{
    /**
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, mixed>>  $rows
     */
    public function downloadExcel(array $headers, array $rows, string $filename, ?string $title = null): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $rowIdx = 1;

        if ($title) {
            $sheet->setCellValue('A1', $title);
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $rowIdx = 3;
        }

        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col.$rowIdx, $h);
            $sheet->getStyle($col.$rowIdx)->getFont()->setBold(true);
            $col++;
        }

        $rowIdx++;
        foreach ($rows as $row) {
            $col = 'A';
            foreach ($row as $cell) {
                $sheet->setCellValue($col.$rowIdx, $cell);
                $col++;
            }
            $rowIdx++;
        }

        foreach (range('A', $sheet->getHighestColumn()) as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $this->safeFilename($filename, 'xlsx'), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, mixed>>  $rows
     */
    public function downloadPdf(
        array $headers,
        array $rows,
        string $filename,
        string $title,
        array $meta = []
    ): \Illuminate\Http\Response {
        $pdf = Pdf::loadView('pdf.comptable-table', [
            'title' => $title,
            'headers' => $headers,
            'rows' => $rows,
            'meta' => $meta,
            'generated_at' => now()->format('d/m/Y H:i'),
        ])->setPaper('a4', 'landscape');

        return $pdf->download($this->safeFilename($filename, 'pdf'));
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, mixed>>  $rows
     */
    public function downloadCsv(array $headers, array $rows, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, $headers, ';');
            foreach ($rows as $row) {
                fputcsv($out, $row, ';');
            }
            fclose($out);
        }, $this->safeFilename($filename, 'csv'), [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function respond(string $format, array $headers, array $rows, string $filename, string $title, array $meta = []): StreamedResponse|\Illuminate\Http\Response
    {
        return match ($format) {
            'excel', 'xlsx' => $this->downloadExcel($headers, $rows, $filename, $title),
            'pdf' => $this->downloadPdf($headers, $rows, $filename, $title, $meta),
            'csv' => $this->downloadCsv($headers, $rows, $filename),
            default => abort(422, 'Format d\'export non supporté.'),
        };
    }

    public function safeFilename(string $name, string $ext): string
    {
        $base = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $name) ?: 'export';

        return $base.'.'.$ext;
    }

    public function formatNum(mixed $v): string
    {
        if ($v === null || $v === '') {
            return '';
        }

        return number_format((float) $v, 2, ',', ' ');
    }
}
