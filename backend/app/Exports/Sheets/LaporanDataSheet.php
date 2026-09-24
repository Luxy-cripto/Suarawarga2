<?php

namespace App\Exports\Sheets;

use App\Models\Laporan;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class LaporanDataSheet implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithCustomStartCell,
    WithColumnWidths,
    WithEvents,
    WithTitle
{
    public function __construct(
        protected ?string $status = null
    ) {
    }

    public function title(): string
    {
        if ($this->status) {
            return 'Laporan ' . ucfirst($this->status);
        }

        return 'Data Laporan';
    }

    public function collection(): Collection
    {
        $query = Laporan::with([
            'user',
            'kategoriRelasi',
            'petugas',
        ])->latest();

        if ($this->status) {
            $query->where('status', $this->status);
        }

        return $query->get();
    }

    public function startCell(): string
    {
        return 'A6';
    }

    public function headings(): array
    {
        return [
            'ID',
            'Judul Laporan',
            'Deskripsi',
            'Kategori',
            'Lokasi',
            'Pelapor',
            'Petugas',
            'Status',
            'Tanggal Laporan',
            'Tanggal Diperbarui',
        ];
    }

    public function map($laporan): array
    {
        return [
            $laporan->id,
            $laporan->judul ?? '-',
            $laporan->deskripsi ?? '-',
            $laporan->kategoriRelasi?->nama
                ?? $laporan->kategori
                ?? 'Lainnya',
            $laporan->lokasi ?? 'Lokasi tidak tersedia',
            $laporan->user?->name ?? 'Warga',
            $laporan->petugas?->name ?? 'Belum ditugaskan',
            $this->statusLabel($laporan->status),
            $laporan->created_at?->format('d-m-Y H:i') ?? '-',
            $laporan->updated_at?->format('d-m-Y H:i') ?? '-',
        ];
    }

    private function statusLabel(?string $status): string
    {
        return match ($status) {
            'baru' => 'Menunggu',
            'diproses' => 'Diproses',
            'selesai' => 'Selesai',
            'ditolak' => 'Ditolak',
            default => $status ?? '-',
        };
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,
            'B' => 28,
            'C' => 42,
            'D' => 20,
            'E' => 32,
            'F' => 22,
            'G' => 22,
            'H' => 16,
            'I' => 20,
            'J' => 20,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $lastRow = $sheet->getHighestRow();

                /*
                |--------------------------------------------------------------------------
                | HALAMAN
                |--------------------------------------------------------------------------
                */

                $sheet->setShowGridlines(false);

                $sheet->getPageSetup()
                    ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
                    ->setPaperSize(PageSetup::PAPERSIZE_A4)
                    ->setFitToWidth(1)
                    ->setFitToHeight(0);

                $sheet->getPageMargins()
                    ->setTop(0.4)
                    ->setBottom(0.4)
                    ->setLeft(0.3)
                    ->setRight(0.3);

                /*
                |--------------------------------------------------------------------------
                | JUDUL
                |--------------------------------------------------------------------------
                */

                $sheet->mergeCells('A1:J1');
                $sheet->mergeCells('A2:J2');
                $sheet->mergeCells('A3:J3');

                $sheet->setCellValue('A1', '📢 SUARAWARGA');
                $sheet->setCellValue(
                    'A2',
                    $this->status
                        ? 'DATA LAPORAN WARGA - ' . strtoupper($this->statusLabel($this->status))
                        : 'DATA LAPORAN WARGA'
                );
                $sheet->setCellValue(
                    'A3',
                    'Laporan masyarakat • Diperbarui ' . now()->format('d F Y, H:i')
                );

                $sheet->getRowDimension(1)->setRowHeight(34);
                $sheet->getRowDimension(2)->setRowHeight(27);
                $sheet->getRowDimension(3)->setRowHeight(22);

                $sheet->getStyle('A1:J1')->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '1E3A8A'],
                    ],
                    'font' => [
                        'name' => 'Arial',
                        'size' => 19,
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_LEFT,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                $sheet->getStyle('A2:J2')->applyFromArray([
                    'font' => [
                        'name' => 'Arial',
                        'size' => 14,
                        'bold' => true,
                        'color' => ['rgb' => '172B4D'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_LEFT,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                $sheet->getStyle('A3:J3')->applyFromArray([
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
                | HEADER TABEL
                |--------------------------------------------------------------------------
                */

                $sheet->getStyle('A6:J6')->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '1E3A8A'],
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
                        'wrapText' => true,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'FFFFFF'],
                        ],
                    ],
                ]);

                $sheet->getRowDimension(6)->setRowHeight(30);

                /*
                |--------------------------------------------------------------------------
                | DATA
                |--------------------------------------------------------------------------
                */

                if ($lastRow >= 7) {
                    $sheet->getStyle("A7:J{$lastRow}")->applyFromArray([
                        'font' => [
                            'name' => 'Arial',
                            'size' => 9,
                            'color' => ['rgb' => '172B4D'],
                        ],
                        'alignment' => [
                            'vertical' => Alignment::VERTICAL_TOP,
                            'wrapText' => true,
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_HAIR,
                                'color' => ['rgb' => 'CBD5E1'],
                            ],
                        ],
                    ]);

                    for ($row = 7; $row <= $lastRow; $row++) {
                        $sheet->getRowDimension($row)->setRowHeight(34);

                        if ($row % 2 === 1) {
                            $sheet->getStyle("A{$row}:J{$row}")
                                ->getFill()
                                ->setFillType(Fill::FILL_SOLID);

                            $sheet->getStyle("A{$row}:J{$row}")
                                ->getFill()
                                ->getStartColor()
                                ->setRGB('F8FAFC');
                        }
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | STATUS
                    |--------------------------------------------------------------------------
                    */

                    for ($row = 7; $row <= $lastRow; $row++) {
                        $status = $sheet->getCell("H{$row}")->getValue();

                        $style = match ($status) {
                            'Selesai' => [
                                'background' => 'DCFCE7',
                                'text' => '166534',
                            ],
                            'Diproses' => [
                                'background' => 'DBEAFE',
                                'text' => '1D4ED8',
                            ],
                            'Menunggu' => [
                                'background' => 'FEF3C7',
                                'text' => '92400E',
                            ],
                            'Ditolak' => [
                                'background' => 'FEE2E2',
                                'text' => 'B91C1C',
                            ],
                            default => [
                                'background' => 'F1F5F9',
                                'text' => '475569',
                            ],
                        };

                        $sheet->getStyle("H{$row}")->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => [
                                    'rgb' => $style['background'],
                                ],
                            ],
                            'font' => [
                                'bold' => true,
                                'color' => [
                                    'rgb' => $style['text'],
                                ],
                            ],
                            'alignment' => [
                                'horizontal' => Alignment::HORIZONTAL_CENTER,
                                'vertical' => Alignment::VERTICAL_CENTER,
                            ],
                        ]);
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | FILTER & FREEZE
                |--------------------------------------------------------------------------
                */

                $sheet->freezePane('A7');

                if ($lastRow >= 6) {
                    $sheet->setAutoFilter("A6:J{$lastRow}");
                }

                /*
                |--------------------------------------------------------------------------
                | FOOTER
                |--------------------------------------------------------------------------
                */

                $footerRow = $lastRow + 2;

                $sheet->mergeCells("A{$footerRow}:J{$footerRow}");

                $sheet->setCellValue(
                    "A{$footerRow}",
                    'SUARAWARGA • Suara masyarakat, perubahan nyata.'
                );

                $sheet->getStyle("A{$footerRow}:J{$footerRow}")->applyFromArray([
                    'font' => [
                        'name' => 'Arial',
                        'size' => 9,
                        'italic' => true,
                        'color' => ['rgb' => '64748B'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ],
                ]);
            },
        ];
    }
}
