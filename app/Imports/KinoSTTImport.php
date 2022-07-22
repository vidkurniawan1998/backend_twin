<?php

namespace App\Imports;

use App\Models\KinoBridging;
use App\Traits\KinoCodeCompany;
use App\Traits\KinoCodeDistribution;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithStartRow;
use \Carbon\Carbon as Carbon;

class KinoSTTImport  implements ToModel, WithStartRow, WithChunkReading, WithBatchInserts
{
  use KinoCodeCompany, KinoCodeDistribution;
  public function model(array $row)
  {
    // dd($row);
    $transDate = Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[40]))->format("Y-m-d");
    $discAKM    = $this->getDiscount($row[43], 'AKM');
    $discKINO   = $this->getDiscount($row[43], 'KINO');
    $discValue  = $this->getDiscount($row[43], 'VALUE');
    // $cdist   = $this->cdist($row[0]);
    if($row[0] != null){
        $ccompany   = $this->ccompany($row[0]);
        $inPCS = ($row[31] * $row[36]) + $row[32];
        return new KinoBridging([
          'cflag'   => 'C',
          'cdist'   => $ccompany,
          'ctran'   => $row[51],
          'dtran'   => $transDate,
          'outlet'  => $row[1],
          'csales'  => $row[29],
          'ccompany'=> $ccompany,
          'citem'   => trim(strtoupper($row[21])),
          'cgudang1'=> strtoupper($row[0]),
          'cgudang2'=> "",
          'ctypegd1'=> "GU",
          'ctypegd2'=> "",
          'njumlah' => $inPCS,
          'lbonus'  => intval($row[45]) > 0 ? 0:1,
          'unit'    => strtoupper($row[27]),
          'nisi'    => 1,
          'nharga'  => $inPCS > 0 ? $row[41]/$inPCS:0 ,
          'ndisc1'  => $discAKM[0] ?? 0,  
          'ndisc2'  => $discAKM[1] ?? 0,  
          'ndisc3'  => $discAKM[2] ?? 0,  
          'ndisc4'  => $discAKM[3] ?? 0,  
          'ndisc5'  => $discKINO[0] ?? 0,  
          'ndisc6'  => $discKINO[1] ?? 0,  
          'ndiscg1' => $inPCS > 0 ? ($discValue[0] ?? 0)/$inPCS:0,  
          'ndiscg2' => 0,  
          'fppn'    => 1,  
          'netsales'=> $inPCS > 0 ? $row[45]:0,
        ]);
    }
  }

  public function getDiscount($discount = "", String $type): array
  {
    $result = [];
    // VALIDATE
    if ($discount <> '') {
      // REMOVE SPACE
      $discount = explode(" ", $discount);
      // DISCOUNT TEXT
      $discount = $discount[(count($discount) - 1)];
      // VALIDATE DISCOUNT BERTINGKAT
      if (strpos($discount, '+') !== false) {
        $discount = explode("+", $discount);
        foreach ($discount as $key => $disc) {
          $disc = Str::before($disc, "%");
          if ($type === 'AKM') {
            if (strpos($disc, '#') !== false) {
              $result[] = Str::after($disc, "#");
            }
          } elseif ($type === 'KINO') {
            if (strpos($disc, '@') !== false) {
              $result[] = Str::after($disc, "@");
            }
          } else {
            if (strpos($disc, '&') !== false) {
              $result[] = Str::after($disc, "&");
            }
          }
        }
      } else {
        $disc = 0;
        if ($type === 'AKM') {
          if (strpos($discount, '#') !== false) {
            $disc = Str::before($discount, "%");
            $disc = Str::after($discount, "#");
          }
        } elseif ($type === 'KINO') {
          if (strpos($discount, '@') !== false) {
            $disc = Str::before($discount, "%");
            $disc = Str::after($discount, "@");
          }
        } else {
          if (strpos($discount, '&') !== false) {
            $disc = Str::after($discount, "&");
          }
        }
        $result   = [$disc];
      }
    }

    return $result;
  }

  public function startRow(): int
  {
    return 2;
  }

  public function batchSize(): int
  {
    return 500;
  }

  public function chunkSize(): int
  {
      return 500;
  }
}