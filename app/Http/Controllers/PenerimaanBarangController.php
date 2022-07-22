<?php

namespace App\Http\Controllers;

use App\Http\Resources\PenerimaanBarangSimple;
use App\Models\FakturPembelianPenerimaan;
use App\Models\FakturPembelian;
use App\Models\Gudang;
use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;
use App\Models\PenerimaanBarang;
use App\Models\DetailPenerimaanBarang;
use App\Models\Stock;
use App\Models\PosisiStock;
use App\Http\Resources\PenerimaanBarang as PenerimaanBarangResource;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Helpers\Helper;

class PenerimaanBarangController extends Controller
{
    protected $jwt;

    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
    }

    public function index(Request $request)
    {
        // REV: Tambah Filter gudang, start_date end_date, status, keyword (no_pb,no_do,no_spb, nama_principal, nama_gudang, driver, transporter, no_pol_kendaraan, keterangan)

        if ($this->user->can('Menu Penerimaan Barang')) :
            $id_user = $this->user->id;
            $list_penerimaan_barang = PenerimaanBarang::with('principal', 'principal.perusahaan','faktur_pembelian')->latest();

            // Filter Gudang
            if ($request->has('id_gudang')) {
                if ($request->id_gudang == 'all' || $request->id_gudang == '' || $request->id_gudang == null) {
                    if ($this->user->can('Gudang By Depo')):
                        $depo       = Helper::depoIDByUser($id_user);
                        $id_gudang  = Helper::gudangByDepo($depo)->pluck('id');
                    else:
                        $id_gudang  = Helper::gudangByUser($id_user);
                    endif;
                    $list_penerimaan_barang = $list_penerimaan_barang->whereIn('id_gudang', $id_gudang);
                } else {
                    $list_penerimaan_barang = $list_penerimaan_barang->where('id_gudang', $request->id_gudang);
                }
            }

            // Filter Status
            if ($request->has('status') && $request->status != '' && $request->status != 'all') {
                if ($request->status == 'waiting') {
                    $list_penerimaan_barang = $list_penerimaan_barang->where('is_approved', 0);
                } elseif ($request->status == 'approved') {
                    $list_penerimaan_barang = $list_penerimaan_barang->where('is_approved', 1);
                }
            }

            // Filter Keyword (no_pb,no_do,no_spb, nama_principal, nama_gudang, driver, transporter, no_pol_kendaraan, keterangan)
            if ($request->has('keyword') && $request->keyword != '') {
                $keyword = $request->keyword;
                $list_penerimaan_barang = $list_penerimaan_barang->where(function ($q) use ($keyword) {
                    $q->where('id', $keyword)
                        ->orWhere('no_pb', 'like', '%' . $keyword . '%')
                        ->orWhere('no_do', 'like', '%' . $keyword . '%')
                        ->orWhere('no_spb', 'like', '%' . $keyword . '%')
                        ->orWhere('driver', 'like', '%' . $keyword . '%')
                        ->orWhere('transporter', 'like', '%' . $keyword . '%')
                        ->orWhere('no_pol_kendaraan', 'like', '%' . $keyword . '%')
                        ->orWhere('keterangan', 'like', '%' . $keyword . '%')
                        ->orWhereHas('principal', function ($query) use ($keyword) {
                            $query->where('nama_principal', 'like', '%' . $keyword . '%');
                        })
                        ->orWhereHas('gudang', function ($query) use ($keyword) {
                            $query->where('nama_gudang', 'like', '%' . $keyword . '%');
                        });
                });
            }
            else{
                // Filter Date
                if ($request->has('date')) {
                    $list_penerimaan_barang = $list_penerimaan_barang->where('tgl_bongkar', $request->date);
                } elseif ($request->has(['start_date', 'end_date'])) {
                    $list_penerimaan_barang = $list_penerimaan_barang->whereBetween('tgl_bongkar', [$request->start_date, $request->end_date]);
                }
            }

            // filter data sesuai depo user
            $id_depo = Helper::depoIDByUser($this->user->id);
            $list_penerimaan_barang = $list_penerimaan_barang->whereHas('gudang', function ($query) use ($id_depo) {
                $query->whereIn('id_depo', $id_depo);
            });

            $perPage = $request->has('per_page') ? $perPage = $request->per_page : $perPage = 5;
            $list_penerimaan_barang = $perPage == 'all' ? $list_penerimaan_barang->get() : $list_penerimaan_barang->paginate((int)$perPage);

            if ($list_penerimaan_barang) {
                return PenerimaanBarangResource::collection($list_penerimaan_barang);
            }
            return response()->json([
                'message' => 'Data Penerimaan Barang tidak ditemukan!'
            ], 404);
        else :
            return $this->Unauthorized();
        endif;
    }

    public function store(Request $request)
    {

        if ($this->user->can('Tambah Penerimaan Barang')) :
            $this->validate($request, [
                'no_do' => 'required|max:100', //|unique:penerimaan_barang',
                'no_spb' => 'max:100',
                'id_principal' => 'required|numeric|min:0|max:9999999999|exists:principal,id',
                'id_gudang' => 'required|numeric|min:0|max:9999999999',
                'tgl_kirim' => 'required|date',
                'tgl_datang' => 'required|date',
                'tgl_bongkar' => 'required|date',
                'driver' => 'max:100',
                'transporter' => 'max:100',
                'no_pol_kendaraan' => 'max:100'
            ]);

            $input = $request->all();
            $input['is_approved'] = 0;
            $input['created_by'] = $this->user->id;
            if($input['tgl_bongkar']!=Carbon::now()->toDateString() && !$this->user->can('Tambah Penerimaan Barang Forced')){
                return response()->json(['message' => 'Tanggal bongkar tidak sesuai'], 400);
            }

            try {
                $penerimaan_barang = PenerimaanBarang::create($input);

                // GENERATE NOMOR PENERIMAAN BARANG
                $kode_gudang = DB::table('gudang')->where('id', $request->id_gudang)->pluck('kode_gudang')->first();
                $thn_bln = \Carbon\Carbon::now()->format('ym');
                $keyword = 'PB-' . $kode_gudang . '-' . $thn_bln . '-%';
                $list_npb = DB::table('penerimaan_barang')->where('no_pb', 'like', $keyword)->pluck('no_pb');

                if (count($list_npb) != 0) {
                    $string_lnpb = substr($list_npb, -4);
                    $arr = [];
                    foreach ($list_npb as $value) {
                        array_push($arr, (int)substr($value, strrpos($value, '-') + 1));
                    };
                    $new_no = max($arr) + 1;
                    $string_no = sprintf("%04d", $new_no);
                } else {
                    $string_no = '0001';
                }

                $penerimaan_barang->no_pb = 'PB-' . $kode_gudang . '-' . $thn_bln . '-' . $string_no;

                $penerimaan_barang->save();

                $logData = [
                    'action' => 'Tambah Penerimaan Barang',
                    'description' => 'No Pembelian: ' . $penerimaan_barang->no_pb,
                    'user_id' => $this->user->id
                ];

                $this->log($logData);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 400);
            }

            return response()->json([
                'message' => 'Data Penerimaan Barang berhasil disimpan.'
            ], 201);
        else :
            return $this->Unauthorized();
        endif;
    }

    public function show($id)
    {
        if ($this->user->can('Edit Penerimaan Barang')) :
            $penerimaan_barang = PenerimaanBarang::find($id);

            if ($penerimaan_barang) {
                // $detail_penerimaan_barang = DetailPenerimaanBarang::where('id_penerimaan_barang', $id)->get();
                return new PenerimaanBarangResource($penerimaan_barang);
            }

            return response()->json([
                'message' => 'Data Penerimaan Barang tidak ditemukan!'
            ], 404);
        else :
            return $this->Unauthorized();
        endif;
    }

    public function update(Request $request, $id)
    {
        if ($this->user->can('Update Penerimaan Barang')) :
            $penerimaan_barang = PenerimaanBarang::find($id);

            if ($penerimaan_barang->is_approved == 1) {
                return response()->json([
                    'message' => 'Data Penerimaan Barang tidak boleh diubah karena telah disetujui.'
                ], 422);
            }

            $this->validate($request, [
                'no_do'         => 'required|max:100|unique:penerimaan_barang,no_do,' . $id,
                'no_spb'        => 'max:100',
                'id_principal'  => 'required|numeric|min:0|max:9999999999|exists:principal,id',
                'id_gudang'     => 'required|numeric|min:0|max:9999999999',
                'tgl_kirim'     => 'required|date',
                'tgl_datang'    => 'required|date',
                'tgl_bongkar'   => 'required|date',
                'driver'        => 'max:100',
                'transporter'   => 'max:100',
                'no_pol_kendaraan' => 'max:100'
            ]);

            $input = $request->all();
            $input['updated_by'] = $this->user->id;

            if ($penerimaan_barang) {
                $penerimaan_barang->update($input);

                return response()->json([
                    'message' => 'Data Penerimaan Barang telah berhasil diubah.',
                    'data' => new PenerimaanBarangResource($penerimaan_barang)
                ], 201);
            }

            return response()->json([
                'message' => 'Data Penerimaan Barang tidak ditemukan.'
            ], 404);
        else :
            return $this->Unauthorized();
        endif;
    }

    public function destroy($id, Request $request)
    {
        if ($this->user->can('Hapus Penerimaan Barang')) :
            $penerimaan_barang = PenerimaanBarang::find($id);
            if (!$penerimaan_barang) {
                return response()->json([
                    'message' => 'Data Penerimaan Barang tidak ditemukan!'
                ], 400);
            }

            if ($penerimaan_barang->is_approved == 1) {
                return response()->json([
                    'message' => 'Data Penerimaan Barang tidak boleh dihapus karena telah disetujui.'
                ], 422);
            }

            if ($penerimaan_barang) {
                $data = ['deleted_by' => $this->user->id];
                $penerimaan_barang->update($data);
                $penerimaan_barang->delete();

                return response()->json([
                    'message' => 'Data Penerimaan Barang berhasil dihapus.',
                    'data' => $this->index($request)
                ], 200);
            }

            return response()->json([
                'message' => 'Data Penerimaan Barang tidak ditemukan!'
            ], 404);
        else :
            return $this->Unauthorized();
        endif;
    }

    public function restore($id)
    {
        if ($this->user->can('Tambah Penerimaan Barang')) :
            $penerimaan_barang = PenerimaanBarang::withTrashed()->find($id);

            if ($penerimaan_barang) {
                $data = ['deleted_by' => null];
                $penerimaan_barang->update($data);

                $penerimaan_barang->restore();

                return response()->json([
                    'message' => 'Data Penerimaan Barang berhasil dikembalikan.'
                ], 200);
            }

            return response()->json([
                'message' => 'Data Penerimaan Barang tidak ditemukan!'
            ], 404);
        else :
            return $this->Unauthorized();
        endif;
    }

    public function approve(Request $request, $id)
    {

        if ($this->user->can('Approve Penerimaan Barang')) :

            $penerimaan_barang = PenerimaanBarang::find($id);

            if ($penerimaan_barang->is_approved == 1) {
                return response()->json([
                    'message' => 'Data Penerimaan Barang telah disetujui.'
                ], 422);
            }

            $detail_penerimaan_barang = DetailPenerimaanBarang::where('id_penerimaan_barang', $id)->get();

            if ($detail_penerimaan_barang->count() <= 0) {
                return response()->json([
                    'message' => 'Data Penerimaan Barang masih kosong, isi data barang terlebih dahulu.'
                ], 422);
            }

            try {
                DB::beginTransaction();
                $logStock = [];
                foreach ($detail_penerimaan_barang as $dpb) {
                    $stock = Stock::where('id_gudang', $penerimaan_barang->id_gudang)->where('id_barang', $dpb->id_barang)->first();
                    if (!$stock) {
                        $stock = Stock::create([
                            'id_gudang' => $penerimaan_barang->id_gudang,
                            'id_barang' => $dpb->id_barang,
                            'qty' => 0,
                            'qty_pcs' => 0,
                            'created_by' => $this->user->id
                        ]);
                    }

                    $posisi_stock = PosisiStock::where('id_stock', $stock->id)->where('tanggal', Carbon::today()->toDateString())->first();
                    if (!$posisi_stock) {
                        $posisi_stock = PosisiStock::create([
                            'id_stock' => $stock->id,
                            // 'tanggal' => $penerimaan_barang->tgl_bongkar,
                            'tanggal' => Carbon::today()->toDateString(),
                            'harga' => $stock->dbp,
                            'saldo_awal_qty' => $stock->qty,
                            'saldo_awal_pcs' => $stock->qty_pcs,
                            'saldo_akhir_qty' => $stock->qty,
                            'saldo_akhir_pcs' => $stock->qty_pcs,
                        ]);
                    }

                    // tambah stock gudang
                    $stock->increment('qty', $dpb->qty);
                    $stock->increment('qty_pcs', $dpb->qty_pcs);

                    // catat riwayat pergerakan stock
                    $posisi_stock->increment('pembelian_qty', $dpb->qty);
                    $posisi_stock->increment('pembelian_pcs', $dpb->qty_pcs);
                    $posisi_stock->increment('saldo_akhir_qty', $dpb->qty);
                    $posisi_stock->increment('saldo_akhir_pcs', $dpb->qty_pcs);

                    $logStock[] = [
                        'tanggal'       => $penerimaan_barang->tgl_bongkar,
                        'id_barang'     => $stock->id_barang,
                        'id_gudang'     => $stock->id_gudang,
                        'id_user'       => $this->user->id,
                        'id_referensi'  => $dpb->id,
                        'referensi'     => 'penerimaan barang',
                        'no_referensi'  => $penerimaan_barang->id,
                        'qty_pcs'       => ($dpb->qty * $stock->isi) + $dpb->qty_pcs,
                        'status'        => 'approved',
                        'created_at'    => Carbon::now(),
                        'updated_at'    => Carbon::now()
                    ];
                }

                $penerimaan_barang->is_approved = 1;
                $penerimaan_barang->save();
                $logData = [
                    'action' => 'Approve Penerimaan Barang',
                    'description' => 'No Pembelian: ' . $penerimaan_barang->no_pb,
                    'user_id' => $this->user->id
                ];

                $this->log($logData);
                $this->createLogStock($logStock);
                DB::commit();
                return response()->json([
                    'message' => 'Data Penerimaan Barang berhasil disetujui.'
                ], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Data Penerimaan Barang gagal disetujui, coba beberapa saat lagi'
                ], 400);
            }
        else :
            return $this->Unauthorized();
        endif;
    }

    public function cancel_approval(Request $request, $id)
    {

        if ($this->user->can('Unapprove Penerimaan Barang')) :
            $penerimaan_barang = PenerimaanBarang::find($id);

            if ($penerimaan_barang->is_approved == 0) {
                return response()->json([
                    'message' => 'Persetujuan Penerimaan Barang telah dibatalkan.'
                ], 422);
            }

            $detail_penerimaan_barang = DetailPenerimaanBarang::where('id_penerimaan_barang', $id)->get();

            try {
                DB::beginTransaction();
                foreach ($detail_penerimaan_barang as $dpb) {
                    $stock = Stock::where('id_gudang', $penerimaan_barang->id_gudang)->where('id_barang', $dpb->id_barang)->first();
                    if (!$stock) {
                        $stock = Stock::create([
                            'id_gudang' => $penerimaan_barang->id_gudang,
                            'id_barang' => $dpb->id_barang,
                            'qty' => 0,
                            'qty_pcs' => 0,
                            'created_by' => $this->user->id
                        ]);
                    }

                    $posisi_stock = PosisiStock::where('id_stock', $stock->id)->where('tanggal', Carbon::today()->toDateString())->first();
                    if (!$posisi_stock) {
                        $posisi_stock = PosisiStock::create([
                            'id_stock' => $stock->id,
                            // 'tanggal' => $penerimaan_barang->tgl_bongkar,
                            'tanggal' => Carbon::today()->toDateString(),
                            'harga' => $stock->dbp,
                            'saldo_awal_qty' => $stock->qty,
                            'saldo_awal_pcs' => $stock->qty_pcs,
                            'saldo_akhir_qty' => $stock->qty,
                            'saldo_akhir_pcs' => $stock->qty_pcs,
                        ]);
                    }

                    // kembalikan stock gudang
                    $stock->decrement('qty', $dpb->qty);
                    $stock->decrement('qty_pcs', $dpb->qty_pcs);
                    while ($stock->qty_pcs < 0) {
                        $stock->decrement('qty');
                        $stock->increment('qty_pcs', $stock->isi);
                    }

                    // catat riwayat pergerakan stock
                    $posisi_stock->decrement('pembelian_qty', $dpb->qty);
                    $posisi_stock->decrement('pembelian_pcs', $dpb->qty_pcs);
                    $posisi_stock->decrement('saldo_akhir_qty', $dpb->qty);
                    $posisi_stock->decrement('saldo_akhir_pcs', $dpb->qty_pcs);

                    while ($posisi_stock->pembelian_pcs < 0) {
                        $posisi_stock->decrement('pembelian_qty');
                        $posisi_stock->increment('pembelian_pcs', $stock->isi);
                    }
                    while ($posisi_stock->saldo_akhir_pcs < 0) {
                        $posisi_stock->decrement('saldo_akhir_qty');
                        $posisi_stock->increment('saldo_akhir_pcs', $stock->isi);
                    }
                }

                $penerimaan_barang->is_approved = 0;
                $penerimaan_barang->save();

                $this->deleteLogStock([
                    ['referensi', 'penerimaan barang'],
                    ['no_referensi', $penerimaan_barang->id],
                    ['status', 'approved']
                ]);

                $logData = [
                    'action' => 'Cancel Approve Penerimaan Barang',
                    'description' => 'No Pembelian: ' . $penerimaan_barang->no_pb,
                    'user_id' => $this->user->id
                ];

                $this->log($logData);
                DB::commit();
                return response()->json([
                    'message' => 'Persetujuan Penerimaan Barang berhasil dibatalkan.'
                ], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Persetujuan Penerimaan Barang gagal dibatalkan.'
                ], 200);
            }
        else :
            return $this->Unauthorized();
        endif;
    }

    public function getList(Request $request)
    {
        $id_principal           = $request->id_principal;
        $id_penerimaan_barang   = FakturPembelian::join('faktur_pembelian_penerimaan','faktur_pembelian_penerimaan.id_faktur_pembelian','faktur_pembelian.id')
        ->whereNull('faktur_pembelian.deleted_at')
        ->select('id_penerimaan_barang')
        ->get();
        $penerimaan_barang      = PenerimaanBarang::where('id_principal', $id_principal)->where('is_approved', 1)->whereNotIn('id', $id_penerimaan_barang)->orderBy('id', 'desc')->get();
        return PenerimaanBarangSimple::collection($penerimaan_barang);
    }
}
