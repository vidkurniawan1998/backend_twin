<?php


namespace App\Http\Controllers;


use App\Helpers\Helper;
use App\Models\Perusahaan;
use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;
use App\Models\LogStock;
use Illuminate\Support\Facades\DB;

class PosisiStockMitraController extends Controller
{
    protected $jwt;
    public function __construct(JWTAuth $jwt){
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
    }

    public function index(Request $request)
    {
        if (!$this->user->can('Menu Posisi Stock Gudang Mitra')) {
            return $this->Unauthorized();
        }

        $start_date   = $request->start_date; 
        $end_date     = $request->end_date; 
        $id_barang    = $request->has('id_barang') ? is_array($request->id_barang) ? count($request->id_barang)>0 ? $request->id_barang 
        : [] : [$request->id_barang] : [];

        $data_stock_akhir = LogStock::join('barang','barang.id','log_stock.id_barang')
        ->join('gudang','gudang.id','log_stock.id_gudang')
        ->whereNotNull('barang.id_mitra')
        ->whereDate('tanggal','<=',$end_date)
        ->when(count($id_barang)>0, function ($q) use ($id_barang){
            return $q->whereIn('id_barang',$id_barang);
        })
        ->where(function($q){
            return $q->orWhere(function($q){
                return $q->where('referensi','stock awal');
            })
            ->orWhere(function($q){
                return $q->where('referensi','penerimaan barang');
            })
            ->orWhere(function($q){
                return $q->where('referensi','mutasi masuk')->where('log_stock.status','received');
            })
            ->orWhere(function($q){
                return $q->where('referensi','penjualan')->where('log_stock.status','delivered');
            })
            ->orWhere(function($q){
                return $q->where('referensi','mutasi keluar')->where('log_stock.status','received');
            })        
            ->orWhere(function($q){
                return $q->where('referensi','adjustment');
            })
            ->orWhere(function($q){
                return $q->where('referensi','retur');
            });
        })
        ->select(
            DB::raw('id_barang, id_gudang, SUM(qty_pcs*parameter) as stock_akhir, nama_barang, kode_barang, nama_gudang, isi')
        )
        ->groupBy('id_barang')
        ->groupBy('id_gudang')
        ->orderBy('id_barang')
        ->get();

        //DB::connection()->enableQueryLog();

        $data_stock = LogStock::join('barang','barang.id','log_stock.id_barang')
        ->whereNotNull('barang.id_mitra')   
        ->whereBetween('tanggal',[$start_date,$end_date])
        ->when(count($id_barang)>0, function ($q) use ($id_barang){
            return $q->whereIn('id_barang',$id_barang);
        })
        ->where(function($q){
            return $q->orWhere(function($q){
                return $q->where('referensi','stock awal');
            })
            ->orWhere(function($q){
                return $q->where('referensi','penerimaan barang');
            })
            ->orWhere(function($q){
                return $q->where('referensi','mutasi masuk')->where('log_stock.status','received');
            })
            ->orWhere(function($q){
                return $q->where('referensi','penjualan')->where('log_stock.status','delivered');
            })
            ->orWhere(function($q){
                return $q->where('referensi','mutasi keluar')->where('log_stock.status','received');
            })        
            ->orWhere(function($q){
                return $q->where('referensi','adjustment');
            })
            ->orWhere(function($q){
                return $q->where('referensi','retur');
            });
        })
        ->select(
            DB::raw('id_barang, id_gudang, SUM(qty_pcs) as stock, referensi')
        )
        ->groupBy('id_barang')
        ->groupBy('id_gudang')
        ->groupBy('referensi')
        ->orderBy('id_barang')
        ->get();

        $data     = [];
        $total    = [
            'saldo_awal_qty'        => 0,
            'saldo_awal_pcs'        => 0,
            'qty_penerimaan'        => 0,
            'qty_pcs_penerimaan'    => 0,
            'qty_mutasi_masuk'      => 0,
            'qty_pcs_mutasi_masuk'  => 0,
            'qty_adjustment'        => 0,
            'qty_pcs_adjustment'    => 0,
            'qty_mutasi_keluar'     => 0,
            'qty_pcs_mutasi_keluar' => 0,
            'qty_deliver'           => 0,
            'qty_pcs_deliver'       => 0,
            'qty_retur'             => 0,
            'qty_pcs_retur'         => 0,
            'saldo_akhir_qty'       => 0,
            'saldo_akhir_pcs'       => 0,
        ];
        foreach ($data_stock_akhir as $row) {
            $res               = $data_stock->where('id_barang',$row->id_barang)->where('id_gudang',$row->id_gudang);
            $penerimaan_barang = $res->where('referensi','penerimaan barang')->pluck('stock')->toArray();
            $mutasi_masuk      = $res->where('referensi','mutasi masuk')->pluck('stock')->toArray();
            $mutasi_keluar     = $res->where('referensi','mutasi keluar')->pluck('stock')->toArray();
            $penjualan         = $res->where('referensi','penjualan')->pluck('stock')->toArray();
            $adjustment        = $res->where('referensi','adjustment')->pluck('stock')->toArray();
            $retur             = $res->where('referensi','retur')->pluck('stock')->toArray();

            $stock_akhir       = floatval($row->stock_akhir);
            $isi               = floatval($row->isi);
            $penerimaan_barang = floatval(implode($penerimaan_barang));
            $mutasi_masuk      = floatval(implode($mutasi_masuk));
            $adjustment        = floatval(implode($adjustment));
            $mutasi_keluar     = floatval(implode($mutasi_keluar));
            $penjualan         = floatval(implode($penjualan));
            $retur             = floatval(implode($retur));
            $stock_awal        = $stock_akhir
            -$penerimaan_barang
            -$mutasi_masuk
            -$adjustment
            +$mutasi_keluar
            +$penjualan
            -$retur;
            $res = [
                'id_barang'         => $row->id_barang,
                'id_gudang'         => $row->id_gudang,
                'nama_gudang'       => $row->nama_gudang,
                'kode_barang'       => $row->kode_barang,
                'nama_barang'       => $row->nama_barang,
                'saldo_awal_qty'    => floor($stock_awal/$isi),
                'saldo_awal_pcs'    => fmod($stock_awal, $isi),
                'qty_penerimaan'        => floor($penerimaan_barang/$isi),
                'qty_pcs_penerimaan'    => fmod($penerimaan_barang, $isi),
                'qty_mutasi_masuk'      => floor($mutasi_masuk/$isi),
                'qty_pcs_mutasi_masuk'  => fmod($mutasi_masuk, $isi),
                'qty_adjustment'        => floor($adjustment/$isi),
                'qty_pcs_adjustment'    => fmod($adjustment, $isi),
                'qty_mutasi_keluar'     => floor($mutasi_keluar/$isi),
                'qty_pcs_mutasi_keluar' => fmod($mutasi_keluar, $isi),
                'qty_deliver'           => floor($penjualan/$isi),
                'qty_pcs_deliver'       => fmod($penjualan, $isi),
                'qty_retur'             => floor($retur/$isi),
                'qty_pcs_retur'         => fmod($retur, $isi),
                'saldo_akhir_qty'       => floor($stock_akhir/$isi),
                'saldo_akhir_pcs'       => fmod($stock_akhir, $isi),
            ];
            $total['saldo_awal_qty']        += $res['saldo_awal_qty'];   
            $total['qty_penerimaan']        += $res['qty_penerimaan']; 
            $total['qty_mutasi_masuk']      += $res['qty_mutasi_masuk']; 
            $total['qty_adjustment']        += $res['qty_adjustment'];   
            $total['qty_mutasi_keluar']     += $res['qty_mutasi_keluar'];   
            $total['qty_deliver']           += $res['qty_deliver'];  
            $total['qty_retur']             += $res['qty_retur'];   
            $total['saldo_akhir_qty']       += $res['saldo_akhir_qty'];  

            $total['saldo_awal_pcs']        += $res['saldo_awal_pcs']; 
            $total['qty_pcs_penerimaan']    += $res['qty_pcs_penerimaan'];
            $total['qty_pcs_mutasi_masuk']  += $res['qty_pcs_mutasi_masuk'];
            $total['qty_pcs_adjustment']    += $res['qty_pcs_adjustment'];
            $total['qty_pcs_mutasi_keluar'] += $res['qty_pcs_mutasi_keluar'];
            $total['qty_pcs_deliver']       += $res['qty_pcs_deliver'];
            $total['qty_pcs_retur']         += $res['qty_pcs_retur'];
            $total['saldo_akhir_pcs']       += $res['saldo_akhir_pcs'];
            $data [] = $res;
        }

        //dd(DB::getQueryLog());

        return response()->json([
            'total'     => $total,
            'data'      => $data
        ]);
    }
}
