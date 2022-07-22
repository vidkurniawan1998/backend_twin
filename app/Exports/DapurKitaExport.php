<?php

namespace App\Exports;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;

class DapurKitaExport implements FromCollection, WithHeadings, WithCustomStartCell, WithMapping, ShouldAutoSize, WithEvents
{
    use Exportable;
    
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return $this->data;
    }
    
    public function startCell(): string
    {
        return 'A2';
    }

    public function map($data):array
    {
        return [
            $data['kode'],
            $data['supp_kode'],
            $data['barcode'],
            $data['nama_brg'],
            $data['deskripsi'],
            $data['isi'],
            $data['supplier'],
            $data['denpasar']['dus'],
            $data['denpasar']['pcs'],
            $data['klungkung']['dus'],
            $data['klungkung']['pcs'],
            $data['singaraja']['dus'],
            $data['singaraja']['pcs'],
            $data['negara']['dus'],
            $data['negara']['pcs']
        ];
    }

    public function headings(): array
    {
        return [
            'Kode',
            'SuppCode',
            'barcode',
            'NamaBrg',
            'Deskripsi',
            'ISI',
            'Supplier',
            'DUS',
            'PCS',
            'DUS',
            'PCS',
            'DUS',
            'PCS',
            'DUS',
            'PCS'
        ];
    }

    public function registerEvents():array
    {
        return [
            AfterSheet::class    => function(AfterSheet $event) {
                $cellRange = 'A2:O2'; // All headers
                $event->sheet->getDelegate()->getStyle($cellRange)->getFont()->setSize(12);
                // DEPO
                $event->sheet->setCellValue('H1', 'DENPASAR');
                $event->sheet->setCellValue('J1', 'KLUNGKUNG');
                $event->sheet->setCellValue('L1', 'SINGARAJA');
                $event->sheet->setCellValue('N1', 'NEGARA');

                $event->sheet->getStyle('C8:W25')->applyFromArray([
                    'borders' => [
                    'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['argb' => '000000'],
                        ],
                    ]
                ]);
            },
        ];
    }
}
