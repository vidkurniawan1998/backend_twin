<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\Http\Requests\FakturPembelianStoreRequest;
use App\Http\Resources\FakturPembelian as FakturPembelianResources;
use App\Models\DetailFakturPembelian;
use App\Models\FakturPembelian;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\JWTAuth;

class FakturPembelianController extends Controller
{
    protected $jwt, $user;

    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
    }

    public function index(Request $request)
    {
        if ($this->user->can('Menu Faktur Pembelian')) :
            $id_user        = $this->user->id;
            $status         = $request->status;
            $per_page       = $request->per_page ?? 10;

            $keyword        = $request->has('keyword') && $request->keyword!='' ? $request->keyword : '';
            $id_perusahaan  = $request->has('id_perusahaan') && count($request->id_perusahaan) > 0
                ? $request->id_perusahaan : Helper::perusahaanByUser($id_user);
            $id_depo        = $request->has('id_depo') && count($request->id_depo) > 0
                ? $request->id_depo : Helper::depoIDByUser($id_user, $id_perusahaan);
            $id_principal   = $request->has('id_principal') && count($request->id_principal) > 0 ? $request->id_principal : [];
            $due_date       = $request->has('due_date') && $request->due_date!='' && $request->due_date!='0000-00-00' ? $request->due_date : Carbon::today()->toDateString();

            $data = FakturPembelian::with(['perusahaan', 'principal','penerimaan_barang'])
                ->whereIn('id_perusahaan', $id_perusahaan)
                ->whereIn('id_depo', $id_depo)
                ->when(count($id_principal) > 0, function($q) use ($id_principal) {
                    $q->whereHas('principal', function($q) use ($id_principal) {
                        $q->whereIn('id', $id_principal);
                    });
                })
                ->when($keyword<>'', function($q) use ($keyword){
                     $q->where('id', 'like', '%' . $keyword . '%')
                            ->orWhere('tanggal_invoice', 'like', '%' . $keyword . '%')
                            ->orWhere('no_invoice', 'like', '%' . $keyword . '%')
                            ->orWhereHas('penerimaan_barang', function ($query) use ($keyword) {
                                $query->where('no_pb', 'like', '%' . $keyword . '%')
                                    ->orWhere('no_do', 'like', '%' . $keyword . '%')
                                    ->orWhere('transporter', 'like', '%' . $keyword . '%');
                            });
                });

                if ($status == 'lunas'){ // yang dilunasi hari ini (atau sesuai due_date inputan)
                    $data = $data->whereDate('tanggal_bayar', '<=', $due_date);
                } elseif($status == 'belum_lunas'){ // yang belum dilunasi hari ini (atau sesuai due_date inputan)
                    $data = $data->whereNull('tanggal_bayar')->whereDate('tanggal_invoice', '<=', $due_date);
                } elseif($status == 'over_due'){
                    $data = $data->whereNull('tanggal_bayar')->where('tanggal_jatuh_tempo', '<=',  $due_date);
                } else {
                    $data = $data->where('tanggal','<=', $due_date);
                }

            $data = $data->orderBy('id', 'desc');

            $data = $per_page === 'all' ? $data->get() : $data->paginate($per_page);
            return FakturPembelianResources::collection($data);
        else :
            return $this->Unauthorized();
        endif;
    }

    public function store(FakturPembelianStoreRequest $request)
    {
        if ($this->user->can('Tambah Faktur Pembelian')) :
            $this->validate($request, [
                'penerimaan_barang'           => 'required|array|min:1',
                'detail_faktur_pembelian'     => 'required|array|min:1',
            ]);

            $faktur_pembelian = $request->only([
                'id_perusahaan',
                'id_depo',
                'id_principal',
                'no_invoice',
                'faktur_pajak',
                'disc_value',
                'disc_persen',
                'status',
                'tanggal_invoice',
                'tanggal_jatuh_tempo',
                'ppn'
            ]);

            $faktur_pembelian['tanggal']        = Carbon::now()->format('Y-m-d');
            $faktur_pembelian['tanggal_bayar']  = null;
            $penerimaan_barang                  = $request->penerimaan_barang;
            $detail_faktur_pembelian            = $request->detail_faktur_pembelian;


            $detail=[];
            foreach ($detail_faktur_pembelian as $dfp) {
                $detail[] = new DetailFakturPembelian([
                    'id_barang'     => $dfp['id_barang'],
                    'qty'           => $dfp['qty'],
                    'pcs'           => $dfp['pcs'],
                    'disc_persen'   => $dfp['disc_persen'],
                    'disc_value'    => $dfp['disc_value'],
                    'harga'         => round($dfp['harga_barang'], 2)
                ]);
            }

            $penerimaan = [];
            foreach ($penerimaan_barang as $pb) {
                $penerimaan[] = $pb['id'];
            }

            DB::beginTransaction();
            try {
                $pembelian = FakturPembelian::create($faktur_pembelian);
                $pembelian->detail_faktur_pembelian()->saveMany($detail);
                $pembelian->penerimaan_barang()->sync($penerimaan);
                DB::commit();
                return $this->storeTrue('faktur pembelian');
            } catch (\Exception $e) {
                DB::rollBack();
                return $this->storeFalse('faktur pembelian');
            }
        else :
            return $this->Unauthorized();
        endif;
    }

    public function edit($id)
    {
        if ($this->user->can('Edit Faktur Pembelian')) :
            $pembelian = FakturPembelian::find($id);
            if (!$pembelian) {
                return $this->dataNotFound('faktur pembelian');
            }

            return new FakturPembelianResources($pembelian->load([
                    'detail_faktur_pembelian',
                    'detail_faktur_pembelian.barang',
                    'penerimaan_barang'
                ])
            );
        else :
            return $this->Unauthorized();
        endif;
    }

    public function update(FakturPembelianStoreRequest $request, $id)
    {
        if ($this->user->can('Edit Faktur Pembelian')) :  
            $pembelian = FakturPembelian::find($id);
            if (!$pembelian) {
                return $this->dataNotFound('faktur pembelian');
            }

            if ($pembelian->status !== 'input') {
                return response()->json([
                    'message' => "Pembelian {$pembelian->status} tidak dapat diupdate! "
                ], 400);
            }

            $faktur_pembelian = $request->only([
                'id_perusahaan',
                'id_depo',
                'id_principal',
                'no_invoice',
                'faktur_pajak',
                'disc_value',
                'disc_persen',
                'status',
                'tanggal_invoice',
                'tanggal_jatuh_tempo',
                'ppn'
            ]);

            $penerimaan_barang      = $request->penerimaan_barang;
            $detail_faktur_pembelian= $request->detail_faktur_pembelian;
            $penerimaan = [];
            foreach ($penerimaan_barang as $pb) {
                $penerimaan[] = $pb['id'];
            }

            DB::beginTransaction();
            try {
                $id_detail_faktur = [];
                $pembelian->update($faktur_pembelian);
                $pembelian->penerimaan_barang()->sync($penerimaan);
                $delete = DetailFakturPembelian::where('id_faktur_pembelian',$id)->delete();
                
                $detail=[];
                foreach ($detail_faktur_pembelian as $dfp) {
                    $detail[] = new DetailFakturPembelian([
                        'id_barang'     => $dfp['id_barang'],
                        'qty'           => $dfp['qty'],
                        'pcs'           => $dfp['pcs'],
                        'disc_persen'   => $dfp['disc_persen'],
                        'disc_value'    => $dfp['disc_value'],
                        'harga'         => round($dfp['harga_barang'], 2)
                    ]);
                }

                $pembelian->detail_faktur_pembelian()->saveMany($detail);
                DB::commit();
                return $this->updateTrue('pembelian');
            } catch (\Exception $e) {
                DB::rollBack();
                return $this->updateFalse('pembelian');
            }
        else :
            return $this->Unauthorized();
        endif;
    }

    public function approve($id)
    {
        if ($this->user->can('Approve Faktur Pembelian')) :
            $pembelian = FakturPembelian::find($id);
            if (!$pembelian) {
                return $this->dataNotFound('faktur pembelian');
            }

            if ($pembelian->status !== 'input') {
                return response()->json([
                    'message' => "Pembelian {$pembelian->status} tidak dapat disetujui! "
                ], 400);
            }

            return $pembelian->update(['status' => 'approved'])
                ? $this->updateTrue('pembelian') : $this->updateFalse('pembelian');
        else :
            return $this->Unauthorized();
        endif;
    }

    public function unapprove($id)
    {
        if ($this->user->can('Approve Faktur Pembelian')) :
            $pembelian = FakturPembelian::find($id);
            if (!$pembelian) {
                return $this->dataNotFound('faktur pembelian');
            }

            if ($pembelian->status !== 'approved' && $pembelian->status !== 'paid') {
                return response()->json([
                    'message' => "Pembelian {$pembelian->status} tidak dapat dibatalkan! "
                ], 400);
            }

            return $pembelian->update(['status' => 'input'])
                ? $this->updateTrue('pembelian') : $this->updateFalse('pembelian');
        else :
            return $this->Unauthorized();
        endif;
    }

    public function destroy($id, Request $request)
    {
        if ($this->user->can('Hapus Faktur Pembelian')) :
            $faktur_pembelian = FakturPembelian::find($id);

            if ($faktur_pembelian->status != 'input') {
                return response()->json([
                    'message' => 'Data pembelian yang telah disetujui tidak boleh dihapus!'
                ], 422);
            }

            if ($faktur_pembelian) {
                $data = ['deleted_by' => $this->user->id];
                $faktur_pembelian->update($data);
                $faktur_pembelian->delete();
                return response()->json([
                    'message' => 'Data pembelian berhasil dihapus.',
                ], 200);
            }

            return response()->json([
                'message' => 'Data pembelian tidak ditemukan!'
            ], 404);
        else :
            return $this->Unauthorized();
        endif;
    }
}
