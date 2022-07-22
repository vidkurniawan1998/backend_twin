<?php 

namespace App\Imports;

use App\Models\KinoBridging;
use App\Traits\KinoCodeCompany;
use App\Traits\KinoCodeDistribution;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use \Carbon\Carbon as Carbon;

class KinoStockImport implements ToCollection, WithStartRow
{
  use KinoCodeCompany, KinoCodeDistribution;
  public function collection(Collection $rows)
  {
    // warehouse
    $warehouses = [];
    foreach ($rows[0] as $key => $row) {
      if ($key > 9) {
        $warehouses[] = strtoupper($row);
      }
    }

    $rows->forget(0);
    foreach ($rows as $key => $row) {
      $firstWh= 10;
      foreach ($warehouses as $wh) {
        $ccompany      = $this->ccompany($wh);
        $inPCSTotal = floatval($row[$firstWh]);
        $firstWh++;
        if ($ccompany === '') {
            continue;
        }
        
        $transDate = Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[0]))->format("Y-m-d");
        $nharga = 0;
        if (floatval($row[6]) <> 0) {
            $nharga = floatval($row[8])/floatval($row[6]);
        }
        
        $subtotal = $inPCSTotal * $nharga;
        $netsales = $subtotal + ($subtotal * 0.1);
        KinoBridging::create([
          'cflag'   => 'H',
          'cdist'   => $ccompany,
          'ctran'   => "",
          'dtran'   => $transDate,
          'outlet'  => "",
          'csales'  => "",
          'ccompany'=> $ccompany,
          'citem'   => trim(strtoupper($row[2])) ?? "",
          'cgudang1'=> $wh,
          'cgudang2'=> "",
          'ctypegd1'=> $this->whType($wh),
          'ctypegd2'=> "",
          'njumlah' => $inPCSTotal,
          'lbonus'  => 0,
          'unit'    => "PCS",
          'nisi'    => 1,
          'nharga'  => $nharga,
          'ndisc1'  => 0,  
          'ndisc2'  => 0,  
          'ndisc3'  => 0,  
          'ndisc4'  => 0,  
          'ndisc5'  => 0,  
          'ndisc6'  => 0,  
          'ndiscg1' => 0,  
          'ndiscg2' => 0,  
          'fppn'    => 1,  
          'netsales'=> $netsales,
        ]);
      }
    }
  }

  public function startRow(): int
  {
    return 1;
  }
  
  public function whType(String $wh): string
  {
    if (strpos($wh, 'BS') !== false) {
      return "GB";
    } else {
      return "GU";
    }
  }
}