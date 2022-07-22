<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;
use Illuminate\Support\Facades\DB;
use App\Models\StockOpname;
use App\Models\DetailStockOpname;

class DetailStockOpnameController extends Controller
{
    protected $jwt;

    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
    }

    public function index($id)
    {
        try {
            $detail_stock_opname = DB::table("detail_stock_opname")
                ->join("stock", "detail_stock_opname.id_stock", "=", "stock.id")
                ->join("barang", "stock.id_barang", "=", "barang.id")
                ->where('detail_stock_opname.id_stock_opname', '=', $id)
                ->where('barang.status', '=', 1)
                ->select("detail_stock_opname.*", "stock.id_barang", 'barang.kode_barang', 'barang.nama_barang', 'barang.isi')
                ->orderBy('barang.kode_barang')->get();

            $detail = [];
            foreach ($detail_stock_opname as $dso) {
                $harga = DB::table('harga_barang')
                    ->select('harga')->where('id_barang', $dso->id_barang)
                    ->where('tipe_harga', "dbp")->latest()->first();

                $harga_dbp = 0;
                if ($harga)
                    $harga_dbp = $harga->harga;

                $selisih_ctn = $dso->qty_fisik - $dso->qty;
                $selisih_pcs = $dso->qty_pcs_fisik - $dso->qty_pcs;

                $selisih_value = $harga_dbp * ($selisih_ctn + ($selisih_pcs / $dso->isi));

                $detail[] = [
                    "id" => $dso->id,
                    "id_stock_opname" => $dso->id,
                    "id_stock" => $dso->id_stock,
                    "kode_barang" => $dso->kode_barang,
                    "nama_barang" => $dso->nama_barang,
                    "isi" => $dso->isi,
                    "qty" => $dso->qty,
                    "qty_pcs" => $dso->qty_pcs,
                    "qty_fisik" => $dso->qty_fisik,
                    "qty_pcs_fisik" => $dso->qty_pcs_fisik,
                    "selisih_ctn" => $selisih_ctn,
                    "selisih_pcs" => $selisih_pcs,
                    "harga" => $harga_dbp,
                    "selisih_value" => $selisih_value,
                ];
            }
            return [
                "data" => $detail
            ];
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function store(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            for ($i = 0; $i < count(collect($request)); $i++) {
                $input = [
                    'qty' => $request[$i]["qty"],
                    'qty_pcs' => $request[$i]["qty_pcs"],
                    'qty_fisik' => $request[$i]["qty_fisik"],
                    'qty_pcs_fisik' => $request[$i]["qty_pcs_fisik"],
                ];

                DetailStockOpname::where('id', $request[$i]["id"])->update($input);
            }

            // update stock opname menjadi approved
            DB::table('stock_opname')
                ->where('id', $id)
                ->update(['is_approved' => 1]);

            DB::commit();
            return response()->json([
                'message' => "Data Berhasil Disetujui"
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function update(Request $request)
    {
        for ($i = 0; $i < count(collect($request)); $i++) {
            $input = [
                'qty_fisik' => $request[$i]["qty_fisik"],
                'qty_pcs_fisik' => $request[$i]["qty_pcs_fisik"],
            ];

            DetailStockOpname::where('id', $request[$i]["id"])->update($input);
        }

        return response()->json([
            'message' => "Data Berhasil Diupdate"
        ], 201);
    }
}
