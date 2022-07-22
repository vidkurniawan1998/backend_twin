<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tymon\JWTAuth\JWTAuth;
use App\Models\Penjualan;
use App\Models\RiwayatPenagihan;
use App\Http\Resources\PelunasanPenjualan as PelunasanPenjualanResource;
use Carbon\Carbon;
use App\Models\Salesman;
use App\Helpers\Helper;
use App\Traits\ExcelStyle;
use DB;
use App\Http\Resources\ReportPiutang as ReportPiutangResources;

class PelunasanPenjualanController extends Controller
{
    use ExcelStyle;

    protected $jwt;

    public function __construct(JWTAuth $jwt){
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
    }

    public function index(Request $request){ // parameter: due_date, id_salesman, status(semua,lunas,belum_lunas,over_due), keyword (no_po, no_invoice, no_acc, cust_no, nama_toko)
        if ($this->user->can('Menu Pelunasan Penjualan')):
            $id_mitra = $request->has('id_mitra') && $request->id_mitra != '' ? $request->id_mitra : 'include';
            $hari = $request->has('hari') ? $request->hari : '';
            $list_pelunasan_penjualan = Penjualan::with(['toko', 'toko.ketentuan_toko', 'salesman'])
                ->when($id_mitra <> 'exclude' && $id_mitra <> 'include', function ($q) use ($id_mitra) {
                    $q->where('id_mitra', $id_mitra);
                })
                ->when($id_mitra == 'exclude', function ($q) {
                    $q->where('id_mitra', '=', 0);
                })
                ->when($hari <> '', function ($q) use ($hari) {
                    $q->whereHas('toko.ketentuan_toko', function ($q) use ($hari) {
                        $q->where('hari', $hari);
                    });
                })
                ->where('status', 'delivered')
                ->whereDate('tanggal', '>=', '2020-08-05');

            if($this->user->can('Pelunasan Penjualan Salesman')){
                $salesman   = Salesman::find($this->user->id);
                $id_tim     = $salesman->tim->id;
                $list_pelunasan_penjualan = $list_pelunasan_penjualan->whereHas('toko.ketentuan_toko', function ($q) use ($id_tim) {
                    $q->where('id_tim', $id_tim);
                });

                if(!$this->user->hasRole('Salesman Canvass')) {
                    $list_pelunasan_penjualan = $list_pelunasan_penjualan->where('tipe_pembayaran', 'credit');
                }
            }

            if($this->user->can('Pelunasan Penjualan Supervisor')){
                $id_salesman = Helper::salesBySupervisor($this->user->id);
                $list_pelunasan_penjualan = $list_pelunasan_penjualan->whereIn('id_salesman', $id_salesman);
            }

            if($this->user->can('Pelunasan Penjualan Koordinator')){
                $id_salesman = Helper::salesByKoordinator($this->user->id);
                $list_pelunasan_penjualan = $list_pelunasan_penjualan->whereIn('id_salesman', $id_salesman);
            }

            $tipe_pembayaran = $request->has('tipe_pembayaran') ? $request->tipe_pembayaran : 'credit';
            if ($tipe_pembayaran <> 'all') {
                $list_pelunasan_penjualan = $list_pelunasan_penjualan->where('tipe_pembayaran', $tipe_pembayaran);
            }

            $id_perusahaan = $request->has('id_perusahaan') && $request->id_perusahaan <> '' ? [$request->id_perusahaan] : null;
            if ($id_perusahaan <> null) {
                $list_pelunasan_penjualan = $list_pelunasan_penjualan->whereIn('id_perusahaan', $id_perusahaan);
            }

            //Filter Depo yang dimiliki User (id_depo berupa array) [1,2,3]
            if($request->depo !== null & $request->depo){
                $id_depo = $request->depo;
            } else {
                $id_depo = Helper::depoIDByUser($this->user->id, $id_perusahaan);
            }

            if($request->has('id_salesman') && $request->id_salesman != '' && $request->id_salesman != 'all'){
                $salesman   = Salesman::find($request->id_salesman);
                $id_tim     = $salesman->tim->id;
                $list_pelunasan_penjualan = $list_pelunasan_penjualan->whereHas('toko.ketentuan_toko', function ($q) use ($id_tim) {
                    $q->where('id_tim', $id_tim);
                });
            } else {
                $list_pelunasan_penjualan = $list_pelunasan_penjualan->whereIn('id_depo', $id_depo);
            }
           //End Filter Depo yang dimiliki User (id_depo berupa array) [1,2,3]
           if($request->has('keyword') && $request->keyword != ''){
                $keyword = $request->keyword;
                $list_pelunasan_penjualan = $list_pelunasan_penjualan->where(function ($q) use ($keyword) {
                    $q->where('id', $keyword)
                        ->orWhere('no_invoice', 'like', '%' . $keyword . '%')
                        ->orWhere('tanggal', 'like', '%' . $keyword . '%')
                        ->orWhereHas('toko', function ($query) use ($keyword){
                            $query->where('nama_toko', 'like', '%' . $keyword . '%')
                                    ->orWhere('no_acc', 'like', '%' . $keyword . '%')
                                    ->orWhere('cust_no', 'like', '%' . $keyword . '%');
                    });
                });
            }

            $today = Carbon::today();
            $due_date = $request->has('due_date') ? $due_date = $request->due_date : $due_date = $today->toDateString();
            // REV status = 'semua' diganti menjadi 'jatuh_tempo', untuk mendapatkan semua pelunasan beri value '' (null)
            if($request->status == 'due_date'){ // semua pelunasan yang jatuh tempo hari ini (atau sesuai tanggal inputan)
                $list_pelunasan_penjualan = $list_pelunasan_penjualan->where('due_date', $due_date);
            } elseif ($request->status == 'lunas'){ // yang dilunasi hari ini (atau sesuai tanggal inputan)
                $list_pelunasan_penjualan = $list_pelunasan_penjualan->whereDate('paid_at', $due_date);
            } elseif($request->status == 'belum_lunas'){ // yang belum dilunasi hari ini (atau sesuai tanggal inputan)
                $list_pelunasan_penjualan = $list_pelunasan_penjualan->whereNull('paid_at')->whereDate('delivered_at', '<=', $due_date);
            } elseif($request->status == 'over_due'){
                $list_pelunasan_penjualan = $list_pelunasan_penjualan->whereNull('paid_at')->where('due_date', '<=',  $due_date);
            }

            $list_pelunasan_penjualan = $list_pelunasan_penjualan->oldest();

            $perPage = $request->has('per_page') ? $perPage = $request->per_page : $perPage = 10;
            $list_pelunasan_penjualan = $perPage == 'all' ? $list_pelunasan_penjualan->get() : $list_pelunasan_penjualan->paginate((int)$perPage);
            if ($list_pelunasan_penjualan) {
                return PelunasanPenjualanResource::collection($list_pelunasan_penjualan);
            }

            return response()->json([
                'message' => 'Data Pelunasan tidak ditemukan!'
            ], 404);
        else:
            return $this->Unauthorized();
        endif;
    }

