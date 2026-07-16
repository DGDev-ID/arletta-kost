<?php

namespace App\Exports;

use App\Models\Transaction;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class TransactionExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithEvents, WithCustomStartCell
{
    protected $transactions;

    public function __construct(Collection $transactions)
    {
        $this->transactions = $transactions;
    }

    public function collection()
    {
        return $this->transactions;
    }

    public function startCell(): string
    {
        return 'A2';
    }

    public function headings(): array
    {
        return [
            'Order ID',
            'Tenant',
            'Room',
            'Metode Pembayaran',
            'Tipe Transaksi',
            'Total (Rp)',
            'Status',
            'Tanggal Check-in',
            'Tanggal Transaksi',
        ];
    }

    public function map($trx): array
    {
        $paymentLabel = match ($trx->payment_type) {
            'manual'   => 'Manual',
            'midtrans' => $trx->midtrans_method ? strtoupper($trx->midtrans_method) : 'Midtrans',
            'debit'    => 'Debit',
            default    => $trx->payment_type,
        };

        $txType = match ($trx->transaction_type) {
            'full_payment'     => 'Full Payment',
            'down_payment'     => 'Down Payment',
            'finished_payment' => 'Pelunasan',
            default            => $trx->transaction_type ?? 'Full Payment',
        };

        return [
            $trx->order_id,
            $trx->bill?->tenant?->name ?? '-',
            $trx->bill?->room?->room_number ?? '-',
            $paymentLabel,
            $txType,
            (int) $trx->total_price,
            strtoupper($trx->status),
            $trx->bill?->start_date?->format('d-m-Y') ?? '-',
            $trx->created_at->format('d-m-Y H:i'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the headings row
            2 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF4F46E5'], // Indigo-600
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // Add Title
                $sheet->setCellValue('A1', 'LAPORAN TRANSAKSI ARLETTA KOST');
                $sheet->mergeCells('A1:I1');
                
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 16,
                        'color' => ['argb' => 'FF1F2937'] // Gray-800
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // Adjust row height for title
                $sheet->getRowDimension(1)->setRowHeight(30);
                $sheet->getRowDimension(2)->setRowHeight(20);

                // Add Borders to all data
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();
                $range = 'A2:' . $highestColumn . $highestRow;

                $sheet->getStyle($range)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => 'FFD1D5DB'], // Gray-300
                        ],
                    ],
                ]);
                
                // Center align specific columns (Metode, Tipe, Status, Dates)
                $sheet->getStyle('D3:E' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('G3:I' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Format numbers for Total column
                $sheet->getStyle('F3:F' . $highestRow)->getNumberFormat()->setFormatCode('#,##0');
            },
        ];
    }
}
