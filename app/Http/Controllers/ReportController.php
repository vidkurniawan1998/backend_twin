<?php

namespace App\Http\Controllers;

use App\Http\Resources\ReportReturPenjualan as ReportReturResource;
use App\Http\Resources\PosisiStock as PosisiStockResource;
use App\Http\Resources\SalesmanReport as SalesmanReportResource;
use App\Http\Resources\SalesToTrade as SalesToTradeResource;
use App\Http\Resources\SalesToDistributor as SalesToDistributorResource;
use App\Http\Resources\WeeklyReport as WeeklyReportResource;
use App\Helpers\Helper;
use App\Models\Barang;
use App\Models\Mitra;
use App\Models\Perusahaan;
use App\Models\DetailPenjualan;
use App\Models\Depo;
use App\Models\DetailReturPenjualan;
use App\Models\DetailPelunasanPenjualan;
use App\Models\DetailPenerimaanBarang;
use App\Models\Gudang;
use App\Models\Penjualan;
use App\Models\PenerimaanBarang;
use App\Models\PosisiStock;
use App\Models\Promo;
use App\Models\ReturPenjualan;
use App\Models\Salesman;
use App\Models\TargetSalesman;
use App\Models\Driver;
use App\Models\ViewDetailPenjualan;
use App\Models\HargaBarang;
use App\Models\Toko;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tymon\JWTAuth\JWTAuth;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use \PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use App\Traits\ExcelStyle;
use App\Models\Brand;
use App\Models\LogStock;
use App\Models\Reference;

