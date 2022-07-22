<?php

namespace App\Http\Controllers;

use App\Models\Depo;
use App\Models\Mitra;
use App\Models\Penjualan;
use App\Models\DetailPenjualan;
use App\Models\PenjualanPajak;
use App\Models\Reference;
use App\Models\Stock;
use App\Models\PosisiStock;
use App\Models\KetentuanToko;
use App\Models\Toko;
use App\Models\Promo;
use App\Models\DetailPelunasanPenjualan;
use App\Models\Salesman;
use App\Models\Tim;
use App\Models\PenjualanHeader;
use App\Models\LogStock;
use App\Http\Resources\Penjualan as PenjualanResource;
use App\Http\Resources\PenjualanWithHeader as PenjualanWithHeaderResource;
use App\Http\Resources\Toko as TokoResource;
use App\Http\Resources\DistributionPlan as DistributionPlanResource;
use App\Helpers\Helper;
use App\Imports\PajakImport;
use App\Models\TokoNoLimit;
use App\Traits\ExcelStyle;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tymon\JWTAuth\JWTAuth;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\StockAwal;
use App\Http\Requests\PenjualanRequest;
use Log;


class PenjualanController extends Controller
{
    use ExcelStyle;
    protected $jwt;

    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $per_page = 5)
    {
        // id_salesman, start_date, end_date, status, keyword, per_page, page
        if ($this->user->can('Menu Penjualan')) :
            $user_id = $this->user->id;
            $list_penjualan = new Penjualan;

            // Filter based user permission
            // return $list_penjualan->first();
            if ($this->user->can('Penjualan Salesman')) {
                $list_penjualan = Penjualan::where('id_salesman', $user_id)->latest();
            }

            if ($this->user->can('Penjualan Logistik')) {
                $list_penjualan     = Penjualan::whereNotIn('status', ['waiting', 'canceled'])->orderBy('approved_at', 'desc');
            }

            if ($this->user->can('Penjualan Tim')) {
                $salesBySupervisor  = Helper::salesBySupervisor($user_id);
                $list_penjualan     = Penjualan::whereIn('id_salesman', $salesBySupervisor)->latest();
            }

            if ($this->user->can('Penjualan Tim Koordinator')) {
                $salesByKoordinator = Helper::salesByKoordinator($user_id);
                $list_penjualan     = Penjualan::whereIn('id_salesman', $salesByKoordinator)->latest();
            }

            $id_mitra = $request->has('id_mitra') && $request->id_mitra <> '' ? $request->id_mitra:0;
            if ($this->user->can('Mitra') && $id_mitra <> 0) {
                $list_penjualan = $list_penjualan->where('id_mitra', $id_mitra);
            }

            // Filter Salesman (Tim)
            $id_salesman = $request->id_salesman ?? [];
            if (is_array($id_salesman) && count($id_salesman) == 0) {
                $list_penjualan = $list_penjualan;
            } else {
                if (is_array($id_salesman)) {
                    $list_penjualan = $list_penjualan->whereIn('id_salesman', $id_salesman);
                } else {
                    $list_penjualan = $list_penjualan->where('id_salesman', $id_salesman);
                }
            }

            // Filter Date
            if ($request->has(['start_date', 'end_date'])) {
                $list_penjualan = $list_penjualan->whereBetween('tanggal', [$request->start_date, $request->end_date]);
            }

            $id_perusahaan = $request->id_perusahaan == '' ? null:[$request->id_perusahaan];
            if ($id_perusahaan <> null) {
                $list_penjualan = $list_penjualan->whereIn('id_perusahaan', $id_perusahaan);
            }

            // Filter Depo
            if ($request->depo != null) {
                $id_depo = $request->depo;
            } else {
                $id_depo = Helper::depoIDByUser($this->user->id, $id_perusahaan);
            }

            $list_penjualan = $list_penjualan->whereIn('id_depo', $id_depo);

            // Filter Status
            $status = $request->status ?? 'all';
            if ($status <> 'all' && $status <> '') {
                if ($status == 'empty') {
                    $list_penjualan = $list_penjualan->doesnthave('detail_penjualan');
                } elseif ($status == 'fixed') {
                    $list_penjualan = $list_penjualan->whereNotIn('status', ['waiting', 'canceled']);
                } else {
                    $list_penjualan = $list_penjualan->where('status', $request->status);
                }
            }

            // Filter Status Pending
            $status_pending = $request->status_pending;
            if ($status_pending <> '-') {
                if ($status_pending === 'all') {
                    $list_penjualan->whereIn('pending_status', ['od', 'ocl', 'stock']);
                } else {
                    $list_penjualan->where('pending_status', $status_pending);
                }
            }

            if ($this->user->hasRole('Checker')) {
                $id_gudang = Helper::gudangByUser($this->user->id);
                $list_penjualan =  $list_penjualan->when($id_gudang <> '', function ($q) use ($id_gudang) {
                    return $q->whereHas('detail_penjualan', function ($q) use ($id_gudang) {
                        return $q->whereHas('stock', function ($q) use ($id_gudang) {
                            return $q->whereIn('id_gudang', $id_gudang);
                        });
                    });
                })
                    ->whereNotnull('driver_id')
                    ->where('status', 'approved')
                    ->whereDate('tanggal_jadwal', Carbon::today());
            }

            $list_penjualan = $list_penjualan->orderBy('id', 'desc')->orderBy('tanggal', 'desc');

            // Filter tipe_pembayaran
            $paymentType = $request->tipe_pembayaran ?? 'all';
            if ($paymentType != '' && $paymentType <> 'all') {
                $list_penjualan = $list_penjualan->where('tipe_pembayaran', $paymentType);
            }

            // Filter Keyword
            $keyword = $request->keyword ?? '';
            if ($keyword <> '') {
                $list_penjualan = $list_penjualan->where(function ($q) use ($keyword) {
                    $q->where('id', 'like', '%' . $keyword . '%')
                        ->orWhere('po_manual', 'like', '%' . $keyword . '%')
                        ->orWhere('no_invoice', 'like', '%' . $keyword . '%')
                        ->orWhere('keterangan', 'like', '%' . $keyword . '%')
                        ->orWhereHas('toko', function ($query) use ($keyword) {
                            $query->where('nama_toko', 'like', '%' . $keyword . '%')
                                ->orWhere('no_acc', 'like', '%' . $keyword . '%')
                                ->orWhere('cust_no', 'like', '%' . $keyword . '%');
                        });
                });
            }

            $list_penjualan = $list_penjualan->relationData();

            $perPage = $request->has('per_page') ? $perPage = $request->per_page : $perPage = 'all';
            Log::info($this->user->name." :".$perPage);
            $list_penjualan = $perPage == 'all' ? $list_penjualan->get() : $list_penjualan->paginate((int)$perPage);
            if ($list_penjualan) {
                return PenjualanResource::collection($list_penjualan);
            }

            return response()->json([
                'message' => 'Data Penjualan tidak ditemukan!'
            ], 404);
        else :
            return $this->Unauthorized();
        endif;
    }

    public function count_penjualan_today(Request $request)
    {
        $status = $request->has('status') ? $request->status:'';
        $today  = $request->has('tanggal') ? $request->tanggal : Carbon::today()->toDateString();
        $count_penjualan_today = Penjualan::where('tanggal', $today)->count();

        if ($this->user->can('Penjualan Salesman')) {
            $count_penjualan_today = Penjualan::where('id_salesman', $this->user->id)->where('tanggal', $today);
        }

        if ($this->user->can('Penjualan Tim')) {
            $salesBySupervisor      = Helper::salesBySupervisor($this->user->id);
            $count_penjualan_today  = Penjualan::whereIn('id_salesman', $salesBySupervisor)->where('tanggal', $today);
        }

        if ($status <> '') {
            $count_penjualan_today = $count_penjualan_today->where('status', $status);
        }

        return $count_penjualan_today->count();
    }

    public function penjualan_today(Request $request)
    {
        $today      = Carbon::today();
        $user_id    = $this->user->id;
        $keyword    = $request->has('keyword') ? $request->keyword : '';
        $list_penjualan = Penjualan::where('tanggal', $today->toDateString())->latest();

        if ($this->user->can('Penjualan Salesman')) {
            $list_penjualan = Penjualan::when($keyword <> '', function ($q) use ($keyword) {
                return $q->where('no_invoice', 'like', "%$keyword%");
            })->where('id_salesman', $user_id)->where('tanggal', $today->toDateString())->latest();
        }

        if ($this->user->can('Penjualan Tim')) {
            $salesBySupervisor  = Helper::salesBySupervisor($user_id);
            $list_penjualan     = Penjualan::whereIn('id_salesman', $salesBySupervisor)->where('tanggal', $today->toDateString())->latest();
        }

        if ($this->user->can('Penjualan Tim Koordinator')) {
            $salesByKoordinator = Helper::salesByKoordinator($user_id);
            $list_penjualan     = Penjualan::whereIn('id_salesman', $salesByKoordinator)->where('tanggal', $today->toDateString())->latest();
        }

        $list_penjualan = $list_penjualan->relationData();

        $perPage = $request->has('per_page') ? $perPage = $request->per_page : $perPage = 'all';
        $list_penjualan = $perPage == 'all' ? $list_penjualan->get() : $list_penjualan->paginate((int)$perPage);

        return PenjualanResource::collection($list_penjualan);
    }

    public function tanggal_penjualan(Request $request)
    {
        $user_id = $this->user->id;
        $penjualan = Penjualan::latest();
        if ($this->user->can('Penjualan Salesman')) {
            $penjualan = Penjualan::where('id_salesman', $user_id)->latest();
        }

        if ($this->user->can('Penjualan Tim')) {
            $salesBySupervisor = Helper::salesBySupervisor($user_id);
            $penjualan = Penjualan::whereIn('id_salesman', $salesBySupervisor)->latest();
        }

        $perPage    = $request->has('per_page') ? $perPage = $request->per_page : $perPage = 'all';
        $penjualan  = $penjualan->select('tanggal', \DB::raw('count(*) as count'))->groupBy('tanggal');
        $penjualan  = $perPage === 'all' ? $penjualan->get() : $penjualan->simplePaginate((int)$perPage)->items();
        return $penjualan;
    }

    public function riwayat_penjualan(Request $request, $tanggal)
    {
        $user_id = $this->user->id;
        $list_penjualan = Penjualan::where('tanggal', $tanggal)->latest();

        if ($this->user->can('Penjualan Salesman')) {
            $list_penjualan = Penjualan::where('id_salesman', $user_id)->where('tanggal', $tanggal)->latest();
        }

        if ($this->user->can('Penjualan Tim')) {
            $salesBySupervisor = Helper::salesBySupervisor($user_id);
            $list_penjualan = Penjualan::whereIn('id_salesman', $salesBySupervisor)->where('tanggal', $tanggal)->latest();
        }

        $list_penjualan = $list_penjualan->relationData();

        $perPage = $request->has('per_page') ? $perPage = $request->per_page : $perPage = 'all';
        $list_penjualan = $perPage == 'all' ? $list_penjualan->get() : $list_penjualan->paginate((int)$perPage);

        return PenjualanResource::collection($list_penjualan);
    }

    public function list_toko(Request $request)
    {
        $list_toko = [];
        if ($this->user->can('Penjualan Salesman')) {
            $id_tim     = $this->user->salesman->id_tim;
            $list_toko  = Helper::listToko([$id_tim]);
        }

        if ($this->user->can('Penjualan Tim')) {
            $id_tim     = Tim::where('id_sales_supervisor', $this->user->id)->pluck('id');
            $list_toko  = Helper::listToko($id_tim->toArray());
        }

        if ($list_toko) {
            return TokoResource::collection($list_toko->sortBy('nama_toko'));
        }

        return response()->json([
            'message' => 'Data Toko Kosong, Role belum diatur!'
        ], 403);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // untuk app salesman kirim parameter per_page = 1 untuk mengurani beban loading
        if ($this->user->can('Tambah Penjualan')) :

            if (Helper::transactionLimit($this->user, 'penjualan.store')) {
               return $this->limitFalse('waktu operasional');
            }

            $this->validate($request, [
                'id_toko' => 'required|numeric|min:0|max:9999999999',
                // 'id_salesman' => 'required|numeric|min:0|max:9999999999',
                'tanggal' => 'nullable|date',
                'tipe_pembayaran' => 'required|in:credit,cash,bg,trs',
                'tipe_harga' => 'required|exists:tipe_harga,tipe_harga'
            ]);

            $lock_order = TokoNoLimit::where('id_toko', '=', $request->id_toko)->where('tipe', '=', 'lock_order')->first();
            if ($lock_order) {
                return response()->json([
                    'message' => 'Saat ini toko tidak bisa buka order karena riwayat pengambilan di bawah rata-rata'
                ], 400);
            }

            $lock_order_mix = Toko::where('id', '=', $request->id_toko)->first();

            if ($lock_order_mix->lock_order == '1') {
                return response()->json([
                    'message' => 'Toko tidak boleh order, gunakan RPS baru!'
                ], 400);
            }
            if($lock_order_mix->tipe_3 == 'NOO'){
                if($request->tipe_pembayaran != 'cash'){
                    return response()->json([
                        'message' => 'NOO hanya dapat melakukan transaksi dengan tipe pembayaran cash'
                    ], 400);
                }
            }

            $user_id = $this->user->id;
            $ketentuan_toko = Toko::find($request->id_toko)->ketentuan_toko;
            // VALIDATE TEAM & KETENTUAN TOKO
            $salesman = Salesman::where('user_id', $user_id)->first();
            if (!$salesman) {
                return response()->json([
                    'message' => 'Hanya salesman yang boleh melakukan penjualan di aplikasi!'
                ], 400);
            } else {
                $tim_toko = $ketentuan_toko->id_tim;
                if ($tim_toko != $salesman->tim->id) {
                    return response()->json([
                        'message' => 'Team toko tidak sesuai dengan tim salesman'
                    ], 400);
                }
            }

            $depo                   = Depo::find($salesman->tim->id_depo);
            $input                  = $request->all();
            $input['status']        = 'waiting';
            $input['tanggal']       = $input['tanggal'] ?? \Carbon\Carbon::now()->toDateString();
            $input['id_salesman']   = $user_id;
            $input['id_tim']        = $salesman->id_tim;
            $input['created_by']    = $user_id;
            $input['id_depo']       = $depo->id;
            $input['id_perusahaan'] = $depo->perusahaan->id;
            $input['top']           = $request->tipe_pembayaran === 'credit' ? $ketentuan_toko->top : 0;

            if ($this->user->hasRole('Salesman Canvass')) {
                $input['id_gudang'] = $this->user->salesman->tim->canvass->id_gudang_canvass;
            } else {
                $input['id_gudang'] = $this->user->salesman->tim->depo->id_gudang;
            }

            try {
                $penjualan = Penjualan::create($input);
                if ($ketentuan_toko->npwp) {
                    PenjualanPajak::create([
                        'id_penjualan'  => $penjualan->id,
                        'npwp'          => $ketentuan_toko->npwp,
                        'nama_pkp'      => $ketentuan_toko->nama_pkp,
                        'alamat_pkp'    => $ketentuan_toko->alamat_pkp
                    ]);
                }
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 400);
            }

            $list_penjualan = Penjualan::relationData()->where('id_salesman', $user_id)->where('id', $penjualan->id)->get();
            $new_list_penjualan = PenjualanResource::collection($list_penjualan);

            // $today = Carbon::today();
            // $banyak_penjualan = Penjualan::where('id_salesman', $user_id)->where('tanggal', $today->toDateString())->count();

            return response()->json([
                'message' => 'Data Penjualan berhasil disimpan.',
                'id_invoice_baru' => $penjualan->id,
                'data' => $new_list_penjualan,
                'banyak_penjualan' => 0
            ], 201);
        else :
            return $this->Unauthorized();
        endif;
    }

    public function post_penjualan(Request $request)
    {
        if ($this->user->can('Tambah Penjualan')) :

            if (Helper::transactionLimit($this->user, 'penjualan.store')) {
                return $this->limitFalse('waktu operasional');
            }

            $this->validate($request, [
                'id_toko' => 'required|numeric|min:0|max:9999999999',
                'tipe_pembayaran' => 'required|in:credit,cash,bg,trs',
                'tipe_harga' => 'required|exists:tipe_harga,tipe_harga',
                'id_salesman' => 'required|exists:salesman,user_id',
                'tanggal' => 'required|date',
                'id_gudang' => 'required|exists:gudang,id'
            ]);

            $lock_order = TokoNoLimit::where('id_toko', '=', $request->id_toko)->where('tipe', '=', 'lock_order')->first();
            if ($lock_order) {
                return response()->json([
                    'message' => 'Saat ini toko tidak bisa buka order karena riwayat pengambilan di bawah rata-rata'
                ], 400);
            }

            // $lock_order_mix = Toko::where('id', '=', $request->id_toko)->where('lock_order', '=', '1')->first();
            // if ($lock_order_mix) {
            //     return response()->json([
            //         'message' => 'Toko tidak boleh order, gunakan RPS baru!'
            //     ], 400);
            // }

            $lock_order_mix = Toko::where('id', '=', $request->id_toko)->first();
            if ($lock_order_mix->lock_order == '1') {
                return response()->json([
                    'message' => 'Toko tidak boleh order, gunakan RPS baru!'
                ], 400);
            }
            if($lock_order_mix->tipe_3 == 'NOO'){
                if($request->tipe_pembayaran != 'cash'){
                    return response()->json([
                        'message' => 'NOO hanya dapat melakukan transaksi dengan tipe pembayaran cash'
                    ], 400);
                }
            }

            $salesman               = Salesman::find($request->id_salesman);
            $depo                   = Depo::find($salesman->tim->id_depo);
            $user_id                = $this->user->id;
            $input                  = $request->except(['items']);
            $input['status']        = 'waiting';
            $input['tanggal']       = $request->tanggal ?? \Carbon\Carbon::now()->toDateString();
            $input['created_by']    = $user_id;
            $input['id_depo']       = $depo->id;
            $input['id_perusahaan'] = $depo->perusahaan->id;
            $input['id_tim']        = $salesman->id_tim;
            //$items                  = $request->input('items');
            $ketentuan_toko         = Toko::find($request->id_toko)->ketentuan_toko;
            $input['top']           = $request->tipe_pembayaran === 'credit' ? $ketentuan_toko->top : 0;

            // return $request;
            DB::beginTransaction();
            try {
                $penjualan = Penjualan::create($input);
                DB::commit();
                PenjualanPajak::create([
                    'id_penjualan'  => $penjualan->id,
                    'npwp'          => $ketentuan_toko->npwp,
                    'nama_pkp'      => $ketentuan_toko->nama_pkp,
                    'alamat_pkp'    => $ketentuan_toko->alamat_pkp
                ]);
            } catch (\Exception $e) {
                DB::rollback();
                return response()->json(['error' => $e->getMessage()], 400);
            }

            return response()->json([
                'message' => 'Data Penjualan berhasil disimpan.',
                'id' => $penjualan->id
            ], 201);
        else :
            return $this->Unauthorized();
        endif;
    }

    public function show($id)
    {
        if ($this->user->can('Edit Penjualan')) :
            $penjualan = Penjualan::find($id);
            if ($penjualan) {
                return new PenjualanWithHeaderResource($penjualan);
            }
            return response()->json([
                'message' => 'Data Penjualan tidak ditemukan!'
            ], 404);
        else :
            return $this->Unauthorized();
        endif;
    }

    public function update(Request $request, $id)
    {
        if ($this->user->can('Update Penjualan')) :

            if (Helper::transactionLimit($this->user, 'penjualan.update')) {
                return $this->limitFalse('waktu operasional');
            }

            $user_id    = $this->user->id;
            $penjualan  = Penjualan::find($id);

            if ($this->user->hasRole('Salesman Canvass') && ($user_id != $penjualan->id_salesman)) {
                return response()->json([
                    'message' => 'Anda tidak berhak untuk mengubah data penjualan salesman lain!'
                ], 400);
            }

            if ($this->user->hasRole('Salesman') && ($user_id != $penjualan->id_salesman)) {
                return response()->json([
                    'message' => 'Anda tidak berhak untuk mengubah data penjualan salesman lain!'
                ], 400);
            }

            if ($penjualan->status != 'waiting') {
                return response()->json([
                    'message' => 'Data Penjualan yang telah disetujui tidak boleh diubah!'
                ], 422);
            }

            $this->validate($request, [
                'id_toko' => 'required|numeric|min:0|max:9999999999',
                'tanggal' => 'required|date',
                'tipe_pembayaran' => 'required|in:credit,cash,bg,trs',
                'tipe_harga' => 'required|exists:tipe_harga,tipe_harga',
            ]);

            $lock_order_mix = Toko::where('id', '=', $request->id_toko)->first();
            if ($lock_order_mix->lock_order == '1') {
                return response()->json([
                    'message' => 'Toko tidak boleh order, gunakan RPS baru!'
                ], 400);
            }
            if($lock_order_mix->tipe_3 == 'NOO'){
                if($request->tipe_pembayaran != 'cash'){
                    return response()->json([
                        'message' => 'NOO hanya dapat melakukan transaksi dengan tipe pembayaran cash'
                    ], 400);
                }
            }

            // $input = $request->all();
            // $input = $request->only('id_toko', 'id_salesman', 'tanggal', 'tipe_pembayaran', 'tipe_harga', 'keterangan');
            $input = $request->only('id_toko', 'tanggal', 'tipe_pembayaran', 'tipe_harga', 'keterangan', 'po_manual', 'id_toko');
            $id_toko = $request->id_toko;
            $input['id_salesman']   = $penjualan->id_salesman;
            $input['updated_by']    = $this->user->id;
            $ketentuan_toko         = Toko::find($id_toko)->ketentuan_toko;
            $input['top']           = $request->tipe_pembayaran === 'credit' ? $ketentuan_toko->top : 0;

            if ($id_toko != $penjualan->id_toko && !$this->user->can('Update Toko Penjualan')) {
                return response()->json([
                    'message' => 'Data toko pada penjualan tidak boleh diubah!'
                ], 422);
            }

            if ($penjualan) {
                PenjualanPajak::where('id_penjualan', $penjualan->id)->delete();
                $penjualan->update($input);
                PenjualanPajak::create([
                    'id_penjualan'  => $penjualan->id,
                    'npwp'          => $ketentuan_toko->npwp,
                    'nama_pkp'      => $ketentuan_toko->nama_pkp,
                    'alamat_pkp'    => $ketentuan_toko->alamat_pkp
                ]);
                return response()->json([
                    'message' => 'Data Penjualan telah berhasil diubah.',
                ], 201);
            }

            return response()->json([
                'message' => 'Data Penjualan tidak ditemukan.'
            ], 404);
        else :
            return $this->Unauthorized();
        endif;
    }

    public function destroy($id, Request $request)
    {
        if ($this->user->can('Hapus Penjualan')) :

            if (Helper::transactionLimit($this->user, 'penjualan.destroy')) {
                return $this->limitFalse('waktu operasional');
            }

            $penjualan = Penjualan::find($id);

            if ($penjualan->status != 'waiting') {
                return response()->json([
                    'message' => 'Data Penjualan yang telah disetujui tidak boleh dihapus!'
                ], 422);
            }

            if ($penjualan) {
                $data = ['deleted_by' => $this->user->id];
                $penjualan->update($data);
                $penjualan->delete();
                return response()->json([
                    'message' => 'Data Penjualan berhasil dihapus.',
                ], 200);
            }

            return response()->json([
                'message' => 'Data Penjualan tidak ditemukan!'
            ], 404);
        else :
            return $this->Unauthorized();
        endif;
    }

    public function restore($id)
    {
        if ($this->user->can('Tambah Penjualan')) :
            $penjualan = Penjualan::withTrashed()->find($id);

            if ($penjualan) {
                $data = ['deleted_by' => null];
                $penjualan->update($data);
                $penjualan->restore();

                return response()->json([
                    'message' => 'Data Penjualan berhasil dikembalikan.'
                ], 200);
            }

            return response()->json([
                'message' => 'Data Penjualan tidak ditemukan!'
            ], 404);
        else :
            return $this->Unauthorized();
        endif;
    }

    public function cancel($id)
    {
        if ($this->user->can('Cancel Penjualan')) :
            $penjualan = Penjualan::find($id);

            if (!$penjualan) {
                return response()->json([
                    'message' => 'Data penjualan tidak ditemukan.'
                ], 404);
            }

            if ($penjualan->status != 'waiting') {
                return response()->json([
                    'message' => 'Data Pembelian telah disetujui.'
                ], 422);
            }

            $penjualan->status = 'canceled';
            $penjualan->save();

            return response()->json([
                'message' => 'Data Penjualan berhasil dibatalkan.'
            ], 200);
        else :
            return $this->Unauthorized();
        endif;
    }

    public function close($id, Request $request)
    {
        if (!$this->user->can('Cancel Penjualan')) {
            return $this->Unauthorized();
        }

        if ($request->remark_close == '') {
            return response()->json([
                'message' => 'Alasan wajib isi'
            ], 422);
        }

        $penjualan = Penjualan::find($id);
        if (!$penjualan) {
            return response()->json([
                'message' => 'Data penjualan tidak ditemukan.'
            ], 404);
        }

        if ($penjualan->status != 'waiting') {
            return response()->json([
                'message' => 'Data Pembelian telah disetujui.'
            ], 422);
        }

        $penjualan->status = 'closed';
        $penjualan->remark_close = $request->remark_close;
        $penjualan->save();

        return response()->json([
            'message' => 'Data Penjualan berhasil dibatalkan.'
        ], 200);
    }

    public function set_loading($id, Request $request)
    {
        if ($this->user->can('Loading Penjualan')) :
            $penjualan = Penjualan::find($id);
            if (!$penjualan) {
                return response()->json([
                    'message' => 'Data penjualan tidak ditemukan.'
                ], 404);
            }

            if ($penjualan->status != 'approved') {
                return response()->json([
                    'message' => 'Data Pembelian belum disetujui.'
                ], 422);
            }

            $time = Carbon::now()->toTimeString();
            $date = Carbon::parse($request->tanggal)->toDateString();
            $penjualan->loading_at = $date . " " . $time;
            $penjualan->loading_by = $this->user->id;

            $penjualan->save();
            $logData = [
                'action' => 'Loading Penjualan',
                'description' => 'PO ' . $penjualan->id . ' Invoice: ' . $penjualan->no_invoice . ' Loading At ' . $penjualan->loading_at,
                'user_id' => $this->user->id
            ];

            $this->log($logData);
            return response()->json([
                'message' => 'Penjualan berhasil diloading',
                'delivered_at' => $penjualan->delivered_at
            ], 201);
        else :
            return $this->Unauthorized();
        endif;
    }

    public function deliver($id, Request $request)
    {
        // id penjualan
        //auth driver dan logistik, else return error message
        //if status data penjualan != loaded atau approved, return error message
        //set status = delivered & delivered_at = timestamps
        if ($this->user->can('Tanggal Terima Penjualan')) :
            try {
                $penjualan = Penjualan::find($id);
                $delivered_at = $request->delivery_at;

                if($delivered_at != Carbon::now()->toDateString() && !$this->user->can('Deliver Penjualan Forced')){
                    return response()->json([
                        'message' => 'Anda tidak dapat melakukan delivered mundur'
                    ], 400);
                }

                if ($penjualan) {
                    if ($penjualan->status != 'loaded' && $penjualan->status != 'approved') {
                        return response()->json([
                            // 'message' => 'Anda anda hanya bisa mengirimkan barang yang telah di-loading!'
                            'message' => 'Anda hanya bisa mengirimkan barang yang telah disetujui!'
                        ], 400);
                    }

                    // Update due_datenya juga
                    if ($penjualan->tipe_pembayaran == 'credit') {
                        // $top = KetentuanToko::where('id_toko', $penjualan->id_toko)->first()->top;
                        $top = $penjualan->top;
                        $due_date = Carbon::parse($delivered_at)->addDays($top)->toDateString();
                    } else {
                        $due_date = Carbon::parse($delivered_at)->toDateString();
                    }
                    $penjualan->due_date = $due_date;
                    $penjualan->status = 'delivered';
                    $penjualan->delivered_at = $delivered_at;
                    $penjualan->delivered_by = $this->user->id;

                    DB::beginTransaction();
                    $penjualan->save();
                    $logStock = [];
                    $detail_penjualan = DetailPenjualan::where('id_penjualan', $id)->get();
                    //Update Stock
                    foreach ($detail_penjualan as $dpj) {
                        $stock = Stock::find($dpj->id_stock);
                        $logStock[] = [
                            'tanggal'       => $delivered_at,
                            'id_barang'     => $stock->id_barang,
                            'id_gudang'     => $stock->id_gudang,
                            'id_user'       => $this->user->id,
                            'id_referensi'  => $dpj->id,
                            'referensi'     => 'penjualan',
                            'no_referensi'  => $id,
                            'qty_pcs'       => ($dpj->qty * $stock->isi) + $dpj->qty_pcs,
                            'status'        => 'delivered',
                            'created_at'    => Carbon::now(),
                            'updated_at'    => Carbon::now()
                        ];
                    }

                    $logData = [
                        'action' => 'Deliver Penjualan',
                        'description' => 'PO ' . $penjualan->id . ' Invoice: ' . $penjualan->no_invoice . ' Delivered At ' . $delivered_at,
                        'user_id' => $this->user->id
                    ];

                    $this->log($logData);
                    $this->createLogStock($logStock);
                    DB::commit();
                    return response()->json([
                        'message' => 'Barang telah terkirim ke toko.',
                        'delivered_at' => $penjualan->delivered_at
                    ], 201);
                }

                return response()->json([
                    'message' => 'Data Penjualan tidak ditemukan!'
                ], 404);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['error' => $e->getMessage()], 400);
            }

        else :
            return $this->Unauthorized();
        endif;
    }

    public function bulk_deliver(Request $request)
    {
        // id penjualan
        //auth driver dan logistik, else return error message
        //if status data penjualan != loaded atau approved, return error message
        //set status = delivered & delivered_at = timestamps
        if ($this->user->can('Tanggal Terima Penjualan')) :
            try {
                $penjualan = Penjualan::find($request->id);
                $delivered_at = $request->delivery_at;
                foreach ($penjualan as $pj) {
                    if ($pj->status != 'loaded' && $pj->status != 'approved') {
                        return response()->json([
                            'message' => 'Status penjualan ' . $pj->status . ' PO: '. $pj->id .' tidak bisa di tandai terkirim'
                        ], 400);
                    }
                }

                if ($penjualan) {
                    DB::beginTransaction();
                    foreach ($penjualan as $pj) {
                        // Update due_datenya juga
                        if ($pj->tipe_pembayaran == 'credit') {
                            $top        = $pj->top;
                            $due_date   = Carbon::parse($delivered_at)->addDays($top)->toDateString();
                        } else {
                            $due_date   = Carbon::parse($delivered_at)->toDateString();
                        }
                        $pj->due_date       = $due_date;
                        $pj->status         = 'delivered';
                        $pj->delivered_at   = $delivered_at;
                        $pj->delivered_by   = $this->user->id;
                        $pj->save();
                        $logStock           = [];
                        $detail_penjualan   = DetailPenjualan::where('id_penjualan', $pj->id)->get();
                        //Update Stock
                        foreach ($detail_penjualan as $dpj) {
                            $stock = Stock::find($dpj->id_stock);
                            $logStock[] = [
                                'tanggal'       => $delivered_at,
                                'id_barang'     => $stock->id_barang,
                                'id_gudang'     => $stock->id_gudang,
                                'id_user'       => $this->user->id,
                                'id_referensi'  => $dpj->id,
                                'referensi'     => 'penjualan',
                                'no_referensi'  => $pj->id,
                                'qty_pcs'       => ($dpj->qty * $stock->isi) + $dpj->qty_pcs,
                                'status'        => 'delivered',
                                'created_at'    => Carbon::now(),
                                'updated_at'    => Carbon::now()
                            ];
                        }

                        $logData = [
                            'action' => 'Deliver Penjualan',
                            'description' => 'PO ' . $pj->id . ' Invoice: ' . $pj->no_invoice . ' Delivered At ' . $delivered_at,
                            'user_id' => $this->user->id
                        ];

                        $this->log($logData);
                        $this->createLogStock($logStock);
                    }

                    DB::commit();
                    return response()->json([
                        'message' => 'Barang telah terkirim ke toko.',
                        'delivered_at' => $request->delivery_at
                    ], 201);
                }

                return response()->json([
                    'message' => 'Data Penjualan tidak ditemukan!'
                ], 404);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['error' => $e->getMessage()], 400);
            }

        else :
            return $this->Unauthorized();
        endif;
    }

    public function undeliver($id)
    {
        if ($this->user->can('Hapus Tanggal Terima Penjualan')) :
            try {
                $penjualan = Penjualan::find($id);
                $delivered_at = $penjualan->delivered_at;

                $to            = Carbon::now();
                $diff_in_days  = $delivered_at->diffInDays($to, false);

                if($diff_in_days>2 && !$this->user->can('Undeliver Penjualan Forced')){
                    return response()->json([
                        'message' => 'Pembatalan pengiriman tidak dapat dilakukan setelah melebihi 2 hari'
                    ], 400);
                }

                if ($penjualan) {
                    if ($penjualan->status != 'delivered') {
                        return response()->json([
                            'message' => 'Hapus tanggal terima tidak diijinkan'
                        ], 400);
                    }

                    $penjualan->status          = 'approved';
                    $penjualan->delivered_at    = null;

                    DB::beginTransaction();
                    $penjualan->save();

                    $logData = [
                        'action' => 'Cancel Deliver Penjualan',
                        'description' => 'PO ' . $penjualan->id . ' Invoice: ' . $penjualan->no_invoice,
                        'user_id' => $this->user->id
                    ];

                    $this->log($logData);

                    $this->deleteLogStock([
                        ['referensi', 'penjualan'],
                        ['no_referensi', $penjualan->id],
                        ['status', 'delivered']
                    ]);

                    $this->deleteLogStock([
                        ['referensi', 'penjualan'],
                        ['no_referensi', $penjualan->id],
                        ['status', 'loaded']
                    ]);

                    DB::commit();
                    return response()->json([
                        'message' => 'Tanggal terima berhasil dihapus',
                    ], 201);
                }
                return response()->json([
                    'message' => 'Data Penjualan tidak ditemukan!'
                ], 404);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['error' => $e->getMessage()], 400);
            }

        else :
            return $this->Unauthorized();
        endif;
    }

    public function cek_invoice_kosong()
    {
        $no_invoice = Penjualan::doesnthave('detail_penjualan')->pluck('id');
        return $no_invoice;
    }

    public function cek_od_ocl($id)
    {
        $penjualan      = Penjualan::find($id);
        $id_toko        = $penjualan->id_toko;
        $grandTotal     = $penjualan->tipe_pembayaran == 'cash' ? 0 : $penjualan->grand_total;
        $credit_limit   = KetentuanToko::find($id_toko)->limit;
        $tipe_pembayaran = KetentuanToko::find($id_toko)->k_t;
        $today          = Carbon::today()->toDateString();
        $grandTotal     = Penjualan::find($id)->grand_total;
        $id_mitra       = $penjualan->id_mitra;

        // list penjualan belum lunas
        $list_penjualan_belum_lunas = Penjualan::with('detail_penjualan.harga_barang.barang', 'toko')
            ->where('tipe_pembayaran', 'credit')->where('id_toko', $id_toko)
            ->whereIn('status', ['approved', 'delivered'])
            ->whereDate('tanggal', '>=', '2020-08-18')
            ->whereNull('paid_at')
            ->where('id_mitra', '=', $id_mitra)
            ->oldest()
            ->get();
        $id_penjualan_belum_lunas = $list_penjualan_belum_lunas->pluck('id');
        // jumlah yang sudah dicicil
        $total_pelunasan = DetailPelunasanPenjualan::whereIn('id_penjualan', $id_penjualan_belum_lunas)->whereIn('status', ['approved'])->get()->sum('nominal');
        // total credit / sisa piutang
        $total_credit = ($list_penjualan_belum_lunas->sum('grand_total') + $grandTotal) - $total_pelunasan;
        $toko       = Toko::find($id_toko);
        if ($credit_limit > 0 && $penjualan->tipe_pembayaran != 'cash') {
            if ($total_credit > $credit_limit) {
                if(!$this->user->can('Approve OCL')) {
                    $penjualan->pending_status = 'ocl';
                    $penjualan->save();
                    $message    = "Toko: " . $toko->nama_toko . " Total Credit: " . $total_credit . ", Credit Limit: " . $credit_limit . " Penjualan: " . json_encode($id_penjualan_belum_lunas);
                    $this->sendMessageBot(basename(__FILE__), __FUNCTION__, $message);
                    return [
                        'status' => false,
                        'message' => 'OVER CREDIT LIMIT. Melewati kredit limit, lunasi transaksi sebelumnya terlebih dahulu!'
                    ];
                }
            }
        }

        $toko_tanpa_od = TokoNoLimit::where('id_toko', $id_toko)->where('tipe', 'od')->first();
        if ($toko_tanpa_od === null) {
            foreach ($list_penjualan_belum_lunas as $d) {
                $due_date = Carbon::parse($d->due_date);

                // return $due_date->diffInDays($today, false);
                if ($due_date->diffInDays($today, false) > 0) {
                    if(!$this->user->can('Approve OD')) {
                        $penjualan->pending_status = 'od';
                        $penjualan->save();
                        $toko       = Toko::find($id_toko);
                        $message    = "Toko: " . $toko->nama_toko . " OD PO: " . $d->id;
                        $this->sendMessageBot(basename(__FILE__), __FUNCTION__, $message);
                        return [
                            'status' => false,
                            'message' => 'Toko ini mempunyai transaksi yang Over Due, harap lunasi transaksi sebelumnya terlebih dahulu.'
                        ];
                    }
                }
            }
        }

        return ['status' => true];
    }

    // http://localhost:8000/penjualan/list/promo/10003323/1/5 (id_penjualan/id_stock/qty_dus)
    public function list_promo(Request $request, $id_penjualan, $id_stock, $qty_dus)
    {
        // parameter request: start_date, end_date, untuk_id_depo, untuk_id_toko, untuk_id_barang

        // $penjualan = Penjualan::find($id_penjualan);
        // $id_toko = $penjualan->id_toko;
        $id_barang = Stock::find($id_stock)->id_barang;
        // $id_depo = $penjualan->salesman->tim->id_depo;

        $id_depo = 1;
        // $id_barang = 1;
        $id_toko = 1332;

        $today = Carbon::today()->toDateString();

        $list_promo = Promo::whereHas('promo_depo', function ($query) use ($id_depo) {
            $query->where('id_depo', $id_depo);
        })
            ->orDoesntHave('promo_depo')

            ->whereHas('promo_toko', function ($query) use ($id_toko) {
                $query->where('id_toko', $id_toko);
            })
            ->orDoesntHave('promo_toko')

            ->whereHas('promo_barang', function ($query) use ($id_barang) {
                $query->where('id_barang', $id_barang);
            })
            ->orDoesntHave('promo_barang')

            ->where('start_date', '<=', $today)->where('end_date', '>=', $today)
            ->where('min_qty_dus', '<=', $qty_dus)

            // ->get();
            ->get(['id', 'nama_promo']);
        // ->toSql();

        return $list_promo;
    }

    public function approve(Request $request, $id)
    {

        if ($this->user->can('Approve Penjualan')) :

            if (Helper::transactionLimit($this->user, 'penjualan.approve')) {
                return $this->limitFalse('waktu operasional');
            }

            $penjualan = Penjualan::find($id);
            $depo_canvass = [1, 5];
            if (!$penjualan) {
                return response()->json([
                    'message' => 'Data Pembelian tidak ditemukan.'
                ], 404);
            }

            if ($penjualan->status != 'waiting') {
                return response()->json([
                    'message' => 'Data Pembelian telah disetujui.'
                ], 422);
            }

            if ($this->user->hasRole('Salesman Canvass')) {
                if (!in_array($penjualan->id_depo, $depo_canvass)) {
                    return response()->json([
                        'message' => 'Anda tidak berhak untuk mengakses fungsi ini!'
                    ], 422);
                }

                if ($penjualan->salesman->tim->tipe != 'canvass') {
                    return response()->json([
                        'message' => 'Anda tidak berhak untuk mengakses fungsi ini!'
                    ], 422);
                }

                if ($penjualan->id_salesman != $this->user->id) {
                    return response()->json([
                        'message' => 'Anda tidak dapat menyetujui penjualan Salesman lain.'
                    ], 422);
                }
            }

            // CEK STATUS VERIFIKASI TOKO
            if ($penjualan->toko->status_verifikasi === 'N') {
                if ($penjualan->tipe_pembayaran == 'credit') {
                    return response()->json([
                        'message' => 'Toko belum terverifikasi, hanya boleh transaksi tunai',
                    ], 422);
                }
            }

            $detail_penjualan = DetailPenjualan::where('id_penjualan', $id)->get();
            if ($detail_penjualan->count() <= 0) {
                return response()->json([
                    'message' => 'Data penjualan masih kosong, isi data barang terlebih dahulu.'
                ], 422);
            }

            if ($penjualan->print_count == 0) {
                // CEK LIMIT CREDIT
                if ($penjualan->tipe_pembayaran == 'credit') {
                    $limit = $penjualan->toko->ketentuan_toko->limit;
                    $total = $penjualan->grand_total;
                    if ($total > $limit) {
                        if(!$this->user->can('Approve OCL')) {
                            $penjualan->pending_status = 'ocl';
                            $penjualan->save();
                            return response()->json([
                                'message' => 'Limit kredit toko tidak cukup, limit saat ini: Rp ' . number_format($limit, 2, ',', '.')
                            ], 422);
                        }
                    }
                }

                $cekOdOCL = $this->cek_od_ocl($penjualan->id);
                if ($cekOdOCL['status'] === false) {
                    return response()->json(['message' => $cekOdOCL['message']], 422);
                }
            }


            if(!$this->user->can('Approve Penjualan Minimum Order')) {
                // CEK MINIMAL ORDER VALUE
                $grand_total = $penjualan->grand_total;
                if ($penjualan->id_mitra <> 0 && $penjualan->print_count == 0) {
                    $mitra  = Mitra::find($penjualan->id_mitra);
                    if (!$mitra) {
                        return response()->json(['message' => 'Mitra tidak ditemukan'], 422);
                    }

                    $minimal_order  = $mitra->minimal_order;
                    if ($grand_total < $minimal_order) {
                        $penjualan->update(['pending_status' => 'min order']);
                        return response()->json(['message' => 'Minimal order mitra Rp '. number_format($minimal_order, 2, ',', '.')], 422);
                    }
                }

                // MINIMUM ORDER KI DAN KINOFOOD
                if($penjualan->print_count == 0) {
                    $id_principal = [25, 32];
                    if (in_array($penjualan->salesman->id_principal, $id_principal)) {
                        $toko_bebas_minimal_order = TokoNoLimit::where('id_toko',$penjualan->id_toko)
                                                    ->where('tipe','toko_bebas_minimal_order_kino')->exists();
                        if(!$toko_bebas_minimal_order){
                            $minimal_order = Reference::where('code', 'minimal_order_kino')->first();
                            $minimal_order = $minimal_order->value ?? 150000;
                            if ($grand_total < (int) $minimal_order) {
                                $penjualan->update(['pending_status' => 'min order']);
                                return response()->json(['message' => 'Minimal order Rp '. number_format($minimal_order, 2, ',', '.')], 422);
                            }
                        }
                    }
                }
            }

            $id_promo = [];
            //CEK STOCK GUDANG
            foreach ($detail_penjualan as $dpj) {
                $stock = Stock::find($dpj->id_stock);
                if ($penjualan->id_gudang <> null) {
                    if ($penjualan->id_gudang <> $stock->id_gudang) {
                        return response()->json(['message' => 'Stock dan gudang tidak sesuai, hubungi IT Support'], 422);
                    }
                }

                if ($dpj->id_promo <> 0) {
                    $id_promo[] = $dpj->id_promo;
                }

                if (!$this->user->can('Approve Stock Kurang')) {
                    $kode_barang = $stock->barang->kode_barang;
                    $order_qty = $detail_penjualan->where('id_stock', '=', $dpj->id_stock)->sum('qty');
                    $order_pcs = $detail_penjualan->where('id_stock', '=', $dpj->id_stock)->sum('qty_pcs');

                    //VALIDATE TOTAL PCS
                    $volume_stock   = ($stock->qty * $stock->isi) + $stock->qty_pcs;
                    $volume_order   = ($order_qty * $stock->isi) + $order_pcs;
                    if($volume_order > 0) {
                        $sisa_stock     = $volume_stock - $volume_order;
                        if ($sisa_stock < 0) {
                            $penjualan->pending_status = 'stock';
                            $penjualan->save();
                            return response()->json([
                                'message' => '1 Stock barang di gudang tidak cukup ' . $kode_barang . ', Mohon periksa data stock kembali! Volume Stock: ' . $volume_stock . ' Order: ' . $volume_order
                            ], 422);
                        }

                        $stock_order_qty = $stock->qty - $dpj->qty;
                        $stock_order_pcs = $stock->qty_pcs - $dpj->qty_pcs;


                        if ($stock_order_qty <= 0 && $stock_order_pcs < 0) {
                            return response()->json([
                                'message' => '3 Stock barang di gudang tidak cukup ' . $kode_barang . ', Mohon periksa data stock kembali!'
                            ], 422);
                        }
                    }
                }
            }

            if (count($id_promo) > 0) {
                $data_promo = Promo::whereIn('id', $id_promo)->get();
                $subtotal   = $penjualan->total_after_tax;
                foreach ($data_promo as $promo) {
                    $minimal_order_value = $promo->minimal_order;
                    if ($minimal_order_value > 0 && $subtotal < $minimal_order_value) {
                        return response()->json([
                            'message' => 'Penjualan tidak valid, cek promo minimal order value Rp '. number_format($minimal_order_value, 2, ',', '.') . ' Order: Rp '. number_format($subtotal, 2, ',', '.')
                        ], 422);
                    }
                }
            }

            // ============================== kurangi stock gudang ==============================
            try {
                DB::beginTransaction();
                $logStock = [];
                $logStockLoaded = [];
                $logStockCanvass = [];
                foreach ($detail_penjualan as $dpj) {

                    $stock = Stock::find($dpj->id_stock);

                    // $posisi_stock = PosisiStock::where('id_stock', $dpj->id_stock)->where('tanggal', $penjualan->tanggal)->latest()->first();
                    $posisi_stock = PosisiStock::where('id_stock', $dpj->id_stock)->where('tanggal', Carbon::today()->toDateString())->latest()->first();
                    if (!$posisi_stock) {
                        $posisi_stock = PosisiStock::create([
                            'id_stock' => $dpj->id_stock,
                            // 'tanggal' => $penjualan->tanggal,
                            'tanggal' => Carbon::today()->toDateString(),
                            'harga' => $stock->dbp,
                            'saldo_awal_qty' => $stock->qty,
                            'saldo_awal_pcs' => $stock->qty_pcs,
                            'saldo_akhir_qty' => $stock->qty,
                            'saldo_akhir_pcs' => $stock->qty_pcs,
                        ]);
                    }

                    // kurangi stock gudang
                    $stock->decrement('qty', $dpj->qty);
                    $stock->decrement('qty_pcs', $dpj->qty_pcs);
                    // jika stock pcs kurang, pecah 1 dus jadi pcs-an
                    while ($stock->qty_pcs < 0) {
                        $stock->decrement('qty');
                        $stock->increment('qty_pcs', $stock->isi);
                    }

                    // catat riwayat pergerakan stock
                    $posisi_stock->increment('penjualan_qty', $dpj->qty);
                    $posisi_stock->increment('penjualan_pcs', $dpj->qty_pcs);
                    $posisi_stock->decrement('saldo_akhir_qty', $dpj->qty);
                    $posisi_stock->decrement('saldo_akhir_pcs', $dpj->qty_pcs);
                    while ($posisi_stock->saldo_akhir_pcs < 0) {
                        $posisi_stock->decrement('saldo_akhir_qty');
                        $posisi_stock->increment('saldo_akhir_pcs', $stock->isi);
                    }

                    $logStock[] = [
                        'tanggal'       => $penjualan->no_invoice == '' ? Carbon::today()->toDateString() : $penjualan->tanggal_invoice,
                        'id_barang'     => $stock->id_barang,
                        'id_gudang'     => $stock->id_gudang,
                        'id_user'       => $this->user->id,
                        'id_referensi'  => $dpj->id,
                        'referensi'     => 'penjualan',
                        'no_referensi'  => $penjualan->id,
                        'qty_pcs'       => ($dpj->qty * $stock->isi) + $dpj->qty_pcs,
                        'status'        => 'approved',
                        'created_at'    => Carbon::now(),
                        'updated_at'    => Carbon::now()
                    ];

                    $logStockLoaded[] = [
                        'tanggal'       => $penjualan->no_invoice == '' ? Carbon::today()->toDateString() : $penjualan->tanggal_invoice,
                        'id_barang'     => $stock->id_barang,
                        'id_gudang'     => $stock->id_gudang,
                        'id_user'       => $this->user->id,
                        'id_referensi'  => $dpj->id,
                        'referensi'     => 'penjualan',
                        'no_referensi'  => $penjualan->id,
                        'qty_pcs'       => ($dpj->qty * $stock->isi) + $dpj->qty_pcs,
                        'status'        => 'loaded',
                        'created_at'    => Carbon::now(),
                        'updated_at'    => Carbon::now()
                    ];

                    $logStockCanvass[] = [
                        'tanggal'       => $penjualan->no_invoice == '' ? Carbon::today()->toDateString() : $penjualan->tanggal_invoice,
                        'id_barang'     => $stock->id_barang,
                        'id_gudang'     => $stock->id_gudang,
                        'id_user'       => $this->user->id,
                        'id_referensi'  => $dpj->id,
                        'referensi'     => 'penjualan',
                        'no_referensi'  => $penjualan->id,
                        'qty_pcs'       => ($dpj->qty * $stock->isi) + $dpj->qty_pcs,
                        'status'        => 'delivered',
                        'created_at'    => Carbon::now(),
                        'updated_at'    => Carbon::now()
                    ];
                }
                // ==================================================================================

                if ($penjualan->no_invoice == '') {
                    // ======================= GENERATE NOMOR INVOICE =======================
                    $kode_depo = $penjualan->salesman->tim->depo->kode_depo;
                    $today = Carbon::today()->format('dmy');

                    $keyword = '.' . $today . '.' . $kode_depo;
                    $list_no_invoice = \DB::table('penjualan')->where('import', '=', '0')
                        ->where('id_perusahaan', '=', $penjualan->id_perusahaan)
                        ->where('no_invoice', 'like', '%' . $keyword)->pluck('no_invoice');

                    if (count($list_no_invoice) == 0) {
                        $string_no = '00001';
                    } else {
                        $arr = [];
                        foreach ($list_no_invoice as $value) {
                            array_push($arr, (int)substr($value, strrpos($value, '-') + 1));
                        };
                        $new_no = max($arr) + 1;
                        $string_no = sprintf("%05d", $new_no);
                    }

                    $penjualan->no_invoice = $string_no . '.' . $today . '.' . $kode_depo;
                    $penjualan->tanggal_invoice = Carbon::today()->toDateString();
                    // ======================================================================

                    // ====================== due date ======================
                    if ($penjualan->tipe_pembayaran == 'credit') {
                        $top = $penjualan->toko->ketentuan_toko->top;
                        $top = $top == 0 ? $top = 14 : $top;
                        $due_date = Carbon::tomorrow()->addDays($top)->toDateString();
                    } else {
                        $due_date = Carbon::tomorrow()->toDateString();
                    }
                    $penjualan->due_date = $due_date;
                    // ======================================================
                }

                $penjualan->approved_at = Carbon::now()->toDateTimeString();
                $penjualan->approved_by = $this->user->id;
                $penjualan->status = 'approved';

                //Distribution Plan
                //Update Data qty_loading & qty_pcs_loading sesuai barang yang di approve
                foreach ($detail_penjualan as $dpj) {
                    $dpj->qty_loading = $dpj->qty;
                    $dpj->qty_approve = $dpj->qty;
                    $dpj->qty_pcs_loading = $dpj->qty_pcs;
                    $dpj->qty_pcs_approve = $dpj->qty_pcs;
                    $dpj->save();
                }
                //End Update Data qty_loading & qty_pcs_loading sesuai barang yang di approve
                //End Distribution Plan

                PenjualanHeader::create(['no_invoice' => $penjualan->no_invoice]);
                if ($penjualan->salesman->tim->tipe == 'canvass' && in_array($penjualan->id_depo, $depo_canvass)) {
                    $penjualan->delivered_at = Carbon::now()->toDateTimeString();
                    $penjualan->delivered_by = $this->user->id;
                    $penjualan->status = 'delivered';
                    $this->createLogStock($logStockCanvass);
                }

                $logData = [
                    'action' => 'Approve Penjualan',
                    'description' => 'PO ' . $penjualan->id . ' Invoice: ' . $penjualan->no_invoice,
                    'user_id' => $this->user->id
                ];

                $this->log($logData);
                $this->createLogStock($logStock);
                if($penjualan->driver_id <> null) {
                    $this->createLogStock($logStockLoaded);
                    $penjualan->status = 'loaded';
                }

                $penjualan->pending_status = null;
                $penjualan->save();
                DB::commit();
                return response()->json([
                    'message' => 'Data Penjualan berhasil disetujui.',
                    'no_invoice' => $penjualan->no_invoice,
                    'status' => $penjualan->status
                ], 200);
            } catch (\Illuminate\Database\QueryException $e) {
                DB::rollBack();
                $errorCode = $e->errorInfo[1];
                if ($errorCode == 1062) {
                    return response()->json([
                        'message' => 'No Invoice Duplikat, silahkan coba approve kembali'
                    ], 400);
                }

                return response()->json([
                    'message' => 'Data Penjualan gagal disetujui.'
                ], 400);
            }
        else :
            return $this->Unauthorized();
        endif;
    }

    public function cancel_approval($id)
    {
        if ($this->user->can('Unapprove Penjualan')) :
            $penjualan = Penjualan::find($id);

            if (!$penjualan) {
                return response()->json([
                    'message' => 'Data penjualan tidak ditemukan.'
                ], 404);
            }

            if ($this->user->hasRole('Salesman Canvass')) {
                if ($penjualan->id_salesman != $this->user->id) {
                    return response()->json([
                        'message' => 'Anda tidak dapat menyetujui penjualan Salesman lain.'
                    ], 422);
                }
            }

            if ($penjualan->status != 'approved' && $penjualan->status != 'loaded') {
                return response()->json([
                    'message' => 'Anda tidak boleh membatalkan approval untuk penjualan ini'
                ], 422);
            }

            if ($penjualan->no_pajak != '' && $penjualan->no_pajak != null) {
                if(!$this->user->can('Unapprove Penjualan Pajak')) {
                    return response()->json([
                        'message' => 'Faktur pajak sudah di proses, hubungi bagian pajak'
                    ], 422);
                }
            }

            $status = $penjualan->status;
            try {
                DB::beginTransaction();
                $penjualan->status      = 'waiting';
                $penjualan->updated_by  = $this->user->id;
                $penjualan->approved_at = null;
                $penjualan->approved_by = null;
                $header = PenjualanHeader::where('no_invoice', $penjualan->no_invoice)->first();
                if ($header) {
                    $header->delete();
                }
                // $penjualan->due_date = null;
                // $penjualan->no_invoice = null;
                $penjualan->save();

                // ============================== balikin stock gudang ==============================
                $detail_penjualan = DetailPenjualan::where('id_penjualan', $id)->get();

                foreach ($detail_penjualan as $dpj) {

                    $stock = Stock::find($dpj->id_stock);

                    // $posisi_stock = PosisiStock::where('id_stock', $dpj->id_stock)->where('tanggal', $penjualan->tanggal)->latest()->first();
                    $posisi_stock = PosisiStock::where('id_stock', $dpj->id_stock)->where('tanggal', Carbon::today()->toDateString())->latest()->first();
                    if (!$posisi_stock) {
                        $posisi_stock = PosisiStock::create([
                            'id_stock' => $dpj->id_stock,
                            // 'tanggal' => $penjualan->tanggal,
                            'tanggal' => Carbon::today()->toDateString(),
                            'harga' => $stock->dbp,
                            'saldo_awal_qty' => $stock->qty,
                            'saldo_awal_pcs' => $stock->qty_pcs,
                            'saldo_akhir_qty' => $stock->qty,
                            'saldo_akhir_pcs' => $stock->qty_pcs,
                        ]);
                    }

                    // kembalikan stock gudang
                    $stock->increment('qty', $dpj->qty);
                    $stock->increment('qty_pcs', $dpj->qty_pcs);

                    // catat riwayat pergerakan stock
                    $posisi_stock->decrement('penjualan_qty', $dpj->qty);
                    $posisi_stock->decrement('penjualan_pcs', $dpj->qty_pcs);
                    $posisi_stock->increment('saldo_akhir_qty', $dpj->qty);
                    $posisi_stock->increment('saldo_akhir_pcs', $dpj->qty_pcs);
                    while ($posisi_stock->saldo_akhir_pcs >= $stock->isi) {
                        $posisi_stock->increment('saldo_akhir_qty');
                        $posisi_stock->decrement('saldo_akhir_pcs', $stock->isi);
                    }
                }
                //Set Data qty_loading & qty_pcs_loading menjadi 0
                foreach ($detail_penjualan as $dpj) {
                    $dpj->qty_loading = 0;
                    $dpj->qty_pcs_loading = 0;
                    $dpj->save();
                }
                //End Set Data qty_loading & qty_pcs_loading menjadi 0

                $logData = [
                    'action' => 'Cancel Penjualan',
                    'description' => 'PO ' . $penjualan->id . ' Invoice: ' . $penjualan->no_invoice,
                    'user_id' => $this->user->id
                ];

                $this->deleteLogStock([
                    ['referensi', 'penjualan'],
                    ['no_referensi', $penjualan->id],
                    ['status', 'approved']
                ]);

                $this->log($logData);

                if($status === 'loaded') {
                    $this->deleteLogStock([
                        ['referensi', 'penjualan'],
                        ['no_referensi', $penjualan->id],
                        ['status', 'loaded']
                    ]);
                }


                DB::commit();
                // ==================================================================================
                return response()->json([
                    'message' => 'Approval untuk data Penjualan ini telah dibatalkan.',
                    'status' => 'waiting'
                ], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Unapprove gagal' . $e->getMessage(),
                    'status' => 'approved'
                ], 400);
            }
        else :
            return $this->Unauthorized();
        endif;
    }

    /**
     * Update due date penjualan
     * @param Request $request
     * @param $id
     */
    public function updateDueDate(Request $request, $id)
    {
        if ($this->user->can('Update Due Date')) :
            $penjualan = Penjualan::find($id);
            if (!$penjualan) {
                return response()->json([
                    'message' => 'Data penjualan tidak ditemukan.'
                ], 404);
            }

            $this->validate($request, [
                'due_date' => 'required|date'
            ]);

            $logData = [
                'action' => 'Update Due Date Penjualan',
                'description' => 'PO ' . $penjualan->id . ' Invoice: ' . $penjualan->no_invoice . ' Dari ' . $penjualan->due_date . ' Ke ' . $request->due_date,
                'user_id' => $this->user->id
            ];

            $penjualan->due_date = $request->due_date;
            $penjualan->save();

            $this->log($logData);

            return response()->json([
                'message' => 'Due date penjualan berhasil di ubah'
            ], 201);
        else :
            return $this->Unauthorized();
        endif;
    }
    // Parameter filter wajib diisi : id_salesman, date
    public function list_invoice_pdf(Request $request)
    {
        if ($this->user->can('Download Invoice Penjualan')) :
            $list_penjualan = Penjualan::with('toko')->whereNotIn('status', ['waiting', 'canceled'])->oldest()->where('id_salesman', $request->id_salesman)->where('tanggal', $request->date)->get();

            $salesman = Salesman::with('tim', 'user')->find($request->id_salesman);

            return response()->json([
                // 'message' => 'List Invoice.'
                'id_salesman' => $request->id_salesman,
                'nama_salesman' => $salesman->user->name,
                'id_tim' => $salesman->id_tim,
                'nama_tim' => $salesman->tim->nama_tim,
                'date' => $request->date,
                'data' => $list_penjualan
            ], 200);
        else :
            return $this->Unauthorized();
        endif;
    }

    public function importNoPajak(Request $request)
    {
        if ($this->user->can('Import Pajak')) :
            $this->validate($request, [
                'file' => 'required'
            ]);

            $logData = [
                'action' => 'Import No Pajak Penjualan',
                'description' => '',
                'user_id' => $this->user->id
            ];

            $allowExtension = ['xls', 'xlsx'];
            $file       = $request->file;
            $extension  = $file[0]->getClientOriginalExtension();
            if (!in_array($extension, $allowExtension)) {
                return response()->json(['message' => 'hanya mendukung file xls, xlsx'], 422);
            }

            $rows = Excel::toArray(new PajakImport, $file[0]);
            if (count($rows[0]) > 0) {
                DB::beginTransaction();
                try {
//                    foreach ($rows[0] as $key => $row) {
//                        $penjualan = Penjualan::where('no_invoice', '=', $row[2])->first();
//                        if (!$penjualan) {
//                            continue;
//                        }
//                        $penjualan->no_pajak = $row[1];
//                        $penjualan->save();
//                    }
                    $raw_query = "";
                    foreach ($rows[0] as $key => $row) {
                        $where = "AND id_perusahaan IN (SELECT id FROM perusahaan WHERE kode_perusahaan = '{$row[3]}')";
                        if ($row[3] == 'UMJ') {
                            $where = "AND id_perusahaan = 1 AND id_mitra = '1'";
                        }
                        $raw_query.= "UPDATE penjualan SET `no_pajak` = '{$row[1]}' WHERE `no_invoice` = '{$row[2]}' {$where};";
                    }

                    DB::unprepared($raw_query);
                    DB::commit();
                    return response()->json(['message' => 'import berhasil :-)'], 200);
                } catch (\Exception $e) {
                    DB::rollback();
                    return response()->json(['message' => 'Opss... gagal. '.json_encode($e->getMessage())], 400);
                }
            } else {
                return response()->json(['message' => 'file excel kosong :-('], 422);
            }
        else :
            $this->Unauthorized();
        endif;
    }

    public function posisiPenjualan(Request $request)
    {
        if ($this->user->can('Posisi Penjualan')) :
            $this->validate($request, [
                'tipe_salesman' => 'required',
                'start_date'    => 'required|date',
                'end_date'      => 'required|date'
            ]);

            $start_date     = $request->start_date;
            $end_date       = $request->end_date;
            $tipe_salesman  = $request->tipe_salesman;
            $id_salesman    = $request->id_salesman != '' ? $request->id_salesman : 'all';

            if ($tipe_salesman <> 'all' && $id_salesman == 'all') {
                $id_salesman = Salesman::with('tim')
                    ->whereHas('tim', function ($q) use ($tipe_salesman) {
                        return $q->where('tipe', $tipe_salesman);
                    })->pluck('user_id');
            } else {
                $id_salesman = [$id_salesman];
            }

            $posisi = Penjualan::select('latitude', 'longitude')
                ->when($id_salesman[0] <> 'all', function ($q) use ($id_salesman) {
                    return $q->whereIn('id_salesman', $id_salesman);
                })
                ->whereIn('status', ['approved', 'delivered'])
                ->whereBetween('tanggal', [$start_date, $end_date])->get();

            if ($posisi) {
                return response()->json($posisi->toArray(), 200);
            }

            return response()->json(['message' => 'data tidak ditemukan'], 400);
        else :
            return $this->Unauthorized();
        endif;
    }

    //Distribution Plan
    public function setSchedule(PenjualanRequest $request)
    {
        if ($this->user->can('Atur Jadwal Pengiriman')) :
            // $penjualan = Penjualan::find($id);
            $penjualan  = Penjualan::whereIn('id', $request->id)->get();
            $logStock   = [];
            DB::beginTransaction();
            try {
                foreach ($penjualan as $pj) {
                    $data['tanggal_jadwal'] = $request->tanggal_jadwal;
                    $data['driver_id']      = $request->driver_id;
                    $data['checker_id']     = $request->checker_id;
                    $data['loading_by']     = $request->checker_id;
                    $data['loading_at']     =  Carbon::now();
                    $data['status']         = 'loaded';
                    Penjualan::where('id', $pj->id)->update($data);
                    //Update Data qty_loading & qty_pcs_loading sesuai barang yang di order
                    $detail_penjualan = DetailPenjualan::where('id_penjualan', $pj->id)->get();
                    foreach ($detail_penjualan as $dpj) {
                        $dpj->qty_loading = $dpj->qty;
                        $dpj->qty_approve = $dpj->qty;
                        $dpj->qty_pcs_loading = $dpj->qty_pcs;
                        $dpj->qty_pcs_approve = $dpj->qty_pcs;
                        $dpj->save();
                        $stock = Stock::find($dpj->id_stock);

                        $logStock[] = [
                            'tanggal'       => $request->tanggal_jadwal,
                            'id_barang'     => $stock->id_barang,
                            'id_gudang'     => $stock->id_gudang,
                            'id_user'       => $this->user->id,
                            'id_referensi'  => $dpj->id,
                            'referensi'     => 'penjualan',
                            'no_referensi'  => $pj->id,
                            'qty_pcs'       => ($dpj->qty * $stock->isi) + $dpj->qty_pcs,
                            'status'        => 'loaded',
                            'created_at'    => Carbon::now(),
                            'updated_at'    => Carbon::now()
                        ];
                    }
                }

                $this->createLogStock($logStock);

                $po = $penjualan->pluck('id')->toArray();
                $list_po = implode(", ",$po);
                $logData = [
                    'action' => 'Atur Jadwal Pengiriman',
                    'description' => $list_po,
                    'user_id' => $this->user->id
                ];
                $this->log($logData);
                //End Update Data qty_loading & qty_pcs_loading sesuai barang yang di order
                DB::commit();
            } catch (\Exception $e) {
                DB::rollback();
                return response()->json(['message' => 'Opss... gagal'], 400);
            }
        else :
            return $this->Unauthorized();
        endif;
    }

    public function reSchedule(PenjualanRequest $request)
    {
        if ($this->user->can('Atur Jadwal Pengiriman')) :
            // $penjualan = Penjualan::find($id);
            $penjualan  = Penjualan::whereIn('id', $request->id)->get();
            $logStock   = [];
            DB::beginTransaction();
            try {
                foreach ($penjualan as $pj) {
                    $data['tanggal_jadwal'] = $request->tanggal_jadwal;
                    $data['driver_id']      = $request->driver_id;
                    $data['checker_id']     = $request->checker_id;
                    $data['loading_by']     = $request->checker_id;
                    $data['loading_at']     =  Carbon::now();
                    $data['status']         = 'loaded';
                    Penjualan::where('id', $pj->id)->update($data);
                    //Update Data qty_loading & qty_pcs_loading sesuai barang yang di order
                    $detail_penjualan = DetailPenjualan::where('id_penjualan', $pj->id)->get();
                    foreach ($detail_penjualan as $dpj) {
                        $dpj->qty_loading = $dpj->qty;
                        $dpj->qty_approve = $dpj->qty;
                        $dpj->qty_pcs_loading = $dpj->qty_pcs;
                        $dpj->qty_pcs_approve = $dpj->qty_pcs;
                        $dpj->save();
                        $stock = Stock::find($dpj->id_stock);

                        $logStockCheck = LogStock::where('referensi','penjualan')
                                                    ->where('no_referensi',$pj->id)
                                                    ->where('status','loaded')
                                                    ->first();

                        if ($logStockCheck != null) {
                            $this->deleteLogStock([
                                ['referensi', 'penjualan'],
                                ['no_referensi',$pj->id],
                                ['status', 'loaded']
                            ]);
                        }

                        $logStock[] = [
                            'tanggal'       => $request->tanggal_jadwal,
                            'id_barang'     => $stock->id_barang,
                            'id_gudang'     => $stock->id_gudang,
                            'id_user'       => $this->user->id,
                            'id_referensi'  => $dpj->id,
                            'referensi'     => 'penjualan',
                            'no_referensi'  => $pj->id,
                            'qty_pcs'       => ($dpj->qty * $stock->isi) + $dpj->qty_pcs,
                            'status'        => 'loaded',
                            'created_at'    => Carbon::now(),
                            'updated_at'    => Carbon::now()
                        ];
                    }
                }

                $this->createLogStock($logStock);

                $po = $penjualan->pluck('id')->toArray();
                $list_po = implode(", ",$po);
                $logData = [
                    'action' => 'Update Jadwal Pengiriman',
                    'description' => $list_po,
                    'user_id' => $this->user->id
                ];
                $this->log($logData);

                //End Update Data qty_loading & qty_pcs_loading sesuai barang yang di order
                DB::commit();
                return $logData;
            } catch (\Exception $e) {
                DB::rollback();
                return response()->json(['message' => 'Opss... gagal'], 400);
            }
        else :
            return $this->Unauthorized();
        endif;
    }

    public function unSchedule(Request $request)
    {
        if ($this->user->can('Batal Jadwal Pengiriman')) :
            // $penjualan = Penjualan::find($id);
            $penjualan = Penjualan::whereIn('id', $request->id)->get();
            DB::beginTransaction();
            try {
                foreach ($penjualan as $pj) {
                    $data['tanggal_jadwal'] = null;
                    $data['driver_id'] = null;
                    $data['checker_id'] = null;
                    $data['loading_by'] = null;
                    $data['loading_at'] =  null;
                    $data['status'] = 'approved';
                    Penjualan::where('id', $pj->id)->update($data);

                    $this->deleteLogStock([
                        ['referensi', 'penjualan'],
                        ['no_referensi', $pj->id],
                        ['status', 'loaded']
                    ]);
                }
                DB::commit();
                return response()->json(['message' => 'Jadwal penjualan berhasil dibatalkan)'], 200);
            } catch (\Exception $e) {
                DB::rollback();
                return response()->json(['message' => $e->getMessage()], 400);
            }
        else :
            return $this->Unauthorized();
        endif;
    }

    public function loading(Request $request, $id)
    {

        if ($this->user->hasRole('Checker') || $this->user->can('Loading Penjualan')) {

            $penjualan = Penjualan::find($id);
            if (!$penjualan) {
                return response()->json([
                    'message' => 'Data penjualan tidak ditemukan.'
                ], 404);
            }

            if ($penjualan->status != 'approved') {
                return response()->json([
                    'message' => 'Data Pembelian belum disetujui.'
                ], 422);
            }

            if ($penjualan->status == 'delivered') {
                return response()->json([
                    'message' => 'Data Sudah disetujui.'
                ], 422);
            }

            $detail_penjualan = DetailPenjualan::where('id_penjualan', $id)->get();
            // return $detail_penjualan;

            try {
                DB::beginTransaction();

                //Update Stock
                foreach ($detail_penjualan as $dpj) {

                    $stock = Stock::find($dpj->id_stock);

                    $posisi_stock = PosisiStock::where('id_stock', $dpj->id_stock)->where('tanggal', Carbon::today()->toDateString())->latest()->first();
                    if ($posisi_stock === null) {
                        throw new ModelNotFoundException('Posisi stock tidak ditemukan, hubungi IT Support');
                    }

                    $isi         = $stock->isi;
                    $pcs_loading = ($dpj->qty_loading * $stock->isi) + $dpj->qty_pcs_loading;
                    $pcs_approve = ($dpj->qty_approve * $isi) + $dpj->qty_pcs_approve;
                    $pcs_selisih = $pcs_approve - $pcs_loading;


                    if ($pcs_selisih <> 0) {
                        $stock->increment('qty_pcs', $pcs_selisih);
                        $posisi_stock->increment('penjualan_pcs', $pcs_selisih);
                        $posisi_stock->increment('saldo_akhir_pcs', $pcs_selisih);
                    }
                }
                // End Update Stock ==================================================================================

                //Update Status Ke Loading
                $penjualan->status = 'loaded';
                $penjualan->updated_by = $this->user->id;

                // $time = Carbon::now()->toTimeString();
                // $date = Carbon::parse($request->tanggal)->toDateString();
                // $penjualan->loading_at = $date." ".$time;
                $penjualan->loading_at = Carbon::now();
                $penjualan->loading_by = $this->user->id;
                $penjualan->checker_id = $this->user->id;

                $penjualan->save();
                $logData = [
                    'action' => 'Loading Penjualan',
                    'description' => 'PO ' . $penjualan->id . ' Invoice: ' . $penjualan->no_invoice . ' Loading At ' . $penjualan->loading_at,
                    'user_id' => $this->user->id
                ];

                $this->log($logData);

                DB::commit();

                return response()->json([
                    'message' => 'Penjualan berhasil diloading',
                    'delivered_at' => $penjualan->tanggal_jadwal
                ], 201);
            } catch (\Exception $e) {
                DB::rollBack();
                $message    = "Penjualan: " . $id . ", Loading Error: " . $e->getMessage();
                $this->sendMessageBot(basename(__FILE__), __FUNCTION__, $message);
                return response()->json([
                    'message' => 'Loading Gagal',
                    'status' => 'Loading'
                ], 400);
            }
        } else {
            return $this->Unauthorized();
        }
    }

    public function unloading(Request $request, $id)
    {

        if ($this->user->hasRole('Checker') || $this->user->can('Loading Penjualan')) {

            $penjualan = Penjualan::find($id);
            if (!$penjualan) {
                return response()->json([
                    'message' => 'Data penjualan tidak ditemukan.'
                ], 404);
            }

            if ($penjualan->status == 'delivered') {
                return response()->json([
                    'message' => 'Data Pembelian Sudah Dikirim dan Diterima Oleh Customer.'
                ], 422);
            }

            if ($penjualan->status != 'loaded') {
                return response()->json([
                    'message' => 'Data Pembelian tidak berstatus loading.'
                ], 422);
            }

            $detail_penjualan = DetailPenjualan::where('id_penjualan', $id)->get();

            try {
                DB::beginTransaction();

                //Update Stock
                foreach ($detail_penjualan as $dpj) {
                    $posisi_stock = PosisiStock::where('id_stock', $dpj->id_stock)->where('tanggal', Carbon::today()->toDateString())->latest()->first();
                    if (!$posisi_stock) {
                        throw new ModelNotFoundException('Posisi stock tidak ditemukan, hubungi IT Support');
                    }
                }
                // End Update Stock

                //Update Status Ke Approve
                $penjualan->status = 'approved';
                $penjualan->updated_by = $this->user->id;

                // $time = Carbon::now()->toTimeString();
                // $date = Carbon::parse($request->tanggal)->toDateString();
                // $penjualan->loading_at = $date." ".$time;
                $penjualan->loading_at = Carbon::now();
                $penjualan->loading_by = $this->user->id;
                $penjualan->checker_id = $this->user->id;

                $penjualan->save();
                $logData = [
                    'action' => 'UnLoading Penjualan',
                    'description' => 'PO ' . $penjualan->id . ' Invoice: ' . $penjualan->no_invoice . ' UnLoading At ' . $penjualan->loading_at,
                    'user_id' => $this->user->id
                ];

                $this->log($logData);

                DB::commit();

                return response()->json([
                    'message' => 'Penjualan berhasil diunloading',
                    'delivered_at' => $penjualan->tanggal_jadwal
                ], 201);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Loading gagal',
                    'status' => 'Loading'
                ], 400);
            }
        } else {
            return $this->Unauthorized();
        }
    }

    public function penjualan_driver(Request $request, $per_page = 5)
    {
        if ($this->user->can('Menu Penjualan')) :
            $user_id = $this->user->id;

            $list_penjualan = Penjualan::relationData()->where('status', 'loaded')
                ->where('driver_id', $this->user->id)
                ->whereDate('tanggal_jadwal', Carbon::today());


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

            $perPage = $request->has('per_page') ? $perPage = $request->per_page : $perPage = 'all';
            $list_penjualan = $perPage == 'all' ? $list_penjualan->get() : $list_penjualan->paginate((int)$perPage);
            if ($list_penjualan) {
                return PenjualanResource::collection($list_penjualan);
            }

            return response()->json([
                'message' => 'Data Penjualan tidak ditemukan!'
            ], 404);
        else :
            return $this->Unauthorized();
        endif;
    }

    //checker hanya melihat data penjualan yang memiliki driver, tanggal pengiriman hari ini, status approve, dan memiliki akses gudang sesuai dengan stock barang
    public function penjualan_by_id_driver(Request $request, $id)
    {
        if ($this->user->can('List Check Barang') || $this->user->hasRole('Checker') && $id != null) {
            $id_gudang = Helper::gudangByUser($this->user->id);
            $list_penjualan = Penjualan::relationData()->when($id_gudang <> '', function ($q) use ($id_gudang) {
                return $q->whereHas('detail_penjualan', function ($q) use ($id_gudang) {
                    return $q->whereHas('stock', function ($q) use ($id_gudang) {
                        return $q->whereIn('id_gudang', $id_gudang);
                    });
                });
            })
                ->where('driver_id', $id)
                ->where('status', 'approved')
                ->whereDate('tanggal_jadwal', Carbon::today());

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

            $perPage = $request->has('per_page') ? $perPage = $request->per_page : $perPage = 'all';
            $list_penjualan = $perPage == 'all' ? $list_penjualan->get() : $list_penjualan->paginate((int)$perPage);

            if ($list_penjualan) {
                return PenjualanResource::collection($list_penjualan);
            }

            return response()->json([
                'message' => 'Data Penjualan tidak ditemukan!'
            ], 404);
        } else {
            return $this->Unauthorized();
        }
    }

    public function riwayat_checker_date(Request $request)
    {
        $user_id = $this->user->id;
        // $user_id = 131;
        if ($this->user->hasRole('Checker') || $this->user->can('Riwayat Checker')) {
            $date_penjualan = Penjualan::select(
                DB::raw('DATE(tanggal_jadwal) as date'),
                DB::raw('count(*) as invoice')
            )
                ->where('checker_id', $user_id)
                ->groupBy('date')
                ->orderBy('tanggal_jadwal', 'DESC');

            $perPage = $request->has('per_page') ? $perPage = $request->per_page : $perPage = 'all';
            $date_penjualan = $perPage == 'all' ? $date_penjualan->get() : $date_penjualan->paginate((int)$perPage);

            return $date_penjualan;
        } else {
            return $this->Unauthorized();
        }
    }

    public function riwayat_driver_date(Request $request)
    {
        $user_id = $this->user->id;
        // $user_id = 80;
        if ($this->user->hasRole('Driver') || $this->user->can('Riwayat Driver')) {
            $date_penjualan = Penjualan::select(
                DB::raw('DATE(tanggal_jadwal) as date'),
                DB::raw('count(*) as invoice')
            )
                ->where('driver_id', $user_id)
                ->where('status', 'delivered')
                ->groupBy('date')
                ->orderBy('tanggal_jadwal', 'DESC');

            $perPage = $request->has('per_page') ? $perPage = $request->per_page : $perPage = 'all';
            $date_penjualan = $perPage == 'all' ? $date_penjualan->get() : $date_penjualan->paginate((int)$perPage);

            return $date_penjualan;
        } else {
            return $this->Unauthorized();
        }
    }

    public function riwayat_checker_invoice(Request $request)
    {
        if ($this->user->hasRole('Checker') || $this->user->can('Riwayat Checker')) {
            // $tanggal = '2020-10-26';
            // $user_id = 131;
            $tanggal = $request->tanggal;
            $user_id = $this->user->id;
            $list_penjualan = Penjualan::relationData()->where('checker_id', $user_id)
                ->where('tanggal_jadwal', $request->tanggal);

            $perPage = $request->has('per_page') ? $perPage = $request->per_page : $perPage = 'all';
            $list_penjualan = $perPage == 'all' ? $list_penjualan->get() : $list_penjualan->paginate((int)$perPage);

            if ($list_penjualan) {
                return PenjualanResource::collection($list_penjualan);
            }

            return response()->json([
                'message' => 'Data Penjualan tidak ditemukan!'
            ], 404);
        } else {
            return $this->Unauthorized();
        }
    }

    public function riwayat_driver_invoice(Request $request)
    {
        if ($this->user->hasRole('Driver') || $this->user->can('Riwayat Driver')) {
            // $user_id = 80;
            // $tanggal = '2020-10-26';
            $tanggal = $request->tanggal;
            $user_id = $this->user->id;
            $list_penjualan = Penjualan::relationData()->where('driver_id', $user_id)
                ->where('tanggal_jadwal', $request->tanggal)
                ->where('status', 'delivered');

            $perPage = $request->has('per_page') ? $perPage = $request->per_page : $perPage = 'all';
            $list_penjualan = $perPage == 'all' ? $list_penjualan->get() : $list_penjualan->paginate((int)$perPage);

            if ($list_penjualan) {
                return PenjualanResource::collection($list_penjualan);
            }

            return response()->json([
                'message' => 'Data Penjualan tidak ditemukan!'
            ], 404);
        } else {
            return $this->Unauthorized();
        }
    }

    // Set Deliver
    public function distribution_deliver($id)
    {
        //$id -> id penjualan
        if ($this->user->can('Tanggal Terima Penjualan')) :
            try {
                $penjualan = Penjualan::find($id);
                $delivered_at = Carbon::now();

                if ($penjualan) {
                    if ($penjualan->status != 'loaded') {
                        return response()->json([
                            'message' => 'Anda anda hanya bisa mengirimkan barang yang telah dicheck oleh Checker!'
                        ], 400);
                    }

                    // Update due_datenya juga
                    if ($penjualan->tipe_pembayaran == 'credit') {
                        // $top = KetentuanToko::where('id_toko', $penjualan->id_toko)->first()->top;
                        $top = $penjualan->toko->ketentuan_toko->top;
                        $top = $top == 0 ? $top = 14 : $top;
                        $due_date = Carbon::parse($delivered_at)->addDays($top)->toDateString();
                    } else {
                        $due_date = Carbon::parse($delivered_at)->toDateString();
                    }
                    $penjualan->due_date = $due_date;
                    $penjualan->status = 'delivered';
                    $penjualan->delivered_at = $delivered_at;
                    $penjualan->delivered_by = $this->user->id;

                    DB::beginTransaction();
                    $penjualan->save();

                    $detail_penjualan = DetailPenjualan::where('id_penjualan', $id)->get();

                    $logStock = [];
                    //Update Stock
                    foreach ($detail_penjualan as $dpj) {

                        $stock = Stock::find($dpj->id_stock);

                        $posisi_stock = PosisiStock::where('id_stock', $dpj->id_stock)->where('tanggal', Carbon::today()->toDateString())->latest()->first();
                        if ($posisi_stock === null) {
                            throw new ModelNotFoundException('Posisi stock tidak ditemukan, hubungi IT Support');
                        }

                        $isi         = $stock->isi;
                        $pcs_loading = ($dpj->qty_loading * $stock->isi) + $dpj->qty_pcs_loading;
                        $pcs_deliver = ($dpj->qty * $isi) + $dpj->qty_pcs;
                        $pcs_selisih = $pcs_loading - $pcs_deliver;

                        if ($pcs_selisih <> 0) {
                            $stock->increment('qty_pcs', $pcs_selisih);
                            $posisi_stock->increment('penjualan_pcs', $pcs_selisih);
                            $posisi_stock->increment('saldo_akhir_pcs', $pcs_selisih);
                        }

                        $logStock[] = [
                            'tanggal'       => $delivered_at->toDateString(),
                            'id_barang'     => $stock->id_barang,
                            'id_gudang'     => $stock->id_gudang,
                            'id_user'       => $this->user->id,
                            'id_referensi'  => $dpj->id,
                            'referensi'     => 'penjualan',
                            'no_referensi'  => $penjualan->id,
                            'qty_pcs'       => ($dpj->qty * $stock->isi) + $dpj->qty_pcs,
                            'status'        => 'delivered',
                            'created_at'    => Carbon::now(),
                            'updated_at'    => Carbon::now()
                        ];
                    }
                    // End Update Stock ==================================================================================


                    $logData = [
                        'action' => 'Deliver Penjualan',
                        'description' => 'PO ' . $penjualan->id . ' Invoice: ' . $penjualan->no_invoice . ' Delivered At ' . $delivered_at,
                        'user_id' => $this->user->id
                    ];

                    $this->log($logData);
                    $this->createLogStock($logStock);

                    DB::commit();
                    return response()->json([
                        'message' => 'Barang telah terkirim ke toko.',
                        'delivered_at' => $penjualan->delivered_at
                    ], 201);
                }

                return response()->json([
                    'message' => 'Data Penjualan tidak ditemukan!'
                ], 404);
            } catch (\Exception $e) {
                DB::rollBack();
                $message    = "Penjualan: " . $id . ", Loading Error: " . $e->getMessage();
                $this->sendMessageBot(basename(__FILE__), __FUNCTION__, $message);
                return response()->json([
                    'message' => 'Deliver Gagal',
                    'status' => 'Deliver'
                ], 400);
            }

        else :
            return $this->Unauthorized();
        endif;
    }

    public function distribution_undeliver($id)
    {
        //$id -> id penjualan
        if ($this->user->can('Hapus Tanggal Terima Penjualan')) :
            try {
                $penjualan = Penjualan::find($id);
                $delivered_at = Carbon::now()->addHour(8);

                if ($penjualan) {
                    if ($penjualan->status != 'delivered') {
                        return response()->json([
                            'message' => 'Hapus tanggal terima tidak diijinkan'
                        ], 400);
                    }

                    $penjualan->status          = 'loaded';
                    $penjualan->delivered_at    = null;

                    DB::beginTransaction();
                    $penjualan->save();

                    // jika delivered_at < today(), maka increment qty_pending pada stock awal'
                    if ($delivered_at < Carbon::today()->toDateString()) {
                        $detail_penjualan = DetailPenjualan::join('stock', 'detail_penjualan.id_stock', 'stock.id')
                            ->join('barang', 'stock.id_barang', 'barang.id')
                            ->where('id_penjualan', $id)
                            ->select('stock.id', 'detail_penjualan.qty', 'detail_penjualan.qty_pcs', 'barang.isi')->get();

                        foreach ($detail_penjualan as $dt) {
                            $stock_awal = StockAwal::where('id_stock', $dt->id)->where('tanggal', Carbon::today()->toDateString())->first();
                            if ($stock_awal) {
                                $stock_awal->increment('qty_pending', $dt->qty);
                                $stock_awal->increment('qty_pcs_pending', $dt->qty_pcs);
                            }
                        }
                        // akhir penambahan qty pending pada tabel stock_awal
                    }

                    $logData = [
                        'action' => 'Cancel Deliver Penjualan',
                        'description' => 'PO ' . $penjualan->id . ' Invoice: ' . $penjualan->no_invoice,
                        'user_id' => $this->user->id
                    ];

                    $this->log($logData);
                    $this->deleteLogStock([
                        ['referensi', 'penjualan'],
                        ['no_referensi', $penjualan->id],
                        ['status', 'delivered']
                    ]);

                    DB::commit();
                    return response()->json([
                        'message' => 'Tanggal terima berhasil dihapus',
                    ], 201);
                }
                return response()->json([
                    'message' => 'Data Penjualan tidak ditemukan!'
                ], 404);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['error' => $e->getMessage()], 400);
            }

        else :
            return $this->Unauthorized();
        endif;
    }

    public function index_distribution_plan(Request $request)
    {
        // id_salesman, start_date, end_date, status, keyword, per_page, page

        if ($this->user->can('Menu Penjualan')) :
            // $logData = [
            //     'action' => 'Load Data Distribution Plan',
            //     'description' => 'Tanggal '.$request->start_date.' sampai '.$request->end_date,
            //     'user_id' => $this->user->id
            // ];

            // $this->log($logData);
            $id_penjualan = $request->has('id_penjualan') && $request->id_penjualan <> '' ? $request->id_penjualan:'';
            // Filter Depo
            if ($request->depo != null) {
                $id_depo = $request->depo;
            } else {
                $id_depo = Helper::depoIDByUser($this->user->id);
            }

            $list_penjualan = Penjualan::select(DB::raw('
                                        penjualan.*,
                                        toko.id as id_toko,
                                        users.name as nama_salesman,
                                        tim.nama_tim,
                                        toko.nama_toko,
                                        toko.no_acc,
                                        toko.status_verifikasi,
                                        toko.alamat,
                                        nama_kelurahan
                                    '))
                ->with(['detail_penjualan'])
                ->join('toko', 'penjualan.id_toko', '=', 'toko.id')
                ->join('salesman', 'penjualan.id_salesman', '=', 'salesman.user_id')
                ->join('users', 'salesman.user_id', '=', 'users.id')
                ->join('tim', 'salesman.id_tim', '=', 'tim.id')
                ->leftJoin('kelurahan','toko.id_kelurahan','kelurahan.id')
                ->leftJoin('kecamatan','kelurahan.id_kecamatan','kecamatan.id')
                ->where('penjualan.status', 'approved');
            if($id_penjualan) {
                $list_penjualan = $list_penjualan->where('penjualan.id', $id_penjualan)->first();
                return new DistributionPlanResource($list_penjualan);
            } else {
                $list_penjualan = $list_penjualan->whereIn('penjualan.id_depo', $id_depo)
                    ->whereBetween('tanggal', [$request->start_date, $request->end_date]);
            }

            $id_salesman = $request->id_salesman ?? 'all';
            if ($request->id_salesman <> 'all') {
                $list_penjualan = $list_penjualan->where('penjualan.id_salesman', $id_salesman);
            }
            // Filter tipe_pembayaran
            $tipe_pembayaran = $request->tipe_pembayaran ?? 'all';
            if ($tipe_pembayaran != '' && $tipe_pembayaran <> 'all') {
                $list_penjualan = $list_penjualan->where('penjualan.tipe_pembayaran', $tipe_pembayaran);
            }

            // Filter Keyword
            $keyword = $request->keyword ?? '';
            if ($keyword <> '') {
                $list_penjualan = $list_penjualan->where(function ($q) use ($keyword) {
                    $q->where('penjualan.id', 'like', '%' . $keyword . '%')
                        ->orWhere('penjualan.no_invoice', 'like', '%' . $keyword . '%')
                        ->orWhere('penjualan.keterangan', 'like', '%' . $keyword . '%')
                        ->orwhere('toko.nama_toko', 'like', '%' . $keyword . '%')
                        ->orWhere('toko.no_acc', 'like', '%' . $keyword . '%')
                        ->orWhere('toko.cust_no', 'like', '%' . $keyword . '%');
                });
            }


            $depo_khusus = Reference::where('code', '=', 'distribusi_plan_order_kelurahan')->first();
            $depo_khusus_array = explode(',',$depo_khusus['value']);
            $blocker = 0;
            foreach ($id_depo as $row) {
                if(in_array($row, $depo_khusus_array)){
                    $blocker++;
                }
            }
            if($blocker>0){
                 $list_penjualan = $list_penjualan
                ->orderBy('id_kabupaten')
                ->orderBy('id_kecamatan')
                ->orderBy('id_kelurahan')
                ->orderBy('penjualan.no_invoice', 'ASC')
                ->get();
            }
            else{
                $list_penjualan = $list_penjualan->orderBy('penjualan.no_invoice', 'ASC')
                ->orderBy('penjualan.id_depo', 'ASC')
                ->get();
            }

            if ($list_penjualan) {
                return DistributionPlanResource::collection($list_penjualan);
            }
            return response()->json([
                'message' => 'Data Penjualan tidak ditemukan!'
            ], 404);
        else :
            return $this->Unauthorized();
        endif;
    }
    public function report_distribution_plan(Request $request, $per_page = 5)
    {
        // id_salesman, start_date, end_date, status, keyword, per_page, page
        if ($this->user->can('Menu Penjualan')) :
            $user_id = $this->user->id;
            // $list_penjualan = Penjualan::latest();
            $list_penjualan = Penjualan::relationData();

            // Filter Salesman (Tim)
            $id_driver = $request->has('id_driver') && $request->id_driver <> '' ? $request->id_driver : 'all';
            if ($id_driver !== 'all') {
                $list_penjualan = $list_penjualan->where('driver_id', $id_driver);
            }

            // Filter Depo
            if ($request->depo != null) {
                $id_depo = $request->depo;
            } else {
                $id_depo = Helper::depoIDByUser($this->user->id);
            }
            $list_penjualan = $list_penjualan->whereIn('id_depo', $id_depo);

            // Filter Status
            $status = $request->status ?? 'all';
            if ($status <> 'all' && $status <> '') {
                if ($status == 'empty') {
                    $list_penjualan = $list_penjualan->doesnthave('detail_penjualan');
                } elseif ($status == 'fixed') {
                    $list_penjualan = $list_penjualan->whereNotIn('status', ['waiting', 'canceled']);
                } else {
                    $list_penjualan = $list_penjualan->where('status', $request->status);
                }
            }

            //Kondisi untuk distribution plan
            if ($request->has('delivery_report')) {
                $list_penjualan = $list_penjualan->orderBy('delivered_at', 'DESC')
                    ->orderBy('id_depo', 'DESC');
                // Filter Date
                if ($request->has(['start_date', 'end_date'])) {
                    $list_penjualan = $list_penjualan->whereBetween('delivered_at', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
                }
            }

            if ($request->has('penjualan_terjadwal')) {
                $list_penjualan = $list_penjualan->whereNotnull('driver_id')
                    ->where('status', 'loaded')
                    ->whereNotnull('tanggal_jadwal')
                    ->orderBy('tanggal_jadwal', 'DESC');
                if ($request->has(['start_date', 'end_date'])) {
                    $list_penjualan = $list_penjualan->whereBetween('tanggal_jadwal', [$request->start_date, $request->end_date]);
                }
            }
            // End Kondisi untuk distribution plan
            $list_penjualan = $list_penjualan->latest();

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

            $perPage = $request->has('per_page') ? $perPage = $request->per_page : $perPage = 'all';
            $list_penjualan = $perPage == 'all' ? $list_penjualan->get() : $list_penjualan->paginate((int)$perPage);
            if ($list_penjualan) {
                return PenjualanResource::collection($list_penjualan);
            }

            return response()->json([
                'message' => 'Data Penjualan tidak ditemukan!'
            ], 404);
        else :
            return $this->Unauthorized();
        endif;
    }
    //End Distribution Plan

    public function rekapPajak(Request $request)
    {
        $tanggal_awal   = $request->start_date;
        $tanggal_akhir  = $request->end_date;
        $depo           = $request->depo;
        $id_mitra       = $request->id_mitra;

        $penjualan = Penjualan::with(['detail_penjualan', 'detail_penjualan.harga_barang', 'toko', 'toko.ketentuan_toko'])
            ->when($tanggal_awal <> '' && $tanggal_akhir <> '', function ($q) use ($tanggal_awal, $tanggal_akhir) {
                return $q->whereBetween('delivered_at', [$tanggal_awal . " 00:00:00", $tanggal_akhir . " 23:59:59"]);
            })
            ->when($depo, function ($q) use ($depo) {
                if (is_array($depo)) {
                    $q->whereIn('id_depo', $depo);
                } else {
                    $q->where('id_depo', $depo);
                }
            })
            ->when(is_numeric($id_mitra), function ($q) use ($id_mitra) {
                $q->where('id_mitra', '=', $id_mitra);
            })
            ->when($id_mitra == 'exclude', function ($q) use ($id_mitra) {
                $q->where('id_mitra', '=', 0);
            })
            ->where('status', 'delivered')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Pajak');
        $i = 1;
        $cells  = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N'];
        foreach ($cells as $cell) {
            if ($cell == 'A') {
                $sheet->getColumnDimension($cell)->setWidth(5);
                continue;
            }

            $sheet->getColumnDimension($cell)->setAutoSize(true);
        }

        $columns = ['No', 'No Invoice', 'No Pajak', 'Delivered', 'Cust ID', 'Customer', 'NPWP', 'Nama PKP', 'Alamat PKP', 'Subtotal', 'Diskon', 'DPP', 'PPn', 'Total'];
        $sheet->getStyle('A' . $i . ':M' . $i)->getFont()->setBold(true);
        $sheet->getStyle('A' . $i . ':M' . $i)->applyFromArray($this->horizontalCenter());
        $sheet->getStyle('A' . $i . ':M' . $i)->applyFromArray($this->border());
        foreach ($columns as $key => $column) {
            $sheet->setCellValue($cells[$key] . $i, $column);
        }

        $i++;
        $start = $i;
        $no = 1;
        $data = [];
        foreach ($penjualan as $key => $pj) {
            if ($pj->toko == null) {
                return response()->json($pj->id, 200);
            }
            $sheet->setCellValue('A' . $i, $no++);
            $sheet->setCellValue('B' . $i, $pj->no_invoice);
            $sheet->setCellValue('C' . $i, $pj->no_pajak);
            $sheet->setCellValue('D' . $i, Carbon::parse($pj->delivered_at)->toDateString());
            $sheet->setCellValue('E' . $i, $pj->toko->no_acc);
            $sheet->setCellValue('F' . $i, $pj->toko->nama_toko);
            $sheet->setCellValue('G' . $i, $pj->toko->ketentuan_toko->npwp);
            $sheet->setCellValue('H' . $i, $pj->toko->ketentuan_toko->nama_pkp);
            $sheet->setCellValue('I' . $i, $pj->toko->ketentuan_toko->alamat_pkp);
            $sheet->setCellValue('J' . $i, $pj->total);
            $sheet->setCellValue('K' . $i, $pj->disc_total);
            $sheet->setCellValue('L' . $i, $pj->dpp);
            $sheet->setCellValue('M' . $i, $pj->ppn);
            $sheet->setCellValue('N' . $i, $pj->grand_total);
            $i++;

            $data[] = [
                'no' => $no,
                'no_invoice' => $pj->no_invoice,
                'no_pajak' => $pj->no_pajak,
                'delivered_at' => Carbon::parse($pj->delivered_at)->toDateString(),
                'cust_id' => $pj->toko->no_acc,
                'customer' => $pj->toko->nama_toko,
                'npwp' => $pj->toko->ketentuan_toko->npwp,
                'nama_pkp' => $pj->toko->ketentuan_toko->nama_pkp,
                'alamat_pkp' => $pj->toko->ketentuan_toko->alamat_pkp,
                'subtotal' => $pj->total,
                'discount' => $pj->disc_total,
                'dpp' => $pj->dpp,
                'ppn' => $pj->ppn,
                'total' => $pj->grand_total
            ];
        }

        $spreadsheet->setActiveSheetIndex(0);
        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        $content = ob_get_contents();
        ob_end_clean();
        $fileName = "rekap-pajak-{$tanggal_awal}_$tanggal_akhir.xlsx";
        Storage::disk('local')->put('excel/' . $fileName, $content);
        $file = url('/excel/' . $fileName);
        return response()->json(['data' => $data, 'file' => $file], 200);
    }

    public function penjualan_by_salesman($id)
    {
       if ($this->user->can('Menu Invoice Note')) :
            $data = DB::table('penjualan')
            ->leftJoin('toko', 'penjualan.id_toko', 'toko.id')
            ->select('penjualan.id', 'penjualan.no_invoice', 'nama_toko')
            ->where('penjualan.tipe_pembayaran', 'credit')
            ->where('penjualan.id_salesman', $id)
            ->where('penjualan.status', 'delivered')
            ->whereNotNull('penjualan.no_invoice')
            ->whereNull('penjualan.paid_at')
            ->whereNull('penjualan.deleted_at')
            ->get();
            return response()->json($data);
        else :
            return $this->Unauthorized();
        endif;
    }

    public function force_close(Request $request)
    {
        if (!$this->user->can('Force Close Penjualan')) {
            return $this->Unauthorized();
        }

        $messages = [
            'close_remark.required' => 'Alasan wajib isi',
            'remark.min' => 'Alasan minimal 5 karakter'
        ];

        $this->validate($request, [
            'close_remark' => 'required|string|min:5',
            'checkeds' => 'required|array'
        ], $messages);

        $remark         = $request->close_remark;
        $closed_by      = $this->user->id;
        $id_penjualan   = $request->checkeds;

        $input = [
            'closed_by'   => $closed_by,
            'remark_close'=> $remark,
            'status'      => 'closed'
        ];

        $penjualan = Penjualan::with('detail_penjualan')->find($id_penjualan);
        foreach ($penjualan as $pj) {
            if ($pj->status == 'delivered' || $pj->status == 'closed') {
                continue;
            }

            // DETAIL PENJUALAN
            $detail_penjualan = $pj->detail_penjualan;

            DB::beginTransaction();
            try {
                foreach ($detail_penjualan as $dpj) {
                    $stock        = Stock::find($dpj->id_stock);
                    $posisi_stock = PosisiStock::where('id_stock', $dpj->id_stock)->where('tanggal', Carbon::today()->toDateString())->latest()->first();
                    if (!$posisi_stock) {
                        $posisi_stock = PosisiStock::create([
                            'id_stock'        => $dpj->id_stock,
                            'tanggal'         => Carbon::today()->toDateString(),
                            'harga'           => $stock->dbp,
                            'saldo_awal_qty'  => $stock->qty,
                            'saldo_awal_pcs'  => $stock->qty_pcs,
                            'saldo_akhir_qty' => $stock->qty,
                            'saldo_akhir_pcs' => $stock->qty_pcs,
                        ]);
                    }
                    // Pengembalian Stock Gudang
                    $stock->increment('qty', $dpj->qty);
                    $stock->increment('qty_pcs', $dpj->qty_pcs);

                    // Pengembalian Posisi Stock
                    $posisi_stock->decrement('penjualan_qty', $dpj->qty);
                    $posisi_stock->decrement('penjualan_pcs', $dpj->qty_pcs);
                    $posisi_stock->increment('saldo_akhir_qty', $dpj->qty);
                    $posisi_stock->increment('saldo_akhir_pcs', $dpj->qty_pcs);
                    while ($posisi_stock->penjualan_pcs < 0) {
                        $posisi_stock->decrement('penjualan_qty');
                        $posisi_stock->increment('penjualan_pcs', $stock->isi);
                    }
                }

                // Soft Delete Log Stock
                $this->deleteLogStock([
                    ['no_referensi', $pj->id],
                    ['referensi', 'penjualan'],
                    ['status', 'approved']
                ]);

                $this->deleteLogStock([
                    ['no_referensi', $pj->id],
                    ['referensi', 'penjualan'],
                    ['status', 'loaded']
                ]);

                $pj->update($input);
                $logData = [
                    'action' => 'Force Closed',
                    'description' => 'PO ' . $pj->id . ' Invoice: ' . $pj->no_invoice. ' Alasan:'.$remark,
                    'user_id' => $closed_by
                ];
                $this->log($logData);
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'message' => $pj->no_invoice." gagal force close"
                ], 400);
            }
        }
    }
}
