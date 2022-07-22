<?php 

namespace App\Exports;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
// use Maatwebsite\Excel\Concerns\WithMapping;

class ReckitExport implements FromCollection, WithHeadings, WithCustomStartCell
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

    public function headings(): array
    {
        return [
            'Kode DB',
            'Nama DB',
            'City',
            'Tanggal',
            'Invoice',
            'Kode Cust',
            'Nama Customer',
            'DBSR/Nama Sales',
            'Channel Class 1',
            'Channel Class 2',
            'Channel Class 3',
            'Customer Group',
            'DB SKU',
            'DB SKU Deskripsi',
            'Brand/Merek',
            'RB SKU',
            'Nama SKU',
            'Qty/Pieces',
            'Case/Karton',
            'Value'
        ];
    }
}
