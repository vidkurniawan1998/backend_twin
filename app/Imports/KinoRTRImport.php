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

class KinoRTRImport implements ToModel, WithStartRow, WithChunkReading, WithBatchInserts
{
  use KinoCodeCompany, KinoCodeDistribution;
  public function model(Array $row)
  {
    $transDate = Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[2]))->format("Y-m-d");
    $jumlah = ($row[13] * $row[15]) + $row[14];
    // $cdist      = $this->cdist($row[1]);
    $ccompany  = $this->ccompany($row[1]);
    return new KinoBridging([
      'cflag'   => 'D',
      'cdist'   => $ccompany,
      'ctran'   => $row[0],
      'dtran'   => $transDate,
      'outlet'  => $row[4],
      'csales'  => strtoupper($row[7]),
      'ccompany'=> $ccompany,
      'citem'   => trim(strtoupper($row[12])),
      'cgudang1'=> strtoupper($row[1]),
      'cgudang2'=> "",
      'ctypegd1'=> $this->whType($row[9]),
      'ctypegd2'=> "",
      'njumlah' => $jumlah,
      'lbonus'  => $row[21] > 0 ? 0:1,
      'unit'    => "PCS",
      'nisi'    => 1,
      'nharga'  => round(round($row[18], 2)/$jumlah, 2),
      'ndisc1'  => 0,  
      'ndisc2'  => 0,  
      'ndisc3'  => 0,  
      'ndisc4'  => 0,  
      'ndisc5'  => 0,  
      'ndisc6'  => 0,  
      'ndiscg1' => 0,  
      'ndiscg2' => 0,  
      'fppn'    => 1,  
      'netsales'=> round($row[21], 2)
    ]);
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

  public function whType(String $category): string
  {
    $category = Str::substr($category, 0, 2);
    if ($category === 'RB') {
      return "GU";
    } else {
      return "GB";
    }
  }
}