class ReportController extends Controller
{
    use ExcelStyle;
    protected $jwt;
    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
    }

    public function all(Request $request, $id_salesman = 'all') //parameter : start_date, end_date, week, id_salesman, id_tim, id_depo, id_barang, id_brand
    {
        if ($this->user->role != 'admin' && $this->user->role != 'pimpinan' && $this->user->role != 'accounting') {
            return response()->json([
                'message' => 'Anda tidak berhak untuk mengakses fungsi ini!'
            ], 400);
        }

        $list_salesman = Salesman::orderBy('id_tim', 'asc')->get();
        return SalesmanReportResource::collection($list_salesman);
    }

    // http://localhost:8000/report/weekly?id_salesman=46&year=2019&week_number=12
    public function weekly(Request $request) //parameter request: id_salesman, year, week_number
    {
        if ($this->user->can('Menu Laporan Weekly')) :

            if (!$request->id_salesman || !$request->year || !$request->week_number) {
                return response()->json([
                    'message' => 'Kolom Tahun dan Minggu harus diisi!'
                ], 404);
            }
            $date = new Carbon();
            $date->setISODate($request->year, $request->week_number);
            $start_date = $date->startOfWeek()->toDateString();
            $end_date = $date->endOfWeek()->toDateString();
            return new WeeklyReportResource($request->id_salesman, $start_date, $end_date);
        else :
            return $this->Unauthorized();
        endif;
    }

    public function stt(Request $request)
    {
        if ($this->user->can('Menu Laporan STT')) :
            $start_date = $request->start_date;
            $end_date   = $request->end_date;
            $id_depo = $request->depo != null ? $request->depo : Helper::depoIDByUser($this->user->id);
            $id_penjualan = Penjualan::whereIn('id_depo', $id_depo)->oldest();

            if ($request->status == 'all' || $request->status == '' || $request->status == null) {
                $id_penjualan = $id_penjualan->whereNotIn('status', ['waiting', 'canceled']);
            } else {
                $id_penjualan = $id_penjualan->where('status', $request->status);
            }

            if ($this->user->hasRole('Sales Supervisor')) {
                $id_salesman = Salesman::whereHas('tim', function ($q) {
                    $q->where('id_sales_supervisor', $this->user->id);
                })->pluck('user_id');
                $id_penjualan = $id_penjualan->whereIn('id_salesman', $id_salesman)->latest();
            }

            if ($this->user->hasRole('Sales Koordinator')) {
                $id_salesman = Salesman::whereHas('tim', function ($q) {
                    $q->where('id_sales_koordinator', $this->user->id);
                })->pluck('user_id');
                $id_penjualan = $id_penjualan->whereIn('id_salesman', $id_salesman)->latest();
            }

            if ($request->id_salesman == 'all' || $request->id_salesman == '' || $request->id_salesman == null) {
                if ($request->status == 'delivered') {
                    $id_penjualan = $id_penjualan->whereBetween('delivered_at', [$start_date . " 00:00:00", $end_date . " 23:59:59"])->pluck('id');
                } else {
                    $id_penjualan = $id_penjualan->whereBetween('tanggal', [$request->start_date, $request->end_date])->pluck('id');
                }
            } else {
                if ($request->status == 'delivered') {
                    $id_penjualan = $id_penjualan->where('id_salesman', $request->id_salesman)->whereBetween('delivered_at', [$start_date . " 00:00:00", $end_date . " 23:59:59"])->pluck('id');
                } else {
                    $id_penjualan = $id_penjualan->where('id_salesman', $request->id_salesman)->whereBetween('tanggal', [$request->start_date, $request->end_date])->pluck('id');
                }
            }

            if ($id_penjualan->count() == 0) {
                return [];
            }

            $detail_penjualan = DetailPenjualan::whereIn('id_penjualan', $id_penjualan)->whereRaw("(qty + qty_pcs) > 0")->get();
            return response()->json([
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'data' => SalesToTradeResource::collection($detail_penjualan)
            ], 200);
        else :
            return $this->Unauthorized();
        endif;
    }

    public function stt2(Request $request)
    {
        if ($this->user->can('Menu Laporan STT')) :
            $id_user        = $this->user->id;
            $start_date     = $request->start_date;
            $end_date       = $request->end_date;
            $id_perusahaan  = $request->id_perusahaan == '' ? Helper::perusahaanByUser($id_user) : [$request->id_perusahaan];
            $id_depo        = $request->has('depo') && count($request->depo) > 0 ? $request->depo : Helper::depoIDByUser($id_user, $id_perusahaan);
            $id_mitra       = $request->has('id_mitra') ? $request->id_mitra:'exclude';
            $nomor_pajak    = $request->has('nomor_pajak') ? $request->nomor_pajak:'all';
            $isWaitingShowed = $this->user->can('Laporan STT All');
            $logData = [
                'action' => 'Download Laporan STT',
                'description' => 'Tanggal ' . $start_date . ' sampai ' . $end_date,
                'user_id' => $this->user->id
            ];

            $this->log($logData);

            $stt = DB::table('penjualan')
                ->join('detail_penjualan', 'penjualan.id', 'detail_penjualan.id_penjualan')
                ->join('stock', 'detail_penjualan.id_stock', 'stock.id')
                ->join('gudang', 'stock.id_gudang', 'gudang.id')
                ->join('barang', 'stock.id_barang', 'barang.id')
                ->join('segmen', 'barang.id_segmen', 'segmen.id')
                ->join('brand', 'segmen.id_brand', 'brand.id')
                ->join('principal', 'brand.id_principal', 'principal.id')
                ->join('promo', 'detail_penjualan.id_promo', 'promo.id')
                ->join('toko', 'penjualan.id_toko', 'toko.id')
                ->leftJoin('kelurahan', 'toko.id_kelurahan', 'kelurahan.id')
                ->leftJoin('kecamatan', 'kelurahan.id_kecamatan', 'kecamatan.id')
                ->leftJoin('kabupaten', 'kecamatan.id_kabupaten', 'kabupaten.id')
                ->join('ketentuan_toko', 'ketentuan_toko.id_toko', 'toko.id')
                ->join('salesman', 'penjualan.id_salesman', 'salesman.user_id')
                ->join('tim', 'penjualan.id_tim', 'tim.id')
                ->join('users', 'salesman.user_id', 'users.id')
                ->join('depo', 'penjualan.id_depo', 'depo.id')
                ->whereIn('penjualan.id_depo', $id_depo)
                ->when(!$isWaitingShowed, function ($q){
                    return $q->where('penjualan.status','!=','waiting');
                });

            if (count($id_perusahaan) > 0) {
                $stt = $stt->whereIn('penjualan.id_perusahaan', $id_perusahaan);
            }

            if ($nomor_pajak == 'exclude') {
                $stt = $stt->where(function($q) {
                    $q->whereNull('penjualan.no_pajak')
                        ->orWhere('penjualan.no_pajak', '=', '');
                });
            }

            if ($nomor_pajak == 'include') {
                $stt = $stt->where(function($q) {
                   $q->whereNotNull('penjualan.no_pajak')
                        ->where('penjualan.no_pajak', '!=', '');
                });
            }

            $status = $request->status;
            $status = $status == 'cancel' ? 'closed' : $status;

            if ($status == 'all' || $status == '' || $status == null) {
                // $stt = $stt->whereNotIn('penjualan.status', ['waiting', 'canceled']);
            } else {
                $stt = $stt->where('penjualan.status', $status);
            }

            if ($this->user->hasRole('Sales Supervisor')) {
                $stt = $stt->where('tim.id_sales_supervisor', $this->user->id);
            }

            if ($this->user->hasRole('Sales Koordinator')) {
                $stt = $stt->where('tim.id_sales_koordinator', $this->user->id);
            }

            $id_salesman    = $request->id_salesman;
            if ($id_salesman == 'all' || $id_salesman == '' || $id_salesman == null) {
                if ($status == 'delivered') {
                    $stt = $stt->whereBetween('penjualan.delivered_at', [$start_date . " 00:00:00", $end_date . " 23:59:59"]);
                } elseif ($status == 'all') {
                    $stt = $stt->whereBetween('penjualan.tanggal', [$start_date, $end_date]);
                } else {
                    $stt = $stt->where('penjualan.status', $status)->whereBetween('penjualan.tanggal', [$start_date, $end_date]);
                }
            } else {
                if ($status == 'delivered') {
                    $stt = $stt->where('penjualan.id_salesman', $id_salesman)->whereBetween('penjualan.delivered_at', [$start_date . " 00:00:00", $end_date . " 23:59:59"]);
                } elseif ($status == 'all') {
                    $stt = $stt->where('penjualan.id_salesman', $id_salesman)->whereBetween('penjualan.tanggal', [$start_date, $end_date]);
                } else {
                    $stt = $stt->where('penjualan.id_salesman', $id_salesman)->where('penjualan.status', $status)->whereBetween('penjualan.tanggal', [$start_date, $end_date]);
                }
            }

            //filter principal
            if (!empty($request->id_principal)) {
                $stt = $stt->whereIn('principal.id', $request->id_principal);
            } else {
                $id_principal = Helper::principalByUser($this->user->id);
                if (count($id_principal) > 0) {
                    $stt = $stt->whereIn('principal.id', $id_principal);
                }
            }

            //filter mitra
            if (is_numeric($id_mitra)) {
                $stt = $stt->where('penjualan.id_mitra', '=', $id_mitra);
            } else {
                $stt = $stt->where('penjualan.id_mitra', '=', 0);
            }

            $stt = $stt->whereNull('penjualan.deleted_at');

            $stt = $stt->select(
                'gudang.nama_gudang',
                'depo.nama_depo',
                'toko.no_acc',
                'toko.nama_toko',
                'toko.alamat',
                'kabupaten.nama_kabupaten',
                'kecamatan.nama_kecamatan',
                'kelurahan.nama_kelurahan',
                'toko.kode_pos',
                'ketentuan_toko.npwp',
                'ketentuan_toko.nama_pkp',
                'ketentuan_toko.alamat_pkp',
                'toko.tipe',
                'barang.kode_barang',
                'barang.pcs_code',
                'barang.item_code',
                'barang.nama_barang',
                'brand.nama_brand',
                'segmen.nama_segmen',
                'stock.id_barang',
                'barang.isi',
                'barang.berat',
                'barang.satuan',
                'users.name',
                'tim.nama_tim',
                'detail_penjualan.qty',
                'detail_penjualan.qty_pcs',
                'detail_penjualan.order_qty',
                'detail_penjualan.order_pcs',
                'detail_penjualan.id_promo',
                'detail_penjualan.harga_dbp',
                'detail_penjualan.harga_jual',
                'promo.nama_promo',
                'detail_penjualan.disc_persen',
                'detail_penjualan.disc_rupiah',
                'penjualan.tipe_harga',
                'penjualan.tanggal',
                'penjualan.no_invoice',
                'penjualan.id',
                'penjualan.po_manual',
                'penjualan.no_pajak',
                'penjualan.status',
                'penjualan.created_at',
                'penjualan.approved_at',
                'penjualan.delivered_at',
                'penjualan.tipe_pembayaran',
                'penjualan.pending_status',
                'principal.nama_principal',
                'ketentuan_toko.no_ktp',
                'ketentuan_toko.nama_ktp',
                'ketentuan_toko.alamat_ktp',
                'salesman.kode_eksklusif',
                'penjualan.remark_close'
            )->orderBy('penjualan.id')->get();
            $data = [];
            foreach ($stt as $st) {
                if (!($st->qty == 0 && $st->qty_pcs == 0)) {
                    $sum_carton = $st->qty + ($st->qty_pcs / $st->isi);
                    $price_before_tax = $st->harga_jual / 1.1;
                    $subtotal = $price_before_tax * $sum_carton;

                    $discount = 0;
                    if ($st->id_promo) {
                        $disc_rupiah = ($st->disc_rupiah / 1.1) * $sum_carton;
                        $disc_persen = ($st->disc_persen / 100) * $subtotal;
                        $discount = $disc_rupiah + $disc_persen;
                    }

                    $ppn = ($subtotal - $discount) / 10;
                    $dpp = $subtotal - $discount;
                    $total = $subtotal - $discount + $ppn;

                    $dbp = $st->harga_dbp / 1.1;
                    $hpp = $dbp * $sum_carton;

                    $tanggal = $st->delivered_at;
                    if ($tanggal == null || $tanggal == '') {
                        $tanggal = $st->tanggal;
                    }

                    $week = \Carbon\Carbon::parse($tanggal)->weekOfYear;
                    $delivered_at   = $st->delivered_at ? Carbon::parse($st->delivered_at)->toDateString() : $st->tanggal;

                    $no_ktp         = trim($st->no_ktp) == '' || $st->no_ktp == null ? '5171033101720008' : $st->no_ktp;
                    $nama_ktp       = trim($st->nama_ktp) == '' || $st->nama_ktp == null ? 'WIDARTO SELAMAT' : $st->nama_ktp;
                    $alamat_ktp     = trim($st->alamat_ktp) == '' || $st->alamat_ktp == null ?
                        'PENAMPARANBr/linkPENAMPARAN, 000/000, PADANGSAMBIAN, DENPASAR BARAT':$st->alamat_ktp;

                    $nama_pkp       = trim($st->npwp) == '' || $st->npwp == null ? "{$no_ktp} #NIK#NAMA#{$nama_ktp}" : $st->nama_pkp;
                    $alamat_pkp     = trim($st->npwp) == '' || $st->npwp == null ? $alamat_ktp : $st->alamat_pkp;

                    $data[] = [
                        'nama_gudang'   => $st->nama_gudang,
                        'nama_depo'     => $st->nama_depo,
                        'no_acc'        => $st->no_acc,
                        'nama_toko'     => $st->nama_toko,
                        'alamat_toko'   => $st->alamat,
                        'kabupaten'     => $st->nama_kabupaten,
                        'kecamatan'     => $st->nama_kecamatan,
                        'kelurahan'     => $st->nama_kelurahan,
                        'kode_pos'      => $st->kode_pos,
                        'npwp'          => $st->npwp,
                        'nama_pkp'      => $nama_pkp,
                        'alamat_pkp'    => $alamat_pkp,
                        'tipe_harga'    => $st->tipe_harga,
                        'tipe_toko'     => $st->tipe,
                        'kode_barang'   => $st->kode_barang,
                        'item_code'     => $st->item_code,
                        'pcs_code'      => $st->pcs_code,
                        'nama_barang'   => $st->nama_barang,
                        'nama_brand'    => $st->nama_brand,
                        'nama_segmen'   => $st->nama_segmen,
                        'isi'           => (string)$st->isi,
                        'berat'         => (string)$st->berat,
                        'satuan'        => $st->satuan,
                        'nama_salesman' => $st->name,
                        'nama_tim'      => $st->kode_eksklusif ?? $st->nama_tim,
                        'qty_dus'       => $st->qty,
                        'qty_pcs'       => $st->qty_pcs,
                        'order_dus'     => $st->order_qty,
                        'order_pcs'     => $st->order_pcs,
                        'price_after_tax' => $st->harga_jual,
                        'price_before_tax' => round($price_before_tax, 2),
                        'tahun'         =>  substr($delivered_at, 0, 4),
                        'bulan'         => substr($delivered_at, 5, 2),
                        'hari'          => substr($delivered_at, -2, 2),
                        'tanggal_penjualan' => $delivered_at,
                        'subtotal'      => round($subtotal, 2),
                        'discount'      => round($discount, 2),
                        'promo'         => $st->nama_promo,
                        'dpp'           => round($dpp, 2),
                        'ppn'           => round($ppn, 2),
                        'total'         => round($total, 2),
                        'hpp'           => round($hpp, 2),
                        'dbp'           => round($dbp, 2),
                        'week'          => (string)$week,
                        'no_invoice'    => $st->no_invoice,
                        'no_po'         => $st->po_manual ? (string)$st->po_manual : (string) $st->id,
                        'no_pajak'      => $st->no_pajak,
                        'status'        => $st->status == 'closed' ? 'cancel':$st->status,
                        'ordered_at'    => $st->created_at,
                        'approved_at'   => $st->approved_at,
                        'delivered_at'  => (string) date('Y-m-d', strtotime($st->delivered_at)),
                        'tipe_pembayaran'=> $st->tipe_pembayaran,
                        'nama_principal'=> $st->nama_principal,
                        'no_ktp'        => $no_ktp,
                        'nama_ktp'      => $nama_ktp,
                        'alamat_ktp'    => $alamat_ktp,
                        'pending_status'=> $st->pending_status,
                        'remark_close'  => $st->remark_close
                    ];
                }
            }

            return response()->json([
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'data' => $data
            ], 200);
        else :
            return $this->Unauthorized();
        endif;
    }

    public function std(Request $request)
    { // parameter wajib: start_date, end_date
        if ($this->user->can('Menu Laporan STD')) :
            $id_penerimaan_barang = PenerimaanBarang::oldest()->where('is_approved', 1)
                ->whereBetween('created_at', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);

            if ($request->id_gudang != 'all' && $request->id_gudang != '' && $request->id_gudang != null) {
                $id_penerimaan_barang = $id_penerimaan_barang->where('id_gudang', $request->id_gudang);
            }

            $id_penerimaan_barang = $id_penerimaan_barang->pluck('id');

            if ($id_penerimaan_barang->count() == 0) {
                return [];
            }

            $detail_penerimaan_barang = DetailPenerimaanBarang::whereIn('id_penerimaan_barang', $id_penerimaan_barang)->get();

            return response()->json([
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                // 'data' => $detail_penerimaan_barang,
                'data' => SalesToDistributorResource::collection($detail_penerimaan_barang),
            ], 200);
        else :
            return $this->Unauthorized();
        endif;
    }

    public function std2(Request $request)
    {
        if ($this->user->can('Menu Laporan STD')) :
            $id_principal = $request->has('id_principal') && $request->id_principal!='' && $request->id_principal!=null && $request->id_principal!='all' ?
                            $request->id_principal : '';

            $std = DB::table('penerimaan_barang')
                ->join('detail_penerimaan_barang', 'penerimaan_barang.id', 'detail_penerimaan_barang.id_penerimaan_barang')
                ->join('principal', 'penerimaan_barang.id_principal', 'principal.id')
                ->join('gudang', 'penerimaan_barang.id_gudang', 'gudang.id')
                ->join('barang', 'detail_penerimaan_barang.id_barang', 'barang.id')
                ->join('segmen', 'barang.id_segmen', 'segmen.id')
                ->join('brand', 'segmen.id_brand', 'brand.id')
                ->join('harga_barang', 'detail_penerimaan_barang.id_harga', 'harga_barang.id')
                ->where('penerimaan_barang.is_approved', 1)
                ->whereBetween('penerimaan_barang.created_at', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59'])
                ->whereNull('penerimaan_barang.deleted_at')
                ->when($id_principal <> '', function ($q) use ($id_principal){
                    return $q->where('principal.id',$id_principal);
                });

            if ($request->id_gudang != 'all' && $request->id_gudang != '' && $request->id_gudang != null) {
                $std = $std->where('penerimaan_barang.id_gudang', $request->id_gudang);
            }

            $std = $std->select(
                'gudang.nama_gudang',
                'principal.nama_principal',
                'penerimaan_barang.created_at',
                'penerimaan_barang.no_pb',
                'penerimaan_barang.no_do',
                'penerimaan_barang.no_spb',
                'penerimaan_barang.tgl_kirim',
                'penerimaan_barang.tgl_datang',
                'penerimaan_barang.tgl_bongkar',
                'penerimaan_barang.driver',
                'penerimaan_barang.transporter',
                'penerimaan_barang.no_pol_kendaraan',
                'barang.kode_barang',
                'barang.nama_barang',
                'segmen.nama_segmen',
                'brand.nama_brand',
                'barang.satuan',
                'barang.isi',
                'harga_barang.harga',
                'detail_penerimaan_barang.qty',
                'detail_penerimaan_barang.qty_pcs'
            )->orderBy('penerimaan_barang.created_at')->get();

            $data = [];

            foreach ($std as $st) {
                $sum_carton = $st->qty + ($st->qty_pcs / $st->isi);
                $price_before_tax = $st->harga / 1.1;
                $subtotal = $price_before_tax * $sum_carton;
                $ppn = ($st->harga / 11) * $sum_carton;
                $grand_total = $st->harga * $sum_carton;
                $week = \Carbon\Carbon::parse($st->created_at)->weekOfYear;
                $siklus = \Carbon\Carbon::parse($st->created_at)->month;

                $data[] = [
                    'gudang' => $st->nama_gudang,
                    'supplier' =>  $st->nama_principal,
                    'no_pb' =>  $st->no_pb,
                    'no_do' =>  $st->no_do,
                    'no_spb' =>  $st->no_spb,
                    'tgl_kirim' =>  $st->tgl_kirim,
                    'tgl_datang' =>  $st->tgl_datang,
                    'tgl_bongkar' =>  $st->tgl_bongkar,
                    'driver' =>  $st->driver,
                    'transporter' =>  $st->transporter,
                    'no_pol_kendaraan' =>  $st->no_pol_kendaraan,
                    'kode_barang' =>  $st->kode_barang,
                    'nama_barang' =>  $st->nama_barang,
                    'segmen' =>  $st->nama_segmen,
                    'brand' =>  $st->nama_brand,
                    'satuan' =>  $st->satuan,
                    'isi' =>  $st->isi,
                    'qty_dus' =>  $st->qty,
                    'qty_pcs' =>  $st->qty_pcs,
                    'harga' =>  $st->harga,
                    'price_before_tax' => $price_before_tax,
                    'subtotal' => $subtotal,
                    'ppn' => $ppn,
                    'grand_total' => $grand_total,
                    'week' =>  $week,
                    'siklus' => $siklus,
                    'created_at' =>  $st->created_at,
                ];
            }

            return response()->json([
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'id_principal' => $id_principal,
                'data' => $data
            ], 200);
        else :
            return $this->Unauthorized();
        endif;
    }

    public function posisi_stock(Request $request)
    { //id_gudang & tanggal required
        if ($this->user->can('Menu Laporan Posisi Stock')) :
            if ($request->id_gudang == '') {
                return response()->json([
                    'message' => 'Pilih Gudang terlebih dahulu!'
                ], 400);
            }

            if ($request->tanggal == '') {
                return response()->json([
                    'message' => 'Pilih Tanggal posisi stock yang ingin dicari!'
                ], 400);
            }

            $id_gudang  = $request->id_gudang;
            $tanggal    = $request->tanggal;
            $id_principal = $request->has('id_principal') && count($request->id_principal)
                ? $request->id_principal : '';
            $id_brand   = $request->has('id_brand') && count($request->id_brand) > 0
                ? $request->id_brand : '';
            $id_barang  = $request->has('id_barang') && count($request->id_barang) > 0
                ? $request->id_barang : '';
            $gudang     = Gudang::find($id_gudang);

            $posisi_stock = PosisiStock::with(['stock'])
                ->where('tanggal', $tanggal)->get()
                ->when($id_barang <> '', function ($q) use ($id_barang) {
                    return $q->whereIn('id_barang', $id_barang);
                })
                ->when($id_brand <> '', function ($q) use ($id_brand) {
                    return $q->whereIn('id_brand', $id_brand);
                })
                ->when($id_principal <> '', function ($q) use ($id_principal) {
                    return $q->whereIn('id_principal', $id_principal);
                })
                ->where('id_gudang', $id_gudang)
                ->sortBy('kode_barang');
            //
            foreach ($posisi_stock as $key => $stock) {
                if (
                    $stock->saldo_akhir_qty == 0 && $stock->saldo_akhir_pcs == 0 && $stock->pembelian_qty == 0 && $stock->pembelian_pcs == 0
                    && $stock->mutasi_masuk_qty == 0 && $stock->mutasi_masuk_pcs == 0 && $stock->mutasi_keluar_qty == 0 && $stock->mutasi_keluar_pcs == 0
                    && $stock->adjustment_qty == 0 && $stock->adjustment_pcs == 0 && $stock->penjualan_qty == 0 && $stock->penjualan_pcs == 0
                ) {
                    unset($posisi_stock[$key]);
                    continue;
                }

                // hitung penjualan pending
                $pending = DetailPenjualan::with('penjualan')
                    ->whereHas('penjualan', function ($q) use ($tanggal) {
                        return $q->whereDate('tanggal_invoice', '<', $tanggal)
                            ->where('tanggal', '>', '2020-09-18')
                            ->where('status', 'approved')
                            ->whereNull('deleted_at');
                    })
                    ->where('id_stock', $stock->id_stock)
                    ->get();
                $qty        = $pending->sum('qty');
                $qty_pcs    = $pending->sum('qty_pcs');

                $posisi_stock[$key]->pending_qty = $qty;
                $posisi_stock[$key]->pending_pcs = $qty_pcs;

                $deliver = DetailPenjualan::with('penjualan')
                    ->whereHas('penjualan', function ($q) use ($tanggal) {
                        return $q->whereDate('delivered_at', $tanggal)
                            ->where('status', 'delivered');
                    })
                    ->where('id_stock', $stock->id_stock)
                    ->get();

                $qty_deliver        = $deliver->sum('qty');
                $qty_pcs_deliver    = $deliver->sum('qty_pcs');

                $posisi_stock[$key]->deliver_qty    = $qty_deliver;
                $posisi_stock[$key]->deliver_pcs    = $qty_pcs_deliver;

                // hitung mutasi pending
                $mutasi_pending = DB::table('detail_mutasi_barang AS b')
                    ->join('mutasi_barang AS a', 'a.id', 'b.id_mutasi_barang')
                    ->where('a.is_approved', 1)
                    ->where('a.status', 'approved')
                    ->where('id_stock', $stock->id_stock)
                    ->whereNull('a.deleted_at')
                    ->whereNull('b.deleted_at')
                    ->select('b.qty', 'b.qty_pcs')
                    ->get();

                $qty_mutasi_pending = $mutasi_pending->sum('qty');
                $qty_mutasi_pending_pcs = $mutasi_pending->sum('qty_pcs');

                $posisi_stock[$key]->mutasi_pending_qty = $qty_mutasi_pending;
                $posisi_stock[$key]->mutasi_pending_pcs = $qty_mutasi_pending_pcs;
            }

            return response()->json([
                // 'start_date' => $request->start_date,
                // 'end_date' => $request->end_date,
                'id_gudang' => $gudang->id,
                'nama_gudang' => $gudang->nama_gudang,
                'tanggal' => $tanggal,
                'data' => PosisiStockResource::collection($posisi_stock)
            ], 200);
        else :
            return $this->Unauthorized();
        endif;
    }


    public function laporan_penjualan(Request $request)
    { //$date, $id_salesman //laporan penjualan harian
        $id_user = $this->user->id;
        if ($this->user->can('Menu Laporan Penjualan Harian')) :
            $tipe = $request->has('tipe') && $request->tipe !== '' ? $request->tipe : '';
            $id_depo = $request->depo != null ? $request->depo : Helper::depoIDByUser($this->user->id);
            $id_salesman = explode(',', $request->id_salesman);
            $id_mitra = $request->has('id_mitra') ? $request->id_mitra : 'include';

            if ($request->has('id_salesman')) {
                if ($request->id_salesman == 'all' || $request->id_salesman == '' || in_array("all", $id_salesman)) {
                    if ($this->user->hasRole('Sales Supervisor')) {
                        $id_salesman = Helper::salesBySupervisor($id_user);
                    } else if ($this->user->hasRole('Sales Koordinator')) {
                        $id_salesman = Helper::salesByKoordinatorAndDepo($this->user->id, $id_depo);
                    } else {
                        $id_salesman = Salesman::when($id_depo <> '', function ($q) use ($id_depo) {
                            return $q->whereHas('tim', function ($q) use ($id_depo) {
                                return $q->whereIn('id_depo', $id_depo);
                            });
                        })
                            ->pluck('user_id');
                    }
                } else {
                    $id_salesman = explode(',', $request->id_salesman);
                }
            } else {
                if ($this->user->hasRole('Sales Supervisor')) {
                    $id_salesman = Helper::salesBySupervisor($id_user);
                } else if ($this->user->hasRole('Sales Koordinator')) {
                    $id_salesman = Helper::salesByKoordinatorAndDepo($this->user->id, $id_depo);
                } else {
                    $id_salesman = Salesman::when($id_depo <> '', function ($q) use ($id_depo) {
                        return $q->whereHas('tim', function ($q) use ($id_depo) {
                            return $q->whereIn('id_depo', $id_depo);
                        });
                    })
                        ->pluck('user_id');
                }
            }

            // $id_salesman = Salesman::get()->where('tipe_sales', 'to')->whereIn('nama_depo',['Denpasar','Tabanan','HCO'])->pluck('user_id');
            // $id_salesman = collect([$request->id_salesman]);

            // $start_date = $request->start_date;
            // $end_date = $request->end_date;

            $collection = collect([]);
            foreach ($id_salesman as $id_salesman) {
                $salesman   = Salesman::find($id_salesman);
                if (!$salesman->tim) {
                    continue;
                }

                $penjualan  = Penjualan::with('toko')->where('id_salesman', $id_salesman)
                ->when($id_mitra <> 'exclude' && $id_mitra <> 'include', function ($q) use ($id_mitra) {
                    $q->where('id_mitra', $id_mitra);
                })
                ->when($id_mitra == 'exclude', function ($q) {
                    $q->where('id_mitra', '=', 0);
                });

                $detail_pelunasan_penjualan = DetailPelunasanPenjualan::with(['penjualan', 'penjualan.toko'])
                ->whereHas('penjualan', function ($q) use ($id_mitra) {
                    $q->when($id_mitra <> 'exclude' && $id_mitra <> 'include', function ($q) use ($id_mitra) {
                        $q->where('id_mitra', $id_mitra);
                    })
                    ->when($id_mitra == 'exclude', function ($q) {
                        $q->where('id_mitra', 0);
                    });
                })
                ->where('created_by', $id_salesman);
                $target_salesman = TargetSalesman::where('id_user', $id_salesman);

                if ($tipe === 'estimasi') {
                    $penjualan = $penjualan->whereIn('status', ['approved', 'loaded', 'delivered']);
                } else {
                    $penjualan = $penjualan->where('status', 'delivered');
                }

                if ($request->has('date')) {
                    $date       = Carbon::parse($request->date)->toDateString();
                    $detail_pelunasan_penjualan = $detail_pelunasan_penjualan->where('created_at', 'like', $request->date . '%');

                    if ($tipe === 'estimasi') {
                        $penjualan  = $penjualan->where('tanggal', '=', "{$date}");
                        $target_salesman     = $target_salesman->whereRaw("'{$date}' BETWEEN mulai_tanggal AND sampai_tanggal");
                    } else {
                        $penjualan  = $penjualan->whereRaw("DATE(delivered_at) = '{$date}'");
                        $target_salesman     = $target_salesman->whereRaw("'{$date}' BETWEEN mulai_tanggal AND sampai_tanggal");
                    }

                } elseif ($request->has('start_date') && $request->has('end_date')) {
                    $start_date = Carbon::parse($request->start_date)->toDateString();
                    $end_date   = Carbon::parse($request->end_date)->toDateString();
                    $detail_pelunasan_penjualan = $detail_pelunasan_penjualan->whereBetween('created_at', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);

                    if ($tipe === 'estimasi') {
                        $penjualan  = $penjualan->whereRaw("DATE(tanggal) BETWEEN '{$start_date}' AND '{$end_date}'");
                    } else {
                        $penjualan  = $penjualan->whereRaw("DATE(delivered_at) BETWEEN '{$start_date}' AND '{$end_date}'");
                    }
                } else {
                    $penjualan = $penjualan->where('tanggal', Carbon::now()->toDateString());
                    $detail_pelunasan_penjualan = $detail_pelunasan_penjualan->whereDate('created_at', '=', Carbon::now()->toDateString());
                    $target_salesman     = $target_salesman->whereRaw("DATE(NOW()) BETWEEN mulai_tanggal AND sampai_tanggal");
                }

                $penjualan = $penjualan->orderBy('no_invoice', 'ASC')->get();
                $detail_pelunasan_penjualan = $detail_pelunasan_penjualan->get();
                $grand_total = (int)$penjualan->whereNotIn('status', ['waiting', 'canceled'])->sum('grand_total');
                $target = 0;
                $ach    = 0;
                if (!$request->has(['start_date', 'end_date'])) {
                    $target_salesman = $target_salesman->first();
                    if ($target_salesman) {
                        $target = round($target_salesman->target / $target_salesman->hari_kerja);
                        $ach    = round($grand_total/$target * 100);
                    }
                }


                $laporan_salesman = [
                    'id_salesman' => $id_salesman,
                    'tim' => $salesman->tim->nama_tim,
                    'nama_salesman' => $salesman->user->name,
                    'tipe' => $salesman->tim->tipe,
                    'count_inv' => $penjualan->count(), // total invoice
                    'ec' => $penjualan->unique('id_toko')->count(),
                    'sku' => round((float)$penjualan->avg('sku'), 2),
                    'total_qty_order' => (int)$penjualan->sum('total_qty_order'),
                    'total_pcs_order' => (int)$penjualan->sum('total_pcs_order'),
                    'value_order' => (int)$penjualan->sum('grand_total_order'),
                    'total_qty' => (int)$penjualan->whereNotIn('status', ['waiting', 'canceled'])->sum('total_qty'),
                    'total_pcs' => (int)$penjualan->whereNotIn('status', ['waiting', 'canceled'])->sum('total_pcs'),
                    'value' => $grand_total,
                    'value_cash' => (int)$penjualan->whereNotIn('status', ['waiting', 'canceled'])->where('tipe_pembayaran', 'cash')->sum('grand_total'),
                    'value_credit' => (int)$penjualan->whereNotIn('status', ['waiting', 'canceled'])->where('tipe_pembayaran', 'credit')->sum('grand_total'),
                    'total_ppn' => (int)$penjualan->whereNotIn('status', ['waiting', 'canceled'])->sum('ppn'),
                    'total_discount' => (int)$penjualan->whereNotIn('status', ['waiting', 'canceled'])->sum('disc_total'),
                    'penagihan_tunai' => (int)$detail_pelunasan_penjualan->where('tipe', 'tunai')->sum('nominal'), // pembayaran tunai
                    'penagihan_transfer' => (int)$detail_pelunasan_penjualan->where('tipe', 'transfer')->sum('nominal'), // pembayaran transfer
                    'penagihan_bg' => (int)$detail_pelunasan_penjualan->where('tipe', 'bilyet_giro')->sum('nominal'), // penagihan bg
                    'penagihan_retur' => (int)$detail_pelunasan_penjualan->where('tipe', 'saldo_retur')->sum('nominal'), // claim saldo retur
                    'penagihan_lainnya' => (int)$detail_pelunasan_penjualan->where('tipe', 'lainnya')->sum('nominal'), // penagihan lainnya,
                    'target' => $target,
                    'ach'   => $ach
                ];
                $collection->push($laporan_salesman);
            }

            return $collection;
        else :
            return $this->Unauthorized();
        endif;
        // $response = Excel::download($collection, 'laporan_penjualan_harian_' . $request->date . '.xlsx');
        // return $response;
    }

    public function penjualan_toko_barang(Request $request)
    {
        // PARAMETER (REQUIRED) : id_toko, id_stock
        // parameter (NULLABLE) : year, week | start_date, end_date

        if (!$request->has('id_toko') && !$request->has('id_stock')) {
            return response()->json([
                'message' => 'Pilih Toko dan Barang yang ingin dicari!'
            ], 400);
        }

        $id_toko = $request->id_toko;
        $id_stock = $request->id_stock;

        if ($request->has('year') && $request->has('week')) {
            $year_week = Carbon::now()->setISODate($request->year, $request->week);
            $start_date = $year_week->startOfWeek()->toDateString();
            $end_date = $year_week->endOfWeek()->toDateString();
        } elseif ($request->has('start_date') && $request->has('end_date')) {
            $start_date = $request->start_date;
            $end_date = $request->end_date;
        } else {
            $today = Carbon::now()->toDateString();
            $start_date = $today;
            $end_date = $today;
        }

        $list_penjualan = Penjualan::where('id_toko', $id_toko)
            ->whereBetween('tanggal', [$start_date, $end_date])
            ->latest()
            ->whereHas('detail_penjualan', function ($query) use ($id_stock) {
                $query->where('id_stock', '=', $id_stock);
            })
            // ->with('detail_penjualan.stock.barang')
            ->with(['detail_penjualan' => function ($query) use ($id_stock) {
                $query->where('id_stock', '=', $id_stock);
            }])
            // }, 'detail_penjualan.stock.barang'])
            ->get();


        $total_qty = 0;
        $total_pcs = 0;

        foreach ($list_penjualan as $lp) {
            $total_qty += $lp->detail_penjualan[0]->qty;
            $total_pcs += $lp->detail_penjualan[0]->qty_pcs;
        }

        return response()->json([
            'total_qty' => $total_qty,
            'total_pcs' => $total_pcs,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'data' => $list_penjualan
        ]);
    }

    public function retur_penjualan(Request $request)
    {
        if (!$this->user->can('Menu Laporan Retur Penjualan')) {
            return $this->Unauthorized();
        }

        $kategori           = $request->has('kategori') ? $request->kategori : 'all';
        $id_perusahaan      = $request->has('id_perusahaan') && $request->id_perusahaan <> '' ? [$request->id_perusahaan] : Helper::perusahaanByUser($this->user->id);
        $id_depo            = $request->depo != null ? $request->depo : Helper::depoIDByUser($this->user->id, $id_perusahaan);
        $id_principal       = $request->has('id_principal') ? $request->id_principal : Helper::principalByUser($this->user->id);
        $id_mitra           = $request->has('id_mitra') && $request->id_mitra <> '' ? $request->id_mitra : 0;
        $retur_penjualan    = ReturPenjualan::whereIn('id_depo', $id_depo)
                            ->when($kategori <> 'all', function ($q) use ($kategori) {
                                $q->where('tipe_barang', $kategori);
                            })
                            ->where('id_mitra', '=', $id_mitra);
        $id_salesman        = $request->id_salesman ?? 'all';

        if ($id_salesman == 'all') {
            if ($this->user->hasRole('Sales Supervisor')) {
                $id_salesman = Helper::salesBySupervisorAndDepo($this->user->id, $id_depo);
                $retur_penjualan = $retur_penjualan->whereIn('id_salesman', $id_salesman);
            } else if ($this->user->hasRole('Sales Koordinator')) {
                $id_salesman = Helper::salesByKoordinator($this->user->id);
                $retur_penjualan = $retur_penjualan->whereIn('id_salesman', $id_salesman);
            }
        } else {
            $retur_penjualan = $retur_penjualan->where('id_salesman', $request->id_salesman);
        }

        $status = $request->status;
        if ($request->has('start_date') && $request->has('end_date')) {
            $start_date = $request->start_date;
            $end_date   = $request->end_date;
        } else {
            $today      = Carbon::now()->toDateString();
            $start_date = $today;
            $end_date   = $today;
        }

        if ($status == 'all') {
            $retur_penjualan = $retur_penjualan->whereBetween('sales_retur_date', [$start_date, $end_date]);
        } elseif ($status == 'approved') {
            $retur_penjualan = $retur_penjualan->where('status', 'approved')->whereBetween('approved_at', [$start_date . " 00:00:00", $end_date . " 23:59:59"]);
        } else {
            $retur_penjualan = $retur_penjualan->whereBetween('claim_date', [$start_date, $end_date]);
        }

        $id_retur_penjualan = $retur_penjualan->pluck('id');

        $detail_retur_penjualan = DetailReturPenjualan::whereIn('id_retur_penjualan', $id_retur_penjualan)
            ->with(['retur_penjualan', 'barang', 'barang.segmen', 'barang.segmen.brand', 'barang.segmen.brand.principal'])
            ->when(count($id_principal) <> 0, function ($q) use ($id_principal) {
                $q->whereHas('barang.segmen.brand', function ($q) use ($id_principal) {
                    $q->whereIn('id_principal', $id_principal);
                });
            })
            ->orderBy('id_retur_penjualan')
            ->get();

        $result = ReportReturResource::collection($detail_retur_penjualan);

        return response()->json([
            'data'          => $result,
            'start_date'    => $start_date,
            'end_date'      => $end_date
        ], 200);
    }

    public function laporan_actual2(Request $request)
    {
        //$start_date, $end_date, $id_salesman //laporan actual
        if ($this->user->can('Menu Laporan Aktual')) :
            $id_depo = $request->depo != null ? $request->depo : Helper::depoIDByUser($this->user->id);
            $id_mitra = $request->has('id_mitra') ? $request->id_mitra : 'include';

            if ($request->has('id_salesman')) {
                if ($request->id_salesman == 'all' || $request->id_salesman == '') {
                    if ($this->user->hasRole('Sales Supervisor')) {
                        $id_salesman = Helper::salesBySupervisorAndDepo($this->user->id, $id_depo);
                    } else if ($this->user->hasRole('Sales Koordinator')) {
                        $id_salesman = Helper::salesByKoordinatorAndDepo($this->user->id, $id_depo);
                    } else {
                        $id_salesman = Salesman::when($id_depo <> '', function ($q) use ($id_depo) {
                            return $q->whereHas('tim', function ($q) use ($id_depo) {
                                return $q->whereIn('id_depo', $id_depo);
                            });
                        })
                            ->pluck('user_id');
                    }
                } else {
                    $id_salesman = explode(',', $request->id_salesman);
                }
            } else {
                if ($this->user->hasRole('Sales Supervisor')) {
                    $id_salesman = Helper::salesBySupervisorAndDepo($this->user->id, $id_depo);
                } else if ($this->user->hasRole('Sales Koordinator')) {
                    $id_salesman = Helper::salesByKoordinatorAndDepo($this->user->id, $id_depo);
                } else {
                    $id_salesman = Salesman::when($id_depo <> '', function ($q) use ($id_depo) {
                        return $q->whereHas('tim', function ($q) use ($id_depo) {
                            return $q->whereIn('id_depo', $id_depo);
                        });
                    })
                        ->pluck('user_id');
                }
            }


            $penjualan = DB::table('penjualan')->join('salesman', 'penjualan.id_salesman', 'salesman.user_id')
                ->join('toko', 'penjualan.id_toko', 'toko.id')
                ->join('users', 'salesman.user_id', 'users.id')
                ->join('tim', 'salesman.id_tim', 'tim.id')
                ->join('detail_penjualan', 'penjualan.id', 'detail_penjualan.id_penjualan')
                ->join('promo', 'detail_penjualan.id_promo', 'promo.id')
                ->join('stock', 'detail_penjualan.id_stock', 'stock.id')
                ->join('barang', 'stock.id_barang', 'barang.id')
                ->join('perusahaan', 'penjualan.id_perusahaan', 'perusahaan.id')
                ->when($id_mitra <> 'exclude' && $id_mitra <> 'include', function ($q) use ($id_mitra) {
                    $q->where('penjualan.id_mitra', $id_mitra);
                })
                ->when($id_mitra == 'exclude', function ($q) {
                    $q->where('penjualan.id_mitra', '=', 0);
                })
                ->whereIn('penjualan.id_salesman', $id_salesman)
                ->whereNull('penjualan.deleted_at');

            $date_type = 'penjualan.delivered_at'; // 'approved_at's
            if ($request->has('date')) {
                $penjualan = $penjualan->where($date_type, 'like', $request->date . '%');
            } elseif ($request->has('start_date') && $request->has('end_date')) {
                $penjualan = $penjualan->whereBetween($date_type, [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
                $logData = [
                    'action' => 'Download Laporan Actual',
                    'description' => 'Tanggal ' . $request->start_date . ' sampai ' . $request->end_date,
                    'user_id' => $this->user->id
                ];

                $this->log($logData);
            } else {
                $penjualan = $penjualan->where($date_type, 'like', Carbon::now()->toDateString() . '%');
            }

            $penjualan = $penjualan->select(
                'penjualan.id_salesman',
                'users.name',
                'tim.nama_tim',
                'tim.tipe',
                'penjualan.no_invoice',
                'penjualan.id_toko',
                'penjualan.status',
                'barang.isi',
                'detail_penjualan.id_stock',
                'detail_penjualan.id_harga',
                'detail_penjualan.qty',
                'detail_penjualan.qty_pcs',
                'detail_penjualan.order_qty',
                'detail_penjualan.order_pcs',
                'detail_penjualan.id_promo',
                'detail_penjualan.harga_jual',
                'penjualan.tipe_pembayaran',
                'detail_penjualan.disc_persen',
                'detail_penjualan.disc_rupiah',
                'perusahaan.nama_perusahaan'
            )->get();

            $colPenjualan = collect();
            foreach ($penjualan as $p) {
                $sum_carton = $p->qty + ($p->qty_pcs / $p->isi);
                $price_before_tax = $p->harga_jual / 1.1;
                $subtotal = $price_before_tax * $sum_carton;

                $discount = 0;
                if ($p->id_promo) {
                    $disc_rupiah = ($p->disc_rupiah / 1.1) * $sum_carton;
                    $disc_persen = ($p->disc_persen / 100) * $subtotal;
                    $discount = $disc_rupiah + $disc_persen;
                }

                $ppn = ($subtotal - $discount) / 10;
                $grand_total = $subtotal - $discount + $ppn;

                $qty_total_order = 0;

                if ($p->id_harga != 0) {
                    $qty_total_order = $p->order_qty + ($p->order_pcs / $p->isi); //dlm jumlah dus dalam bentuk koma
                } else {
                    $qty_total_order = 0;
                }

                $subtotal_order = ($p->harga_jual / 1.1) * $qty_total_order;

                $colP = collect($p);
                $colP->put('subtotal_order', $subtotal_order);
                $colP->put('ppn', $ppn);
                $colP->put('disc_total', $discount);
                $colP->put('grand_total', $grand_total);

                $colPenjualan->push($colP);
            }

            // retur claim
            $retur = ReturPenjualan::with('toko')->select('id_salesman', DB::raw('SUM(saldo_retur) as saldo_retur'))
                ->when($id_mitra <> 'exclude' && $id_mitra <> 'include', function ($q) use ($id_mitra) {
                   $q->where('id_mitra', $id_mitra);
                })
                ->when($id_mitra == 'exclude', function ($q) {
                    $q->where('id_mitra', 0);
                })
                ->whereIn('id_salesman', $id_salesman)
                ->whereBetween('claim_date', [$request->start_date, $request->end_date])
                ->groupBy('id_salesman')
                ->get();

            // retur approved
            $retur_approved = ReturPenjualan::with('toko')->select('id_salesman', DB::raw('SUM(saldo_retur) as saldo_retur'))
                ->when($id_mitra <> 'exclude' && $id_mitra <> 'include', function ($q) use ($id_mitra) {
                   $q->where('id_mitra', $id_mitra);
                })
                ->when($id_mitra == 'exclude', function ($q) {
                    $q->where('id_mitra', 0);
                })
                ->whereIn('id_salesman', $id_salesman)
                ->whereBetween('approved_at', [$request->start_date." 00:00:00", $request->end_date." 23:59:59"])
                ->groupBy('id_salesman')
                ->get();
//
            $data = [];
            $salesman = Salesman::join('users', 'salesman.user_id', 'users.id')
                ->join('tim', 'salesman.id_tim', 'tim.id')
                ->select('salesman.user_id', 'users.name', 'tim.nama_tim', 'tim.tipe')->get();

            $header_actual = '';
            if (is_numeric($id_mitra)) {
                $mitra          = Mitra::find($id_mitra);
                $header_actual  = $mitra->perusahaan ?? '-';
            }
            foreach ($salesman as $s) {
                $coll = $colPenjualan->where('id_salesman', $s->user_id);
                if (!$coll->isEmpty()) {
                    $collRetur      = $retur->where('id_salesman', $s->user_id)->first();
                    $collApproved   = $retur_approved->where('id_salesman', $s->user_id)->first();
                    $data[] = [
                        'perusahaan'    => $header_actual != '-' ? $header_actual : $coll->first()['nama_perusahaan'],
                        'id_salesman'   => $s->user_id,
                        'tim'           => $s->nama_tim,
                        'nama_salesman' =>  $s->name,
                        'tipe'          => $s->tipe,
                        'count_inv'     => $coll->unique('no_invoice')->count(),
                        'ec'            => $coll->unique('id_toko')->count(),
                        'sku'           => round($coll->count('id_stock') / $coll->unique('no_invoice')->count(), 2),
                        'total_qty_order' => $coll->sum('order_qty'),
                        'total_pcs_order' => $coll->sum('order_pcs'),
                        'value_order'   => (int)round($coll->sum('subtotal_order')),
                        'total_qty'     => $coll->sum('qty'),
                        'total_pcs'     => $coll->sum('qty_pcs'),
                        'value'         => (int)round(($coll->whereNotIn('status', ['waiting', 'canceled', 'approved'])->sum('grand_total') / 1.1)),
                        'value_cash'    => (int)round(($coll->whereNotIn('status', ['waiting', 'canceled', 'approved'])->where('tipe_pembayaran', 'cash')->sum('grand_total') / 1.1)),
                        'value_credit'  => (int)round(($coll->whereNotIn('status', ['waiting', 'canceled', 'approved'])->where('tipe_pembayaran', 'credit')->sum('grand_total') / 1.1)),
                        'total_ppn'     => (int)round($coll->whereNotIn('status', ['waiting', 'canceled', 'approved'])->sum('ppn')),
                        'total_discount'=> (int)round($coll->whereNotIn('status', ['waiting', 'canceled', 'approved'])->sum('disc_total')),
                        'claim_retur'   => (int) round($collRetur->saldo_retur ?? 0),
                        'retur_approved'=> (int) round($collApproved->saldo_retur ?? 0)
                    ];
                }
            }

            $file = $this->laporan_actual_download($request, $data);

            return response()->json([$data, $file]);

        else :
            return $this->Unauthorized();
        endif;
    }

    public function laporan_actual_download($request, $collection)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Laporan Actual');
        $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        $sheet->getPageMargins()->setTop(0.3);
        $sheet->getPageMargins()->setRight(0.3);
        $sheet->getPageMargins()->setLeft(0.3);
        $sheet->getPageMargins()->setBottom(0.3);
        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->getPageSetup()->setFitToHeight(0);

        $i = 1;
        $sheet->setCellValue('A' . $i, $collection[0]['perusahaan'] ?? '');
        $sheet->mergeCells('A' . $i . ':C' . $i);
        $sheet->getStyle('A' . $i . ':A' . $i)->applyFromArray($this->fontSize(18));
        $i++;
        $contentStart = $i;
        $sheet->setCellValue('A' . $i, 'Laporan Actual');
        $sheet->mergeCells('A' . $i . ':C' . $i);
        $i++;
        $sheet->setCellValue('A' . $i, 'Periode');
        $sheet->mergeCells('A' . $i . ':B' . $i);
        $start_date = Carbon::parse($request->start_date)->format('d F Y');
        $end_date   = Carbon::parse($request->end_date)->format('d F Y');
        $sheet->setCellValue('C' . $i, $start_date . ' - ' . $end_date);
        $i = $i + 2;
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setAutoSize(true);
        $sheet->getColumnDimension('C')->setAutoSize(true);
        $sheet->getColumnDimension('D')->setAutoSize(true);
        $sheet->getColumnDimension('E')->setAutoSize(true);
        $sheet->getColumnDimension('F')->setAutoSize(true);
        $sheet->getColumnDimension('G')->setWidth(5);
        $sheet->getColumnDimension('H')->setWidth(15);
        $sheet->getColumnDimension('I')->setAutoSize(true);
        $sheet->getColumnDimension('J')->setWidth(15);
        $sheet->getColumnDimension('K')->setWidth(15);
        $sheet->getColumnDimension('L')->setWidth(15);
        $sheet->getColumnDimension('M')->setWidth(20);
        $sheet->getColumnDimension('N')->setWidth(20);
        $sheet->getColumnDimension('O')->setWidth(20);
        $sheet->getColumnDimension('P')->setWidth(20);
        $sheet->getColumnDimension('Q')->setWidth(20);
        $sheet->getColumnDimension('R')->setWidth(20);
        $sheet->getColumnDimension('S')->setWidth(20);
        $sheet->getColumnDimension('T')->setAutoSize(true);
        $cells  = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T'];
        $columns = ['No', 'Tim', 'Nama Salesman', 'Tipe', 'Count Inv', 'EC', 'SKU', 'Total Qty Order', 'Total PCS Order', 'Value Order', 'Total Qty', 'Total PCS', 'Value', 'Cash', 'Credit', 'PPN', 'Discount', 'Grand Total', 'Claim Retur', 'Approved Retur'];
        $sheet->getStyle('A' . $i . ':T' . $i)->getFont()->setBold(true);
        $sheet->getStyle('A' . $i . ':T' . $i)->applyFromArray($this->horizontalCenter());
        $sheet->getStyle('A' . $i . ':T' . $i)->applyFromArray($this->border());

        foreach ($columns as $key => $column) {
            $sheet->setCellValue($cells[$key] . $i, $column);
        }

        $sheet->setAutoFilter('A' . $i . ':T' . $i);

        $i++;
        $start  = $i;
        $no     = 1;
        foreach ($collection as $key => $collect) {
            if ($collect['value'] <= 0) {
                continue;
            }

            $sheet->setCellValue('A' . $i, $no);
            $sheet->setCellValue('B' . $i, $collect['tim']);
            $sheet->setCellValue('C' . $i, $collect['nama_salesman']);
            $sheet->setCellValue('D' . $i, $collect['tipe']);
            $sheet->setCellValue('E' . $i, $collect['count_inv']);
            $sheet->setCellValue('F' . $i, $collect['ec']);
            $sheet->setCellValue('G' . $i, $collect['sku']);
            $sheet->setCellValue('H' . $i, $collect['total_qty_order']);
            $sheet->setCellValue('I' . $i, $collect['total_pcs_order']);
            $sheet->setCellValue('J' . $i, $collect['value_order']);
            $sheet->setCellValue('K' . $i, $collect['total_qty']);
            $sheet->setCellValue('L' . $i, $collect['total_pcs']);
            $sheet->setCellValue('M' . $i, $collect['value']);
            $sheet->setCellValue('N' . $i, $collect['value_cash']);
            $sheet->setCellValue('O' . $i, $collect['value_credit']);
            $sheet->setCellValue('P' . $i, $collect['total_ppn']);
            $sheet->setCellValue('Q' . $i, $collect['total_discount']);
            $sheet->setCellValue('R' . $i, ($collect['value'] + $collect['total_ppn']));
            $sheet->setCellValue('S' . $i, $collect['claim_retur']);
            $sheet->setCellValue('T' . $i, $collect['retur_approved']);
            $i++;
            $no++;
        }

        $end = $i - 1;
        $sheet->getStyle('A' . $contentStart . ':T' . $i)->applyFromArray($this->fontSize(14));
        $sheet->setCellValue('M' . $i, "=SUBTOTAL(9, M{$start}:M{$end})");
        $sheet->setCellValue('N' . $i, "=SUBTOTAL(9, N{$start}:N{$end})");
        $sheet->setCellValue('O' . $i, "=SUBTOTAL(9, O{$start}:O{$end})");
        $sheet->setCellValue('P' . $i, "=SUBTOTAL(9, P{$start}:P{$end})");
        $sheet->setCellValue('Q' . $i, "=SUBTOTAL(9, Q{$start}:Q{$end})");
        $sheet->setCellValue('R' . $i, "=SUBTOTAL(9, R{$start}:R{$end})");
        $sheet->setCellValue('S' . $i, "=SUBTOTAL(9, S{$start}:S{$end})");
        $sheet->setCellValue('T' . $i, "=SUBTOTAL(9, T{$start}:T{$end})");
        $sheet->getStyle('M' . $i . ':T' . $i)->applyFromArray($this->fontSize(16));
        $sheet->getStyle('I' . $start . ':T' . $i)->getNumberFormat()->setFormatCode('_(* #,##0_);_(* (#,##0);_(* "-"??_);_(@_)');
        $sheet->getStyle('A' . $start . ':T' . $i)->applyFromArray($this->border());
        //hide column
        $hides = ['E', 'F', 'G', 'H', 'I', 'J', 'K', 'L'];
        foreach ($hides as $hide) {
            $sheet->getColumnDimension($hide)->setVisible(false);
        }

        $writer = new Xlsx($spreadsheet);
        $id_user = $this->user->id;
        ob_start();
        $writer->save('php://output');
        $content = ob_get_contents();
        ob_end_clean();
        $fileName = "laporan_actual_{$request->start_date}_{$id_user}.xlsx";
        Storage::disk('local')->put('excel/' . $fileName, $content);
        $file = url('/excel/' . $fileName);
        return $file;
    }

    public function realisasi_program(Request $request)
    {
        // filter get all promo, all tim
        // PARAMETER (REQUIRED) : id_promo, start_date, end_date
        // parameter (NULLABLE) :
        // IMPORTANT : batasi date rangenya, jgn terlalu banyak, loadnya jd lama

        if (!$request->has('id_salesman') || $request->id_salesman == '') {
            return response()->json([
                'message' => 'Salesman tidak boleh kosong!'
            ], 400);
        }

        if (!$request->has('id_promo') || $request->id_promo == '') {
            return response()->json([
                'message' => 'Promo tidak boleh kosong!'
            ], 400);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $start_date = $request->start_date;
            $end_date   = $request->end_date;
        } else {
            $today      = Carbon::now()->toDateString();
            $start_date = $today;
            $end_date   = $today;
        }

        $promo = Promo::find($request->id_promo);

        // tarik data berdasarkan tanggal approved_at / delivered_at
        $detail_penjualan = DetailPenjualan::where('id_promo', $request->id_promo)
            ->whereHas('penjualan', function ($query) use ($start_date, $end_date) {
                $query->whereIn('status', ['delivered'])
                    ->whereBetween('delivered_at', [$start_date . " 00:00:00", $end_date . " 23:59:59"]);
            })
            ->where( function ($q) {
                $q->where('qty', '!=', 0)->orWhere('qty_pcs', '!=', 0);
            });

        // Filter Salesman (Tim)
        $nama_team = 'all';
        if ($request->id_salesman != '' && $request->id_salesman != 'all') {
            $salesman = Salesman::where('user_id', $request->id_salesman)->first();
            $nama_team =  $salesman->user->name;

            $detail_penjualan->select(DB::raw('detail_penjualan.*, penjualan.id_salesman'))
                ->leftJoin('penjualan', 'detail_penjualan.id_penjualan', '=', 'penjualan.id')
                ->where('id_salesman', $request->id_salesman);
        }

        $detail_penjualan = $detail_penjualan->get();
        $detail_penjualan_by_po = $detail_penjualan->groupBy('id_penjualan');

        $data = [];
        $total_qty_pcs = 0;
        $total_qty = 0;
        foreach ($detail_penjualan_by_po as $key => $po) {
            $penjualan  = $po[0]->penjualan;
            $toko       = $penjualan->toko;

            $data[$key] = [
                'no_invoice' => $po[0]->penjualan->no_invoice,
                'no_po'     => $penjualan->po_manual != '' ? $penjualan->po_manual : $po[0]->id_penjualan,
                'tgl'       => Carbon::parse($penjualan->delivered_at)->format('d-M'),
                'nama_toko' => $toko->nama_toko,
                'alamat'    => $toko->alamat,
                'nama_tim'  => $po[0]->penjualan->salesman->tim->nama_tim
            ];

            foreach ($po as $detail) {
                if ($detail->qty == 0 && $detail->qty_pcs == 0) {
                    continue;
                }

                $barang_extra = [];
                //Filter Barang Extra
                if ($request->barang_extra == 'include') {
                    if ($po[0]->promo) {
                        $id_promo_barang = $po[0]->promo->id_barang;
                        $extra = DetailPenjualan::with('stock')
                                                ->where('id_penjualan', $po[0]->id_penjualan)
                                                ->whereHas('stock', function ($q) use ($id_promo_barang) {
                                                    return $q->where('id_barang', $id_promo_barang);
                                                })
                                                ->first();
                        if ($extra) {
                            if ($extra->stock) {
                                $isiExtra       = $extra->stock->barang->isi;
                                $totalExtra     = $extra->qty_pcs + ($extra->qty * $isiExtra);
                                $qtyExtra       = floor($totalExtra/$isiExtra);
                                $qtyPcsExtra    = $totalExtra % $isiExtra;
                                $barang_extra = [
                                    'kode_barang' => $extra->stock->barang->kode_barang,
                                    'total_bonus' => $totalExtra.' pcs',
                                    'bonus_qty' => $qtyExtra,
                                    'bonus_qty_pcs' => $qtyPcsExtra,
                                ];
                                $total_qty_pcs += $qtyPcsExtra;
                                $total_qty += $qtyExtra;
                            }
                        }
                    }
                }

                $data[$key]['items'][] = [
                    'kode_barang'   => $detail->kode_barang,
                    'qty'           => $detail->qty,
                    'qty_pcs'       => $detail->qty_pcs,
                    'discount'      => round($detail->discount, 2),
                    'ppn'           => round($detail->discount * 0.1, 2),
                    'discount_inc_tax' => round($detail->discount * 1.1, 2),
                    'barang_extra'  => $barang_extra,
                ];

                $total_qty_pcs += $detail->qty_pcs;
                $total_qty += $detail->qty;
            }
        }

        return response()->json([
            'start_date' => $start_date,
            'end_date' => $end_date,
            'promo' => $promo,
            'team' => strtoupper($nama_team),
            'data' => $data,
            'total_qty' => $total_qty,
            'total_qty_pcs' => $total_qty_pcs,
        ]);
    }

    public function ec_per_item(Request $request)
    {
        $id_salesman = $request->has('id_salesman') ? $request->id_salesman : $this->user->id;
        $start_date  = $request->has('start_date') ? $request->start_date : date('Y-m-d');
        $end_date    = $request->has('end_sate') ? $request->end_date : date('Y-m-d');
        $id_barang   = $request->has('id_barang') ? $request->id_barang : '';

        // Jumlah toko
        // $jumlah_toko = Penjualan::select('id_toko')
        //                   ->whereBetween('created_at', [$start_date, $end_date])
        //                   ->where('id_salesman', $id_salesman)
        //                   ->get();

        // $jumlah_toko = $jumlah_toko->unique('id_toko')->count();
        $jumlah_toko = 0;
        $report = [];
        $barang = Barang::when($id_barang <> '', function ($q) use ($id_barang) {
            $id_barang = explode(',', $id_barang);
            return $q->whereIn('id', $id_barang);
        })->get();

        foreach ($barang as $key => $brg) {
            $id_barang = $brg->id;
            $penjualan = Penjualan::with(['detail_penjualan' => function ($q) use ($id_barang) {
                $q->with(['stock'])->whereHas('stock', function ($q) use ($id_barang) {
                    $q->where('id_barang', $id_barang);
                });
            }])
                ->whereHas('detail_penjualan.stock', function ($q) use ($id_barang) {
                    $q->where('id_barang', $id_barang);
                })
                ->whereBetween('tanggal', [$start_date, $end_date])
                ->where('id_salesman', $id_salesman)
                ->get();
            $total_pcs   = 0;
            if ($penjualan->count() > 0) {
                $jumlah_toko = $penjualan->unique('id_toko')->count();
                $in_carton = $penjualan->sum(function ($penjualan) {
                    return $penjualan->detail_penjualan->sum('qty');
                });

                $in_pcs = $penjualan->sum(function ($penjualan) {
                    return $penjualan->detail_penjualan->sum('qty_pcs');
                });

                $total_pcs   = ($in_carton * $brg->isi) + $in_pcs;
            }

            $report[] = [
                'id_barang'     => $brg->id,
                'kode_barang'   => $brg->kode_barang,
                'nama_barang'   => $brg->nama_barang,
                'total_pcs'     => $total_pcs,
                'jumlah_toko'   => $jumlah_toko
            ];

            $jumlah_toko    = 0;
            $total_pcs   = 0;
        }

        return response()->json($report, 200);
    }

    public function report_by_salesman_global(Request $request)
    {
        $this->validate($request, [
            'id_salesman'   => 'required',
            'tanggal_awal'  => 'required|date',
            'tanggal_akhir' => 'required|date',
            'tipe_pembayaran' => 'required|in:all,cash,credit'
        ]);

        $id_depo = $request->depo != null ? $request->depo : Helper::depoIDByUser($this->user->id);
        $id_salesman = explode(',', $request->id_salesman);
        $id_mitra = $request->has('id_mitra') ? $request->id_mitra : 'include';

        if (in_array("all", $id_salesman)) {
            $id_salesman = Salesman::when($id_depo <> '', function ($q) use ($id_depo) {
                        return $q->whereHas('tim', function ($q) use ($id_depo) {
                            return $q->whereIn('id_depo', $id_depo);
                        });
                    })
                    ->pluck('user_id');
            $salesman       = 'ALL TEAM';
            $nama_salesman  = 'ALL TEAM';
            $tim            = 'ALL TEAM';
        }
        else
        {
            $salesman       = Salesman::find($id_salesman[0]);
            $nama_salesman  = $salesman->user->name;
            $tim            = $salesman->tim->nama_tim;
        }

        $tanggal_awal   = $request->tanggal_awal;
        $tanggal_akhir  = $request->tanggal_akhir;
        //$id_salesman    = $id_salesman;
        $tipe_pembayaran = $request->tipe_pembayaran;

        $list_penjualan = Penjualan::select('id', 'no_invoice', 'tanggal', 'id_toko', 'tipe_pembayaran', 'delivered_at', 'id_perusahaan', 'id_tim')
            ->with(['toko:id,no_acc,cust_no,nama_toko,status_verifikasi,id_mitra', 'toko.mitra:id,kode_mitra', 'tim'])
            ->whereIn('id_salesman', $id_salesman)
            ->whereBetween('delivered_at', [$tanggal_awal . " 00:00:00", $tanggal_akhir . " 23:59:59"])
            ->whereIn('status', ['delivered'])
            ->when($tipe_pembayaran <> 'all', function ($q) use ($tipe_pembayaran) {
                return $q->where('tipe_pembayaran', $tipe_pembayaran);
            })
            ->when($id_mitra <> 'exclude' && $id_mitra <> 'include', function ($q) use ($id_mitra) {
                $q->where('id_mitra', $id_mitra);
            })
            ->when($id_mitra == 'exclude', function ($q) {
                $q->where('id_mitra', '=', 0);
            })
            ->orderBy('no_invoice')
            ->get();

        $id_perusahaan      = $list_penjualan[0]->id_perusahaan;
        $perusahaan         = Perusahaan::find($id_perusahaan);
        $list_penjualan_id  = $list_penjualan->pluck('id');
        $total_invoce       = $list_penjualan->unique('id')->count();
        $total_toko         = $list_penjualan->unique('id_toko')->count();
        $detail_penjualan   = ViewDetailPenjualan::whereIn('id', $list_penjualan_id)->get();
        $detail_penjualan_brand   = $detail_penjualan->groupBy('id_brand');
        $header_lph = $perusahaan->nama_perusahaan;
        if (is_numeric($id_mitra)) {
            $mitra      = Mitra::find($id_mitra);
            $header_lph = $mitra->perusahaan ?? '-';
        }

        $logData = [
            'action' => 'Download LPH Tim ' . $tim . ' ' . $nama_salesman,
            'description' => 'Tanggal ' . $tanggal_awal . ' sampai ' . $tanggal_akhir,
            'user_id' => $this->user->id
        ];

        $this->log($logData);

        // Excel Report
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Sales Report (Summary)');
        $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        $sheet->getPageMargins()->setTop(0.3);
        $sheet->getPageMargins()->setRight(0.3);
        $sheet->getPageMargins()->setLeft(0.3);
        $sheet->getPageMargins()->setBottom(0.3);
        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->getPageSetup()->setFitToHeight(0);

        $i = 1;
        $sheet->setCellValue('A' . $i, $header_lph);
        $sheet->getStyle('A' . $i)->getFont()->setBold(true);
        $sheet->setCellValue('K' . $i, 'Outlet');
        $sheet->getStyle('L' . $i)->getFont()->setBold(true);
        $sheet->mergeCells('L' . $i . ':M' . $i);
        $sheet->getStyle('L' . $i . ':M' . $i)->applyFromArray($this->border());
        $i++;
        $sheet->setCellValue('A' . $i, 'Sales Report By Salesman (Global)');
        $sheet->getStyle('A' . $i)->getFont()->setBold(true);
        $sheet->setCellValue('K' . $i, 'Curr');
        $sheet->setCellValue('L' . $i, 'Rupiah');
        $sheet->getStyle('K' . $i)->getFont()->setBold(true);
        $sheet->mergeCells('L' . $i . ':M' . $i);
        $sheet->getStyle('L' . $i . ':M' . $i)->applyFromArray($this->border());
        $i++;
        $tanggal_awal_text  = date('d M Y', strtotime($tanggal_awal));
        $tanggal_akhir_text = date('d M Y', strtotime($tanggal_akhir));
        $sheet->setCellValue('A' . $i, 'Delivery at ' . $tanggal_awal_text . ' s.d. ' . $tanggal_akhir_text);
        $sheet->getStyle('A' . $i)->getFont()->setBold(true);
        $sheet->setCellValue('K' . $i, 'Stock Type');
        $sheet->getStyle('K' . $i)->getFont()->setBold(true);
        $sheet->mergeCells('L' . $i . ':M' . $i);
        $sheet->getStyle('L' . $i . ':M' . $i)->applyFromArray($this->border());
        $i++;
        $i++;
        $sheet->setCellValue('A' . $i, 'Salesman');
        $sheet->setCellValue('C' . $i, $tim . " - " . $nama_salesman);
        $i++;

        //COLUMN WIDTH
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(15);
        $sheet->getColumnDimension('C')->setWidth(12);
        $sheet->getColumnDimension('D')->setAutoSize(true);
        $sheet->getColumnDimension('E')->setAutoSize(true);
        $sheet->getColumnDimension('F')->setAutoSize(true);
        $sheet->getColumnDimension('G')->setAutoSize(true);
        $sheet->getColumnDimension('H')->setAutoSize(true);
        $sheet->getColumnDimension('I')->setAutoSize(true);
        $sheet->getColumnDimension('J')->setAutoSize(true);
        $sheet->getColumnDimension('K')->setAutoSize(true);
        $sheet->getColumnDimension('L')->setAutoSize(true);
        $sheet->getColumnDimension('M')->setAutoSize(true);
        $sheet->getColumnDimension('N')->setAutoSize(true);

        if ($salesman == 'ALL TEAM') {
            $end_style = 'O';
            $cells  = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N','O'];
            $columns = ['No', 'Inv. No', 'Tanggal PO', 'Tim', 'Customer', 'Account 1', 'Account 2', 'Sub Total', 'Disc', 'Pajak (%)', 'Grand Total', 'Kontan', 'Kredit', 'Giro', 'Mitra'];
        }
        else{
            $end_style = 'N';
            $cells  = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N'];
            $columns = ['No', 'Inv. No', 'Tanggal PO', 'Customer', 'Account 1', 'Account 2', 'Sub Total', 'Disc', 'Pajak (%)', 'Grand Total', 'Kontan', 'Kredit', 'Giro', 'Mitra'];
        }

        $sheet->getStyle('A' . $i . ':'.$end_style . $i)->getFont()->setBold(true);
        $sheet->getStyle('A' . $i . ':'.$end_style . $i)->applyFromArray($this->horizontalCenter());
        $sheet->getStyle('A' . $i . ':'.$end_style . $i)->applyFromArray($this->border());
        foreach ($columns as $key => $column) {
            $sheet->setCellValue($cells[$key] . $i, $column);
        }

        $sheet->setAutoFilter('A' . $i . ':'.$end_style . $i);

        $i++;
        $start = $i;
        foreach ($list_penjualan as $key => $penjualan) {
            $kontan = $penjualan->tipe_pembayaran == 'cash' ? $penjualan->grand_total : 0;
            $kredit = $penjualan->tipe_pembayaran == 'credit' ? $penjualan->grand_total : 0;
            $giro   = $penjualan->tipe_pembayaran == 'bg' ? $penjualan->grand_total : 0;
            $noo    = $penjualan->toko->status_verifikasi == 'Y' ? 0 : 1;
            $mitra  = $penjualan->id_mitra == 0 ? '':$penjualan->mitra->kode_mitra;
            $sheet->getRowDimension($i)->setRowHeight(25);
            $sheet->setCellValue('A' . $i, $key + 1);
            $sheet->setCellValue('B' . $i, $penjualan->no_invoice);
            $sheet->setCellValue('C' . $i, $penjualan->tanggal);

            if ($salesman == 'ALL TEAM') {
                $sheet->setCellValue('D' . $i, $penjualan->nama_tim);
                $sheet->getStyle('D' . $i)->applyFromArray($this->horizontalCenter());
                $sheet->setCellValue('E' . $i, $penjualan->toko->nama_toko);
                $sheet->setCellValueExplicit('F' . $i, $penjualan->toko->no_acc, DataType::TYPE_STRING);
                $sheet->setCellValueExplicit('G' . $i, $penjualan->toko->cust_no, DataType::TYPE_STRING);
                $sheet->setCellValue('H' . $i, round($penjualan->total, 2));
                $sheet->setCellValue('I' . $i, round($penjualan->disc_total, 2));
                $sheet->setCellValue('J' . $i, round($penjualan->ppn, 2));
                $sheet->setCellValue('K' . $i, round($penjualan->grand_total, 2));
                $sheet->setCellValue('L' . $i, round($kontan, 2));
                $sheet->setCellValue('M' . $i, round($kredit, 2));
                $sheet->setCellValue('N' . $i, $giro);
                $sheet->setCellValue('O' . $i, $mitra);
            }
            else{
                $sheet->setCellValue('D' . $i, $penjualan->toko->nama_toko);
                $sheet->setCellValueExplicit('E' . $i, $penjualan->toko->no_acc, DataType::TYPE_STRING);
                $sheet->setCellValueExplicit('F' . $i, $penjualan->toko->cust_no, DataType::TYPE_STRING);
                $sheet->setCellValue('G' . $i, round($penjualan->total, 2));
                $sheet->setCellValue('H' . $i, round($penjualan->disc_total, 2));
                $sheet->setCellValue('I' . $i, round($penjualan->ppn, 2));
                $sheet->setCellValue('J' . $i, round($penjualan->grand_total, 2));
                $sheet->setCellValue('K' . $i, round($kontan, 2));
                $sheet->setCellValue('L' . $i, round($kredit, 2));
                $sheet->setCellValue('M' . $i, $giro);
                $sheet->setCellValue('N' . $i, $mitra);
            }
            $i++;
        }

        $sheet->getStyle('A' . $i)->getFont()->setBold(true);
        $sheet->setCellValue('A' . $i, 'Grand Total');
        $sheet->mergeCells('A' . $i . ':B' . $i);
        $sheet->setCellValue('G' . $i, '=SUM(G' . $start . ':G' . ($i - 1) . ')');
        $sheet->setCellValue('H' . $i, '=SUM(H' . $start . ':H' . ($i - 1) . ')');
        $sheet->setCellValue('I' . $i, '=SUM(I' . $start . ':I' . ($i - 1) . ')');
        $sheet->setCellValue('J' . $i, '=SUM(J' . $start . ':J' . ($i - 1) . ')');
        $sheet->setCellValue('K' . $i, '=SUM(K' . $start . ':K' . ($i - 1) . ')');
        $sheet->setCellValue('L' . $i, '=SUM(L' . $start . ':L' . ($i - 1) . ')');
        $sheet->setCellValue('M' . $i, '=SUM(M' . $start . ':M' . ($i - 1) . ')');
        $sheet->getStyle('A' . $i . ':M' . $i)->getFont()->setBold(true);
        $sheet->getStyle('G' . $start . ':M' . $i)->getNumberFormat()->setFormatCode('_(* #,##0_);_(* (#,##0);_(* "-"??_);_(@_)');
        $sheet->getStyle('A' . $start . ':A' . $i)->applyFromArray($this->horizontalCenter());
        $sheet->getStyle('E' . $start . ':E' . $i)->applyFromArray($this->horizontalRight());
        $sheet->getStyle('A1:I' . $i)->applyFromArray($this->fontSize(12));
        $sheet->getColumnDimension('N')->setVisible(false);
        $sheet->getColumnDimension('M')->setVisible(false);

        // DETAIL PER ITEM
        $spreadsheet->createSheet();
        $sheet = $spreadsheet->setActiveSheetIndex(1);
        $sheet->getPageMargins()->setTop(0.3);
        $sheet->getPageMargins()->setRight(0.3);
        $sheet->getPageMargins()->setLeft(0.3);
        $sheet->getPageMargins()->setBottom(0.3);
        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->getPageSetup()->setFitToHeight(0);
        $sheet->setTitle('Detail Item');
        $i = 1;
        $sheet->setCellValue('A' . $i, $header_lph);
        $sheet->getStyle('A' . $i)->getFont()->setBold(true);
        $i++;
        $sheet->setCellValue('A' . $i, 'Sales Accumulative Report by Item');
        $sheet->getStyle('A' . $i)->getFont()->setBold(true);
        $i++;
        $sheet->setCellValue('A' . $i, 'Periode ' . $tanggal_awal_text . ' s.d. ' . $tanggal_akhir_text);
        $sheet->getStyle('A' . $i)->getFont()->setBold(true);
        $sheet->setCellValue('F' . $i, $tim . " - " . $nama_salesman);
        $sheet->mergeCells('F' . $i . ':H' . $i);
        $sheet->getStyle('F' . $i)->getFont()->setBold(true);
        $i++;
        $cells  = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
        $columns = ['Code', '', 'Qty', '', 'Sub Total', 'Disc', 'PPn', 'Balance'];
        $sheet->getColumnDimension('A')->setWidth(22);
        $sheet->getColumnDimension('B')->setWidth(46);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setAutoSize(true);
        $sheet->getColumnDimension('G')->setWidth(15);
        $sheet->getColumnDimension('H')->setWidth(16);
        foreach ($columns as $key => $column) {
            $sheet->setCellValue($cells[$key] . $i, $column);
        }
        //SYLING HEADER
        $sheet->mergeCells('A' . $i . ':B' . ($i + 1));
        $sheet->mergeCells('C' . $i . ':D' . $i);
        $sheet->mergeCells('E' . $i . ':E' . ($i + 1));
        $sheet->mergeCells('F' . $i . ':F' . ($i + 1));
        $sheet->mergeCells('G' . $i . ':G' . ($i + 1));
        $sheet->mergeCells('H' . $i . ':H' . ($i + 1));
        $sheet->getStyle('A' . $i . ':H' . $i)->getFont()->setBold(true);
        $sheet->getStyle('A' . $i . ':H' . $i)->applyFromArray($this->horizontalCenter());
        $sheet->getStyle('C' . ($i + 1) . ':D' . ($i + 1))->getFont()->setBold(true);
        $sheet->getStyle('C' . ($i + 1) . ':D' . ($i + 1))->applyFromArray($this->horizontalCenter());
        $sheet->getStyle('A' . $i . ':H' . ($i + 1))->applyFromArray($this->verticalCenter());
        $sheet->getStyle('A' . $i . ':H' . ($i + 1))->applyFromArray($this->border());
        // END STYLING HEADER

        $i++;
        $sheet->setCellValue('C' . $i, 'Dus');
        $sheet->setCellValue('D' . $i, 'Pcs');

        $i++;
        $start          = $i;
        $total_carton   = [];
        $total_pcs      = [];
        $total_sub_total = [];
        $total_disc     = [];
        $total_ppn      = [];
        $total_balance  = [];

        foreach ($detail_penjualan_brand as $key => $detail) {
            $sheet->getRowDimension($i)->setRowHeight(25);
            $sheet->setCellValue('A' . $i, 'Group: ' . $detail_penjualan_brand[$key][0]->nama_brand);
            $per_barang = $detail->groupBy('id_barang');
            $start_per_barang = $i + 1;
            $kode_barang = [];
            foreach ($per_barang as $key => $barang) {
                $i++;
                $sheet->getRowDimension($i)->setRowHeight(25);
                $carton     = $barang->sum('qty');
                $pcs        = $barang->sum('qty_pcs');
                $subtotal   = $barang->sum('subtotal');
                $discount   = $barang->sum('discount');
                $ppn        = $barang->sum('ppn');
                $isi        = $barang[0]->isi;
                $in_pcs     = $carton * $isi + $pcs;
                $carton     = $in_pcs >= $isi ? floor($in_pcs/$isi) : 0;
                $pcs        = $in_pcs % $isi;

                $kode_barang[] = $barang[0]->kode_barang;
                $sheet->setCellValue('A' . $i, $barang[0]->kode_barang);
                $sheet->setCellValue('B' . $i, $barang[0]->nama_barang);
                $sheet->setCellValue('C' . $i, $carton);
                $sheet->setCellValue('D' . $i, $pcs);
                $sheet->setCellValue('E' . $i, $subtotal);
                $sheet->setCellValue('F' . $i, $discount);
                $sheet->setCellValue('G' . $i, $ppn);
                $sheet->setCellValue('H' . $i, (($subtotal - $discount) + $ppn));
                $sheet->getStyle('A' . $i . ':H' . $i)->applyFromArray($this->borderBottom());
            }

            $invoice = $detail_penjualan->whereIn('kode_barang', $kode_barang)->unique('id')->count();
            $toko   = $detail_penjualan->whereIn('kode_barang', $kode_barang)->unique('id_toko')->count();
            $i++;
            $sheet->setCellValue('A' . $i, 'Subtotal :');
            $sheet->setCellValue('B' . $i, 'Invoice = ' . $invoice . ' Customer = ' . $toko);
            $sheet->setCellValue('C' . $i, '=SUM(C' . $start_per_barang . ':C' . ($i - 1) . ')');
            $total_carton[]     = "C" . $i;
            $total_pcs[]        = "D" . $i;
            $total_sub_total[]  = "E" . $i;
            $total_disc[]       = "F" . $i;
            $total_ppn[]        = "G" . $i;
            $total_balance[]    = "H" . $i;
            $sheet->setCellValue('D' . $i, '=SUM(D' . $start_per_barang . ':D' . ($i - 1) . ')');
            $sheet->setCellValue('E' . $i, '=SUM(E' . $start_per_barang . ':E' . ($i - 1) . ')');
            $sheet->setCellValue('F' . $i, '=SUM(F' . $start_per_barang . ':F' . ($i - 1) . ')');
            $sheet->setCellValue('G' . $i, '=SUM(G' . $start_per_barang . ':G' . ($i - 1) . ')');
            $sheet->setCellValue('H' . $i, '=SUM(H' . $start_per_barang . ':H' . ($i - 1) . ')');
            $sheet->getStyle('A' . $i . ':H' . $i)->applyFromArray($this->verticalCenter());
            $sheet->getStyle('A' . $i . ':H' . $i)->getFont()->setBold(true);
            $sheet->getStyle('E' . $start . ':H' . $i)->getNumberFormat()->setFormatCode('_(* #,##0_);_(* (#,##0);_(* "-"??_);_(@_)');
            $i++;
        }

        $sheet->setCellValue('A' . $i, 'Grand Total');
        $sheet->setCellValue('B' . $i, 'Invoice = ' . $total_invoce . ' Customer = ' . $total_toko);
        $sheet->setCellValue('C' . $i, "=" . implode("+", $total_carton));
        $sheet->setCellValue('D' . $i, "=" . implode("+", $total_pcs));
        $sheet->setCellValue('E' . $i, "=" . implode("+", $total_sub_total));
        $sheet->setCellValue('F' . $i, "=" . implode("+", $total_disc));
        $sheet->setCellValue('G' . $i, "=" . implode("+", $total_ppn));
        $sheet->setCellValue('H' . $i, "=" . implode("+", $total_balance));
        $sheet->getStyle('A' . $i . ':H' . $i)->getFont()->setBold(true);
        $sheet->getStyle('A1:H' . $i)->applyFromArray($this->fontSize(14));
        $sheet->getStyle('A1:H' . $i)->applyFromArray($this->verticalCenter());
        $sheet->getStyle('E' . $i . ':H' . $i)->getNumberFormat()->setFormatCode('_(* #,##0_);_(* (#,##0);_(* "-"??_);_(@_)');
        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        $content = ob_get_contents();
        ob_end_clean();
        $fileName = "{$tim}_{$tanggal_awal_text}.xlsx";
        Storage::disk('local')->put('excel/' . $fileName, $content);
        $file = url('/excel/' . $fileName);
        return response()->json($file, 200);
    }


    public function posisi_stock_gudang_2(Request $request)
    {

        if ($this->user->can('Menu Posisi Stock Gudang')) :
            if ($request->id_gudang == '') {
                return response()->json([
                    'message' => 'Pilih Gudang terlebih dahulu!'
                ], 400);
            }

            if ($request->start_date == '') {
                return response()->json([
                    'message' => 'Pilih Tanggal stock awal yang ingin dicari!'
                ], 400);
            }

            $allowed = Reference::where('code', 'download_stock')->first();
            if($allowed['value'] != '-') {
                $gudang = explode(',', $allowed['value']);
                if(!in_array($request->id_gudang, $gudang)) {
                    return response()->json([
                        'message' => 'Jam sibuk, sementara fitur ini belum bisa diakses'
                    ], 400);
                }
            }


            $id_gudang      = $request->id_gudang;
            $tanggal        = $request->start_date;
            $tanggal_akhir  = $request->end_date;
            $id_principal = $request->has('id_principal') && count($request->id_principal) > 0
                ? $request->id_principal : Helper::principalByUser($this->user->id);
            $id_barang  = $request->has('id_barang') && count($request->id_barang) > 0 ? $request->id_barang : '';
            $id_brand   = $request->has('id_brand') && count($request->id_brand) > 0
                ? $request->id_brand : '';

            $logData = [
                'action' => 'Posisi Stock Gudang',
                'description' => 'Tanggal ' . $tanggal . ' sampai ' . $tanggal_akhir,
                'user_id' => $this->user->id
            ];

            $this->log($logData);

            $gudang = Gudang::find($id_gudang);

            $barang = DB::table('barang')
                ->join('segmen', 'barang.id_segmen', 'segmen.id')
                ->join('brand', 'segmen.id_brand', 'brand.id')
                ->join('stock', 'stock.id_barang', 'barang.id')
                ->when($id_barang <> '', function ($q) use ($id_barang) {
                    return $q->whereIn('barang.id', $id_barang);
                })
                ->when($id_brand <> '', function ($q) use ($id_brand) {
                    return $q->whereIn('segmen.id_brand', $id_brand);
                })
                ->when(count($id_principal) > 0, function ($q) use ($id_principal){
                    return $q->whereIn('brand.id_principal', $id_principal);
                })
                ->whereNull('barang.deleted_at')
                ->whereNull('stock.deleted_at')
                ->where('stock.id_gudang', '=', $id_gudang)
                ->select(
                    'barang.id',
                    'barang.kode_barang',
                    'barang.nama_barang',
                    'barang.isi',
                    'segmen.id_brand',
                    'segmen.nama_segmen'
                )
                ->orderBy('barang.kode_barang')->get();

            $id_barang = $barang->pluck('id');

            $stock_awal = LogStock::whereDate('tanggal', '<', $tanggal)
                ->where('id_gudang', $id_gudang)
                ->whereIn('id_barang', $id_barang)
                ->whereIn('status', ['approved', 'delivered', 'received'])
                ->select('id_barang', 'referensi', 'status', DB::raw('SUM(qty_pcs) as qty_pcs'))
                ->groupBy('id_barang', 'referensi', 'status')->get();

            $stock_today = LogStock::whereBetween('tanggal',  [$tanggal, $tanggal_akhir])
                ->where('id_gudang', $id_gudang)
                ->whereIn('id_barang', $id_barang)
                ->select('id_barang', 'referensi', 'status', DB::raw('SUM(qty_pcs) as qty_pcs'))
                ->groupBy('id_barang', 'referensi', 'status')->get();

            $detail_sales_pending = DB::table('detail_penjualan AS a')
                ->join('penjualan AS b', 'a.id_penjualan', 'b.id')
                ->join('stock AS c', 'a.id_stock', 'c.id')
                ->where('c.id_gudang', $id_gudang)
                ->whereIn('c.id_barang', $id_barang)
                ->where('b.tanggal_invoice', '<=', $tanggal_akhir)
                ->where('b.tanggal', '>', '2020-09-18')
                ->whereIn('b.status', ['approved', 'loaded'])
                ->whereNull('b.deleted_at')
                ->select('c.id_barang', 'b.status', DB::raw('SUM(a.qty) AS qty'), DB::raw('SUM(a.qty_pcs) AS qty_pcs'))
                ->groupBy('c.id_barang', 'b.status')->get();

            $detail_mutasi_pending = DB::table('detail_mutasi_barang AS b')
                ->join('mutasi_barang AS a', 'a.id', 'b.id_mutasi_barang')
                ->join('stock AS c', 'b.id_stock', '=', 'c.id')
                ->where('a.dari', $id_gudang)
                ->whereIn('c.id_barang', $id_barang)
                ->where('a.is_approved', 1)
                ->whereIn('a.status', ['approved', 'on the way'])
                ->whereDate('a.tanggal_mutasi', '<=', $tanggal_akhir)
                ->where('a.tanggal_mutasi', '>', '2020-09-18')
                ->whereNull('a.deleted_at')
                ->select('c.id_barang', DB::raw('SUM(b.qty) AS qty'), DB::raw('SUM(b.qty_pcs) AS qty_pcs '))
                ->groupBy('c.id_barang', 'a.status')->get();
//            $harga_brg = HargaBarang::where('tipe_harga', 'dbp')->select('id_barang','harga')->get(); // default DBP

            $id_barang      = implode(',', array_values($barang->pluck('id')->unique()->toArray()));
            $harga_barang   = DB::select("SELECT a.id_barang, a.harga FROM harga_barang a WHERE
                           created_at = (
                               SELECT MAX(created_at) FROM harga_barang b WHERE a.id_barang = b.id_barang AND b.tipe_harga = 'dbp' AND id_barang IN ($id_barang)
                            ) AND tipe_harga = 'dbp' AND id_barang IN ($id_barang)");
            $harga_brg      = collect($harga_barang);

            $data = [];
            foreach ($barang as $b) {
                $saldo_awal_dus = 0;
                $saldo_awal_pcs = 0;

                $saldo_awal = $stock_awal->where('id_barang', $b->id)->where('referensi', 'stock awal');
                if (!$saldo_awal->isEmpty()) {
                    $saldo_awal_pcs += $saldo_awal->first()->qty_pcs;
                }

                $penerimaan_awal = $stock_awal->where('id_barang', $b->id)->where('referensi', 'penerimaan barang');
                if (!$penerimaan_awal->isEmpty()) {
                    $saldo_awal_pcs += $penerimaan_awal->first()->qty_pcs;
                }

                $mutasi_masuk_awal = $stock_awal->where('id_barang', $b->id)->where('referensi', 'mutasi masuk')->where('status', 'received');
                if (!$mutasi_masuk_awal->isEmpty()) {
                    $saldo_awal_pcs += $mutasi_masuk_awal->first()->qty_pcs;
                }

                $penjualan_awal = $stock_awal->where('id_barang', $b->id)->where('referensi', 'penjualan')->where('status', 'delivered');
                if (!$penjualan_awal->isEmpty()) {
                    $saldo_awal_pcs -= $penjualan_awal->first()->qty_pcs;
                }

                $mutasi_keluar_awal = $stock_awal->where('id_barang', $b->id)->where('referensi', 'mutasi keluar')->where('status', 'received');
                if (!$mutasi_keluar_awal->isEmpty()) {
                    $saldo_awal_pcs -= $mutasi_keluar_awal->first()->qty_pcs;
                }

                $adjustment_awal = $stock_awal->where('id_barang', $b->id)->where('referensi', 'adjustment');
                if (!$adjustment_awal->isEmpty()) {
                    $saldo_awal_pcs += $adjustment_awal->first()->qty_pcs;
                }

                $retur_awal = $stock_awal->where('id_barang', $b->id)->where('referensi', 'retur');
                if (!$retur_awal->isEmpty()) {
                    $saldo_awal_pcs += $retur_awal->first()->qty_pcs;
                }

                $sales_pending_dus = 0;
                $sales_pending_pcs = 0;

                $mutasi_pending_dus = 0;
                $mutasi_pending_pcs = 0;

                $loaded_dus = 0;
                $loaded_pcs = 0;

                $penerimaan_dus = 0;
                $penerimaan_pcs = 0;

                $mutasi_masuk_dus = 0;
                $mutasi_masuk_pcs = 0;

                $adjustment_dus = 0;
                $adjustment_pcs = 0;

                $mutasi_keluar_dus = 0;
                $mutasi_keluar_pcs = 0;

                $deliver_dus = 0;
                $deliver_pcs = 0;

                $retur_dus = 0;
                $retur_pcs = 0;

                $all_approved_dus = 0;
                $all_approved_pcs = 0;

                $all_loaded_dus = 0;
                $all_loaded_pcs = 0;

                $penerimaan = $stock_today->where('id_barang', $b->id)->where('referensi', 'penerimaan barang');
                if (!$penerimaan->isEmpty()) {
                    $penerimaan_pcs += $penerimaan->first()->qty_pcs;
                }

                $mutasi_masuk = $stock_today->where('id_barang', $b->id)->where('referensi', 'mutasi masuk')->where('status', 'received');
                if (!$mutasi_masuk->isEmpty()) {
                    $mutasi_masuk_pcs += $mutasi_masuk->first()->qty_pcs;
                }

                $penjualan_approved = $stock_today->where('id_barang', $b->id)->where('referensi', 'penjualan')->where('status', 'approved');
                if (!$penjualan_approved->isEmpty()) {
                    $sales_pending_pcs += $penjualan_approved->first()->qty_pcs;
                }

                $penjualan_loaded = $stock_today->where('id_barang', $b->id)->where('referensi', 'penjualan')->where('status', 'loaded');
                if (!$penjualan_loaded->isEmpty()) {
                    $loaded_pcs += $penjualan_loaded->first()->qty_pcs;
                }

                $penjualan_delivered = $stock_today->where('id_barang', $b->id)->where('referensi', 'penjualan')->where('status', 'delivered');
                if (!$penjualan_delivered->isEmpty()) {
                    $deliver_pcs += $penjualan_delivered->first()->qty_pcs;
                }


                $mutasi_keluar_received = $stock_today->where('id_barang', $b->id)->where('referensi', 'mutasi keluar')->where('status', 'received');
                if (!$mutasi_keluar_received->isEmpty()) {
                    $mutasi_keluar_pcs += $mutasi_keluar_received->first()->qty_pcs;
                }

                $adjustment = $stock_today->where('id_barang', $b->id)->where('referensi', 'adjustment');
                if (!$adjustment->isEmpty()) {
                    $adjustment_pcs += $adjustment->first()->qty_pcs;
                }

                $retur = $stock_today->where('id_barang', $b->id)->where('referensi', 'retur');
                if (!$retur->isEmpty()) {
                    $retur_pcs += $retur->first()->qty_pcs;
                }

                $all_approved = $detail_sales_pending->where('id_barang', $b->id)->where('status', 'approved');
                if (!$all_approved->isEmpty()) {
                    $all_approved_pcs += ($all_approved->first()->qty * $b->isi) + $all_approved->first()->qty_pcs;
                }

                $all_loaded = $detail_sales_pending->where('id_barang', $b->id)->where('status', 'loaded');
                if (!$all_loaded->isEmpty()) {
                    $all_loaded_pcs += ($all_loaded->first()->qty * $b->isi) + $all_loaded->first()->qty_pcs;
                }

                $mutasi_keluar_pending = $detail_mutasi_pending->where('id_barang', $b->id);
                if (!$mutasi_keluar_pending->isEmpty()) {
                    $mutasi_pending_pcs += ($mutasi_keluar_pending->first()->qty * $b->isi) + $mutasi_keluar_pending->first()->qty_pcs;
                }

                if (!($saldo_awal_pcs == 0 && $sales_pending_pcs == 0 && $loaded_pcs == 0 && $mutasi_pending_pcs == 0 && $penerimaan_pcs == 0 && $mutasi_masuk_pcs == 0 && $adjustment_pcs == 0 && $mutasi_keluar_pcs == 0 && $deliver_pcs == 0 && $retur_pcs == 0)) {
                    $harga = $harga_brg->where('id_barang', $b->id)->first(); // default DBP
                    $harga_barang = 0;
                    if ($harga) {
                        $harga_barang = $harga->harga / 1.1;
                    }

                    $saldo_fisik_dus = 0;
                    $saldo_fisik_pcs = $saldo_awal_pcs + $penerimaan_pcs + $mutasi_masuk_pcs + $adjustment_pcs - $mutasi_keluar_pcs - $deliver_pcs + $retur_pcs;

                    $saldo_akhir_dus = 0;
                    $saldo_akhir_pcs = $saldo_fisik_pcs - $all_approved_pcs - $all_loaded_pcs - $mutasi_pending_pcs;

                    if ($saldo_awal_pcs  >= $b->isi) {
                        $saldo_awal_dus = floor($saldo_awal_pcs/$b->isi);
                        $saldo_awal_pcs = $saldo_awal_pcs % $b->isi;
                    }

                    if ($sales_pending_pcs  >= $b->isi) {
                        $sales_pending_dus = floor($sales_pending_pcs/$b->isi);
                        $sales_pending_pcs = $sales_pending_pcs % $b->isi;
                    }

                    if ($loaded_pcs  >= $b->isi) {
                        $loaded_dus = floor($loaded_pcs/$b->isi);
                        $loaded_pcs = $loaded_pcs % $b->isi;
                    }

                    if ($mutasi_pending_pcs  >= $b->isi) {
                        $mutasi_pending_dus = floor($mutasi_pending_pcs/$b->isi);
                        $mutasi_pending_pcs = $mutasi_pending_pcs % $b->isi;
                    }

                    if ($penerimaan_pcs  >= $b->isi) {
                        $penerimaan_dus = floor($penerimaan_pcs/$b->isi);
                        $penerimaan_pcs = $penerimaan_pcs % $b->isi;
                    }

                    if ($mutasi_masuk_pcs  >= $b->isi) {
                        $mutasi_masuk_dus = floor($mutasi_masuk_pcs/$b->isi);
                        $mutasi_masuk_pcs = $mutasi_masuk_pcs % $b->isi;
                    }

                    if ($adjustment_pcs  >= $b->isi) {
                        $adjustment_dus = floor($adjustment_pcs/$b->isi);
                        $adjustment_pcs = $adjustment_pcs % $b->isi;
                    }

                    if ($mutasi_keluar_pcs  >= $b->isi) {
                        $mutasi_keluar_dus = floor($mutasi_keluar_pcs/$b->isi);
                        $mutasi_keluar_pcs = $mutasi_keluar_pcs % $b->isi;
                    }

                    if ($deliver_pcs  >= $b->isi) {
                        $deliver_dus = floor($deliver_pcs/$b->isi);
                        $deliver_pcs = $deliver_pcs % $b->isi;
                    }

                    if ($retur_pcs  >= $b->isi) {
                        $retur_dus = floor($retur_pcs/$b->isi);
                        $retur_pcs = $retur_pcs % $b->isi;
                    }

                    if ($saldo_fisik_pcs  >= $b->isi) {
                        $saldo_fisik_dus = floor($saldo_fisik_pcs/$b->isi);
                        $saldo_fisik_pcs = $saldo_fisik_pcs % $b->isi;
                    }

                    if ($all_approved_pcs  >= $b->isi) {
                        $all_approved_dus = floor($all_approved_pcs/$b->isi);
                        $all_approved_pcs = $all_approved_pcs % $b->isi;
                    }

                    if ($all_loaded_pcs  >= $b->isi) {
                        $all_loaded_dus = floor($all_loaded_pcs/$b->isi);
                        $all_loaded_pcs = $all_loaded_pcs % $b->isi;
                    }

                    if ($saldo_akhir_pcs  >= $b->isi) {
                        $saldo_akhir_dus = floor($saldo_akhir_pcs/$b->isi);
                        $saldo_akhir_pcs = $saldo_akhir_pcs % $b->isi;
                    }

                    $data[] = [
                        "id_gudang" => $id_gudang,
                        "nama_gudang" => $gudang->nama_gudang,
                        "tanggal" => $tanggal,
                        "nama_segmen" => $b->nama_segmen,
                        "id_brand" => $b->id_brand,
                        'kode_barang' => $b->kode_barang,
                        'nama_barang' => $b->nama_barang,
                        'isi' => $b->isi,
                        "saldo_awal" => $saldo_awal_dus,
                        "saldo_awal_pcs" => $saldo_awal_pcs,
                        "qty_sales_pending" => (int)$sales_pending_dus,
                        "qty_pcs_sales_pending" => (int)$sales_pending_pcs,
                        "qty_mutasi_pending" => (int)$mutasi_pending_dus,
                        "qty_pcs_mutasi_pending" => (int)$mutasi_pending_pcs,
                        "qty_penerimaan" => (int)$penerimaan_dus,
                        "qty_pcs_penerimaan" => (int)$penerimaan_pcs,
                        "qty_mutasi_masuk" => (int)$mutasi_masuk_dus,
                        "qty_pcs_mutasi_masuk" => (int)$mutasi_masuk_pcs,
                        "qty_adjustment" => (int)$adjustment_dus,
                        "qty_pcs_adjustment" => (int)$adjustment_pcs,
                        "qty_mutasi_keluar" => (int)$mutasi_keluar_dus,
                        "qty_pcs_mutasi_keluar" => (int)$mutasi_keluar_pcs,
                        "qty_loaded" => (int)$loaded_dus,
                        "qty_pcs_loaded" => (int)$loaded_pcs,
                        "qty_deliver" => (int)$deliver_dus,
                        "qty_pcs_deliver" => (int)$deliver_pcs,
                        "qty_retur" => (int)$retur_dus,
                        "qty_pcs_retur" => (int)$retur_pcs,
                        "all_approved_pcs" => (int)$all_approved_pcs,
                        "all_approved_dus" => (int)$all_approved_dus,
                        "all_loaded_pcs" => (int)$all_loaded_pcs,
                        "all_loaded_dus" => (int)$all_loaded_dus,
                        "stock_akhir" => (int)$saldo_akhir_dus,
                        "stock_akhir_pcs" => (int)$saldo_akhir_pcs,
                        "stock_fisik" => (int)$saldo_fisik_dus,
                        "stock_fisik_pcs" => (int)$saldo_fisik_pcs,
                        "harga" => $harga_barang,
                    ];
                }
            }

            return response()->json([
                "data" => $data
            ]);

        else :
            return $this->Unauthorized();
        endif;
    }

    public function print_posisi_stock_gudang(Request $request)
    {
        $data = [];
        $collection = collect($request->items);

        $brand = Brand::all();

        foreach ($brand as $b) {
            $coll = $collection->where('id_brand', $b->id);
            if (!$coll->isEmpty()) {
                $data[] = [
                    'id' => $b->id,
                    'nama_brand' => $b->nama_brand,
                    'detail' => $coll,
                    'saldo_awal' => $coll->sum('saldo_awal'),
                    'saldo_awal_pcs' => $coll->sum('saldo_awal_pcs'),
                    'qty_sales_pending' => $coll->sum('qty_sales_pending'),
                    'qty_pcs_sales_pending' => $coll->sum('qty_pcs_sales_pending'),
                    'qty_mutasi_pending' => $coll->sum('qty_mutasi_pending'),
                    'qty_pcs_mutasi_pending' => $coll->sum('qty_pcs_mutasi_pending'),
                    'qty_penerimaan' => $coll->sum('qty_penerimaan'),
                    'qty_pcs_penerimaan' => $coll->sum('qty_pcs_penerimaan'),
                    'qty_mutasi_masuk' => $coll->sum('qty_mutasi_masuk'),
                    'qty_pcs_mutasi_masuk' => $coll->sum('qty_pcs_mutasi_masuk'),
                    'qty_adjustment' => $coll->sum('qty_adjustment'),
                    'qty_pcs_adjustment' => $coll->sum('qty_pcs_adjustment'),
                    'qty_mutasi_keluar' => $coll->sum('qty_mutasi_keluar'),
                    'qty_pcs_mutasi_keluar' => $coll->sum('qty_pcs_mutasi_keluar'),
                    'qty_loaded' => $coll->sum('qty_loaded'),
                    'qty_pcs_loaded' => $coll->sum('qty_pcs_loaded'),
                    'qty_deliver' => $coll->sum('qty_deliver'),
                    'qty_pcs_deliver' => $coll->sum('qty_pcs_deliver'),
                    'qty_retur' => $coll->sum('qty_retur'),
                    'qty_pcs_retur' => $coll->sum('qty_pcs_retur'),
                    'all_approved_dus' => $coll->sum('all_approved_dus'),
                    'all_approved_pcs' => $coll->sum('all_approved_pcs'),
                    'all_loaded_dus' => $coll->sum('all_loaded_dus'),
                    'all_loaded_pcs' => $coll->sum('all_loaded_pcs'),
                    'stock_akhir' => $coll->sum('stock_akhir'),
                    'stock_akhir_pcs' => $coll->sum('stock_akhir_pcs'),
                    'stock_fisik' => $coll->sum('stock_fisik'),
                    'stock_fisik_pcs' => $coll->sum('stock_fisik_pcs'),
                    'nilai' => $coll->sum('nilai'),
                ];
            }
        }

        return response()->json([
            "data" => $data,
        ]);
    }

    public function mutasi_barang(Request $request)
    {
        if ($this->user->can('Menu Laporan Mutasi Barang')) :
            $mutasi_barang = DB::table('mutasi_barang AS a')
                ->join('detail_mutasi_barang AS b', 'a.id', 'b.id_mutasi_barang')
                ->join('gudang AS c', 'a.dari', 'c.id')
                ->join('gudang AS d', 'a.ke', 'd.id')
                ->join('stock AS e', 'b.id_stock', 'e.id')
                ->join('barang AS f', 'e.id_barang', 'f.id')
                ->join('segmen AS g', 'f.id_segmen', 'g.id')
                ->join('brand AS h', 'g.id_brand', 'h.id')
                ->select(
                    'a.tanggal_mutasi',
                    'a.tanggal_realisasi',
                    'a.id',
                    DB::raw('c.nama_gudang AS dari'),
                    DB::raw('d.nama_gudang AS ke'),
                    'f.item_code',
                    'f.kode_barang',
                    'f.nama_barang',
                    'g.nama_segmen',
                    'h.nama_brand',
                    'f.satuan',
                    'b.qty',
                    'b.qty_pcs',
                    'f.isi',
                    'a.status'
                );

            // Filter Gudang Asal (Dari)
            $id_depo = Helper::depoIDByUser($this->user->id);
            if ($request->has('dari')) {
                if ($request->dari == 'all' || $request->dari == '' || $request->dari == null) {
                    $mutasi_barang = $mutasi_barang->whereIn('c.id_depo', $id_depo);
                } else {
                    $mutasi_barang = $mutasi_barang->where('a.dari', $request->dari);
                }
            }

            // Filter Gudang Tujuan (Ke)
            if ($request->has('ke')) {
                if ($request->ke == 'all' || $request->ke == '' || $request->ke == null) {
                    $mutasi_barang = $mutasi_barang->whereIn('d.id_depo', $id_depo);
                } else {
                    $mutasi_barang = $mutasi_barang->where('a.ke', $request->ke);
                }
            }

            // Filter Date
            if ($request->has('date')) {
                $tanggal = $request->date;
                $mutasi_barang = $mutasi_barang->where(function ($query) use ($tanggal) {
                    $query->where('a.tanggal_realisasi', $tanggal . '%')
                        ->orWhere('a.tanggal_mutasi', $tanggal . '%');
                });
            } elseif ($request->has(['start_date', 'end_date'])) {
                $start_date = $request->start_date;
                $end_date = $request->end_date;
                $mutasi_barang = $mutasi_barang->where(function ($query) use ($start_date, $end_date) {
                    $query->whereBetween('a.tanggal_realisasi', [$start_date, $end_date])
                        ->orWhereBetween('a.tanggal_mutasi', [$start_date, $end_date]);
                });
            }

            // Filter Status
            if ($request->has('status') && $request->status != '' && $request->status != 'all') {
                $mutasi_barang = $mutasi_barang->where('a.status', $request->status);
            }

            $mutasi_barang = $mutasi_barang->orderBy('a.id')->get();

            return response()->json([
                "file" => $this->mutasi_barang_excel($mutasi_barang),
                "data" => $mutasi_barang,
            ]);
        else :
            return $this->Unauthorized();
        endif;
    }

    public function mutasi_barang_excel($mutasi)
    {
        //Create Tampilan Excel
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Detail Mutasi Barang');

        $i = 1;
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
        $sheet->getColumnDimension('C')->setAutoSize(true);
        $sheet->getColumnDimension('D')->setAutoSize(true);
        $sheet->getColumnDimension('E')->setAutoSize(true);
        $sheet->getColumnDimension('F')->setAutoSize(true);
        $sheet->getColumnDimension('G')->setAutoSize(true);
        $sheet->getColumnDimension('H')->setAutoSize(true);
        $sheet->getColumnDimension('I')->setAutoSize(true);
        $sheet->getColumnDimension('J')->setAutoSize(true);
        $sheet->getColumnDimension('L')->setAutoSize(true);
        $sheet->getColumnDimension('L')->setAutoSize(true);
        $sheet->getColumnDimension('M')->setAutoSize(true);
        $sheet->getColumnDimension('N')->setAutoSize(true);

        $cells  = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N'];
        $columns = [
            'Tgl Mutasi', 'Tgl Realisasi', 'No. Mutasi', 'Gudang Asal', 'Gudang Tujuan', 'Item Code', 'Kode Barang', 'Nama Barang',
            'Nama Segmen', 'Nama Brand', 'Qty', 'Qty Pcs', 'Isi', 'Status'
        ];
        $sheet->getStyle('A' . $i . ':N' . $i)->getFont()->setBold(true);
        $sheet->getStyle('A' . $i . ':N' . $i)->applyFromArray($this->horizontalCenter());
        $sheet->getStyle('A' . $i . ':N' . $i)->applyFromArray($this->border());

        foreach ($columns as $key => $column) {
            $sheet->setCellValue($cells[$key] . $i, $column);
        }

        $sheet->setAutoFilter('A' . $i . ':N' . $i);

        $i++;
        $start = $i;
        foreach ($mutasi as $data) {
            $data = (array) $data;
            $sheet->setCellValue('A' . $i, $data["tanggal_mutasi"]);
            $sheet->setCellValue('B' . $i, $data["tanggal_realisasi"]);
            $sheet->setCellValueExplicit('C' . $i, $data["id"], DataType::TYPE_STRING);
            $sheet->setCellValue('D' . $i, $data["dari"]);
            $sheet->setCellValue('E' . $i, $data["ke"]);

            $sheet->setCellValue('F' . $i, $data["item_code"]);
            $sheet->setCellValue('G' . $i, $data["kode_barang"]);
            $sheet->setCellValue('H' . $i, $data["nama_barang"]);

            $sheet->setCellValue('I' . $i, $data["nama_segmen"]);
            $sheet->setCellValue('J' . $i, $data["nama_brand"]);
            $sheet->setCellValue('K' . $i, $data["qty"]);
            $sheet->setCellValue('L' . $i, $data["qty_pcs"]);
            $sheet->setCellValue('M' . $i, $data["isi"]);
            $sheet->setCellValue('N' . $i, $data["status"]);
            $i++;
        }

        $sheet->getStyle('A1:N' . $i)->applyFromArray($this->fontSize(14));
        $sheet->setCellValue('J' . $i, 'Total');
        $end = $i - 1;
        $sheet->setCellValue('K' . $i, "=SUBTOTAL(9, K{$start}:K{$end})");
        $sheet->setCellValue('L' . $i, "=SUBTOTAL(9, L{$start}:L{$end})");
        $sheet->getStyle('A' . $start . ':N' . $i)->applyFromArray($this->border());

        $spreadsheet->setActiveSheetIndex(0);

        $today = Carbon::today();
        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        $content = ob_get_contents();
        ob_end_clean();
        $now = $today->toDateString();
        $fileName = "laporan_mutasi_barang_{$now}.xlsx";
        Storage::disk('local')->put('excel/' . $fileName, $content);
        $file = url('/excel/' . $fileName);
        return $file;
    }

    public function rekapitulasi_do(Request $request)
    {
        $id_depo    = $request->id_depo;
        $id_salesman= $request->id_salesman;
        $start_date = $request->start_date;
        $end_date   = $request->end_date;

        $do = DB::table('penjualan')->join('salesman', 'penjualan.id_salesman', 'salesman.user_id')
            ->join('users', 'salesman.user_id', 'users.id')
            ->join('detail_penjualan', 'penjualan.id', 'detail_penjualan.id_penjualan')
            ->join('promo', 'detail_penjualan.id_promo', 'promo.id')
            ->join('stock', 'detail_penjualan.id_stock', 'stock.id')
            ->join('barang', 'stock.id_barang', 'barang.id')
            ->join('segmen', 'barang.id_segmen', 'segmen.id')
            ->where('penjualan.id_depo', $id_depo)
            ->where('penjualan.id_salesman', $id_salesman)
            ->whereNull('penjualan.deleted_at')
            ->where( function ($q) {
                $q->where('detail_penjualan.qty', '!=', 0)
                    ->orWhere('detail_penjualan.qty_pcs', '!=', 0);
            });

        $status = $request->status;
        if ($status == 'all' || $status == '' || $status == null) {
            $do = $do->whereBetween("penjualan.tanggal", [$start_date, $end_date])
                ->whereNotIn('penjualan.status', ['waiting', 'canceled']);
        } else {
            if ($status == 'delivered') {
                $do = $do->whereBetween("penjualan.delivered_at", [$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
            } else {
                $do = $do->whereBetween("penjualan.tanggal", [$start_date, $end_date]);
            }

            $do = $do->where('penjualan.status', $status);
        }

        $do = $do->select(
            'stock.id_barang',
            'segmen.id_brand',
            'barang.item_code',
            'barang.kode_barang',
            'barang.nama_barang',
            'barang.isi',
            'penjualan.tipe_harga',
            DB::raw('SUM(detail_penjualan.qty) as qty'),
            DB::raw('SUM(detail_penjualan.qty_pcs) as qty_pcs')
        )->groupBy(
            'stock.id_barang',
            'barang.item_code',
            'barang.kode_barang',
            'barang.nama_barang',
            'segmen.id_brand',
            'penjualan.tipe_harga'
        )->get();

        // get invoice
        $invoice = Penjualan::select('no_invoice')->where('id_salesman', '=', $id_salesman);
        if ($status == 'all' || $status == '' || $status == null) {
            $invoice = $invoice->whereBetween("penjualan.tanggal", [$start_date, $end_date])
                ->whereNotIn('penjualan.status', ['waiting', 'canceled']);
        } else {
            if ($status == 'delivered') {
                $invoice = $invoice->whereBetween("penjualan.delivered_at", [$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
            }

            if ($status == 'loaded') {
                $invoice = $invoice->whereBetween("penjualan.tanggal_jadwal", [$start_date, $end_date]);
            }

            if ($status == 'approved') {
                $invoice = $invoice->whereBetween("penjualan.tanggal", [$start_date, $end_date]);
            }

            $invoice = $invoice->where('penjualan.status', $status);
        }
        $invoice = $invoice->distinct()->get();

        $arr_invoice = array();
        foreach ($invoice as $inv) {
            array_push($arr_invoice, $inv->no_invoice);
        }

        // get salesman & depo
        $salesman = Salesman::join('tim', 'salesman.id_tim', 'tim.id')
            ->join('depo', 'tim.id_depo', 'depo.id')
            ->join('users', 'salesman.user_id', 'users.id')
            ->join('perusahaan', 'depo.id_perusahaan', 'perusahaan.id')
            ->where('salesman.user_id', $id_salesman)
            ->select('users.name', 'depo.nama_depo AS depo_nama', 'perusahaan.nama_perusahaan')->first();
        //return response()->json($salesman, 200);
        $detail = [];
        $brand = Brand::all();
        foreach ($brand as $b) {
            $coll = $do->where('id_brand', $b->id);
            if (!$coll->isEmpty()) {
                foreach ($coll as $key => $col) {
                    $isi    = $col->isi;
                    $in_pcs = ($col->qty * $isi) + $col->qty_pcs;
                    $coll[$key]->qty = $in_pcs >= $col->isi ? floor($in_pcs/$isi) : 0;
                    $coll[$key]->qty_pcs = $in_pcs % $isi;
                }

                $detail[] = [
                    'id' => $b->id,
                    'nama_brand' => $b->nama_brand,
                    'subtotal_dus' => $coll->sum("qty"),
                    'subtotal_pcs' => $coll->sum("qty_pcs"),
                    'barang' => $coll,
                ];
            }
        }

        $data = [
            'salesman' => $salesman->name,
            'depo' => $salesman->depo_nama,
            'perusahaan' => $salesman->nama_perusahaan,
            'invoice' => $arr_invoice,
            'num_invoice' => count($invoice),
            'start_date' => $start_date,
            'end_date' => $end_date,
            'detail' => $detail,
        ];
        return $data;
    }

    public function get_report_delivery(Request $request)
    {
        $list_penjualan = Penjualan::where('status', 'delivered')->orderBy('tanggal_jadwal', 'DESC');

        $id_driver = $request->id_driver ?? 'all';
        if ($id_driver != 'all' && $id_driver != '') {
            $list_penjualan = $list_penjualan->where('driver_id', $id_driver);
        }

        // Filter Date
        if ($request->has(['start_date', 'end_date'])) {
            $list_penjualan = $list_penjualan->whereBetween('delivered_at', [$request->start_date.' 00:00:00', $request->end_date . ' 23:59:59']);
        }

        // Filter Depo
        if ($request->depo != null) {
            $id_depo = $request->depo;
        } else {
            $id_depo = Helper::depoIDByUser($this->user->id);
        }

        $list_penjualan = $list_penjualan->whereIn('id_depo', $id_depo);

        // Filter tipe_pembayaran
        $tipe_pembayaran = $request->tipe_pembayaran ?? 'all';
        if ($tipe_pembayaran != '' && $tipe_pembayaran <> 'all') {
            $list_penjualan = $list_penjualan->where('tipe_pembayaran', $tipe_pembayaran);
        }

        // Filter Keyword
        $keyword = $request->keyword ?? '';
        if ($keyword <> '') {
            $list_penjualan = $list_penjualan->where(function ($q) use ($keyword) {
                $q->where('id', 'like', '%' . $keyword . '%')
                    ->orWhere('no_invoice', 'like', '%' . $keyword . '%')
                    ->orWhere('keterangan', 'like', '%' . $keyword . '%')
                    ->orWhereHas('toko', function ($query) use ($keyword) {
                        $query->where('nama_toko', 'like', '%' . $keyword . '%')
                            ->orWhere('no_acc', 'like', '%' . $keyword . '%')
                            ->orWhere('cust_no', 'like', '%' . $keyword . '%');
                    });
            });
        }

        $list_penjualan = $list_penjualan->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Delivery Report');
        $tanggal_string = '';
        if ($request->start_date == $request->end_date) {
            $tanggal_string = Carbon::parse($request->start_date)->format('d F Y');
        } else {
            $tanggal_string = Carbon::parse($request->start_date)->format('d F Y') . ' - ' . Carbon::parse($request->end_date)->format('d F Y');
        }

        $i = 1;
        $sheet->setCellValue('A'.$i, "Delivery Report");
        $sheet->mergeCells('A'.$i.':B'.$i);
        $i++;
        $sheet->setCellValue('A'.$i, "Driver: ". ($request->id_driver != 'all' ? ($list_penjualan[0]->driver != null ? $list_penjualan[0]->driver->user->name : '-') : 'ALL'));
        $sheet->mergeCells('A'.$i.':C'.$i);
        $i++;
        $sheet->setCellValue('A'.$i, "Tanggal: ".$tanggal_string);
        $sheet->mergeCells('A'.$i.':B'.$i);
        $i++;
        $i++;
        $cells  = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L'];
        foreach ($cells as $key => $cell) {
            if ($key === 0) {
                $sheet->getColumnDimension('A')->setWidth(5);
            } else {
                $sheet->getColumnDimension($cell)->setAutoSize(true);
            }
        }

        $columns = ['No', 'No Invoice', 'Nama Toko', 'Tgl PO', 'Tgl Deliver', 'Jam Sampai', 'Salesman', 'Tim', 'Driver', 'Checker', 'Cash', 'Credit'];
        $sheet->getStyle('A' . $i . ':L' . $i)->getFont()->setBold(true);
        $sheet->getStyle('A' . $i . ':L' . $i)->applyFromArray($this->horizontalCenter());
        $sheet->getStyle('A' . $i . ':L' . $i)->applyFromArray($this->border());
        foreach ($columns as $key => $column) {
            $sheet->setCellValue($cells[$key] . $i, $column);
        }

        $sheet->setAutoFilter('A' . $i . ':L' . $i);

        $i++;
        $start = $i;
        foreach ($list_penjualan as $key => $penjualan) {
            $sheet->setCellValue('A' . $i, $key + 1);
            $sheet->setCellValueExplicit('B' . $i, $penjualan->no_invoice, DataType::TYPE_STRING);
            $sheet->setCellValue('C' . $i, $penjualan->nama_toko);
            $sheet->setCellValue('D' . $i, $penjualan->tanggal);
            $sheet->setCellValue('E' . $i, Carbon::parse($penjualan->delivered_at)->toDateString());
            $sheet->setCellValue('F' . $i, $penjualan->delivered_at != null ? $penjualan->delivered_at->format('H:i:s') : '-');
            $sheet->setCellValue('G' . $i, $penjualan->salesman->user->name);
            $sheet->setCellValue('H' . $i, $penjualan->tim->nama_tim);
            $sheet->setCellValue('I' . $i, $penjualan->driver != null ? $penjualan->driver->user->name : '');
            $sheet->setCellValue('J' . $i, $penjualan->nama_checker);
            $sheet->setCellValue('K' . $i, $penjualan->tipe_pembayaran == 'cash' ? $penjualan->grand_total : 0);
            $sheet->setCellValue('L' . $i, $penjualan->tipe_pembayaran == 'credit' ? $penjualan->grand_total : 0);
            $i++;
        }
        $end = $i - 1;
        $sheet->getColumnDimension('D')->setVisible(false);
        $sheet->getColumnDimension('I')->setVisible(false);
        $sheet->getColumnDimension('J')->setVisible(false);
        $sheet->getColumnDimension('F')->setVisible(false);
        $sheet->getColumnDimension('E')->setVisible(false);
        if($request->id_driver != 'all'){
            $sheet->getColumnDimension('H')->setVisible(false);
        }
        $sheet->setCellValue('G' . $i, "TOTAL");
        $sheet->setCellValue('K' . $i, "=SUBTOTAL(9, K{$start}:K{$end})");
        $sheet->setCellValue('L' . $i, "=SUBTOTAL(9, L{$start}:L{$end})");
        $sheet->getStyle('A1:L' . $i)->applyFromArray($this->fontSize(14));
        $sheet->getStyle('K' . $start . ':K' . $i)->getNumberFormat()->setFormatCode('_(* #,##0_);_(* (#,##0);_(* "-"??_);_(@_)');
        $sheet->getStyle('L' . $start . ':L' . $i)->getNumberFormat()->setFormatCode('_(* #,##0_);_(* (#,##0);_(* "-"??_);_(@_)');
        $sheet->getStyle('A' . $start . ':L' . $i)->applyFromArray($this->border());
        $sheet->getPageSetup()->setFitToWidth(0);

        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        $content = ob_get_contents();
        ob_end_clean();
        $today = Carbon::today();
        $now = $today->toDateString();
        $fileName = "delivery_report_{$now}.xlsx";
        Storage::disk('local')->put('excel/' . $fileName, $content);
        $file = url('/excel/' . $fileName);
        return response()->json($file, 200);
    }

    public function report_jadwal_pengiriman(Request $request)
    {
        $this->validate($request, [
           'id_depo' => 'required'
        ]);

        $id_depo    = $request->id_depo;
        $id_driver  = $request->driver_id === '' ? 'all' : $request->driver_id;
        $start_date = $request->start_date;
        $end_date   = $request->end_date;

        //DB::connection()->enableQueryLog();
        $do = DB::table('penjualan')->join('driver', 'penjualan.driver_id', 'driver.user_id')
            ->join('users', 'driver.user_id', 'users.id')
            ->join('detail_penjualan', 'penjualan.id', 'detail_penjualan.id_penjualan')
            ->join('promo', 'detail_penjualan.id_promo', 'promo.id')
            ->join('stock', 'detail_penjualan.id_stock', 'stock.id')
            ->join('barang', 'stock.id_barang', 'barang.id')
            ->join('segmen', 'barang.id_segmen', 'segmen.id')
            ->where('penjualan.id_depo', $id_depo)
            ->when($id_driver <> 'all' && $id_driver <> '', function ($q) use ($id_driver) {
                $q->where('penjualan.driver_id', $id_driver);
            })
            ->where( function ($q) {
                $q->where('detail_penjualan.qty', '!=', 0)
                    ->orWhere('detail_penjualan.qty_pcs', '!=', 0);
            })
            ->whereNull('penjualan.deleted_at');

        $status = $request->has('status') && $request->status <> '' ? $request->status : 'all';
        if ($status === 'delivered') {
            $do = $do->whereBetween("penjualan.delivered_at", [$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
        } elseif ($status === 'loaded') {
            $do = $do->whereBetween("penjualan.tanggal_jadwal", [$start_date, $end_date]);
        } else {
            $do = $do->whereBetween("penjualan.tanggal_jadwal", [$start_date, $end_date]);
        }


        if ($status === 'all') {
            $do = $do->whereNotIn('penjualan.status', ['waiting', 'canceled']);
        } else {
            $do = $do->where('penjualan.status', $status);
        }

        $do = $do->select(
            'stock.id_barang',
            'segmen.id_brand',
            'barang.item_code',
            'barang.kode_barang',
            'barang.nama_barang',
            'barang.isi',
            'penjualan.tipe_harga',
            DB::raw('SUM(detail_penjualan.qty) as qty'),
            DB::raw('SUM(detail_penjualan.qty_pcs) as qty_pcs')
        )->groupBy(
            'stock.id_barang',
            'barang.item_code',
            'barang.kode_barang',
            'barang.nama_barang',
            'barang.isi',
            'segmen.id_brand',
            'penjualan.tipe_harga'
        )->get();


        // get invoice
        //DB::connection()->enableQueryLog();
        $invoice = DB::table('penjualan')->join('driver', 'penjualan.driver_id', 'driver.user_id')
            ->where('penjualan.id_depo', $id_depo)
            ->when($id_driver <> 'all' && $id_driver <> '', function ($q) use ($id_driver) {
                $q->where('penjualan.driver_id', $id_driver);
            })
            ->whereNull('penjualan.deleted_at');

        if($status === 'delivered') {
            $invoice = $invoice->whereBetween("penjualan.delivered_at", [$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
        } else {
            $invoice = $invoice->whereBetween("penjualan.tanggal_jadwal", [$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
        }

        if ($status === 'all') {
            $invoice = $invoice->whereNotIn('penjualan.status', ['waiting', 'canceled']);
        } else {
            $invoice = $invoice->where('penjualan.status', $status);
        }

        $invoice = $invoice->select(
            'penjualan.no_invoice'
        )->distinct()->get();

        //return DB::getQueryLog();

        $arr_invoice = array();
        foreach ($invoice as $inv) {
            array_push($arr_invoice, $inv->no_invoice);
        }

        //DB::connection()->enableQueryLog();
        // get Driver & depo
        $driver = '';
        if ($id_driver <> 'all') {
            $driver = Driver::join('users', 'driver.user_id', '=', 'users.id')
                ->join('user_perusahaan', 'user_perusahaan.user_id', '=', 'driver.user_id')
                ->join('perusahaan as p', 'user_perusahaan.perusahaan_id', '=', 'p.id')
                ->select('driver.*', 'users.name', 'users.email', 'p.kode_perusahaan', 'p.nama_perusahaan')
                ->find($id_driver);
        }

        $depo = Depo::find($id_depo);
        // return response()->json($id_driver);

        $detail = [];
        $brand = Brand::all();
        $grand_total_dus = 0;
        $grand_total_pcs = 0;
        foreach ($brand as $b) {
            $coll = $do->where('id_brand', $b->id);
            if (!$coll->isEmpty()) {

                foreach ($coll as $key => $barang) {
                    $isi            = $barang->isi;
                    $pcs            = ($barang->qty * $isi) + $barang->qty_pcs;
                    $barang->qty    = $pcs < $isi ? 0 : floor($pcs/$isi);
                    $barang->qty_pcs= $pcs % $isi;
                    $coll[$key]     = $barang;
                }

                $detail[] = [
                    'id' => $b->id,
                    'nama_brand' => $b->nama_brand,
                    'subtotal_dus' => $coll->sum("qty"),
                    'subtotal_pcs' => $coll->sum("qty_pcs"),
                    'barang' => $coll,
                ];
                $grand_total_dus = $grand_total_dus + $coll->sum("qty");
                $grand_total_pcs = $grand_total_pcs + $coll->sum("qty_pcs");
            }
        }

        $data = [
            'nama_perusahaan' => $depo->perusahaan->nama_perusahaan,
            'driver' => $id_driver === 'all' ? 'ALL' : $driver->name,
            'depo' => $depo->nama_depo,
            'invoice' => $id_driver === 'all' ? '-' : $arr_invoice,
            'num_invoice' => count($invoice),
            'start_date' => $start_date,
            'end_date' => $end_date,
            'detail' => $detail,
            'grand_total_dus' => $grand_total_dus,
            'grand_total_pcs' => $grand_total_pcs,
        ];
        return $data;
    }

    public function report_klaim_retur(Request $request)
    {
        $list_retur_penjualan = ReturPenjualan::with('toko', 'salesman.user', 'gudang')
                                            ->whereNotNull('claim_date')
                                            ->orderBy('id', 'DESC');

        // Filter Date
        if ($request->has(['start_date', 'end_date'])) {
            $list_retur_penjualan = $list_retur_penjualan->whereBetween('claim_date', [$request->start_date.' 00:00:00', $request->end_date . ' 23:59:59']);
        }

        $id_salesman = $request->id_salesman ?? 'all';
        if($id_salesman <> 'all' && $id_salesman <> ''){
            $list_retur_penjualan = $list_retur_penjualan->where('id_salesman', $id_salesman);
        }

        // Filter Depo
        if($request->depo != null){
            $id_depo = $request->depo;
        } else {
            $id_perusahaan  = $request->has('id_perusahaan') && $request->id_perusahaan ? [$request->id_perusahaan] : null;
            $id_depo = Helper::depoIDByUser($this->user->id, $id_perusahaan);
        }
        $list_retur_penjualan = $list_retur_penjualan->whereIn('id_depo', $id_depo);

        // Filter Keyword
        $keyword = $request->keyword ?? '';
        $list_retur_penjualan = $list_retur_penjualan->when($keyword <> '', function ($q) use ($keyword) {
            $q->where('id', 'like', "%{$keyword}%")
                ->orWhereHas('toko', function ($q) use ($keyword) {
                    $q->where('toko.nama_toko', 'like', "%{$keyword}%")
                        ->orWhere('toko.no_acc', 'like', "%{$keyword}%")
                        ->orWhere('toko.cust_no', 'like', "%{$keyword}%");
                });
        });

        $list_retur_penjualan = $list_retur_penjualan->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Retur Report');
        $tanggal_string = '';
        if ($request->start_date == $request->end_date) {
            $tanggal_string = Carbon::parse($request->start_date)->format('d F Y');
        } else {
            $tanggal_string = Carbon::parse($request->start_date)->format('d F Y') . ' - ' . Carbon::parse($request->end_date)->format('d F Y');
        }

        $i = 1;
        $sheet->setCellValue('A'.$i, "Laporan Klaim Retur");
        $sheet->mergeCells('A'.$i.':B'.$i);
        $i++;
        $sheet->setCellValue('A'.$i, "Salesmen: ". ($id_salesman != 'all' ? ($list_retur_penjualan[0]->salesman != null ? $list_retur_penjualan[0]->salesman->user->name : '-') : 'ALL'));
        $sheet->mergeCells('A'.$i.':C'.$i);
        $i++;
        $sheet->setCellValue('A'.$i, "Tanggal: ".$tanggal_string);
        $sheet->mergeCells('A'.$i.':D'.$i);
        $i++;
        $i++;
        $cells  = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'];
        foreach ($cells as $key => $cell) {
            if ($key === 0) {
                $sheet->getColumnDimension('A')->setWidth(5);
            } else {
                $sheet->getColumnDimension($cell)->setAutoSize(true);
            }
        }

        $columns = ['No', 'No Retur', 'Nama Toko', 'Tgl Retur', 'Tgl Klaim Retur','Gudang','Salesman','TIM','Saldo Retur'];
        $sheet->getStyle('A' . $i . ':I' . $i)->getFont()->setBold(true);
        $sheet->getStyle('A' . $i . ':I' . $i)->applyFromArray($this->horizontalCenter());
        $sheet->getStyle('A' . $i . ':I' . $i)->applyFromArray($this->border());
        foreach ($columns as $key => $column) {
            $sheet->setCellValue($cells[$key] . $i, $column);
        }

        $sheet->setAutoFilter('A' . $i . ':I' . $i);

        $i++;
        $start = $i;
        foreach ($list_retur_penjualan as $key => $retur) {
            $sheet->setCellValue('A' . $i, $key + 1);
            $sheet->setCellValueExplicit('B' . $i, $retur->id, DataType::TYPE_STRING);
            $sheet->setCellValue('C' . $i, $retur->toko != null ? $retur->toko->nama_toko : '');
            $sheet->setCellValue('D' . $i, $retur->sales_retur_date);
            $sheet->setCellValue('E' . $i, Carbon::parse($retur->claim_date)->toDateString());
            $sheet->setCellValue('F' . $i, $retur->gudang->nama_gudang);
            $sheet->setCellValue('G' . $i, $retur->salesman->user->name);
            $sheet->setCellValue('H' . $i, $retur->salesman->tim != null ? $retur->salesman->tim->nama_tim : '');
            $sheet->setCellValue('I' . $i, $retur->grand_total);
            $i++;
        }
        $end = $i - 1;
        // $sheet->getColumnDimension('D')->setVisible(false);
        $sheet->setCellValue('H' . $i, "TOTAL");
        $sheet->setCellValue('I' . $i, "=SUBTOTAL(9, I{$start}:I{$end})");
        $sheet->getStyle('A1:I' . $i)->applyFromArray($this->fontSize(14));
        $sheet->getStyle('I' . $start . ':I' . $i)->getNumberFormat()->setFormatCode('_(* #,##0_);_(* (#,##0);_(* "-"??_);_(@_)');
        $sheet->getStyle('A' . $start . ':I' . $i)->applyFromArray($this->border());
        $sheet->getPageSetup()->setFitToWidth(0);

        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        $content = ob_get_contents();
        ob_end_clean();
        $today = Carbon::today();
        $now = $today->toDateString();
        $fileName = "retur_report_{$now}.xlsx";
        Storage::disk('local')->put('excel/' . $fileName, $content);
        $file = url('/excel/' . $fileName);
        return response()->json($file, 200);
    }

    public function report_outlet()
    {
        $id_perusahaan = 1;
        $list_toko = Toko::with('depo', 'depo.perusahaan','ketentuan_toko')
                            ->whereHas('depo.perusahaan', function ($query) use ($id_perusahaan)
                            {
                                $query->where('id',$id_perusahaan);
                            })
                            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Outlet');

        $i = 1;
        $cells  = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N','O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V'];
        foreach ($cells as $key => $cell) {
            if ($key === 0) {
                $sheet->getColumnDimension('A')->setWidth(5);
            } else {
                $sheet->getColumnDimension($cell)->setAutoSize(true);
            }
        }

        $columns = [
            'No',
            'Kode Perusahaan',
            'Depo',
            'OutCode',
            'OutName',
            'Address',
            'Kab',
            'Kecamatan',
            'Kelurahan',
            'Hari Kunjuangan',
            'Kredit/Tunai',
            'TOP',
            'Limit',
            'Key Person',
            'Contact',
            'Team',
            'NPWP',
            'Nama PKP',
            'Alamat PKP',
            'No KTP',
            'Nama KTP',
            'Alamat KTP',
        ];
        $sheet->getStyle('A' . $i . ':V' . $i)->getFont()->setBold(true);
        $sheet->getStyle('A' . $i . ':V' . $i)->applyFromArray($this->horizontalCenter());
        $sheet->getStyle('A' . $i . ':V' . $i)->applyFromArray($this->border());
        foreach ($columns as $key => $column) {
            $sheet->setCellValue($cells[$key] . $i, $column);
        }

        $sheet->setAutoFilter('A' . $i . ':V' . $i);

        $i++;
        $start = $i;
        foreach ($list_toko as $key => $toko) {
            $sheet->setCellValue('A' . $i, $key + 1);
            $sheet->setCellValue('B' . $i, $toko->depo->perusahaan->kode_perusahaan);
            $sheet->setCellValue('C' . $i, $toko->depo->nama_depo);
            $sheet->setCellValueExplicit('D' . $i, $toko->no_acc, DataType::TYPE_STRING);
            $sheet->setCellValue('E' . $i, $toko->nama_toko);
            $sheet->setCellValue('F' . $i, $toko->alamat);
            $sheet->setCellValue('G' . $i, $toko->kabupaten);
            $sheet->setCellValue('H' . $i, $toko->kecamatan);
            $sheet->setCellValue('I' . $i, $toko->nama_kelurahan);
            $sheet->setCellValue('J' . $i, $toko->ketentuan_toko->hari ?? 'Senin');
            $sheet->setCellValue('K' . $i, $toko->ketentuan_toko->k_t);
            $sheet->setCellValue('L' . $i, $toko->ketentuan_toko->top);
            $sheet->setCellValue('M' . $i, $toko->ketentuan_toko->limit);
            $sheet->setCellValue('N' . $i, $toko->pemilik);
            $sheet->setCellValue('O' . $i, $toko->telepon);
            $sheet->setCellValue('P' . $i, $toko->ketentuan_toko->tim->nama_tim);
            $sheet->setCellValue('Q' . $i, $toko->ketentuan_toko->npwp);
            $sheet->setCellValue('R' . $i, $toko->ketentuan_toko->nama_pkp);
            $sheet->setCellValue('S' . $i, $toko->ketentuan_toko->alamat_pkp);
            $sheet->setCellValue('T' . $i, $toko->ketentuan_toko->no_ktp);
            $sheet->setCellValue('U' . $i, $toko->ketentuan_toko->nama_ktp);
            $sheet->setCellValue('V' . $i, $toko->ketentuan_toko->alamat_ktp);
            $i++;
        }
        $end = $i - 1;

        // $sheet->getColumnDimension('O')->setVisible(false);
        $sheet->getStyle('A1:V' . $i)->applyFromArray($this->fontSize(14));
        $sheet->getStyle('M' . $start . ':M' . $i)->getNumberFormat()->setFormatCode('_(* #,##0_);_(* (#,##0);_(* "-"??_);_(@_)');
        $sheet->getStyle('A' . $start . ':V' . $i)->applyFromArray($this->border());
        $sheet->getPageSetup()->setFitToWidth(0);

        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        $content = ob_get_contents();
        ob_end_clean();
        $today = Carbon::today();
        $now = $today->toDateString();
        $fileName = "Data_Outlet{$now}.xlsx";
        Storage::disk('local')->put('excel/' . $fileName, $content);
        $file = url('/excel/' . $fileName);
        return response()->json($file, 200);
    }
}
