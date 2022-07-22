<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;
use Illuminate\Support\Facades\DB;
use App\Models\StockOpname;
use App\Http\Resources\StockOpname as StockOpnameResource;
use App\Models\DetailStockOpname;
use App\Models\Gudang;
use App\Helpers\Helper;
use Carbon\Carbon as Carbon;

class StockOpnameController extends Controller
{
    protected $jwt;

    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
    }

    public function index(Request $request)
    {
        if ($this->user->can('Menu Stock Opname')) :
            $stock_opname = DB::table('stock_opname')
                ->join('gudang', 'stock_opname.id_gudang', '=', 'gudang.id')
                ->select('stock_opname.id', 'stock_opname.tanggal_so', 'stock_opname.id_gudang', 'gudang.nama_gudang', 'stock_opname.is_approved', 'stock_opname.keterangan')
                ->whereNull('stock_opname.deleted_at');

            // Filter Gudang
            if ($request->has('id_gudang')) {
                if (!($request->id_gudang == 'all' || $request->id_gudang == '' || $request->id_gudang == null)) {
                    $stock_opname->where('stock_opname.id_gudang', '=', $request->id_gudang);
                }
            }

            // Filter Date
            if ($request->has('date')) {
                $stock_opname->where('stock_opname.tanggal_so', '=', $request->date);
            } elseif ($request->has(['start_date', 'end_date'])) {
                $stock_opname->whereBetween('stock_opname.tanggal_so', [$request->start_date, $request->end_date]);
            }

            // Filter Status
            if ($request->has('status') && $request->status != '' && $request->status != 'all') {
                if ($request->status == 'waiting') {
                    $stock_opname->where('stock_opname.is_approved', '=', 0);
                } elseif ($request->status == 'approved') {
                    $stock_opname->where('stock_opname.is_approved', '=', 0);
                }
            }

            // Filter Keyword (no_mutasi)
            if ($request->has('keyword') && $request->keyword != '') {
                $keyword = $request->keyword;
                $stock_opname = $stock_opname->where('stock_opname.id', 'like', '%' . $keyword . '%');
            }

            // filter data sesuai depo user
            $id_depo = Helper::depoIDByUser($this->user->id);
            $stock_opname = $stock_opname->whereIn('gudang.id_depo', $id_depo);

            $stock_opname = $stock_opname->orderBy('stock_opname.id', 'desc');

            $perPage = $request->has('per_page') ? $perPage = $request->per_page : $perPage = 5;
            $stock_opname = $perPage == 'all' ? $stock_opname->get() : $stock_opname->paginate((int)$perPage);

            if ($stock_opname) {
                return StockOpnameResource::collection($stock_opname);
            }
            return response()->json([
                'message' => 'Data tidak ditemukan!'
            ], 404);
        else :
            return $this->Unauthorized();
        endif;
    }

    public function store(Request $request)
    {
        if ($this->user->can('Tambah Stock Opname')) :
            try {
                DB::beginTransaction();
                $this->validate($request, [
                    'tanggal_so' => 'required|date',
                    'id_gudang' => 'required|numeric|min:0|max:9999999999'
                ]);
                
                $depo = Gudang::find($request->id_gudang)->depo;

                // simpan stock opname
                $id = DB::table('stock_opname')->insertGetId(
                    [
                        'tanggal_so' => $request->tanggal_so,
                        'id_gudang' => $request->id_gudang,
                        'keterangan' => $request->keterangan,
                        'is_approved' => 0,
                        'created_by' => $this->user->id,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now()
                    ]
                );

                //hitung stock
                $stock = DB::table('stock AS a')
                    ->join('barang AS b', 'a.id_barang', '=', 'b.id')
                    ->join('barang_depo AS c', 'b.id', '=', 'c.barang_id')
                    ->where('a.id_gudang', '=', $request->id_gudang)
                    ->where('b.status', '=', 1)
                    ->whereNull('a.deleted_at')
                    ->where('c.depo_id', $depo->id)
                    ->select(
                        'a.id',
                        'a.id_barang',
                        'b.kode_barang',
                        'b.nama_barang',
                        'b.isi',
                        'a.qty',
                        'a.qty_pcs'
                    )
                    ->orderBy('b.kode_barang')->get();
              
                $input = [];
                foreach ($stock as $st) {
                    $stock_fisik = $this->stock_fisik($st->id_barang, $request->id_gudang);
                    $input[] = [
                        'id_stock_opname' => $id,
                        'id_stock' => $st->id,
                        'qty' => floor($stock_fisik / $st->isi),
                        'qty_pcs' => $stock_fisik % $st->isi,
                        'qty_fisik' => 0,
                        'qty_pcs_fisik' => 0,
                    ];
                }

                if (count($input) > 0)
                    DetailStockOpname::insert($input);

                DB::commit();
                return response()->json([
                    'message' => 'Data berhasil disimpan.',
                    'data' =>  $this->index($request)

                ], 201);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['error' => $e->getMessage()], 400);
            }
        else :
            return $this->Unauthorized();
        endif;
    }

    public function update(Request $request, $id)
    {
        if ($this->user->can('Update Stock Opname')) :
            $stock_opname = StockOpname::find($id);

            if ($stock_opname->is_approved == 1) {
                return response()->json([
                    'message' => 'Data tidak boleh diubah karena telah disetujui.'
                ], 422);
            }

            $this->validate($request, [
                'tanggal_so' => 'required|date',
                'id_gudang' => 'required|numeric|min:0|max:9999999999'
            ]);

            if ($stock_opname) {
                $stock_opname = DB::table('stock_opname')
                    ->where('id', $id)
                    ->update([
                        'tanggal_so' => $request->tanggal_so,
                        'id_gudang' => $request->id_gudang,
                        'keterangan' => $request->keterangan,
                        'is_approved' => 0,
                        'updated_by' => $this->user->id
                    ]);

                return response()->json([
                    'message' => 'Data telah berhasil diubah.',
                    'data' => $stock_opname
                ], 201);
            }

            return response()->json([
                'message' => 'Data tidak ditemukan.'
            ], 404);
        else :
            return $this->Unauthorized();
        endif;
    }

    public function cancel_approval($id)
    {
        if ($this->user->can('Update Stock Opname')) :
            $stock_opname = StockOpname::find($id);

            if ($stock_opname) {
                if ($stock_opname->is_approved == 0) {
                    return response()->json([
                        'message' => 'Status data belum disetujui'
                    ], 422);
                }

                $stock_opname->is_approved = 0;
                $stock_opname->update();

                return response()->json([
                    'message' => 'Data telah batal disetujui.',
                    'data' => $stock_opname
                ], 201);
            }

            return response()->json([
                'message' => 'Data tidak ditemukan.'
            ], 404);
        else :
            return $this->Unauthorized();
        endif;
    }

    public function destroy($id, Request $request)
    {
        if ($this->user->can('Delete Stock Opname')) :
            $stock_opname = StockOpname::find($id);
            if (!$stock_opname) {
                return response()->json([
                    'message' => 'Data tidak ditemukan!'
                ], 400);
            }

            if ($stock_opname->is_approved == 1) {
                return response()->json([
                    'message' => 'Data tidak boleh dihapus karena telah disetujui.'
                ], 422);
            }

            DB::beginTransaction();
            try {

                DB::table('detail_stock_opname')->where('id_stock_opname', '=', $id)->delete();

                if ($stock_opname) {
                    $data = ['deleted_by' => $this->user->id];
                    $stock_opname->update($data);
                    $stock_opname->delete();

                    DB::commit();
                    return response()->json([
                        'message' => 'Data berhasil dihapus.',
                        'data' => $this->index($request)
                    ], 200);
                }

                return response()->json([
                    'message' => 'Data tidak ditemukan!'
                ], 404);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['error' => $e->getMessage()], 400);
            }
        else :
            return $this->Unauthorized();
        endif;
    }
}
