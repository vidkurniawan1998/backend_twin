<?php


namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class PajakImport implements ToCollection
{
    public function collection(Collection $rows)
    {
        return $rows;
    }
}