    public function show($id)
    {
        if ($this->user->can('Edit Pelunasan Penjualan')):
            $pelunasan_penjualan = Penjualan::find($id);

            if ($pelunasan_penjualan) {
                return response()->json([
                    'data' => new PelunasanPenjualanResource($pelunasan_penjualan)
                ], 200);
            }

            return response()->json([
                'message' => 'Data Pelunasan tidak ditemukan!'
            ], 404);
        else:
            return $this->Unauthorized();
        endif;
    }

    // REV: buat fungsi untuk kasir menandai bahwa uang telah diterima/disetor ke kasir
    public function lunasi($id){ // REV: yg melunasi salesman atau driver, bukan admin/kasir
        if ($this->user->can('Melunasi Penjualan')):
            $pelunasan_penjualan = Penjualan::find($id);

            if ($pelunasan_penjualan) {
                if($pelunasan_penjualan->paid_at != ''){
                    return response()->json([
                        'message' => 'Penjualan ' . $id . ' sudah lunas!'
                    ], 400);
                }

                $pelunasan_penjualan->paid_at = Carbon::now()->toDateTimeString();
                $pelunasan_penjualan->save();

                return response()->json([
                    'message' => 'Penjualan ' . $id . ' telah dilunasi.',
                    'data' => new PelunasanPenjualanResource($pelunasan_penjualan)
                ], 200);
            }

            return response()->json([
                'message' => 'Data Penjualan tidak ditemukan!'
            ], 404);
        else:
            return $this->Unauthorized();
        endif;
    }

    public function batalkan($id)
    {
        if ($this->user->can('Batalkan Pelunasan Penjualan')):
            $pelunasan_penjualan = Penjualan::find($id);

            if ($pelunasan_penjualan) {
                $pelunasan_penjualan->paid_at = null;
                $pelunasan_penjualan->save();

                return response()->json([
                    'message' => 'Pelunasan Penjualan ' . $id . ' telah dibatalkan.',
                    'data' => new PelunasanPenjualanResource($pelunasan_penjualan)
                ], 200);
            }

            return response()->json([
                'message' => 'Data Penjualan tidak ditemukan!'
            ], 404);
        else:
            return $this->Unauthorized();
        endif;
    }


