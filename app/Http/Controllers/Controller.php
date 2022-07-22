<?php

namespace App\Http\Controllers;

use App\Models\Log;
use App\Models\Barang;
use App\Models\LogStock;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
    public function Unauthorized()
    {
        return response()->json(['message' => 'Tidak memiliki akses'], 403);
    }

    public function sendMessageBot($file, $method, $error)
    {
        $token = '1253058966:AAH0_mP2-Z3z47f5KKx89GbtYZjFykuOgpM';
        $chatID = '-467075398';
        $data = [
            'text' => '#Hi.. File:'.$file.' -- Function:'.$method.' -- Message: '.$error,
            'chat_id' => $chatID,
            'parse_mode' => 'HTML',
        ];
        $url = "https://api.telegram.org/bot".$token."/sendMessage";
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data)
        ]);
        $res = curl_exec($ch);
        curl_close($ch);
    }

    public function log($data)
    {
        $data['created_at'] = Carbon::now();
        $data['updated_at'] = Carbon::now();
        Log::create($data);
    }

    public function dataNotFound($param)
    {
        return response()->json([
            'message' => "Data {$param} tidak ditemukan!"
        ], 404);
    }

    public function storeTrue($param)
    {
        return response()->json([
            'message' => "Tambah data {$param} berhasil"
        ], 201);
    }

    public function storeFalse($param)
    {
        return response()->json([
            'message' => "Gagal menambah data {$param}!"
        ], 400);
    }

    public function updateTrue($param)
    {
        return response()->json([
            'message' => "Update data {$param} berhasil"
        ], 200);
    }

    public function updateFalse($param)
    {
        return response()->json([
            'message' => "Gagal mengupdate data {$param}!"
        ], 400);
    }

    public function destroyTrue($param)
    {
        return response()->json([
            'message' => "Hapus data {$param} berhasil"
        ], 200);
    }

    public function destroyFalse($param)
    {
        return response()->json([
            'message' => "Gagal menghapus data {$param}!"
        ], 400);
    }

    public function createLogStock(Array $data)
    {
        foreach ($data as $row) {
            $row['parameter'] = ($row['referensi'] == 'penjualan' || $row['referensi'] == 'mutasi keluar') ? -1 : 1 ;
            LogStock::insert($row);
        }
    }

    public function deleteLogStock(Array $data)
    {
        LogStock::where($data)->delete();
    }

    public function limitFalse($param)
    {
        return response()->json([
            'message' => "Melibihi batas {$param}!"
        ], 400);
    }

    public function stock_fisik(Int $id_barang, Int $id_gudang)
    {
        $barang     = Barang::find($id_barang);
        $log_stock  = LogStock::whereIdBarang($id_barang)->whereIdGudang($id_gudang)
                    ->select('id_barang', 'referensi', 'status', DB::raw('SUM(qty_pcs) as qty_pcs'))
                    ->groupBy('id_barang', 'referensi', 'status')->get();
        $stock          = 0;
        $stock_awal     = $log_stock->where('referensi', 'stock awal');
        $penerimaan     = $log_stock->where('referensi', 'penerimaan barang');
        $penjualan      = $log_stock->where('referensi', 'penjualan')->where('status', 'delivered');
        $mutasi_masuk   = $log_stock->where('referensi', 'mutasi masuk')->where('status', 'received');
        $mutasi_keluar  = $log_stock->where('referensi', 'mutasi keluar')->where('status', 'received');
        $adj            = $log_stock->where('referensi', 'adjustment')->where('status', 'approved');
        $retur          = $log_stock->where('referensi', 'retur')->where('status', 'approved');

        // $detail_sales_pending = DB::table('detail_penjualan AS a')
        //         ->join('penjualan AS b', 'a.id_penjualan', 'b.id')
        //         ->join('stock AS c', 'a.id_stock', 'c.id')
        //         ->where('c.id_gudang', $id_gudang)
        //         ->where('c.id_barang', $id_barang)
        //         ->where('b.tanggal', '>', '2020-09-18')
        //         ->whereDate('b.tanggal', '<=', $tanggal)
        //         ->whereIn('b.status', ['loaded'])
        //         ->whereNull('b.deleted_at')
        //         ->select('c.id_barang', 'b.status', DB::raw('SUM(a.qty) AS qty'), DB::raw('SUM(a.qty_pcs) AS qty_pcs'))
        //         ->groupBy('c.id_barang', 'b.status')->get();

        if (!$stock_awal->isEmpty()) {
            $stock += $stock_awal->first()->qty_pcs;
        }

        if (!$penerimaan->isEmpty()) {
            $stock += $penerimaan->first()->qty_pcs;
        }

        if (!$mutasi_masuk->isEmpty()) {
            $stock += $mutasi_masuk->first()->qty_pcs;
        }

        if (!$mutasi_keluar->isEmpty()) {
            $stock -= $mutasi_keluar->first()->qty_pcs;
        }

        if (!$penjualan->isEmpty()) {
            $stock -= $penjualan->first()->qty_pcs;
        }

        if (!$adj->isEmpty()) {
            $stock += $adj->first()->qty_pcs;
        }

        if (!$retur->isEmpty()) {
            $stock += $retur->first()->qty_pcs;
        }

        // if (!$detail_sales_pending->isEmpty()) {
        //     $stock -= $detail_sales_pending->first()->qty * $barang->isi + $detail_sales_pending->first()->qty_pcs;
        // }

        return $stock;
    }

    public function stock_akhir(Int $id_barang, Int $id_gudang)
    {
        $log_stock = LogStock::whereIdBarang($id_barang)->whereIdGudang($id_gudang)
            ->select('id_barang', 'referensi', 'status', DB::raw('SUM(qty_pcs) as qty_pcs'))
            ->groupBy('id_barang', 'referensi', 'status')->get();
        $stock          = 0;
        $stock_awal     = $log_stock->where('referensi', 'stock awal');
        $penerimaan     = $log_stock->where('referensi', 'penerimaan barang');
        $penjualan      = $log_stock->where('referensi', 'penjualan')->where('status', 'approved');
        $mutasi_masuk   = $log_stock->where('referensi', 'mutasi masuk')->where('status', 'received');
        $mutasi_keluar  = $log_stock->where('referensi', 'mutasi keluar')->where('status', 'approved');
        $adj            = $log_stock->where('referensi', 'adjustment')->where('status', 'approved');
        $retur          = $log_stock->where('referensi', 'retur')->where('status', 'approved');

        if (!$stock_awal->isEmpty()) {
            $stock += $stock_awal->first()->qty_pcs;
        }

        if (!$penerimaan->isEmpty()) {
            $stock += $penerimaan->first()->qty_pcs;
        }

        if (!$mutasi_masuk->isEmpty()) {
            $stock += $mutasi_masuk->first()->qty_pcs;
        }

        if (!$mutasi_keluar->isEmpty()) {
            $stock -= $mutasi_keluar->first()->qty_pcs;
        }

        if (!$penjualan->isEmpty()) {
            $stock -= $penjualan->first()->qty_pcs;
        }

        if (!$adj->isEmpty()) {
            $stock += $adj->first()->qty_pcs;
        }

        if (!$retur->isEmpty()) {
            $stock += $retur->first()->qty_pcs;
        }

        return $stock;
    }

}
