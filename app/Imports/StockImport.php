<?php

namespace App\Imports;

use App\Models\StockBridging;
use App\Traits\DepoLocationTrait;
use Auth;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use \Carbon\Carbon as Carbon;

class StockImport implements ToCollection, WithChunkReading, WithBatchInserts
{
    use DepoLocationTrait;
    public function collection(Collection $rows)
    {
        // warehouse
        $warehouses = [];
        foreach ($rows[0] as $key => $row) {
            if ($key > 8) {
                $warehouses[] = strtoupper($row);
            }
        }

        $rows->forget(0);
        foreach ($rows as $key => $row) {
            $firstWh = 9;
            if ($row[5] == '') {
                continue;
            }

            foreach ($warehouses as $key => $wr) {
                $depo = $this->depoLocation($wr);
                if ($depo == '') {
                    continue;
                }

                if ($row[$firstWh] < 0) {
                    $firstWh++;
                    continue;
                }

                $data = [
                    'depo'      => $depo,
                    'gudang'    => $wr,
                    'kode'      => $row[0],
                    'supp_code' => $row[1],
                    'barcode'   => $row[2],
                    'supplier'  => $row[6],
                    'nama_barang' => $row[3],
                    'deskripsi' => $row[4],
                    'harga'     => $row[7],
                    'dus'       => 0,
                    'pcs'       => intval($row[$firstWh]),
                    'volume'    => intval($row[5]),
                    'total_pcs' => intval($row[$firstWh]),
                    'nominal_per_pcs' => round($row[7]/$row[5], 2),
                    'nominal'   => $row[8],
                    'user_id'   => Auth::id()
                ];

                // dd($data);
                StockBridging::create($data);

                $firstWh++;
            }
        }
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
