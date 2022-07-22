<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;
use App\Models\User;
use App\Helpers\Helper;
use Carbon\Carbon;
use App\Models\Penjualan;
use Illuminate\Support\Facades\DB;

class GrossProfitController extends Controller
{
    protected $jwt;

    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
    }

    public function index(Request $request)
    {
        if (!$this->user->can('Menu Gross Profit')){
            return $this->Unauthorized();
        }
        // $tipe_group     = $request->tipe_group;
        // $start_date     = $request->start_date;
        // $end_date       = $request->end_date;
        // $id_gudang      = $request->id_gudang;
        // $id_depo        = $request->id_depo;
        // $id_barang      = $request->id_barang;

        $id_user        = $this->user->id;
        $id_mitra       = $request->has('id_mitra') ? $request->id_mitra : '';
        $tipe_group     = strtolower('barang');
        $start_date     = $request->start_date;
        $end_date       = $request->end_date;
        $id_perusahaan  = $request->has('id_perusahaan') && count($request->id_perusahaan)>0 ? $request->id_perusahaan
                        : Helper::perusahaanByUser($id_user);
        $id_depo        = $request->has('id_depo') ? is_array($request->id_depo) ? $request->id_depo : [$request->id_depo]
                        : Helper::depoIDByUser($id_user, $id_perusahaan) ;
        $id_barang      = $request->has('id_barang') ? is_array($request->id_barang) ? $request->id_barang : [$request->id_barang] : [] ;

        $id_barang_implode = implode(',', $id_barang);
        $id_barang_search  = count($id_barang)>0 ? ' AND harga_barang.id_barang IN ('.$id_barang_implode.')' : '';
        $date_filter    = [$start_date.' 00:00:00',$end_date. ' 23:59:59'];
        $id_tipe_group  = 'id_'.$tipe_group;

        switch ($tipe_group) {
          case "perusahaan":
            $select_group   = 'depo.id_perusahaan';
            $select_group_2 = 'depo.id_perusahaan';
            $select_group_3 = 'depo.id_perusahaan';
            break;
          case "depo":
            $select_group   = 'tim.id_depo';
            $select_group_2 = 'gudang.id_depo';
            $select_group_3 = 'tim.id_depo';
            break;
          case "barang":
            $select_group   = 'barang.id as id_barang';
            $select_group_2 = 'log_stock.id_barang';
            $select_group_3 = 'log_stock.id_barang';
            break;
          default:
            $select_group   = 'barang.id as id_barang';
            $select_group_2 = 'log_stock.id_barang';
            $select_group_3 = 'log_stock.id_barang';
        }

        $barang = DB::select("
            SELECT barang.id as id_barang, (harga_barang.harga/1.1) as harga, nama_barang, kode_barang, item_code, isi FROM
            barang
            LEFT JOIN
            (SELECT MAX(created_at) as created_at, id_barang FROM harga_barang WHERE created_at<'$start_date' AND tipe_harga='dbp' GROUP BY id_barang) as data_max
            ON barang.id=data_max.id_barang
            JOIN harga_barang
            ON data_max.id_barang=harga_barang.id_barang
            WHERE harga_barang.tipe_harga='dbp' AND barang.deleted_at IS NULL AND harga_barang.created_at=data_max.created_at $id_barang_search
        ");
        $id_barang = array_column($barang, 'id_barang');
        $barang = collect($barang);

        $penjualan   = DB::table('barang')
        ->join('stock', 'stock.id_barang', 'barang.id')
        ->join('detail_penjualan','stock.id','=','detail_penjualan.id_stock')
        ->join('penjualan', 'penjualan.id', 'detail_penjualan.id_penjualan')
        ->join('tim','tim.id','penjualan.id_tim')
        ->whereNull('penjualan.deleted_at')
        ->where('penjualan.status','delivered')
        ->when($tipe_group=='perusahaan', function ($q){
            return $q->join('depo','depo.id','tim.id_depo');
        })
        ->when($id_mitra!='', function ($q) use ($id_mitra){
            return $q->where('penjualan.id_mitra',$id_mitra);
        })
        ->where('penjualan.status', '=', 'delivered')
        ->whereBetween('penjualan.delivered_at', $date_filter)
        ->when(count($id_barang)>0, function ($q) use ($id_barang){
            return $q->whereIn('barang.id',$id_barang);
        })
        ->when(count($id_depo)>0, function ($q) use ($id_depo){
            return $q->whereIn('tim.id_depo',$id_depo);
        })
        ->select(
            $select_group,
            DB::raw('
                SUM(
                    ((detail_penjualan.qty+(detail_penjualan.qty_pcs/barang.isi)) * detail_penjualan.harga_jual)/1.1
                ) as subtotal,
                SUM(
                    ((detail_penjualan.qty+(detail_penjualan.qty_pcs/barang.isi)) * detail_penjualan.disc_rupiah )/1.1
                ) as discount_rupiah,
                SUM(
                ((detail_penjualan.qty+(detail_penjualan.qty_pcs/barang.isi)) * detail_penjualan.harga_jual) / 1.1 * disc_persen / 100
                ) as discount_persen,
                SUM(detail_penjualan.qty+(detail_penjualan.qty_pcs/barang.isi)) as total_qty,
                SUM(detail_penjualan.qty_pcs+(detail_penjualan.qty*barang.isi)) as total_qty_pcs
            ')
        )
        ->when($tipe_group=='barang', function ($q){
            return $q->groupBy('barang.id')->orderBy('barang.id');
        })
        ->when($tipe_group=='depo', function ($q){
            return $q->groupBy('tim.id_depo')->orderBy('tim.id_depo');
        })
        ->when($tipe_group=='perusahaan', function ($q){
            return $q->groupBy('depo.id_perusahaan')->orderBy('depo.id_perusahaan');
        })
        ->get();

        $retur_penjualan   = DB::table('barang')
        ->join('detail_retur_penjualan','barang.id','=','detail_retur_penjualan.id_barang')
        ->join('retur_penjualan', 'retur_penjualan.id', 'detail_retur_penjualan.id_retur_penjualan')
        ->join('tim','tim.id','retur_penjualan.id_tim')
        ->when($tipe_group=='perusahaan', function ($q){
            return $q->join('depo','depo.id','tim.id_depo');
        })
        ->when($id_mitra!='', function ($q) use ($id_mitra){
            return $q->where('retur_penjualan.id_mitra',$id_mitra);
        })
        ->whereBetween('retur_penjualan.approved_at', $date_filter)
        ->when(count($id_barang)>0, function ($q) use ($id_barang){
            return $q->whereIn('barang.id',$id_barang);
        })
        ->when(count($id_depo)>0, function ($q) use ($id_depo){
            return $q->whereIn('tim.id_depo',$id_depo);
        })
        ->where('retur_penjualan.status','approved')
        ->whereNull('retur_penjualan.deleted_at')
        ->select(
            $select_group,
            DB::raw('
                SUM(
                    (
                        detail_retur_penjualan.qty_dus+(detail_retur_penjualan.qty_pcs/barang.isi)) *
                        (
                            (
                            detail_retur_penjualan.harga *
                                (
                                    1-((
                                        100*(detail_retur_penjualan.disc_persen+retur_penjualan.potongan) +
                                        disc_persen * retur_penjualan.potongan
                                    )/10000)
                                )
                            ) -
                            (
                            detail_retur_penjualan.disc_nominal *
                                (
                                    1-(
                                        retur_penjualan.potongan/100
                                    )
                                )
                            )
                    )
                ) as total,
                SUM(detail_retur_penjualan.qty_dus+(detail_retur_penjualan.qty_pcs/barang.isi)) as total_qty,
                SUM(detail_retur_penjualan.qty_pcs+(detail_retur_penjualan.qty_dus*barang.isi)) as total_qty_pcs
            ')
        )
        ->when($tipe_group=='barang', function ($q){
            return $q->groupBy('barang.id')->orderBy('barang.id');
        })
        ->when($tipe_group=='depo', function ($q){
            return $q->groupBy('tim.id_depo')->orderBy('tim.id_depo');
        })
        ->when($tipe_group=='perusahaan', function ($q){
            return $q->groupBy('depo.id_perusahaan')->orderBy('depo.id_perusahaan');
        })
        ->get();

        $stock_awal = DB::table('log_stock')
        ->join('gudang','log_stock.id_gudang','gudang.id')
        ->when($id_mitra!='', function ($q) use ($id_mitra){
            return $q->join('barang','stock.id_barang','barang.id');
        })
        ->when($tipe_group=='perusahaan', function ($q){
            return $q->join('depo','depo.id','gudang.id_depo');
        })
        ->when(count($id_depo)>0, function ($q) use ($id_depo){
            return $q->whereIn('gudang.id_depo',$id_depo);
        })
        ->when(count($id_barang)>0, function ($q) use ($id_barang){
            return $q->whereIn('id_barang',$id_barang);
        })
        ->when($id_mitra!='', function ($q) use ($id_mitra){
            return $q->when($id_mitra==0, function($q) use ($id_mitra){
                $q->whereNull('barang.id_mitra');
            })
            ->when($id_mitra>0, function($q) use ($id_mitra){
                $q->where('barang.id_mitra',$id_mitra);
            });
        })
        ->whereDate('tanggal','<',$start_date)
        ->where(function($q){
            return $q->orWhere(function($q){
                return $q->where('referensi','stock awal');
             })
            ->orWhere(function($q){
                return $q->where('referensi','penerimaan barang');
             })
            ->orWhere(function($q){
                return $q->where('referensi','mutasi masuk')->where('status','received');
             })
            ->orWhere(function($q){
                return $q->where('referensi','penjualan')->where('status','delivered');
             })
            ->orWhere(function($q){
                return $q->where('referensi','mutasi keluar')->where('status','received');
             })
            ->orWhere(function($q){
                return $q->where('referensi','adjustment');
             })
            ->orWhere(function($q){
                return $q->where('referensi','retur');
             });
        })
        ->whereNull('log_stock.deleted_at')
        ->select(
            $select_group_2,
            DB::raw('SUM(qty_pcs*parameter) as stock')
        )
        ->when($tipe_group=='barang', function ($q){
            return $q->groupBy('log_stock.id_barang')->orderBy('log_stock.id_barang');
        })
        ->when($tipe_group=='depo', function ($q){
            return $q->groupBy('gudang.id_depo')->orderBy('gudang.id_depo');
        })
        ->when($tipe_group=='perusahaan', function ($q){
            return $q->groupBy('depo.id_perusahaan')->orderBy('depo.id_perusahaan');
        })
        ->get();

        $stock_today = DB::table('log_stock')
        ->join('gudang','log_stock.id_gudang','gudang.id')
        ->when($tipe_group=='perusahaan', function ($q){
            return $q->join('depo','depo.id','gudang.id_depo');
        })
        ->when($id_mitra!='', function ($q) use ($id_mitra){
            return $q->join('barang','stock.id_barang','barang.id');
        })
        ->when(count($id_depo)>0, function ($q) use ($id_depo){
            return $q->whereIn('gudang.id_depo',$id_depo);
        })
        ->when(count($id_barang)>0, function ($q) use ($id_barang){
            return $q->whereIn('id_barang',$id_barang);
        })
        ->when($id_mitra!='', function ($q) use ($id_mitra){
            return $q->when($id_mitra==0, function($q) use ($id_mitra){
                $q->whereNull('barang.id_mitra');
            })
            ->when($id_mitra>0, function($q) use ($id_mitra){
                $q->where('barang.id_mitra',$id_mitra);
            });
        })
        ->whereBetween('tanggal',$date_filter)
        ->whereNull('log_stock.deleted_at')
        ->select(
            DB::raw('SUM(qty_pcs) as total_qty, '.$id_tipe_group.', referensi, status')
        )
        ->when($tipe_group=='barang', function ($q){
            return $q->groupBy('log_stock.id_barang');
        })
        ->when($tipe_group=='depo', function ($q){
            return $q->groupBy('gudang.id_depo');
        })
        ->groupBy('referensi')
        ->groupBy('status')
        ->when($tipe_group=='barang', function ($q){
            return $q->orderBy('log_stock.id_barang');
        })
        ->when($tipe_group=='depo', function ($q){
            return $q->orderBy('gudang.id_depo');
        })
        ->when($tipe_group=='perusahaan', function ($q){
            return $q->groupBy('depo.id_perusahaan')->orderBy('depo.id_perusahaan');
        })
        ->get();

        $stock_barang   = [];
        $temp_id_barang = 0;
        $temp_sum       = [
            'penjualan'         => 0,
            'penerimaan_barang' => 0,
            'mutasi_masuk'      => 0,
            'mutasi_keluar'     => 0,
            'adjustment'        => 0,
            'retur'             => 0,
        ];
        $n     = 0;
        $limit = count($stock_today);
        foreach ($stock_today as $row) {

            switch ($tipe_group) {
              case "perusahaan":
                $id_checker   = $row->id_perusahaan;
                break;
              case "depo":
                $id_checker   = $row->id_depo;
                break;
              case "barang":
                $id_checker   = $row->id_barang;
                break;
              default:
                $id_checker   = 0;
            }

            if($temp_id_barang!=$id_checker){
                if($n>0){
                    $stock_barang[] = [
                        $id_tipe_group  => $temp_id_barang,
                        'stock'         => $temp_sum
                    ];
                    $temp_sum       = [
                        'penjualan'         => 0,
                        'penerimaan_barang' => 0,
                        'mutasi_masuk'      => 0,
                        'mutasi_keluar'     => 0,
                        'adjustment'        => 0,
                        'retur'             => 0,
                    ];
                }
                $temp_id_barang = $id_checker;
            }
            if($row->referensi=='penjualan' && $row->status=='delivered'){
                $temp_sum ['penjualan'] =  $row->total_qty;
            }
            if($row->referensi=='penerimaan barang'){
                $temp_sum ['penerimaan_barang'] =  $row->total_qty;
            }
            if($row->referensi=='mutasi masuk' && $row->status=='received'){
                $temp_sum ['mutasi_masuk'] =  $row->total_qty;
            }
            if($row->referensi=='mutasi keluar' && $row->status=='received'){
                $temp_sum ['mutasi_keluar'] =  $row->total_qty;
            }
            if($row->referensi=='adjustment'){
                $temp_sum ['adjustment'] =  $row->total_qty;
            }
            if($row->referensi=='retur'){
                $temp_sum ['retur'] =  $row->total_qty;
            }
            $n++;
            if($n==$limit){
                $stock_barang[] = [
                    $id_tipe_group  => $id_checker,
                    'stock'         => $temp_sum
                ];
            }
        }

        $stock_bs = DB::table('log_stock')
        ->join('detail_retur_penjualan','detail_retur_penjualan.id','log_stock.id_referensi')
        ->join('retur_penjualan', 'retur_penjualan.id', 'detail_retur_penjualan.id_retur_penjualan')
        ->join('tim','tim.id','retur_penjualan.id_tim')
        ->when($tipe_group=='perusahaan', function ($q){
            return $q->join('depo','depo.id','tim.id_depo');
        })
        ->when($id_mitra!='', function ($q) use ($id_mitra){
            return $q->join('barang','stock.id_barang','barang.id');
        })
        ->where('log_stock.referensi','retur')
        ->where('log_stock.status','approved')
        ->whereBetween('retur_penjualan.approved_at', $date_filter)
        ->when(count($id_depo)>0, function ($q) use ($id_depo){
            return $q->whereIn('tim.id_depo',$id_depo);
        })
        ->when(count($id_barang)>0, function ($q) use ($id_barang){
            return $q->whereIn('log_stock.id_barang',$id_barang);
        })
        ->when($id_mitra!='', function ($q) use ($id_mitra){
            return $q->when($id_mitra==0, function($q) use ($id_mitra){
                $q->whereNull('barang.id_mitra');
            })
            ->when($id_mitra>0, function($q) use ($id_mitra){
                $q->where('barang.id_mitra',$id_mitra);
            });
        })
        ->whereNull('log_stock.deleted_at')
        ->whereNull('retur_penjualan.deleted_at')
        ->select(
            DB::raw('SUM(log_stock.qty_pcs) as stock, SUM(detail_retur_penjualan.qty_dus) as retur_penjualan_dus, SUM(detail_retur_penjualan.qty_pcs) as retur_penjualan_pcs, '. $select_group_3)
        )
        ->when($tipe_group=='barang', function ($q){
            return $q->groupBy('log_stock.id_barang')->orderBy('log_stock.id_barang');
        })
        ->when($tipe_group=='depo', function ($q){
            return $q->groupBy('tim.id_depo')->orderBy('tim.id_depo');
        })
        ->when($tipe_group=='perusahaan', function ($q){
            return $q->groupBy('depo.id_perusahaan')->orderBy('depo.id_perusahaan');
        })
        ->get();

        return response()->json([
            'barang'               => $barang,
            'penjualan'            => $penjualan,
            'retur_penjualan'      => $retur_penjualan,
            'stock'                => $stock_barang,
            'stock_bs'             => $stock_bs,
            'stock_awal'           => $stock_awal,
            // 'id_barang'            => $id_barang_implode,
            // 'id_gudang'            => $id_gudang,
        ]);
    }

    public function gross_profit_lite(Request $request)
    {
        if (!$this->user->can('Menu Gross Profit')){
            return $this->Unauthorized();
        }

        $id_user        = $this->user->id;
        $id_mitra       = $request->has('id_mitra') ? $request->id_mitra : '';
        $tipe_group     = strtolower($request->tipe_group);
        $start_date     = $request->start_date;
        $end_date       = $request->end_date;
        $id_perusahaan  = $request->has('id_perusahaan') && count($request->id_perusahaan)>0 ? $request->id_perusahaan
                        : Helper::perusahaanByUser($id_user);
        $id_depo        = $request->has('id_depo') ? is_array($request->id_depo) ? $request->id_depo : [$request->id_depo]
                        : Helper::depoIDByUser($id_user, $id_perusahaan) ;
        $id_principal   = $request->has('id_principal') && count($request->id_principal)>0 ? $request->id_principal : [] ;
        $id_brand       = $request->has('id_brand') ? is_array($request->id_brand) ? $request->id_brand : [$request->id_brand] : [] ;
        $id_barang      = $request->has('id_barang') ? is_array($request->id_barang) ? $request->id_barang : [$request->id_barang] : [] ;
        $date_filter    = [$start_date.' 00:00:00',$end_date. ' 23:59:59'];
        DB::enableQueryLog();
        switch ($tipe_group) {
          case "perusahaan":
            $select_group   = 'depo.id_perusahaan as id, nama_perusahaan as nama, kode_perusahaan as kode';
            break;
          case "depo":
            $select_group   = 'tim.id_depo as id, nama_depo as nama, kode_depo as kode';
            break;
          case "barang":
            $select_group   = 'barang.id as id, nama_barang as nama, kode_barang as kode, depo.id as id_depo, nama_depo';
            break;
          case "tim":
            $select_group   = 'tim.id as id, nama_tim as nama, tim.tipe as kode';
            break;
          case "toko":
            $select_group   = 'toko.id as id, nama_toko as nama, toko.no_acc as kode';
            break;
          default:
            $select_group   = '';
        }

        $penjualan   = DB::table('barang')
        ->join('stock', 'stock.id_barang', 'barang.id')
        ->join('detail_penjualan','stock.id','=','detail_penjualan.id_stock')
        ->join('penjualan', 'penjualan.id', 'detail_penjualan.id_penjualan')
        ->join('tim','tim.id','penjualan.id_tim')
        ->join('depo','depo.id','tim.id_depo')
        ->join('perusahaan','depo.id_perusahaan','perusahaan.id')
        ->when(count($id_principal)>0, function ($q) use ($id_brand, $id_principal){
            return $q->join('segmen','segmen.id','barang.id_segmen')
                    ->join('brand','brand.id','segmen.id_brand')
                    ->whereIn('brand.id_principal',$id_principal)
                    ->when(count($id_brand)>0, function ($q) use($id_brand){
                        return $q->whereIn('segmen.id_brand',$id_brand);
                    });
        })
        ->whereNull('penjualan.deleted_at')
        ->when($tipe_group=='toko', function ($q){
            return $q->join('toko','toko.id','penjualan.id_toko');
        })
        ->when($id_mitra!='', function ($q) use ($id_mitra){
            return $q->where('penjualan.id_mitra',$id_mitra);
        })
        ->whereBetween('penjualan.delivered_at', $date_filter)
        ->when(count($id_barang)>0, function ($q) use ($id_barang){
            return $q->whereIn('barang.id',$id_barang);
        })
        ->when(count($id_depo)>0, function ($q) use ($id_depo){
            return $q->whereIn('tim.id_depo',$id_depo);
        })
        ->when(count($id_perusahaan)>0, function ($q) use ($id_perusahaan){
            return $q->whereIn('depo.id_perusahaan',$id_perusahaan);
        })
        ->select(
            DB::raw('
                '.$select_group.',
                SUM(
                    ((detail_penjualan.qty+(detail_penjualan.qty_pcs/barang.isi)) * detail_penjualan.harga_jual)/1.1
                ) as subtotal_penjualan,
                SUM(
                    ((detail_penjualan.qty+(detail_penjualan.qty_pcs/barang.isi)) * detail_penjualan.disc_rupiah )/1.1
                ) as discount_rupiah_penjualan,
                SUM(
                ((detail_penjualan.qty+(detail_penjualan.qty_pcs/barang.isi)) * detail_penjualan.harga_jual) / 1.1 * disc_persen / 100
                ) as discount_persen_penjualan,
                SUM(
                    ((detail_penjualan.qty+(detail_penjualan.qty_pcs/barang.isi)) * detail_penjualan.harga_dbp)/1.1
                ) as subtotal_pembelian,
                SUM(detail_penjualan.qty+(detail_penjualan.qty_pcs/barang.isi)) as total_qty_penjualan,
                SUM(detail_penjualan.qty_pcs+(detail_penjualan.qty*barang.isi)) as total_qty_pcs_penjualan,
                0 as subtotal_retur_penjualan,
                0 as subtotal_retur_stock,
                0 as total_qty_pembelian,
                0 as total_qty_pcs_pembelian
            ')
        )
        ->when($tipe_group=='barang', function ($q){
            return $q->groupBy('barang.id')->groupBy('penjualan.id_depo')->orderBy('barang.id');
        })
        ->when($tipe_group=='depo', function ($q){
            return $q->groupBy('tim.id_depo')->orderBy('tim.id_depo');
        })
        ->when($tipe_group=='perusahaan', function ($q){
            return $q->groupBy('depo.id_perusahaan')->orderBy('depo.id_perusahaan');
        })
        ->when($tipe_group=='tim', function ($q){
            return $q->groupBy('tim.id')->orderBy('tim.id');
        })
        ->when($tipe_group=='toko', function ($q){
            return $q->groupBy('penjualan.id_toko')->orderBy('penjualan.id_toko');
        });
        // ->get();


        $retur_penjualan   = DB::table('barang')
        ->join('detail_retur_penjualan','barang.id','=','detail_retur_penjualan.id_barang')
        ->join('retur_penjualan', 'retur_penjualan.id', 'detail_retur_penjualan.id_retur_penjualan')
        ->join('tim','tim.id','retur_penjualan.id_tim')
        ->join('depo','depo.id','tim.id_depo')
        ->join('perusahaan','depo.id_perusahaan','perusahaan.id')
        ->when(count($id_principal)>0, function ($q) use ($id_brand, $id_principal){
            return $q->join('segmen','segmen.id','barang.id_segmen')
                    ->join('brand','brand.id','segmen.id_brand')
                    ->whereIn('brand.id_principal',$id_principal)
                    ->when(count($id_brand)>0, function ($q) use($id_brand){
                        return $q->whereIn('segmen.id_brand',$id_brand);
                    });
        })
        ->when($tipe_group=='toko', function ($q){
            return $q->join('toko','toko.id','retur_penjualan.id_toko');
        })
        ->when($id_mitra!='', function ($q) use ($id_mitra){
            return $q->where('retur_penjualan.id_mitra',$id_mitra);
        })
        ->whereBetween('retur_penjualan.approved_at', $date_filter)
        ->when(count($id_barang)>0, function ($q) use ($id_barang){
            return $q->whereIn('barang.id',$id_barang);
        })
        ->when(count($id_depo)>0, function ($q) use ($id_depo){
            return $q->whereIn('tim.id_depo',$id_depo);
        })
        ->when(count($id_perusahaan)>0, function ($q) use ($id_perusahaan){
            return $q->whereIn('depo.id_perusahaan',$id_perusahaan);
        })
        ->where('retur_penjualan.status','approved')
        ->whereNull('retur_penjualan.deleted_at')
        ->select(
            DB::raw('
                '.$select_group.',
                0 as subtotal_penjualan,
                0 as discount_rupiah_penjualan,
                0 as discount_persen_penjualan,
                0 as subtotal_pembelian,
                0 as total_qty_penjualan,
                0 as total_qty_pcs_penjualan,
                SUM(
                    (
                        detail_retur_penjualan.qty_dus+(detail_retur_penjualan.qty_pcs/barang.isi)) *
                        (
                            (
                            detail_retur_penjualan.harga *
                                (
                                    1-((
                                        100*(detail_retur_penjualan.disc_persen+retur_penjualan.potongan) +
                                        disc_persen * retur_penjualan.potongan
                                    )/10000)
                                )
                            ) -
                            (
                            detail_retur_penjualan.disc_nominal *
                                (
                                    1-(
                                        retur_penjualan.potongan/100
                                    )
                                )
                            )
                    )
                ) as subtotal_retur_penjualan,
                SUM(
                    ((detail_retur_penjualan.qty_dus+(detail_retur_penjualan.qty_pcs/barang.isi)) * detail_retur_penjualan.harga_dbp)/1.1
                ) as subtotal_retur_stock,
                SUM(detail_retur_penjualan.qty_dus+(detail_retur_penjualan.qty_pcs/barang.isi)) as total_qty_pembelian,
                SUM(detail_retur_penjualan.qty_pcs+(detail_retur_penjualan.qty_dus*barang.isi)) as total_qty_pcs_pembelian
            ')
        )
        ->when($tipe_group=='barang', function ($q){
            return $q->groupBy('barang.id')->groupBy('retur_penjualan.id_depo')->orderBy('barang.id');
        })
        ->when($tipe_group=='depo', function ($q){
            return $q->groupBy('tim.id_depo')->orderBy('tim.id_depo');
        })
        ->when($tipe_group=='perusahaan', function ($q){
            return $q->groupBy('depo.id_perusahaan')->orderBy('depo.id_perusahaan');
        })
        ->when($tipe_group=='tim', function ($q){
            return $q->groupBy('tim.id')->orderBy('tim.id');
        })
        ->when($tipe_group=='toko', function ($q){
            return $q->groupBy('retur_penjualan.id_toko')->orderBy('retur_penjualan.id_toko');
        });

        $data_merge = $penjualan->unionAll($retur_penjualan);
        $data_sum = DB::table($data_merge)
        ->select(DB::raw(
                'id,
                nama_depo,
                nama,
                kode,
                SUM(subtotal_penjualan)         as subtotal_penjualan,
                SUM(discount_rupiah_penjualan)  as discount_rupiah_penjualan,
                SUM(discount_persen_penjualan)  as discount_persen_penjualan,
                SUM(subtotal_pembelian)         as subtotal_pembelian,
                SUM(total_qty_pembelian)        as total_qty_pembelian,
                SUM(total_qty_penjualan)        as total_qty_penjualan,
                SUM(total_qty_pcs_penjualan)    as total_qty_pcs_penjualan,
                SUM(total_qty_pcs_pembelian)    as total_qty_pcs_pembelian,
                SUM(subtotal_retur_penjualan)   as subtotal_retur_penjualan,
                SUM(subtotal_retur_stock)       as subtotal_retur_stock
                '))
        ->groupBy('id')
        ->when($tipe_group=='barang',function ($q){
            return $q->groupBy('id_depo');
        })
        ->get();
        return response()->json($data_sum);
        //->get();

        // dd(DB::getQueryLog());
        //  return response()->json([
        //     'penjualan'            => $penjualan,
        //     'retur_penjualan'      => $retur_penjualan
        // ]);
    }

    public function gross_profit_2(Request $request)
    {
        if (!$this->user->can('Menu Gross Profit')){
            return $this->Unauthorized();
        }
        $id_user        = $this->user->id;
        $id_mitra       = $request->has('id_mitra') ? $request->id_mitra : '';
        $tipe_group     = $request->has('tipe_group') ? count($request->tipe_group)>0 ? $request->tipe_group : [] : [];
        $start_date     = $request->start_date;
        $end_date       = $request->end_date;
        $id_perusahaan  = $request->has('id_perusahaan') && count($request->id_perusahaan)>0 ? $request->id_perusahaan
                        : Helper::perusahaanByUser($id_user);
        $id_depo        = $request->has('id_depo') ? is_array($request->id_depo) ? $request->id_depo : [$request->id_depo]
                        : Helper::depoIDByUser($id_user, $id_perusahaan) ;
        $id_principal   = $request->has('id_principal') && count($request->id_principal)>0 ? $request->id_principal : [] ;
        $id_brand       = $request->has('id_brand') ? is_array($request->id_brand) ? $request->id_brand : [$request->id_brand] : [] ;
        $id_barang      = $request->has('id_barang') ? is_array($request->id_barang) ? $request->id_barang : [$request->id_barang] : [] ;
        $date_filter    = [$start_date.' 00:00:00',$end_date. ' 23:59:59'];
        DB::enableQueryLog();

         $penjualan   = DB::table('penjualan')
        ->join('detail_penjualan', 'penjualan.id', 'detail_penjualan.id_penjualan')
        ->join('stock', 'detail_penjualan.id_stock', 'stock.id')
        ->where('penjualan.status','delivered')
        ->whereNull('penjualan.deleted_at')
        ->selectRaw(
            '
            detail_penjualan.id as id_check,
            id_barang,
            id_tim,
            id_toko,
            id_depo,
            id_mitra as id_mitra_penjualan,
            delivered_at as tanggal,

            0 as qty_retur_bs,
            0 as qty_pcs_retur_bs,

            0 as qty_retur_non_bs,
            0 as qty_pcs_retur_non_bs,

            detail_penjualan.qty as qty_penjualan,
            detail_penjualan.qty_pcs as qty_pcs_penjualan,
            harga_jual,
            harga_dbp as harga_pembelian_penjualan,
            disc_persen as disc_persen_penjualan,
            disc_rupiah as disc_rupiah_penjualan,

            0 as potongan,
            0 as qty_retur,
            0 as qty_pcs_retur,
            0 as harga_retur,
            0 as harga_pembelian_retur,
            0 as disc_persen_retur,
            0 as disc_rupiah_retur'
        );

        $retur_penjualan   = DB::table('retur_penjualan')
        ->join('detail_retur_penjualan', 'retur_penjualan.id', 'detail_retur_penjualan.id_retur_penjualan')
        ->whereNull('retur_penjualan.deleted_at')
        ->where('retur_penjualan.status','approved')
        //->where('tipe_barang','!=','bs')
        ->selectRaw(
            '
            detail_retur_penjualan.id as id_check,
            id_barang,
            id_tim,
            id_toko,
            id_depo,
            id_mitra as id_mitra_penjualan,
            approved_at as tanggal,

            CASE WHEN tipe_barang = "bs" THEN qty_dus
            ELSE 0
            END AS qty_retur_bs,

            CASE WHEN tipe_barang = "bs" THEN qty_pcs
            ELSE 0
            END AS qty_pcs_retur_bs,

            CASE WHEN tipe_barang = "bs" THEN 0
            ELSE qty_dus
            END AS qty_retur_non_bs,

            CASE WHEN tipe_barang = "bs" THEN 0
            ELSE qty_pcs
            END AS qty_pcs_retur_non_bs,

            0 as qty_penjualan,
            0 as qty_pcs_penjualan,
            0 as harga_jual,
            0 as harga_pembelian_penjualan,
            0 as disc_persen_penjualan,
            0 as disc_rupiah_penjualan,

            retur_penjualan.potongan as potongan,
            qty_dus as qty_retur,
            qty_pcs as qty_pcs_retur,
            harga as harga_retur,
            harga_dbp as harga_pembelian_retur,
            disc_persen as disc_persen_retur,
            disc_nominal as disc_rupiah_retur
            '
        );
        //$data_merge   = $retur_penjualan;
        $data_merge = $penjualan->unionAll($retur_penjualan);
        $data_sum = DB::table($data_merge)
        ->join('barang','barang.id','id_barang')
        ->join('segmen','segmen.id','barang.id_segmen')
        ->join('brand' ,'brand.id' ,'segmen.id_brand')
        ->join('principal', 'principal.id', 'brand.id_principal')

        ->join('tim','tim.id','id_tim')
        ->join('depo','depo.id','tim.id_depo')
        ->join('perusahaan','depo.id_perusahaan','perusahaan.id')
        ->join('toko','toko.id','id_toko')

        ->whereBetween('tanggal', $date_filter)
        ->when(count($id_perusahaan)>0, function ($q) use ($id_perusahaan){
            return $q->whereIn('depo.id_perusahaan',$id_perusahaan);
        })
        ->when(count($id_depo)>0, function ($q) use ($id_depo){
            return $q->whereIn('tim.id_depo',$id_depo);
        })
        ->when($id_mitra!='', function ($q) use ($id_mitra){
            return $q->where('id_mitra_penjualan',$id_mitra);
        })
        ->when(count($id_principal)>0, function ($q) use ($id_principal){
            return $q->whereIn('principal.id',$id_principal);
        })
        ->when(count($id_brand)>0, function ($q) use ($id_brand){
            return $q->whereIn('brand.id',$id_brand);
        })
        ->when(count($id_barang)>0, function ($q) use ($id_barang){
            return $q->whereIn('barang.id',$id_barang);
        })
        ->selectRaw(
            '
            id_barang,
            tim.id as id_tim,
            depo.id as id_depo,
            perusahaan.id as id_perusahaan,
            toko.id as id_toko,
            tanggal,

            nama_barang,
            kode_barang,
            nama_tim,
            nama_segmen,
            nama_brand,
            nama_principal,
            nama_depo,
            nama_toko,
            nama_perusahaan,
            kode_perusahaan,

            SUM(
                ((qty_penjualan+(qty_pcs_penjualan/barang.isi)) * harga_jual)/1.1
            ) as subtotal_penjualan,
            SUM(
                ((qty_penjualan+(qty_pcs_penjualan/barang.isi)) * disc_rupiah_penjualan)/1.1
            ) as disc_rupiah_penjualan,
            SUM(
                ((qty_penjualan+(qty_pcs_penjualan/barang.isi)) * harga_jual)/1.1 * disc_persen_penjualan/100
            ) as disc_persen_penjualan,
            SUM(
                ((qty_penjualan+(qty_pcs_penjualan/barang.isi)) * harga_pembelian_penjualan)/1.1
            ) as subtotal_pembelian,
            SUM(
                (
                    qty_retur+(qty_pcs_retur/barang.isi)) *
                    (
                        (
                        harga_retur *
                            (
                                1-((
                                    100*(disc_persen_retur+potongan) +
                                    disc_persen_retur * potongan
                                )/10000)
                            )
                        ) -
                        (
                        disc_rupiah_retur *
                            (
                                1-(
                                    potongan/100
                                )
                            )
                        )
                )
            ) as subtotal_retur_penjualan,
            SUM(
                ((qty_retur+(qty_pcs_retur/barang.isi)) * harga_pembelian_retur)/1.1
            ) as subtotal_retur_stock,
            SUM(
                ((qty_retur_bs+(qty_pcs_retur_bs/barang.isi)) * harga_pembelian_retur)/1.1
            ) as subtotal_retur_stock_bs,
            SUM(
                ((qty_retur_non_bs+(qty_pcs_retur_non_bs/barang.isi)) * harga_pembelian_retur)/1.1
            ) as subtotal_retur_stock_non_bs
            '
        );


        foreach ($tipe_group as $row) {
            $row = strtolower($row);
            $data_sum = $data_sum->when($row=='barang', function ($q){
                return $q->groupBy('barang.id')->orderBy('barang.id');
            })
            ->when($row=='segmen', function ($q){
                return $q->groupBy('segmen.id')->orderBy('segmen.id');
            })
            ->when($row=='brand', function ($q){
                return $q->groupBy('brand.id')->orderBy('brand.id');
            })
            ->when($row=='principal', function ($q){
                return $q->groupBy('principal.id')->orderBy('principal.id');
            })
            ->when($row=='depo', function ($q){
                return $q->groupBy('depo.id')->orderBy('depo.id');
            })
            ->when($row=='perusahaan', function ($q){
                return $q->groupBy('depo.id_perusahaan')->orderBy('depo.id_perusahaan');
            })
            ->when($row=='tim', function ($q){
                return $q->groupBy('tim.id')->orderBy('tim.id');
            })
            ->when($row=='toko', function ($q){
                return $q->groupBy('id_toko')->orderBy('id_toko');
            });
        }
        //$data_sum = $data_sum->groupBy('id_check')->orderBy('id_barang')->get();
        $data_sum = $data_sum->get();
        //dd(DB::getQueryLog());
        return count($data_sum)>0 ? $data_sum[0]->id_barang!=null ? response()->json($data_sum) : [] : [];

    }
}
