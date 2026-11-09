<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class XlsxExportService
{
    /**
     * Download styled Excel (.xlsx) file.
     *
     * @param string $filename
     * @param array $headers Headers list e.g. ['ID', 'Date', 'Amount']
     * @param array $rows Array of data rows e.g. [['1', '2026-07-26', '₱1,000.00']]
     */
    public function downloadXlsx(string $filename, array $headers, array $rows, array $textColumns = [])
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Title Header Formatting
        $colIndex = 1;
        foreach ($headers as $header) {
            $cellCoordinate = $this->getColumnLetter($colIndex) . '1';
            $sheet->setCellValue($cellCoordinate, strtoupper($header));
            
            // Header Styling (Indigo Background, White Bold Text)
            $sheet->getStyle($cellCoordinate)->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 11,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F46E5'], // Indigo 600
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);
            $colIndex++;
        }

        // Data Rows Insertion
        $rowIndex = 2;
        foreach ($rows as $row) {
            $colIndex = 1;
            foreach ($row as $value) {
                $cellCoordinate = $this->getColumnLetter($colIndex) . $rowIndex;
                if (in_array($colIndex, $textColumns, true)) {
                    $sheet->setCellValueExplicit($cellCoordinate, $value, DataType::TYPE_STRING);
                } else {
                    $sheet->setCellValue($cellCoordinate, $value);
                }
                $colIndex++;
            }
            $rowIndex++;
        }

        // Auto Column Width Calculation
        $highestColumn = $sheet->getHighestColumn();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $colLetter = $this->getColumnLetter($col);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    private function getColumnLetter(int $colIndex): string
    {
        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
    }
}
