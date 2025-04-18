<?php

namespace App\Exports;

use App\Models\Transaksi;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TransaksiExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles, WithEvents
{
    /**
     * @var string
     */
    protected $tanggal;

    public function __construct($tanggal)
    {
        $this->tanggal = $tanggal;
    }

    public function collection()
    {
        $data = Transaksi::whereDate('waktu_transaksi', $this->tanggal)->get([
            'waktu_transaksi',
            'saldo_awal_hari_olibs',
            'saldo_akhir_hari_berjalan_olibs',
            'cut_off_olibs',
            'kewajiban_olibs',
            'outgoing_ossw',
            'incoming_ossw',
            'selisih',
            'status',
        ]);

        $dataArray = $data->toArray();
        foreach ($dataArray as $key => &$item) {
            $item = [
                'no' => $key + 1,
                'waktu_transaksi' => \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $item['waktu_transaksi'])->format('d M H:i'),
                'saldo_awal_hari_olibs' => number_format($item['saldo_awal_hari_olibs'], 2, ',', '.'),
                'saldo_akhir_hari_berjalan_olibs' => number_format($item['saldo_akhir_hari_berjalan_olibs'], 2, ',', '.'),
                'cut_off_olibs' => number_format($item['cut_off_olibs'], 2, ',', '.'),
                'kewajiban_olibs' => number_format($item['kewajiban_olibs'], 2, ',', '.'),
                'outgoing_ossw' => number_format($item['outgoing_ossw'], 2, ',', '.'),
                'incoming_ossw' => number_format($item['incoming_ossw'], 2, ',', '.'),
                'selisih' => number_format($item['selisih'], 2, ',', '.'),
                'status' =>  $item['status'] === 'warning' ? 'Warning' : 'Good',
            ];
        }

        return collect($dataArray);
    }

    public function headings(): array
    {
        return [
            // Baris ke-1: Header grup
            [
                'No',
                'Waktu Transaksi',
                'OLIBS', '', '', '',
                'OSSW', '',
                'Selisih',
                'Status',
            ],
            // Baris ke-2: Sub-header
            [
                '',
                '',
                'Saldo Awal Hari',
                'Saldo Akhir Hari Berjalan',
                'Cut Off',
                'Kewajiban',
                'Outgoing',
                'Incoming',
                '',
                '',
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
    
                // Merge dan style header seperti sebelumnya
                $sheet->mergeCells('A1:A2');
                $sheet->mergeCells('B1:B2');
                $sheet->mergeCells('C1:F1');
                $sheet->mergeCells('G1:H1');
                $sheet->mergeCells('I1:I2');
                $sheet->mergeCells('J1:J2');
    
                $sheet->getStyle('A1:J2')->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'C6EFCE'],
                    ],
                    'font' => [
                        'bold' => true,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => '000000'],
                        ],
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                ]);
    
                // Mulai cek dari baris ke-3 (setelah header)
                $highestRow = $sheet->getHighestRow();
    
                for ($row = 3; $row <= $highestRow; $row++) {
                    $statusCell = 'J' . $row;
                    $statusValue = $sheet->getCell($statusCell)->getValue();
    
                    if (strtolower($statusValue) === 'warning') {
                        // Selisih = kolom I, Status = kolom J
                        $sheet->getStyle("I{$row}:J{$row}")->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['argb' => 'FF0000'],
                            ],
                            'font' => [
                                'color' => ['argb' => Color::COLOR_WHITE],
                                'bold' => true,
                            ],
                        ]);
                    }
                }
            },
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Border untuk seluruh sheet (selain header, karena header sudah dibungkus di events)
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        $range = 'A3:' . $highestColumn . $highestRow;

        $sheet->getStyle($range)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => '000000'],
                ],
            ],
        ]);

        return [];
    }
}