    // ================================ SALESMAN ANDROID ===========================================
    public function get_belum_lunas(Request $request){

        // PARAMETER : id_salesman, id_toko, keyword (no_invoice, nama_toko, no_acc), per_page (20), page
        // http://localhost:8000/pelunasan_penjualan/get/belum_lunas?per_page=20&page=1&id_salesman=40&id_toko=1876&keyword=
        if ($this->user->can('Data Belum Lunas Mobile')):

            $list_pelunasan_penjualan = Penjualan::with(['toko', 'toko.ketentuan_toko', 'salesman'])
                ->whereNotIn('status', ['canceled', 'waiting'])
                ->whereNull('paid_at');

            if($this->user->can('Pelunasan Penjualan Salesman')){
                $salesman   = Salesman::find($this->user->id);
                $id_tim     = $salesman->tim->id;
                $list_pelunasan_penjualan = $list_pelunasan_penjualan->whereHas('toko.ketentuan_toko', function ($q) use ($id_tim) {
                    $q->where('id_tim', $id_tim);
                });
            }

            if($this->user->can('Pelunasan Penjualan Supervisor')){
                $id_salesman = $id_salesman = Helper::salesBySupervisor($this->user->id);
                $list_pelunasan_penjualan = $list_pelunasan_penjualan->whereIn('id_salesman', $id_salesman);
            }


            if($request->has('id_salesman') && $request->id_salesman != '' && $request->id_salesman != 'all'){
                $salesman   = Salesman::find($request->id_salesman);
                $id_tim     = $salesman->tim->id;
                $list_pelunasan_penjualan = $list_pelunasan_penjualan->whereHas('toko.ketentuan_toko', function ($q) use ($id_tim) {
                    $q->where('id_tim', $id_tim);
                });
            }

            if($request->has('id_toko') && $request->id_toko != ''){
                $id_toko = $request->id_toko;
                $list_pelunasan_penjualan = $list_pelunasan_penjualan->where('id_toko', $id_toko);
            }

            if($request->has('keyword') && $request->keyword != ''){
                $keyword = $request->keyword;
                $list_pelunasan_penjualan = $list_pelunasan_penjualan->where(function ($q) use ($keyword) {
                    $q->where('id', 'like', '%' . $keyword . '%')
                        ->orWhereHas('toko', function ($query) use ($keyword){
                            $query->where('nama_toko', 'like', '%' . $keyword . '%')
                                    ->orWhere('no_acc', 'like', '%' . $keyword . '%');
                    });
                });
            }

            $list_pelunasan_penjualan = $list_pelunasan_penjualan->oldest();

            $perPage = $request->has('per_page') ? $perPage = $request->per_page : $perPage = 20;
            $list_pelunasan_penjualan = $perPage == 'all' ? $list_pelunasan_penjualan->get() : $list_pelunasan_penjualan->paginate((int)$perPage);

            if ($list_pelunasan_penjualan) {
                return PelunasanPenjualanResource::collection($list_pelunasan_penjualan);
            }

            return response()->json([
                'message' => 'Data Pelunasan tidak ditemukan!'
            ], 404);
        else:
            return $this->Unauthorized();
        endif;
    }


    public function get_riwayat_pelunasan(Request $request){
        // PARAMETER : tanggal_pelunasan, id_salesman, id_toko, keyword (no_invoice, nama_toko, no_acc), per_page (20), page
        // http://localhost:8000/pelunasan_penjualan/get/riwayat_pelunasan?id_salesman=40&id_toko=4074&tanggal_pelunasan=2019-06-26&keyword=

        if ($this->user->role != 'admin' && $this->user->role != 'pimpinan' && $this->user->role != 'accounting' && $this->user->role != 'salesman' && $this->user->role != 'sales_supervisor'){
            return response()->json([
                'message' => 'Anda tidak berhak untuk mengakses fungsi ini!'
            ], 400);
        }

        if ($this->user->can('Riwayat Pelunasan Mobile')):

            $list_pelunasan_penjualan = Penjualan::with(['toko', 'toko.ketentuan_toko', 'salesman'])
                ->whereNotIn('status', ['canceled', 'waiting'])->whereNotNull('paid_at');

            if($this->user->can('Pelunasan Penjualan Salesman')){
                $salesman   = Salesman::find($this->user->id);
                $id_tim     = $salesman->tim->id;
                $list_pelunasan_penjualan = $list_pelunasan_penjualan->whereHas('toko.ketentuan_toko', function ($q) use ($id_tim) {
                    $q->where('id_tim', $id_tim);
                });
            }

            if($this->user->can('Pelunasan Penjualan Supervisor')){
                $id_salesman = $id_salesman = Helper::salesBySupervisor($this->user->id);
                $list_pelunasan_penjualan = $list_pelunasan_penjualan->whereIn('id_salesman', $id_salesman);
            }

            if($request->has('id_salesman') && $request->id_salesman != '' && $request->id_salesman != 'all'){
                $salesman   = Salesman::find($request->id_salesman);
                $id_tim     = $salesman->tim->id;
                $list_pelunasan_penjualan = $list_pelunasan_penjualan->whereHas('toko.ketentuan_toko', function ($q) use ($id_tim) {
                    $q->where('id_tim', $id_tim);
                });
            }

            if($request->has('tanggal_pelunasan') && $request->tanggal_pelunasan != ''){
                $tanggal_pelunasan = $request->tanggal_pelunasan;
                $list_pelunasan_penjualan = $list_pelunasan_penjualan->where('paid_at', 'like', $tanggal_pelunasan . '%');
            }

            if($request->has('id_toko') && $request->id_toko != ''){
                $list_pelunasan_penjualan = $list_pelunasan_penjualan->where('id_toko', $request->id_toko);
            }

            if($request->has('keyword') && $request->keyword != ''){
                $keyword = $request->keyword;
                $list_pelunasan_penjualan = $list_pelunasan_penjualan->where(function ($q) use ($keyword) {
                    $q->where('id', 'like', '%' . $keyword . '%')
                        ->orWhereHas('toko', function ($query) use ($keyword){
                            $query->where('nama_toko', 'like', '%' . $keyword . '%')
                                    ->orWhere('no_acc', 'like', '%' . $keyword . '%');
                    });
                });
            }

            $list_pelunasan_penjualan = $list_pelunasan_penjualan->oldest();
            $perPage = $request->has('per_page') ? $perPage = $request->per_page : $perPage = 20;
            $list_pelunasan_penjualan = $perPage == 'all' ? $list_pelunasan_penjualan->get() : $list_pelunasan_penjualan->paginate((int)$perPage);

            if ($list_pelunasan_penjualan) {
                return PelunasanPenjualanResource::collection($list_pelunasan_penjualan);
            }

            return response()->json([
                'message' => 'Data Pelunasan tidak ditemukan!'
            ], 404);
        else:
            return $this->Unauthorized();
        endif;
    }

