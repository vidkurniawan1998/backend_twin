<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;
use App\Models\User;
use App\Helpers\Helper;
use App\Helpers\Builder;
use App\Models\Toko;
use Carbon\Carbon;
use App\Models\Penjualan;
use App\Models\Barang;
use App\Models\Brand;
use App\Models\Stock;
use App\Models\LogStock;
use App\Models\HariEfektif;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class MonitoringController extends Controller
{
    protected $jwt;

    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
    }

    public function monitoring_pareto(Request $request)
    {
        if (!$this->user->can('Menu Monitoring Pareto')) {
            return $this->Unauthorized();
        }
        $id_user        = $this->user->id;
        $mid_date       = Carbon::createFromFormat('Y-m-d', $request->start_date);
        $start_date     = Carbon::createFromFormat('Y-m-d', $request->start_date)->subMonths();
        $end_date       = Carbon::createFromFormat('Y-m-d', $request->end_date);
        $date_filter    = [$start_date, $end_date];
        $id_barang      = $request->has('id_barang') && count($request->id_barang) > 0 ? $request->id_barang : [];
        $id_brand       = $request->has('id_brand') && count($request->id_brand) > 0 ? $request->id_brand : [];
        $id_perusahaan  = $request->has('id_perusahaan') ? is_array($request->id_perusahaan) ? $request->id_perusahaan : [$request->id_perusahaan] : Helper::perusahaanByUser($id_user);
        $id_depo        = $request->has('id_depo') ? is_array($request->id_depo) ? $request->id_depo : [$request->id_depo] : Helper::depoIDByUser($id_user, $id_perusahaan);
        $parameter      = '';

        if (count($id_brand) == 0 && count($id_barang) == 0) {
            return response()->json(['data' => [], 'total' => [], 'barang' => [], 'filter' => $start_date . ' ' . $mid_date . ' ' . $end_date]);
        }

        $parameter_group = count($id_brand) > 0 && count($id_barang) == 0 ? 'segmen.id_brand' : 'stock.id_barang';
        $id_group        = $parameter_group ==  'segmen.id_brand' ? $id_brand : $id_barang;

        foreach ($id_group as $key => $row) {
            $parameter = $parameter . 'CASE
                            WHEN ' . $parameter_group . ' = ' . $row . ' THEN
                                CASE
                                    WHEN delivered_at >= "' . $mid_date . '" THEN harga_jual
                                    ELSE 0
                                END
                            ELSE 0
                        END AS harga_jual_after_' . $row . ',' .
                'CASE
                            WHEN ' . $parameter_group . ' = ' . $row . ' THEN
                                CASE
                                    WHEN delivered_at < "' . $mid_date . '" THEN harga_jual
                                    ELSE 0
                                END
                            ELSE 0
                        END AS harga_jual_before_' . $row;
            $parameter = count($id_group) == ($key + 1) ? $parameter : $parameter . ',';
        }

        //DB::enableQueryLog();
        $builder = new Builder();
        $merged  = Penjualan::join('detail_penjualan', 'detail_penjualan.id_penjualan', 'penjualan.id')
            ->join('stock', 'detail_penjualan.id_stock', 'stock.id')
            ->when(count($id_barang) == 0 && count($id_brand) > 0, function ($q) {
                return $q->join('barang', 'stock.id_barang', 'barang.id')->join('segmen', 'barang.id_segmen', 'segmen.id');
            })
            ->select(
                'detail_penjualan.qty',
                'detail_penjualan.qty_pcs',
                'penjualan.id',
                'penjualan.id_toko',
                'penjualan.id_salesman',
                'penjualan.id_tim',
                'stock.id_barang'
            )
            ->where('penjualan.status', 'delivered')
            ->whereIn('penjualan.id_depo', $id_depo)
            ->whereBetween('delivered_at', $date_filter)
            ->whereIn($parameter_group, $id_group)
            ->selectRaw($parameter);

        $penjualan = DB::table($merged)
            ->join('toko', 'id_toko', 'toko.id')
            ->join('tim', 'id_tim', 'tim.id')
            ->join('users', 'id_salesman', 'users.id')
            ->join('barang', 'id_barang', 'barang.id')
            ->selectRaw(
                '
                        id_salesman,
                        id_toko,
                        toko.no_acc as kode_toko,
                        nama_toko,
                        nama_tim,
                        name as nama_salesman
                        '
            );
        foreach ($id_group as $row) {
            $penjualan = $penjualan->selectRaw(
                $builder->request([
                    'parameter' => ['harga_jual' => 'harga_jual_before_' . $row],
                    'select'    => 'sum',
                    'using'     => 'subtotal',
                    'as'        => 'total_before_' . $row,
                    'continue'  => true
                ])
                    .
                    $builder->request([
                        'parameter' => ['harga_jual' => 'harga_jual_after_' . $row],
                        'select'    => 'sum',
                        'using'     => 'subtotal',
                        'as'        => 'total_after_' . $row
                    ])
            );
        }
        $penjualan = $penjualan->groupBy('id_toko')
            ->groupBy('id_salesman')
            ->orderBy('id_salesman');
        foreach ($id_group as $row) {
            $penjualan = $penjualan->orderBy('total_before_' . $row, 'DESC');
        }
        $penjualan = $penjualan->orderBy('id_toko')
            ->get();

        $total = $penjualan->groupBy('id_salesman')
            ->map(function ($item) use ($id_group) {
                $res = [];
                foreach ($id_group as $row) {
                    $res['total_before_' . $row] = $item->sum('total_before_' . $row);
                    $res['total_after_' . $row]  = $item->sum('total_after_' . $row);
                }
                return $res;
            });

        if ($parameter_group == 'segmen.id_brand') {
            $barang = Brand::whereIn('id', $id_group)->selectRaw('nama_brand as kode_barang, id');
        } else {
            $barang = Barang::whereIn('id', $id_group)->select('nama_barang', 'kode_barang', 'id');
        }
        $barang = $barang->orderByRaw('FIELD(id,' . implode(',', $id_group) . ')')->get();
        //dd(DB::getQueryLog());
        return response()->json(['data' => $penjualan, 'total' => $total, 'barang' => $barang, 'filter' => $start_date . ' ' . $mid_date . ' ' . $end_date, 'request' => $request->all()]);
    }

    public function monitoring_ota(Request $request)
    {
        if (!$this->user->can('Menu Monitoring Ota')) {
            return $this->Unauthorized();
        }
        //DB::enableQueryLog();
        $id_user        = $this->user->id;
        $id_perusahaan  = $request->has('id_perusahaan') ? is_array($request->id_perusahaan) ? $request->id_perusahaan : [$request->id_perusahaan] : Helper::perusahaanByUser($id_user);
        $id_depo        = $request->has('id_depo') ? is_array($request->id_depo) ? $request->id_depo : [$request->id_depo] : Helper::depoIDByUser($id_user, $id_perusahaan);
        $id_salesman    = $request->has('id_salesman') ? $request->id_salesman : [];
        $id_salesman    = is_array($id_salesman) ? $id_salesman : [$id_salesman];
        $builder   = new Builder();
        $penjualan = Penjualan::join('toko', 'toko.id', 'penjualan.id_toko')
            ->whereNull('toko.deleted_at')
            ->where('penjualan.tanggal', '>=', '2020-08-18')
            ->whereIn('penjualan.id_depo', $id_depo)
            ->whereIn('penjualan.id_perusahaan', $id_perusahaan)
            ->when(count($id_salesman) > 0, function ($q) use ($id_salesman) {
                return $q->whereIn('penjualan.id_salesman', $id_salesman);
            })
            ->selectRaw('id_toko, MAX(penjualan.tanggal) as tanggal_max, nama_toko')
            ->groupBy('id_toko');
        $data = DB::table('penjualan')->joinSub($penjualan, 'data', function ($join) {
            $join->on('data.id_toko', 'penjualan.id_toko')->on('data.tanggal_max', 'penjualan.tanggal');
        })
            //->join('tim','penjualan.id_tim','tim.id')
            ->join('ketentuan_toko', 'data.id_toko', 'ketentuan_toko.id_toko')
            ->join('tim', 'ketentuan_toko.id_tim', 'tim.id')
            ->join('salesman', 'salesman.id_tim', 'tim.id')
            ->join('users as user_koordinator', 'tim.id_sales_koordinator', 'user_koordinator.id')
            ->join('users as user_salesman', 'salesman.user_id', 'user_salesman.id')
            ->join('detail_penjualan', 'penjualan.id', 'detail_penjualan.id_penjualan')
            ->join('stock', 'stock.id', 'detail_penjualan.id_stock')
            ->join('barang', 'barang.id', 'stock.id_barang')
            ->selectRaw('COUNT(penjualan.id) as banyak_transaksi_terakhir, data.tanggal_max,  penjualan.id_toko, DATEDIFF(now(), data.tanggal_max) as hari_non_aktif, nama_toko, nama_tim, user_salesman.name as nama_salesman, user_koordinator.name as nama_koordinator, ' .
                $builder->request([
                    'parameter' => ['qty' => 'detail_penjualan.qty', 'qty_pcs' => 'detail_penjualan.qty_pcs'],
                    'select'    => 'sum',
                    'using'     => 'subtotal',
                    'as'        => 'total_stt',
                ]))
            // ->whereIn('salesman.user_id',$id_salesman)
            ->whereNull('tim.deleted_at')
            ->whereRaw('DATEDIFF(now(), data.tanggal_max) > 30')
            ->groupBy('penjualan.id_toko')
            ->orderBy('data.tanggal_max', 'ASC')
            ->get();
        //dd(DB::getQueryLog());
        return response()->json($data);
    }

    public function monitoring_pro(Request $request)
    {
        $start_date = $request->has('start_date') ? $request->start_date : Carbon::now()->toDateString();
        $end_date = $request->has('end_date') ? $request->end_date : Carbon::now()->toDateString();
        $id_barang = $request->has('id_barang') ? $request->id_barang : [];
        $id_barang = is_array($id_barang) ? $id_barang : [$id_barang];
        $date_filter = [$start_date, $end_date];

        //DB::enableQueryLog();
        $penjualan = Penjualan::join('detail_penjualan', 'penjualan.id', 'detail_penjualan.id_penjualan')
            ->join('stock', 'stock.id', 'detail_penjualan.id_stock')
            ->join('barang', 'barang.id', 'stock.id_barang')
            ->join('users', 'users.id', 'penjualan.id_salesman')
            ->join('tim', 'penjualan.id_tim', 'tim.id')
            ->join('users as user_koordinator', 'tim.id_sales_koordinator', 'user_koordinator.id')
            ->selectRaw('COUNT(DISTINCT penjualan.id) as banyak_penjualan, id_toko, id_barang, id_sales_koordinator, id_salesman, nama_barang, users.name as nama_salesman, nama_tim, user_koordinator.name as nama_koordinator')
            ->where('penjualan.status', 'delivered')
            ->whereBetween('delivered_at', $date_filter)
            ->where('stock.id_barang', $id_barang)
            ->groupBy('id_toko')
            ->groupBy('id_salesman');
        $toko      = Toko::join('ketentuan_toko', 'ketentuan_toko.id_toko', 'toko.id')
            ->join('tim', 'ketentuan_toko.id_tim', 'tim.id')
            ->join('salesman', 'tim.id', 'salesman.id_tim')
            ->selectRaw('COUNT(toko.id) as total_toko, salesman.user_id as id_salesman')
            ->groupBy('salesman.user_id');
        $sub       = DB::table($penjualan)
            ->selectRaw('COUNT(id_toko) as banyak_toko, banyak_penjualan, id_salesman, id_barang, nama_salesman, nama_barang, nama_tim, nama_koordinator, id_sales_koordinator')
            ->groupBy('banyak_penjualan')
            ->groupBy('id_salesman')
            ->orderBy('id_sales_koordinator')
            ->orderBy('id_salesman');
        $data      = DB::table($sub, 'sub')
            ->joinSub($toko, 'data', function ($join) {
                $join->on('sub.id_salesman', 'data.id_salesman');
            })->get();
        $res         = [];
        $id_salesman = 0;
        $jenis_hari  = [];
        $sub         = [];
        $total       = 0;
        foreach ($data as $key => $row) {
            if (!in_array($row->banyak_penjualan, $jenis_hari)) {
                $jenis_hari[] = $row->banyak_penjualan;
            }
            //echo $row->id_salesman.' '.$row->banyak_toko.' '.$row->banyak_penjualan;
            if ($id_salesman != $row->id_salesman) {
                if ($key > 0) {
                    $sub['total']  = $total;
                    $res[]         = $sub;
                    $sub           = [];
                    $total         = 0;
                }
                $id_salesman             = $row->id_salesman;
                $sub['id_salesman']      = $id_salesman;
                $sub['id_koordinator']   = $row->id_sales_koordinator;
                $sub['nama_salesman']    = $row->nama_salesman;
                $sub['nama_koordinator'] = $row->nama_koordinator;
                $sub['nama_tim']         = $row->nama_tim;
                $sub['nama_barang']      = $row->nama_barang;
                $sub['id_barang']        = $row->id_barang;
                $sub['total_toko']       = $row->total_toko;
            }
            $sub['penjualan_' . $row->banyak_penjualan] = $row->banyak_toko;
            $total += $row->banyak_toko;
            if (($key + 1) == count($data)) {
                $sub['total']  = $total;
                $res[] = $sub;
            }
        }
        sort($jenis_hari);
        //dd(DB::getQueryLog());
        return response()->json(['data' => $res, 'jenis_hari' => $jenis_hari]);
    }

    public function monitoring_pro_detail(Request $request)
    {
        $this->validate($request, [
            'id_salesman' => 'required',
            'id_barang'   => 'required',
            'jenis_hari'  => 'required'
        ]);

        $start_date  = $request->has('start_date') ? $request->start_date : Carbon::now()->toDateString();
        $end_date    = $request->has('end_date') ? $request->end_date : Carbon::now()->toDateString();
        $date_filter = [$start_date, $end_date];
        $id_salesman = $request->has('id_salesman') ? $request->id_salesman : 0;
        $id_barang   = $request->has('id_barang') ? $request->id_barang : 0;
        $jenis_hari  = $request->has('jenis_hari') ? $request->jenis_hari : 0;

        $builder = new Builder();
        $penjualan = Penjualan::join('detail_penjualan', 'detail_penjualan.id_penjualan', 'penjualan.id')
            ->join('stock', 'stock.id', 'detail_penjualan.id_stock')
            ->join('barang', 'barang.id', 'stock.id_barang')
            ->selectRaw(
                'COUNT(DISTINCT penjualan.id) as total_penjualan, id_toko, ' .
                    $builder->request([
                        'parameter' => ['qty' => 'detail_penjualan.qty', 'qty_pcs' => 'detail_penjualan.qty_pcs'],
                        'select'    => 'sum',
                        'using'     => 'grand_total',
                        'as'        => 'total_stt',
                        'continue'  => true,
                    ]) .
                    $builder->request([
                        'parameter' => ['qty' => 'detail_penjualan.qty', 'qty_pcs' => 'detail_penjualan.qty_pcs'],
                        'select'    => 'sum',
                        'using'     => 'qty_pcs',
                        'as'        => 'total_qty',
                    ])
            )
            ->whereBetween('penjualan.delivered_at', $date_filter)
            ->where('penjualan.id_salesman', $id_salesman)
            ->where('stock.id_barang', $id_barang)
            ->groupBy('id_toko');
        $data = DB::table($penjualan, 'data')
            ->join('toko', 'toko.id', 'data.id_toko')
            ->select('no_acc', 'cust_no', 'nama_toko', 'tipe', 'alamat', 'total_stt', 'total_qty')
            ->where('total_penjualan', $jenis_hari)->get();
        return response()->json($data);
    }

    public function monitoring_stock_tmp(Request $request)
    {
        if (!$this->user->can('Menu Monitoring Stock Tmp')) {
            return $this->Unauthorized();
        }
        DB::enableQueryLog();
        $builder   = new Builder();
        $end_date  = Carbon::now();
        $id_barang = [];
        $id_gudang = $request->has('id_gudang') ? $request->id_gudang : [];
        $data      = LogStock::where('tanggal', '<=', $end_date)
            ->when(count($id_barang) > 0, function ($q) use ($id_barang) {
                return $q->whereIn('id_barang', $id_barang);
            })
            ->when(count($id_gudang) > 0, function ($q) use ($id_gudang) {
                return $q->whereIn('id_gudang', $id_gudang);
            })
            ->where(function ($q) {
                return $q->orWhere(function ($q) {
                    return $q->where('referensi', 'stock awal');
                })
                    ->orWhere(function ($q) {
                        return $q->where('referensi', 'penerimaan barang');
                    })
                    ->orWhere(function ($q) {
                        return $q->where('referensi', 'mutasi masuk')->where('log_stock.status', 'received');
                    })
                    ->orWhere(function ($q) {
                        return $q->where('referensi', 'penjualan')->where('log_stock.status', 'delivered');
                    })
                    ->orWhere(function ($q) {
                        return $q->where('referensi', 'mutasi keluar')->where('log_stock.status', 'approved');
                    })
                    ->orWhere(function ($q) {
                        return $q->where('referensi', 'adjustment');
                    })
                    ->orWhere(function ($q) {
                        return $q->where('referensi', 'retur');
                    });
            })
            ->where('tanggal', '>=', '2020-08-18')
            ->select(
                DB::raw('id_barang, id_gudang, (qty_pcs) as stock, parameter')
            );

        $penjualan = Penjualan::join('detail_penjualan', 'penjualan.id', 'detail_penjualan.id_penjualan')
            ->join('stock', 'stock.id', 'detail_penjualan.id_stock')
            ->join('barang', 'barang.id', 'stock.id_barang')
            ->whereIn('penjualan.status', ['approved', 'loaded'])
            ->where('penjualan.tanggal', '>=', '2020-08-18')
            ->when(count($id_gudang) > 0, function ($q) use ($id_gudang) {
                return $q->whereIn('stock.id_gudang', $id_gudang);
            })
            ->selectRaw('stock.id_barang,stock.id_gudang, (' . $builder->qty_pcs(['qty' => 'detail_penjualan.qty', 'qty_pcs' => 'detail_penjualan.qty_pcs']) . ') as stock, -1 as parameter');
        $merged = $data->unionAll($penjualan);
        $all_data = DB::table($merged)->select(
            DB::raw('id_barang, id_gudang, SUM(stock*parameter) as stock_akhir')
        )
            ->groupBy('id_barang')
            ->groupBy('id_gudang');

        $stock  = DB::table('stock')->joinSub($all_data, 'data', function ($join) {
            $join->on('stock.id_barang', 'data.id_barang')->on('stock.id_gudang', 'data.id_gudang');
        })
            ->join('barang', 'stock.id_barang', 'barang.id')
            ->join('gudang', 'stock.id_gudang', 'gudang.id')
            ->selectRaw('
            stock.id,
            stock.id_gudang,
            stock.id_barang,
            nama_barang,
            kode_barang,
            isi,
            nama_gudang,
            kode_gudang,
            keterangan as alamat_gudang,
            gudang.jenis as jenis_gudang,
            (stock.qty+(stock.qty_pcs/barang.isi)) as stock_tampungan,
            (data.stock_akhir/barang.isi) as stock_hitung
            ')
            ->whereRaw('(stock.qty+(stock.qty_pcs / barang.isi)) != (data.stock_akhir / barang.isi)')
            ->whereNull('barang.deleted_at')
            ->whereNull('stock.deleted_at')
            ->orderBy('id_gudang')
            ->get();
        //dd(DB::getQueryLog());
        return response()->json(['counted' => count($stock), 'data' => $stock]);
    }

    public function quick_search_table(Request $request)
    {
        if (!$this->user->can('Menu Quick Search')) {
            return $this->Unauthorized();
        }
        $tables      = DB::select('SHOW TABLES');
        $DB_name     = DB::connection()->getDatabaseName();
        $DB_src      = 'Tables_in_' . $DB_name;
        $data        = [];
        foreach ($tables as $table) {
            $data[]  = $table->{$DB_src};
        }
        return response()->json($data);
    }

    public function quick_search_column(Request $request)
    {
        if (!$this->user->can('Menu Quick Search')) {
            return $this->Unauthorized();
        }
        $tables       = $request->has('tables') ? $request->tables : [];
        $tables       = is_array($tables) ? $tables : [$tables];
        $data         = [];
        foreach ($tables as $table) {
            $columns     = Schema::getColumnListing($table);
            $data        = array_merge($data, $columns);
        }
        return response()->json($data);
    }

    public function quick_search_row(Request $request)
    {
        if (!$this->user->can('Menu Quick Search')) {
            return $this->Unauthorized();
        }
        $tables       = $request->has('tables') ? $request->tables : [];
        $tables       = is_array($tables) ? $tables : [$tables];
        $columns      = $request->has('columns') ? $request->columns : [];
        $columns      = is_array($columns) ? $columns : [$columns];
        $values       = $request->has('values') ? $request->values : [];
        $tipes        = $request->has('tipes') ? $request->tipes : [];
        $cog          = $request->has('cogs') ? $request->cogs : [];
        $selects      = [];
        foreach ($tables as $table) {
            $data = DB::table($table);
            foreach ($columns as $key => $column) {
                $value = explode(',', $values[$key]);
                switch ($tipes[$key]) {
                    case '=':
                        $data = $cog[$key] == 'AND' ?
                            $data->where(function ($q) use ($column, $value) {
                                return $q->whereIn($column, $value);
                            }) :
                            $data->orWhere(function ($q) use ($column, $value) {
                                return $q->whereIn($column, $value);
                            });
                        break;
                    case '!=':
                        $data = $cog[$key] == 'AND' ?
                            $data->where(function ($q) use ($column, $value) {
                                return $q->whereNotIn($column, $value);
                            }) :
                            $data->orWhere(function ($q) use ($column, $value) {
                                return $q->whereNotIn($column, $value);
                            });
                        break;
                    case 'between':
                        $data = $cog[$key] == 'AND' ?
                            $data->where(function ($q) use ($column, $value) {
                                return $q->whereBetween($column, $value);
                            }) :
                            $data->orWhere(function ($q) use ($column, $value) {
                                return $q->whereBetween($column, $value);
                            });
                        break;
                    case 'not between':
                        $data = $cog[$key] == 'AND' ?
                            $data->where(function ($q) use ($column, $value) {
                                return $q->whereNotBetween($column, $value);
                            }) :
                            $data->orWhere(function ($q) use ($column, $value) {
                                return $q->whereNotBetween($column, $value);
                            });
                        break;
                    default:
                        $data->whereIn($column, $value);
                        break;
                }
            }
            $selects[] = $data;
        }
        $merged       = $selects[0];
        for ($i = 1; $i < count($selects); $i++) {
            $merged = $merged->unionAll($selects[$i]);
        }
        $data = $merged->get();
        return response()->json($data);
    }
    public function monitoring_query(Request $request)
    {
        $builder   = new Builder();
        $grand_total =  $builder->qty(['qty' => 'detail_penjualan.qty', 'qty_pcs' => 'detail_penjualan.qty_pcs']);
        echo $grand_total;
    }
}
