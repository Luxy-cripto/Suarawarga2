<?php

namespace App\Exports\Sheets;

use App\Models\Laporan;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class LaporanDashboardSheet implements FromArray, WithTitle, WithEvents, WithColumnWidths
{
    public function title(): string
    {
        return 'Dashboard';
    }

    public function array(): array
    {
        $laporans = Laporan::with([
            'kategoriRelasi',
            'petugas',
        ])->latest()->get();

        $total = $laporans->count();

        $baru = $laporans->where('status', 'baru')->count();
        $diproses = $laporans->where('status', 'diproses')->count();
        $selesai = $laporans->where('status', 'selesai')->count();
        $ditolak = $laporans->where('status', 'ditolak')->count();

        $ditugaskan = $laporans->whereNotNull('petugas_id')->count();
        $belumDitugaskan = $total - $ditugaskan;

        $persenSelesai = $total > 0
            ? round(($selesai / $total) * 100)
            : 0;

        $persenDitugaskan = $total > 0
            ? round(($ditugaskan / $total) * 100)
            : 0;

        $kategori = $laporans
            ->map(function ($laporan) {
                return $laporan->kategoriRelasi?->nama
                    ?? $laporan->kategori
                    ?? 'Lainnya';
            })
            ->countBy()
            ->sortDesc()
            ->take(5);

        $rows = [
            ['SUARAWARGA'],
            ['Dashboard Laporan Masyarakat'],
            ['Diperbarui: ' . now()->format('d F Y • H:i')],
            [''],
            ['TOTAL LAPORAN', 'MENUNGGU', 'DIPROSES', 'SELESAI', 'DITOLAK'],
            [$total, $baru, $diproses, $selesai, $ditolak],
            [''],
            ['RINGKASAN LAPORAN'],
            [''],
            ['STATUS LAPORAN', '', '', 'KATEGORI TERBANYAK', ''],
            ['Baru', $baru, $this->percentage($baru, $total), $kategori->keys()->get(0) ?? '-', $kategori->values()->get(0) ?? 0],
            ['Diproses', $diproses, $this->percentage($diproses, $total), $kategori->keys()->get(1) ?? '-', $kategori->values()->get(1) ?? 0],
            ['Selesai', $selesai, $this->percentage($selesai, $total), $kategori->keys()->get(2) ?? '-', $kategori->values()->get(2) ?? 0],
            ['Ditolak', $ditolak, $this->percentage($ditolak, $total), $kategori->keys()->get(3) ?? '-', $kategori->values()->get(3) ?? 0],
            [''],
            ['PENUGASAN'],
            [''],
            ['SUDAH DITUGASKAN', 'BELUM DITUGASKAN'],
            [$ditugaskan, $belumDitugaskan],
            [$persenDitugaskan . '% dari seluruh laporan', (100 - $persenDitugaskan) . '% dari seluruh laporan'],
            [''],
            ['TINGKAT PENYELESAIAN', $persenSelesai . '%'],
        ];

        return $rows;
    }

    private function percentage(int $value, int $total): string
    {
        if ($total === 0) {
            return '0%';
        }

        return round(($value / $total) * 100) . '%';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 24,
            'B' => 18,
            'C' => 18,
            'D' => 28,
            'E' => 18,
            'F' => 4,
            'G' => 4,
            'H' => 4,
            'I' => 4,
            'J' => 4,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                /*
                |--------------------------------------------------------------------------
                | GLOBAL
                |--------------------------------------------------------------------------
                */

                $sheet->setShowGridlines(false);

                $sheet->getDefaultRowDimension()->setRowHeight(22);

                $sheet->getPageSetup()
                    ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
                    ->setPaperSize(PageSetup::PAPERSIZE_A4)
                    ->setFitToWidth(1)
                    ->setFitToHeight(1);

                $sheet->getPageMargins()
                    ->setTop(0.3)
                    ->setBottom(0.3)
                    ->setLeft(0.3)
                    ->setRight(0.3);

                $sheet->getPageSetup()->setPrintArea('A1:E22');

                /*
                |--------------------------------------------------------------------------
                | TITLE
                |--------------------------------------------------------------------------
                */

                $sheet->mergeCells('A1:E1');
                $sheet->mergeCells('A2:E2');
                $sheet->mergeCells('A3:E3');

                $sheet->getRowDimension(1)->setRowHeight(34);
                $sheet->getRowDimension(2)->setRowHeight(28);
                $sheet->getRowDimension(3)->setRowHeight(20);

                $sheet->getStyle('A1:E1')->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '1E3A8A'],
                    ],
                    'font' => [
                        'name' => 'Arial',
                        'size' => 20,
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_LEFT,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                $sheet->getStyle('A2:E2')->applyFromArray([
                    'font' => [
                        'name' => 'Arial',
                        'size' => 15,
                        'bold' => true,
                        'color' => ['rgb' => '172B4D'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_LEFT,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                $sheet->getStyle('A3:E3')->applyFromArray([
                    'font' => [
                        'name' => 'Arial',
                        'size' => 9,
                        'italic' => true,
                        'color' => ['rgb' => '64748B'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_LEFT,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                /*
                |--------------------------------------------------------------------------
                | KPI CARDS
                |--------------------------------------------------------------------------
                */

                $kpiColors = [
                    '1E3A8A',
                    '3B82F6',
                    'F59E0B',
                    '10B981',
                    'EF4444',
                ];

                foreach (range(0, 4) as $index) {
                    $column = chr(65 + $index);

                    $sheet->getStyle($column . '5:' . $column . '6')
                        ->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => [
                                    'rgb' => $kpiColors[$index],
                                ],
                            ],
                            'font' => [
                                'name' => 'Arial',
                                'color' => ['rgb' => 'FFFFFF'],
                                'bold' => true,
                            ],
                            'alignment' => [
                                'horizontal' => Alignment::HORIZONTAL_CENTER,
                                'vertical' => Alignment::VERTICAL_CENTER,
                            ],
                        ]);

                    $sheet->getStyle($column . '5')->getFont()->setSize(9);
                    $sheet->getStyle($column . '6')->getFont()->setSize(18);

                    $sheet->getStyle($column . '5:' . $column . '6')
                        ->getBorders()
                        ->getOutline()
                        ->setBorderStyle(Border::BORDER_THIN)
                        ->getColor()
                        ->setRGB('FFFFFF');
                }

                $sheet->getRowDimension(5)->setRowHeight(25);
                $sheet->getRowDimension(6)->setRowHeight(38);

                /*
                |--------------------------------------------------------------------------
                | SECTION TITLE
                |--------------------------------------------------------------------------
                */

                $this->section(
                    $sheet,
                    'A8:E8',
                    'RINGKASAN LAPORAN'
                );

                /*
                |--------------------------------------------------------------------------
                | STATUS & CATEGORY
                |--------------------------------------------------------------------------
                */

                $sheet->mergeCells('A10:C10');
                $sheet->mergeCells('D10:E10');

                $sheet->getStyle('A10:C10')->applyFromArray(
                    $this->headerStyle('1E3A8A')
                );

                $sheet->getStyle('D10:E10')->applyFromArray(
                    $this->headerStyle('0F766E')
                );

                $sheet->getStyle('A10:C10')->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->getStyle('D10:E10')->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->getStyle('A11:C14')->applyFromArray([
                    'borders' => [
                        'insideHorizontal' => [
                            'borderStyle' => Border::BORDER_HAIR,
                            'color' => ['rgb' => 'E2E8F0'],
                        ],
                        'outline' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'CBD5E1'],
                        ],
                    ],
                    'font' => [
                        'name' => 'Arial',
                        'size' => 10,
                        'color' => ['rgb' => '172B4D'],
                    ],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                $sheet->getStyle('D11:E14')->applyFromArray([
                    'borders' => [
                        'insideHorizontal' => [
                            'borderStyle' => Border::BORDER_HAIR,
                            'color' => ['rgb' => 'E2E8F0'],
                        ],
                        'outline' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'CBD5E1'],
                        ],
                    ],
                    'font' => [
                        'name' => 'Arial',
                        'size' => 10,
                        'color' => ['rgb' => '172B4D'],
                    ],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                $sheet->getStyle('B11:C14')
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->getStyle('E11:E14')
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                /*
                |--------------------------------------------------------------------------
                | ASSIGNMENT
                |--------------------------------------------------------------------------
                */

                $this->section(
                    $sheet,
                    'A16:E16',
                    'PENUGASAN'
                );

                $sheet->mergeCells('A18:C18');
                $sheet->mergeCells('D18:E18');

                $sheet->getStyle('A18:C18')->applyFromArray(
                    $this->headerStyle('0F766E')
                );

                $sheet->getStyle('D18:E18')->applyFromArray(
                    $this->headerStyle('F59E0B')
                );

                $sheet->getStyle('A19:C20')->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'ECFDF5'],
                    ],
                    'font' => [
                        'name' => 'Arial',
                        'bold' => true,
                        'color' => ['rgb' => '047857'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'outline' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'A7F3D0'],
                        ],
                    ],
                ]);

                $sheet->mergeCells('A19:C19');
                $sheet->mergeCells('D19:E19');
                $sheet->mergeCells('A20:C20');
                $sheet->mergeCells('D20:E20');

                $sheet->getStyle('A19:E19')
                    ->getFont()
                    ->setSize(20);

                $sheet->getStyle('A20:E20')
                    ->getFont()
                    ->setSize(9);

                $sheet->getStyle('D19:E20')->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FFFBEB'],
                    ],
                    'font' => [
                        'name' => 'Arial',
                        'bold' => true,
                        'color' => ['rgb' => '92400E'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'outline' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'FDE68A'],
                        ],
                    ],
                ]);

                /*
                |--------------------------------------------------------------------------
                | FOOTER
                |--------------------------------------------------------------------------
                */

                $sheet->mergeCells('A22:E22');

                $sheet->getStyle('A22:E22')->applyFromArray([
                    'font' => [
                        'name' => 'Arial',
                        'size' => 9,
                        'italic' => true,
                        'color' => ['rgb' => '64748B'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
            },
        ];
    }

    private function section($sheet, string $range, string $title): void
    {
        $sheet->mergeCells($range);

        $sheet->getStyle($range)->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0F766E'],
            ],
            'font' => [
                'name' => 'Arial',
                'size' => 11,
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getRowDimension(
            (int) preg_replace('/[^0-9]/', '', explode(':', $range)[0])
        )->setRowHeight(25);

        $sheet->setCellValue(
            explode(':', $range)[0],
            $title
        );
    }

    private function headerStyle(string $color): array
    {
        return [
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $color],
            ],
            'font' => [
                'name' => 'Arial',
                'size' => 10,
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];
    }
}
