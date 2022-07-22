<?php

namespace App\Http\Controllers;

use App\Http\Requests\TokoStoreRequest;
use App\Http\Resources\TokoListSimple as TokoListSimpleResources;
use App\Models\RiwayatTop;
use App\Models\ViewStt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Tymon\JWTAuth\JWTAuth;
use App\Models\Toko;
use App\Models\KetentuanToko;
use App\Models\Penjualan;
use App\Models\DetailPelunasanPenjualan;
use App\Http\Resources\Toko as TokoResource;
use App\Models\RiwayatLimitKredit;
use Carbon\Carbon;
use DB;
use App\Helpers\Helper;

class TokoController extends Controller
{
    protected $jwt;

    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
    }

    public function index(Request $request, $per_page = 5)
    {
        $id_user = $this->user->id;
        $id_perusahaan  =   $request->has('id_perusahaan') ?
                            is_array($request->id_perusahaan) ?
                            $request->id_perusahaan
                            : [$request->id_perusahaan]
                            : Helper::perusahaanByUser($id_user);

        $id_depo        =   $request->has('id_depo') && count($request->id_depo) > 0 ?
                            $request->id_depo
                            : $depo = Helper::depoIDByUser($this->user->id,$id_perusahaan);

        $id_tim         =   $request->has('id_tim') ? $request->id_tim : [];
        $id_tim         =   $id_tim == 'all' || $id_tim == '' ? [] : $id_tim;
        $id_tim         =   is_array($id_tim) ? $id_tim : [$id_tim];

        $tipe           =   $request->has('tipe') && $request->tipe!='' ? $request->tipe : '';
        $tipe_keyword   =   $request->has('tipe_keyword') && is_array($request->tipe_keyword) ? $request->tipe_keyword : ['Keyword'];
        $keyword        =   $request->has('keyword') && is_array($request->keyword) ? $request->keyword : [$request->keyword];


        if (!$this->user->can('Menu Toko')) {
            return $this->Unauthorized();
        }

        $depo_user = Helper::depoIDByUser($this->user->id);
        if($this->user->can('Toko Per Tim')){
            $id_tim     = $this->user->salesman->id_tim;
            $list_toko  = Toko::with('depo', 'principal', 'depo.perusahaan')->whereHas('ketentuan_toko', function ($query) use ($id_tim){
                $query->where('id_tim', $id_tim);
            })->whereIn('id_depo',$depo_user)->orderBy('nama_toko', 'asc');
        }
        else {
            if(count($id_tim) > 0){
                $list_toko  = Toko::with('depo', 'principal', 'depo.perusahaan')->whereHas('ketentuan_toko', function ($query) use ($id_tim){
                    $query->whereIn('id_tim', $id_tim);
                })->whereIn('id_depo',$depo_user)->orderBy('nama_toko', 'asc');
            }
            else{
                $list_toko = Toko::with('depo', 'depo.perusahaan')->whereIn('id_depo',$depo_user)->orderBy('nama_toko', 'asc');
            }

            if(count($tipe_keyword)>0){
                for ($i=0; $i < count($tipe_keyword) ; $i++) {
                    $keyword_param = $keyword[$i];
                    switch ($tipe_keyword[$i]) {
                        case 'Nama Toko':
                            $list_toko = $list_toko->where('nama_toko', 'like', '%' . $keyword_param . '%');
                            break;

                        case 'Alamat':
                            $list_toko = $list_toko->where('alamat', 'like', '%' . $keyword_param . '%');
                            break;

                        case 'Npwp':
                            $list_toko = $list_toko->whereHas('ketentuan_toko', function ($query) use ($keyword_param){
                                $query->where('npwp', 'like', '%' . $keyword_param . '%');
                            });
                            break;

                        case 'Tipe Harga':
                            $list_toko = $list_toko->whereHas('ketentuan_toko', function ($query) use ($keyword_param){
                                $query->where('tipe_harga', 'like', '%' . $keyword_param . '%');
                            });
                            break;

                        case 'Nama Pkp':
                            $list_toko = $list_toko->whereHas('ketentuan_toko', function ($query) use ($keyword_param){
                                $query->where('nama_pkp', 'like', '%' . $keyword_param . '%');
                            });
                            break;

                        case 'Keyword':
                            $list_toko = $list_toko->where(function ($query) use ($keyword_param){
                                $query->where('nama_toko', 'like', '%' . $keyword_param . '%')
                                ->orWhere('pemilik', 'like', '%' . $keyword_param . '%')
                                // ->orWhere('tipe', 'like', '%' . $keyword_param . '%')
                                ->orWhere('no_acc', 'like', '%' . $keyword_param . '%')
                                ->orWhere('cust_no', 'like', '%' . $keyword_param . '%')
                                ->orWhere('kode_mars', 'like', '%' . $keyword_param . '%')
                                ->orWhere('telepon', 'like', '%' . $keyword_param . '%')
                                ->orWhere('alamat', 'like', '%' . $keyword_param . '%');
                            });
                            break;
                    }
                }
            }

            $id_tim = is_array($id_tim) ? $id_tim:[$id_tim];
            $list_toko = $list_toko->when(count($id_depo)>0, function ($query) use ($id_depo){
                return $query->whereIn('id_depo',$id_depo);
            })
            ->when($tipe <> '', function($query) use ($tipe){
                return $query->where('tipe',$tipe);
            })
            ->when(count($id_tim)>0, function ($query) use ($id_tim){
                return $query->whereHas('ketentuan_toko', function ($query) use ($id_tim){
                    return $query->whereIn('id_tim', $id_tim);
                });
            });

            if ($this->user->can('Penjualan Tim')) {
                $id_tim = Helper::timBySupervisor($id_user);
                $list_toko = $list_toko->whereHas('ketentuan_toko', function ($query) use ($id_tim){
                    $query->whereIn('id_tim', $id_tim);
                });
            }

            if ($this->user->can('Penjualan Tim Koordinator')) {
                $id_tim = Helper::timByKoordinator($id_user);
                $list_toko = $list_toko->whereHas('ketentuan_toko', function ($query) use ($id_tim){
                    $query->whereIn('id_tim', $id_tim);
                });
            }

            $list_toko->orderBy('nama_toko', 'asc')->orderBy('id','DESC');
        }

        $perPage = $request->has('per_page') ? $request->per_page : $perPage = 10;
        $list_toko = $perPage == 'all' ? $list_toko->get() : $list_toko->paginate((int)$perPage);

        if ($list_toko) {
            return TokoResource::collection($list_toko);
        }

        return $this->dataNotFound('toko');
    }

    public function store(TokoStoreRequest $request)
    {
        if (!$this->user->can('Tambah Toko')) {
            return $this->Unauthorized();
        }

        if ($request->limit <> 0) {
            if (!$this->user->can('Update Limit Toko')) {
                return $this->Unauthorized();
            }
        }

        $input = $request->all();
        $input['nama_toko'] = ucwords($request->nama_toko);
        $input['created_by']= $this->user->id;
        $input['no_acc']    = '';
        if($request->cust_no == ''){
            if($request->has('id_kelurahan')){
                // ============ GENERATE NEW CUST NO ============
                // tentukan 4 digit pertama (kode kabupaten)
                // cari record terakhir yang 4 digit pertama sama, ambil cust_no nya
                // jika ada, ubah menjadi angka, lalu tambah 1
                // jika tidak ada, buat 1

                $id_kabupaten   = substr($request->id_kelurahan, 0, 4); // substr(string,start,length)
                $new_cust_no    = '102' . $id_kabupaten . sprintf("%05d", 1);
                $list_toko      = Toko::where('cust_no', 'like', '102' . $id_kabupaten . '%')->orderByDesc('cust_no')->first();
                if($list_toko){
                    $latest_cust_no = $list_toko->cust_no;
                    $next_no        = substr($latest_cust_no, 7) + 1;
                    $new_cust_no    = '102' . $id_kabupaten . sprintf("%05d", $next_no);
                }
                $input['cust_no'] = $new_cust_no;
            }
        }

        if($input['no_acc'] == ''){
            $input['no_acc'] = $input['cust_no'];
        }

        DB::beginTransaction();
        try {
            $toko = Toko::create($input);
            // KETENTUAN TOKO
            $data_ketentuan['id_toko']      = $toko->id;
            $data_ketentuan['k_t']          = $request->k_t ?? 'tunai';
            $data_ketentuan['top']          = $request->top ?? 0;
            $data_ketentuan['limit']        = $request->limit;
            $data_ketentuan['minggu']       = $request->minggu;
            $data_ketentuan['hari']         = $request->hari;
            $data_ketentuan['npwp']         = $request->npwp;
            $data_ketentuan['nama_pkp']     = $request->nama_pkp;
            $data_ketentuan['alamat_pkp']   = $request->alamat_pkp;
            $data_ketentuan['no_ktp']       = $request->no_ktp;
            $data_ketentuan['nama_ktp']     = $request->nama_ktp;
            $data_ketentuan['alamat_ktp']   = $request->alamat_ktp;
            if($this->user->hasRole('Salesman') || $this->user->hasRole('Salesman Canvass')){
                $data_ketentuan['id_tim'] = $this->user->salesman->id_tim;
            } else {
                $data_ketentuan['id_tim'] = $request->id_tim;
            }
            KetentuanToko::create($data_ketentuan);

            // LIMIT KREDIT
            if ($request->limit > 0) {
                $riwayat_kredit = [
                    'id_toko'       => $toko->id,
                    'limit_credit'  => $request->limit,
                    'update_by'     => $this->user->id
                ];

                RiwayatLimitKredit::create($riwayat_kredit);
            }

            //TOP
            if ($request->top > 0) {
                $riwayat_top = [
                    'id_toko'   => $toko->id,
                    'top'       => $request->top,
                    'update_by' => $this->user->id
                ];

                RiwayatTop::create($riwayat_top);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function show($id)
    {
       if (!$this->user->can('Edit Toko')) {
           return $this->Unauthorized();
       }

       $toko = Toko::find($id);
       if ($toko) {
           return new TokoResource($toko);
       }

       return $this->dataNotFound('toko');
    }

    public function update(TokoStoreRequest $request, $id)
    {
        if (!$this->user->can('Update Toko')) {
            return $this->Unauthorized('toko');
        }

        $toko = Toko::find($id);
        if (!$toko) {
            return $this->dataNotFound('toko');
        }

        if($this->user->can('Toko Per Tim') && ($toko->ketentuan_toko->id_tim != $this->user->salesman->id_tim)){
            $nama_tim = $this->user->salesman->tim->nama_tim;
            return response()->json([
                'message' => 'Hanya tim ' . $nama_tim . ' yang dapat mengubah data toko ini.'
            ], 400);
        }

        if ($toko->ketentuan_toko->limit != $request->limit) {
            if (!$this->user->can('Update Limit Toko')) {
                return $this->Unauthorized();
            }

            $riwayat_kredit = [
                'id_toko'       => $toko->id,
                'limit_credit'  => $request->limit,
                'update_by'     => $this->user->id
            ];

            RiwayatLimitKredit::create($riwayat_kredit);
        }

        //TOP
        if ($toko->ketentuan_toko->top <> $request->top) {
            $riwayat_top = [
                'id_toko'   => $toko->id,
                'top'       => $request->top,
                'update_by' => $this->user->id
            ];

            RiwayatTop::create($riwayat_top);
        }

        $input = $request->all();
        $input['nama_toko'] = ucwords($request->nama_toko);
        $input['updated_by'] = $this->user->id;
        $input['id_principal'] = $input['id_principal'] == 0 ? null:$input['id_principal'];

        if($request->cust_no == ''){
            if($request->has('id_kelurahan')){
                // ============ GENERATE NEW CUST NO ============
                // tentukan 4 digit pertama (kode kabupaten)
                // cari record terakhir yang 4 digit pertama sama, ambil cust_no nya
                // jika ada, ubah menjadi angka, lalu tambah 1
                // jika tidak ada, buat 1

                $id_kabupaten = substr($request->id_kelurahan, 0, 4); // substr(string,start,length)

                $list_toko = Toko::where('cust_no', 'like', '102' . $id_kabupaten . '%')->orderByDesc('cust_no')->first();
                if($list_toko){
                    $latest_cust_no = $list_toko->cust_no;
                    $next_no = substr($latest_cust_no, 7) + 1;
                    $new_cust_no = '102' . $id_kabupaten . sprintf("%05d", $next_no);
                }
                else{
                    $new_cust_no = '102' . $id_kabupaten . sprintf("%05d", 1);

                }
                $input['cust_no'] = $new_cust_no;
            }
        }

        if($toko->no_acc == ''){
            $input['no_acc'] = $input['cust_no'];
        }

        $toko->update($input);

        $ketentuan_toko = KetentuanToko::find($toko->id);
        $data_ketentuan['id_toko']      = $toko->id;
        $data_ketentuan['k_t']          = $request->k_t;
        $data_ketentuan['top']          = $request->top;
        $data_ketentuan['limit']        = $request->limit;
        $data_ketentuan['minggu']       = $request->minggu;
        $data_ketentuan['hari']         = $request->hari;
        $data_ketentuan['npwp']         = $request->npwp;
        $data_ketentuan['nama_pkp']     = $request->nama_pkp;
        $data_ketentuan['alamat_pkp']   = $request->alamat_pkp;
        $data_ketentuan['no_ktp']       = $request->no_ktp;
        $data_ketentuan['nama_ktp']     = $request->nama_ktp;
        $data_ketentuan['alamat_ktp']   = $request->alamat_ktp;
        if($this->user->hasRole('Salesman') || $this->user->hasRole('Salesman Canvass')){
            $data_ketentuan['id_tim'] = $this->user->salesman->id_tim;
        }
        else{
            $data_ketentuan['id_tim'] = $request->id_tim;
        }

        return $ketentuan_toko->update($data_ketentuan) ? $this->updateTrue('toko') : $this->updateFalse('toko');
    }

    public function destroy($id)
    {
        if (!$this->user->can('Hapus Toko')) {
            return $this->Unauthorized();
        }

        $toko = Toko::find($id);
        if (!$toko) {
            return $this->dataNotFound('toko');
        }

        $data = ['deleted_by' => $this->user->id];
        $toko->update($data);
        return $toko->delete() ? $this->destroyTrue('toko') : $this->destroyFalse('toko');
    }

    public function restore($id)
    {
        if (!$this->user->can('Tambah Toko')) {
            return $this->Unauthorized();
        }

        $toko = Toko::withTrashed()->find($id);
        if (!$toko) {
            return $this->dataNotFound('toko');
        }

        $data = ['deleted_by' => null];
        $toko->update($data);

        $toko->restore();

        return response()->json([
            'message' => 'Data Toko berhasil dikembalikan.'
        ], 200);
    }

    public function lokasi_penjualan($id){
        //ambil data penjualan suatu toko, yang ada latitude & longitudenya, ambil 5 data terakhir'
        if ($this->user->can('Edit Toko')):
            $toko = Toko::find($id);
            if($toko){
                $list_penjualan = Penjualan::where('id_toko', $id)->whereNotNull('latitude')->latest()->take(10)->select('id','id_toko','latitude','longitude','created_at')->get();

                return response()->json([
                    // 'toko' => $toko,
                    'list_penjualan' => $list_penjualan
                ], 200);
            }

            return response()->json([
                'message' => 'Data Toko tidak ditemukan!'
            ], 404);
        else:
            return $this->Unauthorized();
        endif;
    }

    public function edit_location($id, Request $request){
        if ($this->user->can('Update Toko')):
            $toko = Toko::find($id);
            if($toko){
                if($this->user->role == 'salesman' && ($toko->ketentuan_toko->id_tim != $this->user->salesman->id_tim)){
                    $nama_tim = $this->user->salesman->tim->nama_tim;
                    return response()->json([
                        'message' => 'Hanya tim ' . $nama_tim . ' yang dapat mengubah data toko ini.'
                    ], 400);
                }

                $input['latitude'] = $request->latitude;
                $input['longitude'] = $request->longitude;
                $input['updated_by'] = $this->user->id;
                $toko->update($input);

                return response()->json([
                    'message' => 'Lokasi toko berhasil diperbaharui.',
                    // 'data' => $toko
                ], 200);
            }

            return response()->json([
                'message' => 'Data Toko tidak ditemukan!'
            ], 404);
        else:
            return $this->Unauthorized();
        endif;
    }

    public function get_saldo_retur($id){
        if ($this->user->can('Saldo Retur Toko')):
            $toko = KetentuanToko::find($id);
            $toko ? $saldo_retur = $toko->saldo_retur : $saldo_retur = 0;

            $sum_pelunasan_saldo_retur_waiting = DetailPelunasanPenjualan::whereHas('penjualan', function ($query) use ($id){
                $query->where('id_toko', $id);
            })->where('status', 'waiting')->sum('nominal');
            $saldo_retur_available = $saldo_retur - $sum_pelunasan_saldo_retur_waiting;
            // $saldo_retur_available < 0 ? $saldo_retur_available = 0 : $saldo_retur_available;

            return response()->json([
                'saldo_retur' => round($saldo_retur,0),
                'sum_pelunasan_saldo_retur_waiting' => round($sum_pelunasan_saldo_retur_waiting,0),
                'saldo_retur_available' => round($saldo_retur_available,0),
            ], 200);
        else:
            return $this->Unauthorized();
        endif;
    }

    public function export(){
        if ($this->user->can('Download Toko')):
            $list_toko = Toko::with('ketentuan_toko')->orderBy('no_acc')->get();
            return TokoResource::collection($list_toko);
        else:
            return $this->Unauthorized();
        endif;
    }


    public function cek_od_ocl($id){
        // $id_toko = Penjualan::find($id)->id_toko;
        $id_toko = $id;
        $credit_limit = KetentuanToko::find($id_toko)->limit;
        $today = Carbon::today()->toDateString();

        // list penjualan belum lunas
        $list_penjualan_belum_lunas = Penjualan::with('detail_penjualan.harga_barang.barang', 'toko')->where('tipe_pembayaran', 'credit')->where('id_toko', $id_toko)->whereNull('paid_at')->oldest()->get();
        $id_penjualan_belum_lunas = $list_penjualan_belum_lunas->pluck('id');
        // jumlah yang sudah dicicil
        $total_pelunasan = DetailPelunasanPenjualan::where('id_penjualan')->where('status', 'approved')->get()->sum('nominal');
        // total credit / sisa piutang
        $total_credit = $list_penjualan_belum_lunas->sum('grand_total') - $total_pelunasan;

        if($total_credit > $credit_limit){
            return response()->json([
                'message' => 'OVER CREDIT LIMIT. Melewati kredit limit, lunasi transaksi sebelumnya terlebih dahulu!',
                'total_credit' => $total_credit,
                'credit_limit' => (int)$credit_limit,
                'id_penjualan' => $list_penjualan_belum_lunas->pluck('id'),
                // 'data' => $list_penjualan_belum_lunas
            ], 422);
        }

        foreach($list_penjualan_belum_lunas as $d){
            $due_date = new Carbon($d->due_date);

            // return $due_date->diffInDays($today, false);
            if($due_date->diffInDays($today, false) > 0) {
                return response()->json([
                    'message' => 'Toko ini mempunyai transaksi yang Over Due, harap lunasi transaksi sebelumnya terlebih dahulu.',
                    'total_credit' => $total_credit,
                    'credit_limit' => $credit_limit,
                    'id_penjualan' => $d->pluck('id'),
                    'data' => $d
                ], 422);
            }
        }

        return response()->json([
            'message' => 'OK'
        ], 200);
    }

    public function location()
    {
        $toko = Toko::select('nama_toko', 'latitude', 'longitude')->whereNotNull('latitude')->get();
        return response()->json([
            'data' => $toko->toArray()
        ], 200);
    }

    public function list(Request $request)
    {
        $id_depo    = $request->has('id_depo') && $request->id_depo ? $request->id_depo : Helper::depoIDByUser($this->user->id)->toArray();
        $id_tim     = $request->has('id_tim') && $request->id_tim != '' ? $request->id_tim : '';
        $toko       = Toko::when($id_tim != '', function ($q) use ($id_tim) {
            $q->whereHas('ketentuan_toko', function ($q) use ($id_tim) {
                $q->where('id_tim', $id_tim);
            });
        })->whereIn('id_depo', $id_depo)->get();
        return TokoListSimpleResources::collection($toko);
    }

    public function toko_tanpa_grup_logistik(Request $request)
    {
        $id_toko    = Penjualan::select('id_toko')->whereStatus('delivered')->where('delivered_at', '>=', Carbon::now()->subMonths(3))->distinct()->get();
        $toko       = Toko::with(['kelurahan', 'depo'])->whereNull('id_grup_logistik')->whereIn('id', $id_toko)->get();
        return TokoListSimpleResources::collection($toko);
    }

    public function toko_by_omset(Request $request)
    {
        $omset      = Cache::remember('omset', 600*6*24, function () {
            return ViewStt::select('id_toko', DB::raw('round(SUM((in_dus * `harga_jual`) - ((in_dus * `disc_rupiah`) + (in_dus*`harga_jual`*`disc_persen`/100)))) as omset'))
                ->where('status', '=', 'delivered')
                ->whereDate('tanggal_terkirim', '>=', Carbon::now()->subMonths(3)->toDateString())
                ->where('id_tim', '!=', 20)
                ->groupBy('id_toko')
                ->orderByRaw('omset desc')
                ->limit(2000)
                ->get();
        });

        $omset          = $omset->pluck('id_toko')->toArray();
        $implode_omset  = implode(",", $omset);
        $toko           = Toko::with(['kelurahan', 'depo'])
                        ->whereIn('id', $omset)
                        ->whereNull('id_grup_logistik')
                        ->orderByRaw(DB::raw("FIELD(id, $implode_omset)"))
                        ->paginate(1);
        return TokoListSimpleResources::collection($toko);
    }

    /**
     * ambil data sisa limit kredit dan penjualan yang od
     * @param int $id
     */
    public function sisa_limit_dan_od($id)
    {
        $toko = Toko::find($id);
        if(!$toko) {
            return $this->dataNotFound('toko');
        }

        return response()->json([
            'sisa_limit' => Helper::sisaLimit($id),
            'od' => Helper::listOD($id)
        ], 200);
    }

    public function getTokoByOmset(Request $request)
    {
        $toko = DB::table('toko')->join('penjualan', 'penjualan.id_toko', '=', '');
    }

    public function duplicate($id)
    {
        if (!$this->user->can('Duplikat Toko')) {
            return $this->Unauthorized();
        }

        $toko = Toko::find($id);
        if (!$toko) {
            return $this->dataNotFound('toko');
        }

        $clone = $toko->replicate();
        $clone->push();

        $ketentuan_toko = KetentuanToko::where('id_toko', $id)->first();
        $ketentuan_toko = $ketentuan_toko->toArray();
        $ketentuan_toko['id_toko'] = $clone->id;
        unset($ketentuan_toko['toko']);
        unset($ketentuan_toko['nama_toko']);
        KetentuanToko::insert($ketentuan_toko);
        return $this->storeTrue('toko');
    }
    
    public function updateLockOrder($id)
    {
        if (!$this->user->can('Update Lock Order Toko')) {
            return $this->Unauthorized();
        }
        
        $toko = Toko::find($id);
        $toko->lock_order = '0';
        return $toko->save() ? $this->updateTrue('toko') : $this->updateFalse('toko');
        
    }
}
