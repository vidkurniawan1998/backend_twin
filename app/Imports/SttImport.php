<?php

namespace App\Imports;

use App\Models\SttBridging;
use Auth;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithStartRow;
use \Carbon\Carbon as Carbon;

class SttImport implements ToModel, WithStartRow, WithChunkReading, WithBatchInserts
{
    public function model(Array $row)
    {
        if ($row[0] != null) {
            $transDate = $transDate = Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[40]))->format("Y-m-d");
            return new SttBridging([
              'gudang'          => $row[0],
              'outlet_code'     => $row[1],
              'outlet_name'     => $row[2],
              'address'         => $row[3],
              'kabupaten'       => $row[4],
              'kecamatan'       => $row[5],
              'kode_pos'        => $row[6],
              'selektif'        => $row[7],
              'npwp'            => $row[8],
              'nama_pkp'        => $row[9],
              'alamat_pkp'      => $row[10],
              'cust_type'       => $row[11],
              'type_outlet'     => $row[12],
              'distrik'         => $row[13],
              'lokasi_pasar'    => $row[14],
              'nama_pasar'      => $row[15],
              'rute'            => $row[16],
              'kunjungan'       => $row[17],
              'item_code'       => $row[18],
              's_code'          => $row[19],
              'status'          => $row[20],
              'kode_supp'       => $row[21],
              'grup'            => $row[22],
              'barcode'         => $row[23],
              'brand'           => $row[24],
              'segmen'          => $row[25],
              'kemasan'         => $row[26],
              'satuan'          => $row[27],
              'salesman_name'   => $row[28],
              'team'            => $row[29],
              'spv'             => $row[30],
              'qty_dus'         => $row[31],
              'qty_pcs'         => $row[32],
              'harga_1'         => $row[33],
              'harga_2'         => $row[34],
              'harga_trans'     => $row[35],
              'volume'          => $row[36],
              'tahun'           => $row[37],
              'bulan'           => $row[38],
              'hari'            => $row[39],
              'transdate'       => $transDate,
              'subtotal'        => $row[41],
              'diskon'          => $row[42],
              'proposal'        => $row[43],
              'ppn'             => $row[44],
              'total'           => $row[45],
              'hpp'             => $row[46],
              'harga_nett'      => $row[47],
              'week'            => $row[48],
              'driver'          => $row[49],
              'helper'          => $row[50],
              'number'          => $row[51],
              'pick_slip'       => $row[52],
              'berat'           => $row[53],
              'no_pajak'        => $row[54],
              'cust_id'         => $row[55],
              'outlet_id'       => $row[56],
              'salesman'        => $row[57],
              'category'        => $row[58],
              'subcategory'     => $row[59],
              'pref_vendor'     => $row[60],
              'pembayaran'      => $row[61],
              'term'            => $row[62],
              'keterangan'      => $row[63],
              'vencode'         => $row[64],
              'pro_invoice'     => $row[65],
              'pjp'             => $row[66],
              'no_po'           => $row[67],
              'user_id'         => Auth::id()
            ]);   
        }
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
