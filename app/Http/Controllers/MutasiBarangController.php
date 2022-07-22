<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\Models\MutasiHeader;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;
use App\Models\MutasiBarang;
use App\Models\Stock;
use App\Models\PosisiStock;
use App\Models\Gudang;
use App\Models\DetailMutasiBarang;
use App\Models\KepalaGudang;
use App\Models\Salesman;
use App\Models\Canvass;
use App\Models\StockAwal;
use App\Http\Resources\MutasiBarang as MutasiBarangResource;
use App\Http\Resources\Stock as StockResource;
use App\Http\Resources\Gudang as GudangResource;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MutasiBarangController extends Controller 
{
    protected $jwt;

    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
    }

    // filter : tanggal, dari gudang, ke gudang, status, keyword
    public function index(Request $request)
    {

        // REV: untuk Kepala Gudang, Salesman canvass hanya tampilkan mutasi yang berkaitan dgn gudangnya
        // admin, pimpinan, accounting, logistik = all
        // kepala_gudang, salesman = data mutasi gudang / mobil canvassnya saja

        if (!$this->user->can('Menu Mutasi Barang')) {
            return $this->Unauthorized();
        }

        $list_mutasi_barang = MutasiBarang::latest();
        if ($this->user->can('Mutasi Kepala Gudang')) {
            $kepala_gudang = KepalaGudang::where('user_id', $this->user->id)->first();
            $list_mutasi_barang = MutasiBarang::where('dari', $kepala_gudang->id_gudang)->orWhere('ke', $kepala_gudang->id_gudang)->latest();
        }

        if ($this->user->can('Mutasi Barang Salesman')) {
            $salesman   = Salesman::where('user_id', $this->user->id)->first();
            $canvass    = Canvass::where('id_tim', $salesman->id_tim)->first();
            if (!$canvass) {
                return response()->json([
                    'message' => 'Data Mutasi Barang tidak ditemukan!'
                ], 404);
            }
            $list_mutasi_barang = MutasiBarang::where('dari', $canvass->id_gudang_canvass)->orWhere('ke', $canvass->id_gudang_canvass)->latest();
        }

        if ($this->user->can('Gudang By Depo')) :
            $depo       = Helper::depoIDByUser($this->user->id);
            $gudang     = Helper::gudangByDepo($depo)->pluck('id');
        else :
            $gudang     = Helper::gudangByUser($this->user->id);
        endif;

        $id_gudang_asal     = ($request->id_gudang_asal ?? 'all') == '' ? 'all' : $request->id_gudang_asal;
        $id_gudang_tujuan   = ($request->id_gudang_tujuan ?? 'all') == '' ? 'all' : $request->id_gudang_tujuan;

        $list_mutasi_barang->where(function ($q) use ($gudang, $id_gudang_asal, $id_gudang_tujuan) {
            return $q->when($id_gudang_asal <> 'all', function ($q) use ($id_gudang_asal) {
                $q->where('dari', $id_gudang_asal);
            })
                ->when($id_gudang_tujuan <> 'all', function ($q) use ($id_gudang_tujuan) {
                    $q->where('ke', $id_gudang_tujuan);
                })
                ->when($id_gudang_asal == 'all' && $id_gudang_tujuan == 'all', function ($q) use ($gudang) {
                    $q->whereIn('dari', $gudang)->orWhereIn('ke', $gudang);
                });
        });

        // Filter Date
        if ($request->has('date')) {
            $list_mutasi_barang = $list_mutasi_barang->where('tanggal_mutasi', $request->date);
        } elseif ($request->has(['start_date', 'end_date'])) {
            $list_mutasi_barang = $list_mutasi_barang->whereBetween('tanggal_mutasi', [$request->start_date, $request->end_date]);
        }

        // Filter Status
        if ($request->has('status') && $request->status != '' && $request->status != 'all') {
            $list_mutasi_barang = $list_mutasi_barang->where('status', $request->status);
        }

        // Filter Keyword (no_mutasi)
        if ($request->has('keyword') && $request->keyword != '') {
            $keyword = $request->keyword;
            $list_mutasi_barang = $list_mutasi_barang->where('id', 'like', '%' . $keyword . '%');
        }

        $perPage = $request->has('per_page') ? $perPage = $request->per_page : $perPage = 5;
        $list_mutasi_barang = $perPage == 'all' ? $list_mutasi_barang->get() : $list_mutasi_barang->paginate((int)$perPage);

        if ($list_mutasi_barang) {
            return MutasiBarangResource::collection($list_mutasi_barang);
        }

        return $this->dataNotFound('Muatasi Barang');
    }

    public function store(Request $request)
    {
        if (!$this->user->can('Tambah Mutasi Barang')) {
            return $this->Unauthorized();
        }

        $this->validate($request, [
            'tanggal_mutasi' => 'required|date',
            'dari' => 'required|numeric|min:0|max:9999999999',
            'ke' => 'required|numeric|min:0|max:9999999999',
        ]);

        $input = $request->all();
        $input['is_approved']   = 0;
        $input['created_by']    = $this->user->id;

        try {
            $mutasi_barang = MutasiBarang::create($input);
            $dataLog = [
                'action' => 'Tambah Mutasi Barang',
                'description' => 'No Mutasi: ' . $mutasi_barang->id . ' Mutasi dari ' . $mutasi_barang->dari_gudang->nama_gudang . ' ke ' . $mutasi_barang->ke_gudang->nama_gudang,
                'user_id' => $this->user->id
            ];

            $this->log($dataLog);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

        return response()->json([
            'message' => 'Data Mutasi Barang berhasil disimpan.',
            'data' => $this->index($request)
        ], 201);
    }

    public function show($id)
    {
        if (!$this->user->can('Edit Mutasi Barang')) {
            return $this->Unauthorized();
        }

        $mutasi_barang = MutasiBarang::find($id);

        if ($mutasi_barang) {
            return new MutasiBarangResource($mutasi_barang);
        }

        return $this->dataNotFound('Mutasi Barang');
    }

    public function update(Request $request, $id)
    {
        if (!$this->user->can('Update Mutasi Barang')) {
            return $this->Unauthorized();
        }

        $mutasi_barang = MutasiBarang::find($id);

        if ($mutasi_barang->is_approved == 1) {
            return response()->json([
                'message' => 'Data Mutasi Barang tidak boleh diubah karena telah disetujui.'
            ], 422);
        }

        $this->validate($request, [
            'tanggal_mutasi' => 'required|date',
            'dari' => 'required|numeric|min:0|max:9999999999',
            'ke' => 'required|numeric|min:0|max:9999999999',
        ]);

        $input = $request->all();
        $input['updated_by'] = $this->user->id;

        if ($mutasi_barang) {
            $mutasi_barang->update($input);

            return response()->json([
                'message' => 'Data Mutasi Barang telah berhasil diubah.',
                'data' => new MutasiBarangResource($mutasi_barang)
            ], 201);
        }

        return $this->dataNotFound('Mutasi Barang');
    }

    public function destroy($id, Request $request)
    {
        if (!$this->user->can('Hapus Mutasi Barang')) {
            return $this->Unauthorized();
        }

        $mutasi_barang = MutasiBarang::find($id);
        if (!$mutasi_barang) {
            return $this->dataNotFound('Mutasi Barang');
        }

        if ($mutasi_barang->is_approved == 1) {
            return response()->json([
                'message' => 'Data Mutasi Barang tidak boleh dihapus karena telah disetujui.'
            ], 422);
        }

        $data = ['deleted_by' => $this->user->id];
        $mutasi_barang->update($data);
        $mutasi_barang->delete();

        // hapus log stock
        $this->deleteLogStock([
            ['referensi', 'mutasi keluar'],
            ['no_referensi', $mutasi_barang->id],
            ['status', 'approved']
        ]);

        $this->deleteLogStock([
            ['referensi', 'mutasi keluar'],
            ['no_referensi', $mutasi_barang->id],
            ['status', 'on the way']
        ]);

        $this->deleteLogStock([
            ['referensi', 'mutasi keluar'],
            ['no_referensi', $mutasi_barang->id],
            ['status', 'received']
        ]);

        $this->deleteLogStock([
            ['referensi', 'mutasi masuk'],
            ['no_referensi', $mutasi_barang->id],
            ['status', 'received']
        ]);

        return response()->json([
            'message' => 'Data Mutasi Barang berhasil dihapus.',
            'data' => $this->index($request)
        ], 200);

    }

    public function restore($id)
    {
        if ($this->user->can('Tambah Mutasi Barang')) :
            $mutasi_barang = MutasiBarang::withTrashed()->find($id);

            if ($mutasi_barang) {
                $data = ['deleted_by' => null];
                $mutasi_barang->update($data);

                $mutasi_barang->restore();

                return response()->json([
                    'message' => 'Data Mutasi Barang berhasil dikembalikan.'
                ], 200);
            }

            return response()->json([
                'message' => 'Data Mutasi Barang tidak ditemukan!'
            ], 404);
        else :
            return $this->Unauthorized();
        endif;
    }

    public function approve(Request $request, $id)
    {
        // REV : yang boleh approve mutasi adalah kepala_gudang (salesman canvass) penerima dan accounting (?)
        if (!$this->user->can('Approve Mutasi Barang')) {
            return $this->Unauthorized();
        }

        $mutasi_barang = MutasiBarang::find($id);

        if (!$mutasi_barang) {
            return $this->dataNotFound('Mutasi Barang');
        }

        if ($mutasi_barang->is_approved == 1) {
            return response()->json([
                'message' => 'Data Mutasi Barang telah disetujui.'
            ], 422);
        }

        if ($this->user->can('Approve Mutasi Barang Gudang Baik') ||
            $this->user->can('Approve Mutasi Barang Gudang Canvass')) {
            // jika gudang penerima = gudang baik, get id_user (kepala_gudang), jika tidak sama dgn yg login, show error message
            $gudang_penerima = Gudang::find($mutasi_barang->ke);
            if ($gudang_penerima->jenis == 'baik') {
                $kepala_gudang = KepalaGudang::where('id_gudang', $mutasi_barang->ke)->first();
                if (!$kepala_gudang) {
                    return response()->json([
                        'message' => 'Gudang ' . $gudang_penerima->nama_gudang . ' belum ada Kepala Gudangnya!'
                    ], 422);
                }
                if ($kepala_gudang->id != $this->user->id) {
                    return response()->json([
                        'message' => 'Anda bukan kepala gudang ' . $gudang_penerima->nama_gudang . ', anda tidak berhak menyetujui Mutasi Barang ini'
                    ], 422);
                }
            }

            // jika gudang penerima = canvass
            //   jika user login = salesman
            //      get id_salesman u/ canvas tsb
            //          jika tidak sama dgn user yg login, show error
            //   jika user login = driver
            //      get id_driver u/ canvas tsb
            //          jika tidak sama dgn user yg login show error

            if ($gudang_penerima->jenis == 'canvass') {
                $canvass = Canvass::where('id_gudang_canvass', $mutasi_barang->ke)->first();
                if (!$canvass) {
                    return $this->dataNotFound('Data Canvass');
                }

                if ($this->user->can('Approve Mutasi Barang Gudang Canvass')) {
                    $id_user = $canvass->tim->salesman->user_id;
                    if (!$id_user) {
                        return $this->dataNotFound('Data salesman canvass');
                    }
                }

                if ($this->user->id != $id_user) {
                    return response()->json([
                        'message' => 'Anda tidak berhak untuk menyetujui Mutasi Barang ini!'
                    ], 422);
                }
            }
        }

        $detail_mutasi_barang = DetailMutasiBarang::where('id_mutasi_barang', $id)->get();

        if ($detail_mutasi_barang->count() == 0) {
            return response()->json([
                'message' => 'Data Mutasi Barang masih kosong, isi data barang terlebih dahulu.'
            ], 422);
        }

        try {
            DB::beginTransaction();
            foreach ($detail_mutasi_barang as $dmb) {
                $stock = Stock::where('id', $dmb->id_stock)->first();
                if ($stock->id_gudang != $mutasi_barang->dari) {
                    return response()->json([
                        'message' => 'Stock dan gudang tidak sesuai, refresh browser'
                    ], 422);
                }
            }

            //loop untuk cek stock
            foreach ($detail_mutasi_barang as $dmb) {
                $stock = Stock::where('id', $dmb->id_stock)->first();
                $volume = $stock->barang->isi;
                if (!$stock) {
                    return response()->json([
                        'message' => 'Stock barang di gudang tidak cukup, Mohon periksa kembali!'
                    ], 422);
                }

                $volume_stock   = ($stock->qty * $volume) + $stock->qty_pcs;
                $volume_mutasi  = ($dmb->qty * $volume) + $dmb->qty_pcs;
                $stock_akhir    = $volume_stock - $volume_mutasi;

                //jika stock tidak cukup batalkan
                if ($stock_akhir < 0) {
                    return response()->json([
                        'message' => 'Stock barang di gudang tidak cukup ' . $stock->barang->kode_barang . ' cek stock barang!'
                    ], 422);
                }
            }

            // ======================== perpindahan stock ========================
            foreach ($detail_mutasi_barang as $dmb) {
                //cari id stock (gudang x barang) pertama / gudang asal
                $stock = Stock::where('id', $dmb->id_stock)->first();
                //cari id stock (gudang x barang) kedua / gudang penerima
                $stock2 = Stock::where('id_gudang', $mutasi_barang->ke)->where('id_barang', $dmb->id_barang)->first();
                //jika tidak ketemu buat id stock baru
                if (!$stock2) {
                    $stock2 = Stock::create([
                        'id_gudang' => $mutasi_barang->ke,
                        'id_barang' => $stock->id_barang,
                        'qty' => 0,
                        'qty_pcs' => 0,
                        'created_by' => $this->user->id
                    ]);
                }

                // =======================================================
                // kurangi stock di gudang asal (stock)
                // tambah stock di gudang tujuan (stock2)
                // posisi_stock1 tambah mutasi_keluar, kurangi saldo_akhir
                // posisi_stock2 tambah mutasi_masuk, tambah saldo_akhir
                // =======================================================

                // PINDAHIN STOCK DARI GUDANG ASAL KE GUDANG TUJUAN
                $stock->decrement('qty', $dmb->qty);
                $stock->decrement('qty_pcs', $dmb->qty_pcs);

                while ($stock->qty_pcs < 0) {
                    $stock->decrement('qty');
                    $stock->increment('qty_pcs', $stock->isi);
                }

                $stock2->increment('qty', $dmb->qty);
                $stock2->increment('qty_pcs', $dmb->qty_pcs);

                // CATAT DI TABEL POSISI STOCK
                $posisi_stock = PosisiStock::where('id_stock', $stock->id)->where('tanggal', Carbon::today()->toDateString())->first();
                if (!$posisi_stock) {
                    $posisi_stock = PosisiStock::create([
                        'id_stock' => $stock->id,
                        // 'tanggal' => $mutasi_barang->tanggal_mutasi,
                        'tanggal' => Carbon::today()->toDateString(),
                        'harga' => $stock->dbp,
                        'saldo_awal_qty' => $stock->qty,
                        'saldo_awal_pcs' => $stock->qty_pcs,
                        'saldo_akhir_qty' => $stock->qty,
                        'saldo_akhir_pcs' => $stock->qty_pcs,
                    ]);
                }
                $posisi_stock->increment('mutasi_keluar_qty', $dmb->qty);
                $posisi_stock->increment('mutasi_keluar_pcs', $dmb->qty_pcs);
                $posisi_stock->decrement('saldo_akhir_qty', $dmb->qty);
                $posisi_stock->decrement('saldo_akhir_pcs', $dmb->qty_pcs);
                while ($posisi_stock->saldo_akhir_pcs < 0) {
                    $posisi_stock->decrement('saldo_akhir_qty');
                    $posisi_stock->increment('saldo_akhir_pcs', $stock->isi);
                }

                $posisi_stock2 = PosisiStock::where('id_stock', $stock2->id)->where('tanggal', Carbon::today()->toDateString())->first();
                if (!$posisi_stock2) {
                    $posisi_stock2 = PosisiStock::create([
                        'id_stock' => $stock2->id,
                        // 'tanggal' => $mutasi_barang->tanggal_mutasi,
                        'tanggal' => Carbon::today()->toDateString(),
                        'harga' => $stock->dbp,
                        'saldo_awal_qty' => 0,
                        'saldo_awal_pcs' => 0,
                        'saldo_akhir_qty' => 0,
                        'saldo_akhir_pcs' => 0,
                    ]);
                }

                $posisi_stock2->increment('mutasi_masuk_qty', $dmb->qty);
                $posisi_stock2->increment('mutasi_masuk_pcs', $dmb->qty_pcs);
                $posisi_stock2->increment('saldo_akhir_qty', $dmb->qty);
                $posisi_stock2->increment('saldo_akhir_pcs', $dmb->qty_pcs);
            }
            // ===================================================================

            $mutasi_barang->is_approved = 1;
            $mutasi_barang->save();

            $dataLog = [
                'action' => 'Approve Mutasi Barang',
                'description' => 'No Mutasi:' . $mutasi_barang->id . ' Mutasi dari ' . $mutasi_barang->dari_gudang->nama_gudang . ' ke ' . $mutasi_barang->ke_gudang->nama_gudang,
                'user_id' => $this->user->id
            ];

            $this->log($dataLog);
            DB::commit();
            return response()->json([
                'message' => 'Data Mutasi barang telah disetujui.'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Data Mutasi barang gagal disetujui.'
            ], 400);
        }
    }

    public function pending($id)
    {
        // REV : yang boleh approve mutasi adalah kepala_gudang (salesman canvass) penerima dan accounting (?)
        if (!$this->user->can('Approve Mutasi Barang')) {
            return $this->Unauthorized();
        }

        $mutasi_barang = MutasiBarang::find($id);

        if (!$mutasi_barang) {
            return $this->dataNotFound('Mutasi Barang');
        }

        if ($mutasi_barang->is_approved == 1) {
            return response()->json([
                'message' => 'Data Mutasi Barang telah disetujui.'
            ], 422);
        }

        $detail_mutasi_barang = DetailMutasiBarang::where('id_mutasi_barang', $id)->get();

        if ($detail_mutasi_barang->count() == 0) {
            return response()->json([
                'message' => 'Data Mutasi Barang masih kosong, isi data barang terlebih dahulu.'
            ], 422);
        }

        foreach ($detail_mutasi_barang as $dmb) {
            $stock = Stock::where('id', $dmb->id_stock)->first();
            if ($stock->id_gudang != $mutasi_barang->dari) {
                return response()->json([
                    'message' => 'Stock dan gudang tidak sesuai, refresh browser'
                ], 422);
            }
        }

        //loop untuk cek stock
        $stock_kurang = [];
        foreach ($detail_mutasi_barang as $dmb) {
            $stock = Stock::where('id', $dmb->id_stock)->first();
            $volume = $stock->barang->isi;
            if (!$stock) {
                return response()->json([
                    'message' => 'Stock barang tidak ditemukan!'
                ], 422);
            }

            if (!$this->user->can('Approve Mutasi Stock Kurang')) {
                $volume_stock   = ($stock->qty * $volume) + $stock->qty_pcs;
                $volume_mutasi  = ($dmb->qty * $volume) + $dmb->qty_pcs;
                $stock_akhir    = $volume_stock - $volume_mutasi;

                //jika stock tidak cukup batalkan
                if ($stock_akhir < 0) {
                    $stock_kurang[] = $stock->barang->kode_barang;
                }
            }
        }

        if (count($stock_kurang) > 0) {
            return response()->json([
                'message' => 'Stock barang di gudang tidak cukup ' . implode(",", $stock_kurang) . ' cek stock barang!'
            ], 422);
        }

        DB::beginTransaction();
        try {
            if ($mutasi_barang->no_mutasi == '') {
                $latest = MutasiBarang::withTrashed()->where('is_approved', 1)
                    ->where('no_mutasi', '!=', '')
                    ->orderBy('no_mutasi', 'desc')
                    ->first();
                $no_mutasi = (int) $latest->no_mutasi + 1;
                $mutasi_barang->no_mutasi = $no_mutasi;
            } else {
                $no_mutasi = $mutasi_barang->no_mutasi;
            }
            $mutasi_barang->is_approved = 1;
            $mutasi_barang->status = 'approved';
            $mutasi_barang->save();

            MutasiHeader::create([
                'no_mutasi' => $no_mutasi
            ]);

            // ======================== pengurangan stock gudang asal ========================
            $detail_mutasi_barang = DetailMutasiBarang::where('id_mutasi_barang', $id)->get();
            $logStock = [];
            foreach ($detail_mutasi_barang as $dmb) {
                //cari id stock (gudang x barang) pertama / gudang asal
                $stock = Stock::where('id', $dmb->id_stock)->first();

                $stock->decrement('qty', $dmb->qty);
                $stock->decrement('qty_pcs', $dmb->qty_pcs);

                while ($stock->qty_pcs < 0) {
                    $stock->decrement('qty');
                    $stock->increment('qty_pcs', $stock->isi);
                }

                // CATAT DI TABEL POSISI STOCK
                $posisi_stock = PosisiStock::where('id_stock', $stock->id)->where('tanggal', Carbon::today()->toDateString())->first();
                if (!$posisi_stock) {
                    $posisi_stock = PosisiStock::create([
                        'id_stock' => $stock->id,
                        // 'tanggal' => $mutasi_barang->tanggal_mutasi,
                        'tanggal' => Carbon::today()->toDateString(),
                        'harga' => $stock->dbp,
                        'saldo_awal_qty' => $stock->qty,
                        'saldo_awal_pcs' => $stock->qty_pcs,
                        'saldo_akhir_qty' => $stock->qty,
                        'saldo_akhir_pcs' => $stock->qty_pcs,
                    ]);
                }
                $posisi_stock->increment('mutasi_keluar_qty', $dmb->qty);
                $posisi_stock->increment('mutasi_keluar_pcs', $dmb->qty_pcs);
                $posisi_stock->decrement('saldo_akhir_qty', $dmb->qty);
                $posisi_stock->decrement('saldo_akhir_pcs', $dmb->qty_pcs);
                while ($posisi_stock->saldo_akhir_pcs < 0) {
                    $posisi_stock->decrement('saldo_akhir_qty');
                    $posisi_stock->increment('saldo_akhir_pcs', $stock->isi);
                }

                $logStock[] = [
                    'tanggal'       => $mutasi_barang->tanggal_mutasi,
                    'id_barang'     => $stock->id_barang,
                    'id_gudang'     => $stock->id_gudang,
                    'id_user'       => $this->user->id,
                    'id_referensi'  => $dmb->id,
                    'referensi'     => 'mutasi keluar',
                    'no_referensi'  => $id,
                    'qty_pcs'       => ($dmb->qty * $stock->isi) + $dmb->qty_pcs,
                    'status'        => 'approved',
                    'created_at'    => Carbon::now(),
                    'updated_at'    => Carbon::now()
                ];
            }
            // ===================================================================

            $dataLog = [
                'action' => 'Approve Mutasi Barang',
                'description' => 'No Mutasi:' . $mutasi_barang->id . ' Mutasi dari ' . $mutasi_barang->dari_gudang->nama_gudang . ' ke ' . $mutasi_barang->ke_gudang->nama_gudang,
                'user_id' => $this->user->id
            ];

            $this->log($dataLog);
            $this->createLogStock($logStock);
            DB::commit();
            return response()->json([
                'message' => 'Data Mutasi barang telah disetujui.'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            $errorCode = $e->errorInfo[1];
            if ($errorCode == 1062) {
                return response()->json([
                    'message' => 'Mohon menunggu, mutasi lain dalam proses approve'
                ], 400);
            }

            return response()->json([
                'message' => 'Data Mutasi barang gagal disetujui.'
            ], 400);
        }
    }

    public function deliver($id, Request $request)
    {
        try {
            $mutasi_barang = MutasiBarang::find($id);
            $delivered_at = $request->delivery_at;

            if ($mutasi_barang) {
                if ($mutasi_barang->status != 'approved') {
                    return response()->json([
                        // 'message' => 'Anda anda hanya bisa mengirimkan barang yang telah di-loading!'
                        'message' => 'Anda anda hanya bisa mengirimkan barang yang telah disetujui!'
                    ], 400);
                }

                DB::beginTransaction();
                $mutasi_barang->status = 'on the way';
                $mutasi_barang->tanggal_realisasi = $delivered_at;
                $mutasi_barang->save();

                $detail_mutasi_barang = DetailMutasiBarang::where('id_mutasi_barang', $id)->get();
                $logStock = [];
                foreach ($detail_mutasi_barang as $dmb) {
                    $stock = Stock::where('id', $dmb->id_stock)->first();
                    $logStock[] = [
                        'tanggal'       => $mutasi_barang->tanggal_mutasi,
                        'id_barang'     => $stock->id_barang,
                        'id_gudang'     => $stock->id_gudang,
                        'id_user'       => $this->user->id,
                        'id_referensi'  => $dmb->id,
                        'referensi'     => 'mutasi keluar',
                        'no_referensi'  => $id,
                        'qty_pcs'       => ($dmb->qty * $stock->isi) + $dmb->qty_pcs,
                        'status'        => 'on the way',
                        'created_at'    => Carbon::now(),
                        'updated_at'    => Carbon::now()
                    ];
                }

                $dataLog = [
                    'action' => 'Deliver Mutasi Barang',
                    'description' => 'No Mutasi:' . $mutasi_barang->id . ' Mutasi dari ' . $mutasi_barang->dari_gudang->nama_gudang . ' ke ' . $mutasi_barang->ke_gudang->nama_gudang,
                    'user_id' => $this->user->id
                ];

                $this->log($dataLog);
                $this->createLogStock($logStock);

                DB::commit();
                return response()->json([
                    'message' => 'Barang telah terkirim',
                    'delivered_at' => $mutasi_barang->delivered_at
                ], 201);
            }

            return response()->json([
                'message' => 'Data Mutasi Barang tidak ditemukan!'
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function receive($id, Request $request)
    {
        // REV : yang boleh approve mutasi adalah kepala_gudang (salesman canvass) penerima dan accounting (?)
        if (!$this->user->can('Approve Mutasi Barang')) {
            return $this->Unauthorized();
        }

        $mutasi_barang = MutasiBarang::find($id);
        $delivered_at = $request->delivery_at;

        if (!$mutasi_barang) {
            return $this->dataNotFound('Mutasi Barang');
        }

        if ($mutasi_barang->status == 'received') {
            return response()->json([
                'message' => 'Data Mutasi Barang telah diterima.'
            ], 422);
        }

        if ($this->user->can('Approve Mutasi Barang Gudang Baik') ||
            $this->user->can('Approve Mutasi Barang Gudang Canvass')) {
            // jika gudang penerima = gudang baik, get id_user (kepala_gudang), jika tidak sama dgn yg login, show error message
            $gudang_penerima = Gudang::find($mutasi_barang->ke);
            if ($gudang_penerima->jenis == 'baik') {
                $kepala_gudang = KepalaGudang::where('id_gudang', $mutasi_barang->ke)->first();
                if (!$kepala_gudang) {
                    return response()->json([
                        'message' => 'Gudang ' . $gudang_penerima->nama_gudang . ' belum ada Kepala Gudangnya!'
                    ], 422);
                }
                if ($kepala_gudang->id != $this->user->id) {
                    return response()->json([
                        'message' => 'Anda bukan kepala gudang ' . $gudang_penerima->nama_gudang . ', anda tidak berhak menyetujui Mutasi Barang ini'
                    ], 422);
                }
            }

            if ($gudang_penerima->jenis == 'canvass') {
                $canvass = Canvass::where('id_gudang_canvass', $mutasi_barang->ke)->first();
                if (!$canvass) {
                    return $this->dataNotFound('Data Canvass');
                }

                if ($this->user->can('Approve Mutasi Barang Gudang Canvass')) {
                    $id_user = $canvass->tim->salesman->user_id;
                    if (!$id_user) {
                        return $this->dataNotFound('Data Salesman Canvass');
                    }
                }

                if ($this->user->id != $id_user) {
                    return response()->json([
                        'message' => 'Anda tidak berhak untuk menyetujui Mutasi Barang ini!'
                    ], 422);
                }
            }
        }

        $detail_mutasi_barang = DetailMutasiBarang::where('id_mutasi_barang', $id)->get();

        if ($detail_mutasi_barang->count() == 0) {
            return response()->json([
                'message' => 'Data Mutasi Barang masih kosong, isi data barang terlebih dahulu.'
            ], 422);
        }

        foreach ($detail_mutasi_barang as $dmb) {
            $stock = Stock::where('id', $dmb->id_stock)->first();
            if ($stock->id_gudang != $mutasi_barang->dari) {
                return response()->json([
                    'message' => 'Stock dan gudang tidak sesuai, refresh browser'
                ], 422);
            }

            $stock2 = Stock::where('id_gudang', $mutasi_barang->ke)->where('id_barang', $dmb->id_barang)->first();
            //jika tidak ketemu buat id stock baru
            if ($stock2 === null) {
                $stock2 = Stock::create([
                    'id_gudang' => $mutasi_barang->ke,
                    'id_barang' => $stock->id_barang,
                    'qty' => 0,
                    'qty_pcs' => 0,
                    'created_by' => $this->user->id
                ]);
            }

            $posisi_stock2 = PosisiStock::where('id_stock', $stock2->id)->where('tanggal', Carbon::today()->toDateString())->first();
            if ($posisi_stock2 === null) {
                PosisiStock::create([
                    'id_stock' => $stock2->id,
                    // 'tanggal' => $mutasi_barang->tanggal_mutasi,
                    'tanggal' => Carbon::today()->toDateString(),
                    'harga' => $stock->dbp,
                    'saldo_awal_qty' => 0,
                    'saldo_awal_pcs' => 0,
                    'saldo_akhir_qty' => 0,
                    'saldo_akhir_pcs' => 0,
                ]);
            }
        }

        try {
            DB::beginTransaction();

            $logStock = [];
            // ======================== penambahan stock ========================
            foreach ($detail_mutasi_barang as $dmb) {
                //cari id stock (gudang x barang) kedua / gudang penerima
                $stock2 = Stock::where('id_gudang', $mutasi_barang->ke)->where('id_barang', $dmb->id_barang)->first();
                if ($stock2 === null) {
                    throw new ModelNotFoundException('Stock tidak ditemukan, hubungi IT Support');
                }

                $stock2->increment('qty', $dmb->qty);
                $stock2->increment('qty_pcs', $dmb->qty_pcs);

                $posisi_stock2 = PosisiStock::where('id_stock', $stock2->id)->where('tanggal', Carbon::today()->toDateString())->first();
                if ($posisi_stock2 === null) {
                    throw new ModelNotFoundException('Posisi stock tidak ditemukan, hubungi IT Support');
                }

                $posisi_stock2->increment('mutasi_masuk_qty', $dmb->qty);
                $posisi_stock2->increment('mutasi_masuk_pcs', $dmb->qty_pcs);
                $posisi_stock2->increment('saldo_akhir_qty', $dmb->qty);
                $posisi_stock2->increment('saldo_akhir_pcs', $dmb->qty_pcs);

                // jika delivered_at < today(), maka decrement qty_mutasi_pending pada stock awal
                if ($delivered_at < Carbon::today()->toDateString()) {
                    $stock_awal = StockAwal::where('id_stock', $dmb->id_stock)->where('tanggal', Carbon::today()->toDateString())->first();
                    if ($stock_awal) {
                        $stock_awal->decrement('qty_mutasi_pending', $dmb->qty);
                        $stock_awal->decrement('qty_pcs_mutasi_pending', $dmb->qty_pcs);

                        while ($stock_awal->qty_pcs_mutasi_pending < 0) {
                            $stock_awal->decrement('qty_mutasi_pending');
                            $stock_awal->increment('qty_pcs_mutasi_pending', $stock->isi);
                        }
                    }
                }
                $logStock[] = [
                    'tanggal'       => $delivered_at,
                    'id_barang'     => $dmb->id_barang,
                    'id_gudang'     => $mutasi_barang->dari,
                    'id_user'       => $this->user->id,
                    'id_referensi'  => $dmb->id,
                    'referensi'     => 'mutasi keluar',
                    'no_referensi'  => $id,
                    'qty_pcs'       => ($dmb->qty * $stock2->isi) + $dmb->qty_pcs,
                    'status'        => 'received',
                    'created_at'    => Carbon::now(),
                    'updated_at'    => Carbon::now()
                ];

                $logStock[] = [
                    'tanggal'       => $delivered_at,
                    'id_barang'     => $stock2->id_barang,
                    'id_gudang'     => $stock2->id_gudang,
                    'id_user'       => $this->user->id,
                    'id_referensi'  => $dmb->id,
                    'referensi'     => 'mutasi masuk',
                    'no_referensi'  => $id,
                    'qty_pcs'       => ($dmb->qty * $stock2->isi) + $dmb->qty_pcs,
                    'status'        => 'received',
                    'created_at'    => Carbon::now(),
                    'updated_at'    => Carbon::now()
                ];
            }
            // ===================================================================

            $mutasi_barang->status = 'received';
            $mutasi_barang->tanggal_realisasi = $delivered_at;
            $mutasi_barang->save();

            $dataLog = [
                'action' => 'Terima Mutasi Barang',
                'description' => 'No Mutasi:' . $mutasi_barang->id . ' Mutasi dari ' . $mutasi_barang->dari_gudang->nama_gudang . ' ke ' . $mutasi_barang->ke_gudang->nama_gudang,
                'user_id' => $this->user->id
            ];

            $this->log($dataLog);
            $this->createLogStock($logStock);
            DB::commit();
            return response()->json([
                'message' => 'Data Mutasi barang telah diterima.'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Data Mutasi barang gagal diterima.'
            ], 400);
        }
    }

    public function cancel_approval($id)
    {
        // REV : yang boleh approve mutasi adalah kepala_gudang (salesman canvass) penerima dan accounting (?)
        if (!$this->user->can('Cancel Mutasi Barang')) {
            return $this->Unauthorized();
        }

        $mutasi_barang = MutasiBarang::find($id);

        if (!$mutasi_barang) {
            return $this->dataNotFound('Mutasi Barang');
        }

        if ($mutasi_barang->is_approved == 0) {
            return response()->json([
                'message' => 'Persetujuan Mutasi Barang telah dibatalkan.'
            ], 422);
        }

        if ($this->user->can('Approve Mutasi Barang Gudang Baik')
            || $this->user->can('Approve Mutasi Barang Gudang Canvass')) {
            // jika gudang penerima = gudang baik, get id_user (kepala_gudang), jika tidak sama dgn yg login, show error message
            $gudang_penerima = Gudang::find($mutasi_barang->ke);
            if ($gudang_penerima->jenis == 'baik') {
                $kepala_gudang = KepalaGudang::where('id_gudang', $mutasi_barang->ke)->first();
                if (!$kepala_gudang) {
                    return response()->json([
                        'message' => 'Gudang ' . $gudang_penerima->nama_gudang . ' belum ada Kepala Gudangnya!'
                    ], 422);
                }
                if ($kepala_gudang->id != $this->user->id) {
                    return response()->json([
                        'message' => 'Anda bukan kepala gudang ' . $gudang_penerima->nama_gudang . ', anda tidak berhak menyetujui Mutasi Barang ini'
                    ], 422);
                }
            }

            // jika gudang penerima = canvass
            //   jika user login = salesman
            //      get id_salesman u/ canvas tsb
            //          jika tidak sama dgn user yg login, show error
            //   jika user login = driver
            //      get id_driver u/ canvas tsb
            //          jika tidak sama dgn user yg login show error

            if ($gudang_penerima->jenis == 'canvass') {
                $canvass = Canvass::where('id_gudang_canvass', $mutasi_barang->ke)->first();
                if (!$canvass) {
                    return $this->dataNotFound('Data Canvass');
                }

                if ($this->user->can('Approve Mutasi Barang Gudang Canvass')) {
                    $id_user = $canvass->tim->salesman->user_id;
                    if (!$id_user) {
                        return $this->dataNotFound('Data Salesman Canvass');
                    }
                }

                if ($this->user->id != $id_user) {
                    return response()->json([
                        'message' => 'Anda tidak berhak untuk menyetujui Mutasi Barang ini!'
                    ], 422);
                }
            }
        }

        try {
            DB::beginTransaction();
            $detail_mutasi_barang = DetailMutasiBarang::where('id_mutasi_barang', $id)->get();

            // ======================== perpindahan stock ========================
            foreach ($detail_mutasi_barang as $dmb) {
                //cari id stock (gudang x barang) pertama / gudang asal
                $stock = Stock::where('id', $dmb->id_stock)->first();
                //cari id stock (gudang x barang) kedua / gudang penerima
                $stock2 = Stock::where('id_gudang', $mutasi_barang->ke)->where('id_barang', $dmb->id_barang)->first();
                //jika tidak ketemu buat id stock baru
                if (!$stock2) {
                    $stock2 = Stock::create([
                        'id_gudang' => $mutasi_barang->ke,
                        'id_barang' => $stock->id_barang,
                        'qty' => 0,
                        'qty_pcs' => 0,
                        'created_by' => $this->user->id
                    ]);
                }

                // KEMBALIKAN STOCKNYA DARI GUDANG 2 KE GUDANG 1
                // =======================================================
                // tambah stock di gudang asal (stock)
                // kurangi stock di gudang tujuan (stock2)
                // posisi_stock1 kurangi mutasi_keluar, tambah saldo_akhir
                // posisi_stock2 kurangi mutasi_masuk, kurangi saldo_akhir
                // =======================================================

                // KEMBALIKAN STOCK DARI TUJUAN ASAL KE GUDANG ASAL
                $stock->increment('qty', $dmb->qty);
                $stock->increment('qty_pcs', $dmb->qty_pcs);
                $stock2->decrement('qty', $dmb->qty);
                $stock2->decrement('qty_pcs', $dmb->qty_pcs);
                while ($stock2->qty_pcs < 0) {
                    $stock2->decrement('qty');
                    $stock2->increment('qty_pcs', $stock->isi);
                }

                // CATAT DI TABEL POSISI STOCK
                $posisi_stock = PosisiStock::where('id_stock', $stock->id)->where('tanggal', Carbon::today()->toDateString())->first();
                if (!$posisi_stock) {
                    $posisi_stock = PosisiStock::create([
                        'id_stock' => $stock->id,
                        // 'tanggal' => $mutasi_barang->tanggal_mutasi,
                        'tanggal' => Carbon::today()->toDateString(),
                        'harga' => $stock->dbp,
                        'saldo_awal_qty' => $stock->qty,
                        'saldo_awal_pcs' => $stock->qty_pcs,
                        'saldo_akhir_qty' => $stock->qty,
                        'saldo_akhir_pcs' => $stock->qty_pcs,
                    ]);
                }
                $posisi_stock->decrement('mutasi_keluar_qty', $dmb->qty);
                $posisi_stock->decrement('mutasi_keluar_pcs', $dmb->qty_pcs);
                while ($posisi_stock->mutasi_keluar_pcs < 0) {
                    $posisi_stock->decrement('mutasi_keluar_qty');
                    $posisi_stock->increment('mutasi_keluar_pcs', $stock->isi);
                }
                $posisi_stock->increment('saldo_akhir_qty', $dmb->qty);
                $posisi_stock->increment('saldo_akhir_pcs', $dmb->qty_pcs);

                $posisi_stock2 = PosisiStock::where('id_stock', $stock2->id)->where('tanggal', Carbon::today()->toDateString())->first();
                if (!$posisi_stock2) {
                    $posisi_stock2 = PosisiStock::create([
                        'id_stock' => $stock2->id,
                        // 'tanggal' => $mutasi_barang->tanggal_mutasi,
                        'tanggal' => Carbon::today()->toDateString(),
                        'harga' => $stock2->dbp,
                        'saldo_awal_qty' => 0,
                        'saldo_awal_pcs' => 0,
                        'saldo_akhir_qty' => $stock2->qty,
                        'saldo_akhir_pcs' => $stock2->qty_pcs,
                    ]);
                }
                $posisi_stock2->decrement('mutasi_masuk_qty', $dmb->qty);
                $posisi_stock2->decrement('mutasi_masuk_pcs', $dmb->qty_pcs);
                $posisi_stock2->decrement('saldo_akhir_qty', $dmb->qty);
                $posisi_stock2->decrement('saldo_akhir_pcs', $dmb->qty_pcs);

                while ($posisi_stock2->mutasi_masuk_pcs < 0) {
                    $posisi_stock2->decrement('mutasi_masuk_qty');
                    $posisi_stock2->increment('mutasi_masuk_pcs', $stock->isi);
                }

                while ($posisi_stock2->saldo_akhir_pcs < 0) {
                    $posisi_stock2->decrement('saldo_akhir_qty');
                    $posisi_stock2->increment('saldo_akhir_pcs', $stock->isi);
                }
            }
            // ===================================================================

            $mutasi_barang->is_approved = 0;
            $mutasi_barang->save();

            $dataLog = [
                'action' => 'Cancel Mutasi Barang',
                'description' => 'No Mutasi: ' . $mutasi_barang->id . ' Mutasi dari ' . $mutasi_barang->dari_gudang->nama_gudang . ' ke ' . $mutasi_barang->ke_gudang->nama_gudang,
                'user_id' => $this->user->id
            ];

            $this->log($dataLog);

            DB::commit();
            return response()->json([
                'message' => 'Persetujuan Mutasi barang telah dibatalkan.'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal membatalkan persetujuan mutasi barang.'
            ], 400);
        }
    }

    public function cancel_mutasi($id)
    {
        if (!$this->user->can('Cancel Mutasi Barang')) {
            return $this->Unauthorized('Mutasi Barang');
        }
        $mutasi_barang = MutasiBarang::find($id);

        if (!$mutasi_barang) {
            return $this->dataNotFound('Mutasi Barang');
        }

        if ($mutasi_barang->is_approved == 0) {
            return response()->json([
                'message' => 'Persetujuan Mutasi Barang telah dibatalkan.'
            ], 422);
        }

        if ($this->user->can('Approve Mutasi Barang Gudang Baik') || $this->user->can('Approve Mutasi Barang Gudang Canvass')) {
            // jika gudang penerima = gudang baik, get id_user (kepala_gudang), jika tidak sama dgn yg login, show error message
            $gudang_penerima = Gudang::find($mutasi_barang->ke);
            if ($gudang_penerima->jenis == 'baik') {
                $kepala_gudang = KepalaGudang::where('id_gudang', $mutasi_barang->ke)->first();
                if (!$kepala_gudang) {
                    return response()->json([
                        'message' => 'Gudang ' . $gudang_penerima->nama_gudang . ' belum ada Kepala Gudangnya!'
                    ], 422);
                }
                if ($kepala_gudang->id != $this->user->id) {
                    return response()->json([
                        'message' => 'Anda bukan kepala gudang ' . $gudang_penerima->nama_gudang . ', anda tidak berhak menyetujui Mutasi Barang ini'
                    ], 422);
                }
            }

            // jika gudang penerima = canvass
            //   jika user login = salesman
            //      get id_salesman u/ canvas tsb
            //          jika tidak sama dgn user yg login, show error
            //   jika user login = driver
            //      get id_driver u/ canvas tsb
            //          jika tidak sama dgn user yg login show error

            if ($gudang_penerima->jenis == 'canvass') {
                $canvass = Canvass::where('id_gudang_canvass', $mutasi_barang->ke)->first();
                if (!$canvass) {
                    return response()->json([
                        'message' => 'Data Canvass tidak ditemukan!'
                    ], 404);
                }

                if ($this->user->can('Approve Mutasi Barang Gudang Canvass')) {
                    $id_user = $canvass->tim->salesman->user_id;
                    if (!$id_user) {
                        return response()->json([
                            'message' => 'Data Salesman Canvass tidak ditemukan!'
                        ], 404);
                    }
                }

                if ($this->user->id != $id_user) {
                    return response()->json([
                        'message' => 'Anda tidak berhak untuk menyetujui Mutasi Barang ini!'
                    ], 422);
                }
            }
        }

        try {
            DB::beginTransaction();
            if ($mutasi_barang->is_approved == 1) {
                MutasiHeader::where('no_mutasi', $mutasi_barang->no_mutasi)->delete();
                $detail_mutasi_barang = DetailMutasiBarang::where('id_mutasi_barang', $id)->get();

                // ======================== perpindahan stock ========================
                foreach ($detail_mutasi_barang as $dmb) {

                    //cari id stock (gudang x barang) pertama / gudang asal
                    $stock = Stock::where('id', $dmb->id_stock)->first();

                    $stock->increment('qty', $dmb->qty);
                    $stock->increment('qty_pcs', $dmb->qty_pcs);

                    // CATAT DI TABEL POSISI STOCK
                    $posisi_stock = PosisiStock::where('id_stock', $stock->id)->where('tanggal', Carbon::today()->toDateString())->first();
                    if ($posisi_stock === null) {
                        throw new ModelNotFoundException('Posisi stock tidak ditemukan, hubungi IT Support');
                    }

                    $posisi_stock->decrement('mutasi_keluar_qty', $dmb->qty);
                    $posisi_stock->decrement('mutasi_keluar_pcs', $dmb->qty_pcs);
                    while ($posisi_stock->mutasi_keluar_pcs < 0) {
                        $posisi_stock->decrement('mutasi_keluar_qty');
                        $posisi_stock->increment('mutasi_keluar_pcs', $stock->isi);
                    }
                    $posisi_stock->increment('saldo_akhir_qty', $dmb->qty);
                    $posisi_stock->increment('saldo_akhir_pcs', $dmb->qty_pcs);


                    // jika barang sudah diterima maka kurangi stocknya di gudang penerima
                    if ($mutasi_barang->status == 'received') {
                        //cari id stock (gudang x barang) kedua / gudang penerima
                        $stock2 = Stock::where('id_gudang', $mutasi_barang->ke)->where('id_barang', $dmb->id_barang)->first();

                        //jika tidak ketemu buat id stock baru
                        if (!$stock2) {
                            $stock2 = Stock::create([
                                'id_gudang' => $mutasi_barang->ke,
                                'id_barang' => $stock->id_barang,
                                'qty' => 0,
                                'qty_pcs' => 0,
                                'created_by' => $this->user->id
                            ]);
                        }

                        $stock2->decrement('qty', $dmb->qty);
                        $stock2->decrement('qty_pcs', $dmb->qty_pcs);
                        while ($stock2->qty_pcs < 0) {
                            $stock2->decrement('qty');
                            $stock2->increment('qty_pcs', $stock->isi);
                        }

                        $posisi_stock2 = PosisiStock::where('id_stock', $stock2->id)->where('tanggal', Carbon::today()->toDateString())->first();
                        if ($posisi_stock2 === null) {
                            throw new ModelNotFoundException('Posisi stock tidak ditemukan, hubungi IT Support');
                        }

                        $posisi_stock2->decrement('mutasi_masuk_qty', $dmb->qty);
                        $posisi_stock2->decrement('mutasi_masuk_pcs', $dmb->qty_pcs);
                        $posisi_stock2->decrement('saldo_akhir_qty', $dmb->qty);
                        $posisi_stock2->decrement('saldo_akhir_pcs', $dmb->qty_pcs);

                        while ($posisi_stock2->mutasi_masuk_pcs < 0) {
                            $posisi_stock2->decrement('mutasi_masuk_qty');
                            $posisi_stock2->increment('mutasi_masuk_pcs', $stock->isi);
                        }

                        while ($posisi_stock2->saldo_akhir_pcs < 0) {
                            $posisi_stock2->decrement('saldo_akhir_qty');
                            $posisi_stock2->increment('saldo_akhir_pcs', $stock->isi);
                        }
                    }

                    // jika delivered_at < today(), maka decrement qty_mutasi_pending pada stock awal
                    if ($mutasi_barang->tanggal_mutasi < Carbon::today()->toDateString()) {
                        $stock_awal = StockAwal::where('id_stock', $dmb->id_stock)->where('tanggal', Carbon::today()->toDateString())->first();
                        if ($stock_awal) {
                            $stock_awal->increment('qty_mutasi_pending', $dmb->qty);
                            $stock_awal->increment('qty_pcs_mutasi_pending', $dmb->qty_pcs);
                        }
                    }
                }
                // ===================================================================
            }

            if ($mutasi_barang->status == "approved") {
                $this->deleteLogStock([
                    ['referensi', 'mutasi keluar'],
                    ['no_referensi', $mutasi_barang->id],
                    ['status', 'approved']
                ]);
            }

            if ($mutasi_barang->status == "on the way") {
                $this->deleteLogStock([
                    ['referensi', 'mutasi keluar'],
                    ['no_referensi', $mutasi_barang->id],
                    ['status', 'approved']
                ]);

                $this->deleteLogStock([
                    ['referensi', 'mutasi keluar'],
                    ['no_referensi', $mutasi_barang->id],
                    ['status', 'on the way']
                ]);
            }

            if ($mutasi_barang->status == "received") {
                $this->deleteLogStock([
                    ['referensi', 'mutasi keluar'],
                    ['no_referensi', $mutasi_barang->id],
                    ['status', 'approved']
                ]);

                $this->deleteLogStock([
                    ['referensi', 'mutasi keluar'],
                    ['no_referensi', $mutasi_barang->id],
                    ['status', 'on the way']
                ]);

                $this->deleteLogStock([
                    ['referensi', 'mutasi keluar'],
                    ['no_referensi', $mutasi_barang->id],
                    ['status', 'received']
                ]);

                $this->deleteLogStock([
                    ['referensi', 'mutasi masuk'],
                    ['no_referensi', $mutasi_barang->id],
                    ['status', 'received']
                ]);
            }

            $mutasi_barang->is_approved = 0;
            $mutasi_barang->status = 'waiting';
            $mutasi_barang->save();

            $dataLog = [
                'action' => 'Cancel Mutasi Barang',
                'description' => 'No Mutasi: ' . $mutasi_barang->id . ' Mutasi dari ' . $mutasi_barang->dari_gudang->nama_gudang . ' ke ' . $mutasi_barang->ke_gudang->nama_gudang,
                'user_id' => $this->user->id
            ];

            $this->log($dataLog);

            DB::commit();
            return response()->json([
                'message' => 'Persetujuan Mutasi barang telah dibatalkan.'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal membatalkan persetujuan mutasi barang.'
            ], 400);
        }
    }

    public function list_barang($id)
    {

        $gudang = Gudang::findOrFail($id);

        if ($gudang) {
            $list_barang = Stock::where('id_gudang', $id)->get();
            $list_barang = $list_barang->sortBy(function ($list_barang) {
                return $list_barang->kode_barang;
            });

            return StockResource::collection($list_barang);
        }
        return response()->json([
            'message' => 'Data Gudang tidak ditemukan!'
        ], 404);
    }

    public function list_gudang($id)
    {

        $gudang = Gudang::findOrFail($id);

        if ($gudang) {
            $id_perusahaan = $gudang->depo->perusahaan->id;
            if ($gudang->jenis == 'baik' || $gudang->jenis == 'canvass') {
                $list_gudang = Gudang::whereNotIn('id', [$id])
                    ->whereHas('depo', function ($q) use ($id_perusahaan) {
                        $q->whereHas('perusahaan', function ($q) use ($id_perusahaan) {
                            $q->where('id', $id_perusahaan);
                        });
                    })->orderBy('jenis', 'asc')->get();
                return GudangResource::collection($list_gudang);
            } else {
                $list_gudang = Gudang::whereIn('jenis', ['baik', 'bad_stock'])->whereNotIn('id', [$id])
                ->whereHas('depo', function ($q) use ($id_perusahaan) {
                    $q->whereHas('perusahaan', function ($q) use ($id_perusahaan) {
                        $q->where('id', $id_perusahaan);
                    });
                })->get();
                return GudangResource::collection($list_gudang);
            }
        }
        return response()->json([
            'message' => 'Data Gudang tidak ditemukan!'
        ], 404);
    }

    // list gudang dari berdasarkan hak akses gudang dan depo
    public function list_gudang_dari()
    {
        $id_user = $this->user->id;
        $gudang = [];
        if ($this->user->can('Gudang By Depo')) :
            $depo       = Helper::depoIDByUser($id_user);
            $id_gudang  = Helper::gudangByDepo($depo)->pluck('id');
            $gudang     = Gudang::whereIn('id', $id_gudang)->get();
        else :
            $id_gudang  = Helper::gudangByUser($id_user);
            $gudang     = Gudang::whereIn('id', $id_gudang)->get();
        endif;

        return GudangResource::collection($gudang);
    }
}
