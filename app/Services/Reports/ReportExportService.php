<?php

namespace App\Services\Reports;

use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Mpdf\Mpdf;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;

class ReportExportService
{
    public function exportExcel(array $data, string $filename, array $columns): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $export = new class($data, $columns) implements FromArray, WithHeadings, ShouldAutoSize, WithStyles {
            private array $data;
            private array $columns;

            public function __construct(array $data, array $columns)
            {
                $this->data = $data;
                $this->columns = $columns;
            }

            public function array(): array
            {
                return collect($this->data)->map(function ($row) {
                    $mapped = [];
                    foreach ($this->columns as $key => $label) {
                        $value = $row[$key] ?? '';
                        if (is_numeric($value)) {
                            $value = (float) $value;
                        }
                        $mapped[] = $value;
                    }
                    return $mapped;
                })->toArray();
            }

            public function headings(): array
            {
                return array_values($this->columns);
            }

            public function styles(Worksheet $sheet): array
            {
                $sheet->setRightToLeft(true);

                return [
                    1 => [
                        'font' => ['bold' => true, 'size' => 12],
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'E9ECEF'],
                        ],
                    ],
                ];
            }
        };

        return Excel::download($export, $filename . '_' . date('Y-m-d_His') . '.xlsx');
    }

    public function exportPdf(string $view, array $data, string $filename): \Illuminate\Http\Response
    {
        $html = view($view, $data)->render();

        $defaultConfig = (new ConfigVariables())->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];

        $defaultFontConfig = (new FontVariables())->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];

        $tempDir = storage_path('app/temp');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'dejavusans',
            'tempDir' => $tempDir,
            'fontDir' => $fontDirs,
            'fontdata' => $fontData,
            'autoArabic' => true,
            'autoLangToFont' => true,
            'useSubstitutions' => true,
        ]);

        $mpdf->SetDirectionality('rtl');
        $mpdf->SetTitle($filename);
        $mpdf->WriteHTML($html);

        $pdfContent = $mpdf->Output('', 'S');

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '_' . date('Y-m-d_His') . '.pdf"');
    }

    public function streamPdf(string $view, array $data, string $filename): \Illuminate\Http\Response
    {
        $html = view($view, $data)->render();

        $defaultConfig = (new ConfigVariables())->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];

        $defaultFontConfig = (new FontVariables())->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];

        $tempDir = storage_path('app/temp');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'dejavusans',
            'tempDir' => $tempDir,
            'fontDir' => $fontDirs,
            'fontdata' => $fontData,
            'autoArabic' => true,
            'autoLangToFont' => true,
            'useSubstitutions' => true,
        ]);

        $mpdf->SetDirectionality('rtl');
        $mpdf->SetTitle($filename);
        $mpdf->WriteHTML($html);

        $pdfContent = $mpdf->Output('', 'S');

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $filename . '.pdf"');
    }
}
