<?php


namespace App\Imports;


use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;

class TipeBarangImport implements ToCollection, WithStartRow
{
    public function collection(Collection $collection)
    {
        return $collection;
    }

    public function startRow():int
    {
        return 2;
    }
}