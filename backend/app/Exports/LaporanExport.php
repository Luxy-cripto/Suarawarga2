<?php

namespace App\Exports;

use App\Models\Laporan;
use Illuminate\Support\Enumerable;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LaporanExport implements Export, FromCollection, WithHeadings, WithMapping, WithStyles, WithEvents
{
    protected ?string $status;

    public function __construct(?string $status = null)
    {
        $this->status = $status;
    }

    public function collection(): Enumerable
    {
        $query = Laporan::with([
            'user',
            'kategoriRelasi',
            'petugas',
        ])
        ->latest();

        if ($this->status && $this->status !== 'semua') {
            $query->where('status', $this->status);
        }

        return $query->get();
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
            'Status',
            'Petugas',
            'Tanggal Lapor',
            'Terakhir Diperbarui',
        ];
    }

    public function map($laporan): array
    {
        $status = [
            'baru' => 'Menunggu',
            'diproses' => 'Diproses',
            'selesai' => 'Selesai',
            'ditolak' => 'Ditolak',
        ];

        return [
            $laporan->id,
            $laporan->judul ?? '-',
            $laporan->deskripsi ?? '-',
            $laporan->kategoriRelasi?->nama
                ?? $laporan->kategori
                ?? '-',
            $laporan->lokasi ?? '-',
            $laporan->user?->name ?? 'Warga',
            $status[$laporan->status] ?? $laporan->status ?? '-',
            $laporan->petugas?->name ?? 'Belum ditugaskan',
            $laporan->created_at?->format('d/m/Y H:i') ?? '-',
            $laporan->updated_at?->format('d/m/Y H:i') ?? '-',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => [
                        'rgb' => 'FFFFFF',
                    ],
                ],
                'fill' => [
                    'fillType' => 'solid',
                    'startColor' => [
                        'rgb' => '244A7C',
                    ],
                ],
                'alignment' => [
                    'horizontal' => 'center',
                    'vertical' => 'center',
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $lastRow = $sheet->getHighestRow();

                // Judul laporan
                $sheet->insertNewRowBefore(1, 4);

                $sheet->mergeCells('A1:J1');
                $sheet->setCellValue(
                    'A1',
                    '📢 SUARAWARGA'
                );

                $sheet->mergeCells('A2:J2');
                $sheet->setCellValue(
                    'A2',
                    'DATA LAPORAN WARGA'
                );

                $sheet->mergeCells('A3:J3');

                $judulStatus = match ($this->status) {
                    'baru' => 'Laporan Menunggu',
                    'diproses' => 'Laporan Sedang Diproses',
                    'selesai' => 'Laporan Selesai',
                    'ditolak' => 'Laporan Ditolak',
                    default => 'Seluruh Laporan Warga',
                };

                $sheet->setCellValue(
                    'A3',
                    $judulStatus
                );

                $sheet->mergeCells('A4:J4');
                $sheet->setCellValue(
                    'A4',
                    'Dicetak pada ' . now()->format('d/m/Y H:i')
                );

                // Judul utama
                $sheet->getStyle('A1:J1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 20,
                        'color' => [
                            'rgb' => 'FFFFFF',
                        ],
                    ],
                    'fill' => [
                        'fillType' => 'solid',
                        'startColor' => [
                            'rgb' => '244A7C',
                        ],
                    ],
                    'alignment' => [
                        'horizontal' => 'center',
                        'vertical' => 'center',
                    ],
                ]);

                // Subjudul
                $sheet->getStyle('A2:J2')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 14,
                        'color' => [
                            'rgb' => '244A7C',
                        ],
                    ],
                    'alignment' => [
                        'horizontal' => 'center',
                        'vertical' => 'center',
                    ],
                ]);

                $sheet->getStyle('A3:J3')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 12,
                        'color' => [
                            'rgb' => '475569',
                        ],
                    ],
                    'alignment' => [
                        'horizontal' => 'center',
                    ],
                ]);

                $sheet->getStyle('A4:J4')->applyFromArray([
                    'font' => [
                        'italic' => true,
                        'size' => 10,
                        'color' => [
                            'rgb' => '64748B',
                        ],
                    ],
                    'alignment' => [
                        'horizontal' => 'center',
                    ],
                ]);

                // Header tabel sekarang berada di baris 5
                $sheet->getStyle('A5:J5')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => [
                            'rgb' => 'FFFFFF',
                        ],
                    ],
                    'fill' => [
                        'fillType' => 'solid',
                        'startColor' => [
                            'rgb' => '315B85',
                        ],
                    ],
                    'alignment' => [
                        'horizontal' => 'center',
                        'vertical' => 'center',
                        'wrapText' => true,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => 'thin',
                            'color' => [
                                'rgb' => 'D1D5DB',
                            ],
                        ],
                    ],
                ]);

                // Data
                if ($lastRow >= 6) {
                    $sheet->getStyle("A6:J{$lastRow}")->applyFromArray([
                        'alignment' => [
                            'vertical' => 'top',
                            'wrapText' => true,
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => 'thin',
                                'color' => [
                                    'rgb' => 'E2E8F0',
                                ],
                            ],
                        ],
                    ]);
                }

                // Tinggi baris
                $sheet->getRowDimension(1)->setRowHeight(32);
                $sheet->getRowDimension(2)->setRowHeight(24);
                $sheet->getRowDimension(3)->setRowHeight(22);
                $sheet->getRowDimension(4)->setRowHeight(20);
                $sheet->getRowDimension(5)->setRowHeight(32);

                // Lebar kolom
                $widths = [
                    'A' => 8,
                    'B' => 28,
                    'C' => 42,
                    'D' => 18,
                    'E' => 32,
                    'F' => 20,
                    'G' => 16,
                    'H' => 22,
                    'I' => 20,
                    'J' => 22,
                ];

                foreach ($widths as $column => $width) {
                    $sheet->getColumnDimension($column)
                        ->setWidth($width);
                }

                // Filter tabel
                if ($lastRow >= 5) {
                    $sheet->setAutoFilter("A5:J{$lastRow}");
                }

                // Freeze header
                $sheet->freezePane('A6');

                // Print
                $sheet->getPageSetup()
                    ->setOrientation(
                        \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE
                    );

                $sheet->getPageSetup()
                    ->setPaperSize(
                        \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4
                    );

                $sheet->getPageSetup()
                    ->setFitToWidth(1);

                $sheet->getPageSetup()
                    ->setFitToHeight(0);

                $sheet->setShowGridlines(false);

                // Footer
                $sheet->getHeaderFooter()
                    ->setOddFooter(
                        '&C SUARAWARGA - Suara masyarakat, perubahan nyata.'
                    );
            },
        ];
    }
}