    public function total_sisa_pembayaran(Request $request){

        $list_pelunasan_penjualan = Penjualan::whereNotIn('status', ['canceled', 'waiting']);

        if($this->user->can('Pelunasan Penjualan Salesman')){
            $list_pelunasan_penjualan = $list_pelunasan_penjualan->where('id_salesman', $this->user->id);
        }

        if($this->user->can('Pelunasan Penjualan Supervisor')){
            $id_salesman = $id_salesman = Helper::salesBySupervisor($this->user->id);
            $list_pelunasan_penjualan = $list_pelunasan_penjualan->whereIn('id_salesman', $id_salesman);
        }

        if($request->has('id_salesman') && $request->id_salesman != '' && $request->id_salesman != 'all'){
            $list_pelunasan_penjualan = $list_pelunasan_penjualan->where('id_salesman', $request->id_salesman);
        }

        if($request->has('keyword') && $request->keyword != ''){
            $keyword = $request->keyword;
            $list_pelunasan_penjualan = $list_pelunasan_penjualan->where(function ($q) use ($keyword) {
                $q->where('id', $keyword)
                    ->orWhere('no_invoice', 'like', '%' . $keyword . '%')
                    ->orWhere('tanggal', 'like', '%' . $keyword . '%')
                    ->orWhereHas('toko', function ($query) use ($keyword){
                        $query->where('nama_toko', 'like', '%' . $keyword . '%')
                                ->orWhere('no_acc', 'like', '%' . $keyword . '%')
                                ->orWhere('cust_no', 'like', '%' . $keyword . '%');
                });
            });
        }

        $list_pelunasan_penjualan = $list_pelunasan_penjualan->whereNull('paid_at')->get();
        return response()->json([
            'total_sisa_pembayaran' => $list_pelunasan_penjualan->sum('jumlah_belum_bayar'),
        ], 200);
    }

    public function download_report(Request $request)
    {
        $list_pelunasan_penjualan = Penjualan::with(['toko', 'toko.ketentuan_toko', 'salesman'])
            ->where('status', 'delivered')->whereDate('tanggal', '>=', '2020-08-05');

        if($request->has('id_salesman') && $request->id_salesman != '' && $request->id_salesman != 'all'){
            $salesman   = Salesman::find($request->id_salesman);
            $id_tim     = $salesman->tim->id;
            $list_pelunasan_penjualan = $list_pelunasan_penjualan->whereHas('toko.ketentuan_toko', function ($q) use ($id_tim) {
                $q->where('id_tim', $id_tim);
            });
        }

        if($request->has('keyword') && $request->keyword != ''){
            $keyword = $request->keyword;
            $list_pelunasan_penjualan = $list_pelunasan_penjualan->where(function ($q) use ($keyword) {
                $q->where('id', $keyword)
                    ->orWhere('no_invoice', 'like', '%' . $keyword . '%')
                    ->orWhere('tanggal', 'like', '%' . $keyword . '%')
                    ->orWhereHas('toko', function ($query) use ($keyword){
                        $query->where('nama_toko', 'like', '%' . $keyword . '%')
                            ->orWhere('no_acc', 'like', '%' . $keyword . '%')
                            ->orWhere('cust_no', 'like', '%' . $keyword . '%');
                    });
            });
        }

        $today = Carbon::today();
        $due_date = $request->has('due_date') ? $due_date = $request->due_date : $due_date = $today->toDateString();
        // REV status = 'semua' diganti menjadi 'jatuh_tempo', untuk mendapatkan semua pelunasan beri value '' (null)
        if($request->status == 'due_date'){ // semua pelunasan yang jatuh tempo hari ini (atau sesuai tanggal inputan)
            $list_pelunasan_penjualan = $list_pelunasan_penjualan->where('due_date', $due_date);
        } elseif ($request->status == 'lunas'){ // yang dilunasi hari ini (atau sesuai tanggal inputan)
            $list_pelunasan_penjualan = $list_pelunasan_penjualan->whereDate('paid_at', $due_date)
                ->orWhereHas('detail_pelunasan_penjualan', function ($query) use ($due_date){
                    $query->where('created_at', 'like', $due_date . '%');
                });
        } elseif($request->status == 'belum_lunas'){ // yang belum dilunasi hari ini (atau sesuai tanggal inputan)
            $list_pelunasan_penjualan = $list_pelunasan_penjualan->whereNull('paid_at')->whereDate('delivered_at', '<=', $due_date);
        } elseif($request->status == 'over_due'){
            $list_pelunasan_penjualan = $list_pelunasan_penjualan->whereNull('paid_at')->where('due_date', '<=',  $due_date);
        }

        $list_pelunasan_penjualan = $list_pelunasan_penjualan->oldest()->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Pelunasan');

        $i = 1;
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setAutoSize(true);
        $sheet->getColumnDimension('C')->setAutoSize(true);
        $sheet->getColumnDimension('D')->setAutoSize(true);
        $sheet->getColumnDimension('E')->setAutoSize(true);
        $sheet->getColumnDimension('F')->setAutoSize(true);
        $sheet->getColumnDimension('G')->setWidth(15);
        $sheet->getColumnDimension('H')->setWidth(15);
        $sheet->getColumnDimension('I')->setAutoSize(true);
        $sheet->getColumnDimension('J')->setWidth(15);
        $sheet->getColumnDimension('K')->setWidth(15);
        $sheet->getColumnDimension('L')->setWidth(15);
        $sheet->getColumnDimension('M')->setAutoSize(true);
        $sheet->getColumnDimension('N')->setWidth(15);
        $sheet->getColumnDimension('O')->setWidth(15);
        $sheet->getColumnDimension('P')->setWidth(15);

        $cells  = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P'];
        $columns= ['No','Cust No', 'No Acc', 'Nama Toko', 'Alamat', 'Tgl PO', 'Tgl Deliver', 'Due Date', 'No Invoice', 'No PO', 'Total', 'Jumlah Lunas', 'Jumlah Belum Bayar', 'Over Due', 'Tim', 'Tipe Pembayaran'];
        $sheet->getStyle('A'.$i.':O'.$i)->getFont()->setBold(true);
        $sheet->getStyle('A'.$i.':O'.$i)->applyFromArray($this->horizontalCenter());
        $sheet->getStyle('A'.$i.':O'.$i)->applyFromArray($this->border());
        foreach ($columns as $key => $column) {
            $sheet->setCellValue($cells[$key].$i, $column);
        }

        $sheet->setAutoFilter('A'.$i.':P'.$i);

        $i++;
        $start = $i;
        foreach ($list_pelunasan_penjualan as $key => $pelunasan) {
            $sheet->setCellValue('A'.$i, $key+1);
            $sheet->setCellValueExplicit('B'.$i, $pelunasan->cust_no, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('C'.$i, $pelunasan->no_acc, DataType::TYPE_STRING);
            $sheet->setCellValue('D'.$i, $pelunasan->nama_toko);
            $sheet->setCellValue('E'.$i, $pelunasan->toko->alamat);
            $sheet->setCellValue('F'.$i, $pelunasan->tanggal);
            $sheet->setCellValue('G'.$i, Carbon::parse($pelunasan->delivered_at)->toDateString());
            $sheet->setCellValue('H'.$i, $pelunasan->due_date);
            $sheet->setCellValue('I'.$i, $pelunasan->no_invoice);
            $sheet->setCellValue('J'.$i, $pelunasan->id);
            $sheet->setCellValue('K'.$i, round($pelunasan->grand_total,0));
            $sheet->setCellValue('L'.$i, round($pelunasan->jumlah_lunas, 0));
            $sheet->setCellValue('M'.$i, round($pelunasan->jumlah_belum_bayar, 0));
            $sheet->setCellValue('N'.$i, $pelunasan->over_due);
            $sheet->setCellValue('O'.$i, $pelunasan->tip);
            $sheet->setCellValue('P'.$i, $pelunasan->tipe_pembayaran);
            $i++;
        }

        $sheet->getStyle('A1:P'.$i)->applyFromArray($this->fontSize(14));
        $sheet->setCellValue('L'.$i, 'Total Belum Bayar');
        $end = $i-1;
        $sheet->setCellValue('M'.$i, "=SUBTOTAL(9, M{$start}:M{$end})");
        $sheet->getStyle('L'.$i.':M'.$i)->applyFromArray($this->fontSize(16));
        $sheet->getStyle('K'.$start.':M'.$i)->getNumberFormat()->setFormatCode('_(* #,##0_);_(* (#,##0);_(* "-"??_);_(@_)');
        $sheet->getStyle('A'.$start.':P'.$i)->applyFromArray($this->border());
        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        $content = ob_get_contents();
        ob_end_clean();
        $now = $today->toDateString();
        $fileName = "piutang_{$now}.xlsx";
        Storage::disk('local')->put('excel/'.$fileName, $content);
        $file = url('/excel/'.$fileName);
        return response()->json($file, 200);
    }

    public function laporan_pelunasan_download(Request $request)
    {
        if ($this->user->can('Menu Pelunasan Penjualan')):
            $lite = $request->has('lite') && $request->lite == 1 ? 1:0;
            $id_mitra = $request->has('id_mitra') && $request->id_mitra != '' ? $request->id_mitra:'include';
            //Ambil data sesuai dengan data yang di filter
            $list_pelunasan_penjualan = Penjualan::with(['detail_penjualan', 'detail_penjualan.stock', 'pembayaran', 'toko', 'tim', 'toko.ketentuan_toko', 'toko.ketentuan_toko.tim'])
                ->when($id_mitra <> 'exclude' && $id_mitra <> 'include', function ($q) use ($id_mitra) {
                    $q->where('id_mitra', $id_mitra);
                })
                ->when($id_mitra == 'exclude', function ($q) {
                    $q->where('id_mitra', '=', 0);
                })
                ->where('status', 'delivered')
                ->whereDate('tanggal', '>=', '2020-08-05')
                ->where('tipe_pembayaran', 'credit');

            if($this->user->can('Pelunasan Penjualan Salesman')){
                $salesman   = Salesman::find($this->user->id);
                $id_tim     = $salesman->tim->id;
                $list_pelunasan_penjualan = $list_pelunasan_penjualan->whereHas('toko.ketentuan_toko', function ($q) use ($id_tim) {
                    $q->where('id_tim', $id_tim);
                });
            }

            if($this->user->can('Pelunasan Penjualan Supervisor')){
                $id_salesman = Helper::salesBySupervisor($this->user->id);
                $list_pelunasan_penjualan = $list_pelunasan_penjualan->whereIn('id_salesman', $id_salesman);
            }

            //Filter Depo yang dimiliki User (id_depo berupa array) [1,2,3]
           if($request->depo != null){
                $id_depo = $request->depo;
           } else {
                $id_depo = Helper::depoIDByUser($this->user->id);
           }

            if($request->id_salesman != null && $request->id_salesman != 'all'){
                $salesman   = Salesman::find($request->id_salesman);
                $id_tim     = $salesman->tim->id;
                $list_pelunasan_penjualan = $list_pelunasan_penjualan->whereHas('toko.ketentuan_toko', function ($q) use ($id_tim) {
                    $q->where('id_tim', $id_tim);
                });
            } else {
                $list_pelunasan_penjualan = $list_pelunasan_penjualan->whereIn('id_depo', $id_depo);
               //End Filter Depo yang dimiliki User (id_depo berupa array) [1,2,3]
            }

           if($request->has('keyword') && $request->keyword != ''){
                $keyword = $request->keyword;
                $list_pelunasan_penjualan = $list_pelunasan_penjualan->where(function ($q) use ($keyword) {
                    $q->where('id', $keyword)
                        ->orWhere('no_invoice', 'like', '%' . $keyword . '%')
                        ->orWhere('tanggal', 'like', '%' . $keyword . '%')
                        ->orWhereHas('toko', function ($query) use ($keyword){
                            $query->where('nama_toko', 'like', '%' . $keyword . '%')
                                    ->orWhere('no_acc', 'like', '%' . $keyword . '%')
                                    ->orWhere('cust_no', 'like', '%' . $keyword . '%');
                    });
                });
            }

            $today = Carbon::today();
            $due_date = $request->has('due_date') ? $due_date = $request->due_date : $due_date = $today->toDateString();
            // REV status = 'semua' diganti menjadi 'jatuh_tempo', untuk mendapatkan semua pelunasan beri value '' (null)
            if($request->status == 'due_date'){ // semua pelunasan yang jatuh tempo hari ini (atau sesuai tanggal inputan)
                $list_pelunasan_penjualan = $list_pelunasan_penjualan->where('due_date', $due_date);
            } elseif ($request->status == 'lunas'){ // yang dilunasi hari ini (atau sesuai tanggal inputan)
                $list_pelunasan_penjualan = $list_pelunasan_penjualan->whereDate('paid_at', $due_date);

            } elseif($request->status == 'belum_lunas'){ // yang belum dilunasi hari ini (atau sesuai tanggal inputan)
                $list_pelunasan_penjualan = $list_pelunasan_penjualan->whereNull('paid_at')->whereDate('delivered_at', '<=', $due_date);
            } elseif($request->status == 'over_due'){
                $list_pelunasan_penjualan = $list_pelunasan_penjualan->whereNull('paid_at')->where('due_date', '<=',  $due_date);
            }

            $list_pelunasan_penjualan = $list_pelunasan_penjualan->orderBy('id_toko', 'DESC')->orderBy('due_date', 'ASC')->get();
            
            $logData = [
                'action' => 'Download Laporan Pelunasan Penjualan',
                'description' => 'Due date '.$due_date,
                'user_id' => $this->user->id
            ];

            $this->log($logData);
            
            if ($lite == 1) {
                return ReportPiutangResources::collection($list_pelunasan_penjualan);
            }

            $list_pelunasan_penjualan = $list_pelunasan_penjualan->sortBy('toko.nama_toko');
            //Untuk Mendapatkan Jumlah Toko
            $id_toko = array_unique($list_pelunasan_penjualan->pluck('id_toko')->toArray());
            //End Untuk Mendapatkan Jumlah Toko
            //Create Tampilan Excel
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Estimasi Penagihan');

            $i = 1;
            $sheet->getColumnDimension('A')->setWidth(5);
            $sheet->getColumnDimension('B')->setAutoSize(true);
            $sheet->getColumnDimension('C')->setAutoSize(true);
            $sheet->getColumnDimension('D')->setAutoSize(true);
            $sheet->getColumnDimension('E')->setAutoSize(true);
            $sheet->getColumnDimension('F')->setAutoSize(true);
            $sheet->getColumnDimension('G')->setWidth(15);
            $sheet->getColumnDimension('H')->setAutoSize(true);

            $cells  = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
            $columns= ['','Tgl Faktur', 'Tgl JT', 'No Faktur', 'Total', 'Sisa', 'OD','Tim'];
            $sheet->getStyle('A'.$i.':H'.$i)->getFont()->setBold(true);
            $sheet->getStyle('A'.$i.':H'.$i)->applyFromArray($this->horizontalCenter());
            $sheet->getStyle('A'.$i.':H'.$i)->applyFromArray($this->border());
            foreach ($columns as $key => $column) {
                $sheet->setCellValue($cells[$key].$i, $column);
            }
            //Tambilan Beda
            $i++;
            $start = $i;
            $total_sisa = 0;
            $total_grand_total = 0;
            foreach ($id_toko as $toko_id) {
                $per_toko = $list_pelunasan_penjualan->where('id_toko',$toko_id);
                //Mencari Total Sisa / Total Belum bayar
                $sisa = 0;
                foreach ($per_toko as $pl) {
                    $sisa = $sisa + $pl->jumlah_belum_bayar;
                }

                $total_sisa += $sisa;
                //End Mencari Total Sisa / Total Belum bayar
                // $pelunasan = $per_toko;
                $j = 0;
                foreach ($per_toko as $pelunasan) {
                    if ($j == 0) {
                        $sheet->setCellValue('A' . $i, $pelunasan->nama_toko.' ('.$pelunasan->cust_no.') ');
                        $sheet->mergeCells('A' . $i . ':E' . $i);
                        $sheet->setCellValue('F'.$i, round($sisa, 0));
                        $sheet->getStyle('A'.$i.':F'.$i)->getFont()->setBold(true);
                        $i++;
                        $sheet->setCellValue('A' . $i, $pelunasan->toko->alamat);
                        $sheet->mergeCells('A' . $i . ':H' . $i);
                        $i++;
                        //Cetak Detail
                        $sheet->setCellValue('A'.$i, '');
                        $sheet->setCellValue('B'.$i, $pelunasan->tanggal);
                        $sheet->setCellValue('C'.$i, $pelunasan->due_date);
                        $sheet->setCellValue('D'.$i, $pelunasan->no_invoice);
                        $sheet->setCellValue('E'.$i, round($pelunasan->grand_total,0));
                        $sheet->setCellValue('F'.$i, round($pelunasan->jumlah_belum_bayar,0));
                        $sheet->setCellValue('G'.$i, $pelunasan->over_due);
                        $sheet->setCellValue('H'.$i, $pelunasan->nama_tim);
                        $total_grand_total += round($pelunasan->grand_total,0);
                        $i++;
                    }
                    else
                    {
                        //Cetak Detail
                        $sheet->setCellValue('A'.$i, '');
                        $sheet->setCellValue('B'.$i, $pelunasan->tanggal);
                        $sheet->setCellValue('C'.$i, $pelunasan->due_date);
                        $sheet->setCellValue('D'.$i, $pelunasan->no_invoice);
                        $sheet->setCellValue('E'.$i, round($pelunasan->grand_total,0));
                        $sheet->setCellValue('F'.$i, round($pelunasan->jumlah_belum_bayar,0));
                        $sheet->setCellValue('G'.$i, $pelunasan->over_due);
                        $sheet->setCellValue('H'.$i, $pelunasan->nama_tim);
                        $total_grand_total += round($pelunasan->grand_total,0);
                        $i++;
                    }
                    $j++;
                }
            }

            $sheet->setCellValue('D'.$i, 'TOTAL');
            $sheet->setCellValue('E'.$i, $total_grand_total);
            $sheet->setCellValue('F'.$i, $total_sisa);
            $sheet->getStyle('D'.$i.':F'.$i)->getFont()->setBold(true);
            $sheet->getStyle('A1:H'.$i)->applyFromArray($this->fontSize(14));
            $sheet->getStyle('E'.$i.':F'.$i)->applyFromArray($this->fontSize(16));
            $sheet->getStyle('E'.$start.':F'.$i)->getNumberFormat()->setFormatCode('_(* #,##0_);_(* (#,##0);_(* "-"??_);_(@_)');
            $sheet->getStyle('A'.$start.':H'.$i)->applyFromArray($this->border());
            for ($x=1; $x <= $i ; $x++) {
                $sheet->getRowDimension($x)->setRowHeight(25);
                $sheet->getStyle('H'.$x)->applyFromArray($this->horizontalCenter());
            }

            $sheet->getPageSetup()->setScale(70);


            //Create Sheet 2
            $spreadsheet->createSheet();
            // Zero based, so set the second tab as active sheet
            $sheet2 = $spreadsheet->setActiveSheetIndex(1);
            $sheet2 = $spreadsheet->getActiveSheet()->setTitle('Detail Penagihan');

            $i = 1;
            $sheet2->getColumnDimension('A')->setWidth(5);
            $sheet2->getColumnDimension('B')->setAutoSize(true);
            $sheet2->getColumnDimension('C')->setAutoSize(true);
            $sheet2->getColumnDimension('D')->setAutoSize(true);
            $sheet2->getColumnDimension('E')->setAutoSize(true);
            $sheet2->getColumnDimension('F')->setAutoSize(true);
            $sheet2->getColumnDimension('G')->setWidth(15);
            $sheet2->getColumnDimension('H')->setAutoSize(true);
            $sheet2->getColumnDimension('I')->setAutoSize(true);
            $sheet2->getColumnDimension('J')->setWidth(25);
            $sheet2->getColumnDimension('K')->setWidth(25);
            $sheet2->getColumnDimension('L')->setWidth(25);
            $sheet2->getColumnDimension('M')->setAutoSize(true);
            $sheet2->getColumnDimension('N')->setWidth(15);
            $sheet2->getColumnDimension('O')->setWidth(15);

            $cells  = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P'];
            $columns= ['No','Cust No', 'Nama Toko', 'Alamat', 'transdate', 'Tgl Deliver', 'Due Date', 'No Invoice', 'No PO', 'Total', 'Jumlah Lunas', 'Waiting', 'Jumlah Belum Bayar', 'Over Due', 'Tim', 'Tipe Pembayaran'];
            $sheet2->getStyle('A'.$i.':P'.$i)->getFont()->setBold(true);
            $sheet2->getStyle('A'.$i.':P'.$i)->applyFromArray($this->horizontalCenter());
            $sheet2->getStyle('A'.$i.':P'.$i)->applyFromArray($this->border());
            foreach ($columns as $key => $column) {
                $sheet2->setCellValue($cells[$key].$i, $column);
            }

            $sheet2->setAutoFilter('A'.$i.':P'.$i);

            $i++;
            $start = $i;
            foreach ($list_pelunasan_penjualan as $key => $pelunasan) {
                $sheet2->setCellValue('A'.$i, $key+1);
                $sheet2->setCellValueExplicit('B'.$i, $pelunasan->cust_no, DataType::TYPE_STRING);
                $sheet2->setCellValue('C'.$i, $pelunasan->nama_toko);
                $sheet2->setCellValue('D'.$i, $pelunasan->toko->alamat);
                $sheet2->setCellValue('E'.$i, $pelunasan->tanggal);
                $sheet2->setCellValue('F'.$i, Carbon::parse($pelunasan->delivered_at)->toDateString());
                $sheet2->setCellValue('G'.$i, $pelunasan->due_date);
                $sheet2->setCellValue('H'.$i, $pelunasan->no_invoice);
                $sheet2->setCellValueExplicit('I'.$i, $pelunasan->id, DataType::TYPE_STRING);
                $sheet2->setCellValue('J'.$i, round($pelunasan->grand_total,0));
                $sheet2->setCellValue('K'.$i, round($pelunasan->jumlah_lunas, 0));
                $sheet2->setCellValue('L'.$i, round($pelunasan->jumlah_waiting, 0));
                $sheet2->setCellValue('M'.$i, round($pelunasan->jumlah_belum_bayar, 0));
                $sheet2->setCellValue('N'.$i, $pelunasan->over_due);
                $sheet2->setCellValue('O'.$i, $pelunasan->nama_tim);
                $sheet2->setCellValue('P'.$i, $pelunasan->tipe_pembayaran);
                $i++;
            }

            $sheet2->getStyle('A1:P'.$i)->applyFromArray($this->fontSize(14));
            $sheet2->setCellValue('I'.$i, 'Total Belum Bayar');
            $end = $i-1;
            $sheet2->setCellValue('J'.$i, "=SUBTOTAL(9, J{$start}:J{$end})");
            $sheet2->setCellValue('K'.$i, "=SUBTOTAL(9, K{$start}:K{$end})");
            $sheet2->setCellValue('L'.$i, "=SUBTOTAL(9, L{$start}:L{$end})");
            $sheet2->setCellValue('M'.$i, "=SUBTOTAL(9, M{$start}:M{$end})");
            $sheet2->getStyle('J'.$i.':M'.$i)->applyFromArray($this->fontSize(16));
            $sheet2->getStyle('H'.$start.':M'.$i)->getNumberFormat()->setFormatCode('_(* #,##0_);_(* (#,##0);_(* "-"??_);_(@_)');
            $sheet2->getStyle('A'.$start.':P'.$i)->applyFromArray($this->border());
            $hides = ['E'];
            foreach ($hides as $hide) {
                $sheet2->getColumnDimension($hide)->setVisible(false);
            }
            $spreadsheet->setActiveSheetIndex(0);
            $writer = new Xlsx($spreadsheet);
            ob_start();
            $writer->save('php://output');
            $content = ob_get_contents();
            ob_end_clean();
            $now = $today->toDateString();
            $fileName = "laporan_piutang_{$now}_{$this->user->id}.xlsx";
            Storage::disk('local')->put('excel/'.$fileName, $content);
            $file = url('/excel/'.$fileName);
            return $file;
        else:
            return $this->Unauthorized();
        endif;
    }
    public function riwayat_penagihan(Request $request)
    {
        foreach ($request['id_penjualan'] as $id_penjualan) {
            $data = array('id_salesman' => $request['id_salesman'], 'id_penjualan'=>$id_penjualan, 'tanggal_penagihan'=>$request['tanggal_penagihan']);
            RiwayatPenagihan::create($data);
        }
    }
}
