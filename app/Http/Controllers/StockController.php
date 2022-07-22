<?php

namespace App\Http\Controllers;

use App\Models\Penjualan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;
use App\Models\Stock;
use App\Models\Gudang;
use App\Models\Barang;
use App\Models\ViewPenjualan;
use App\Models\ViewAdjusmentBarang;
use App\Models\ViewMutasiMasuk;
use App\Models\ViewMutasiKeluar;
use App\Models\ViewPenerimaanBarang;
use App\Http\Resources\Stock as StockResource;
use App\Http\Resources\StockDetail as StockDetailResource;
use App\Http\Resources\Gudang as GudangResource;
use App\Http\Resources\Barang as BarangResource;
use DB;
use App\Helpers\Helper;

class StockController extends Controller
{
    protected $jwt;

    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
    }

    public function index(Request $request)
    {
        if (!$this->user->can('Menu Stock Barang')) {
            return $this->Unauthorized();
        }

        $depo_user = Helper::depoIDByUser($this->user->id);
        $list_stock_gudang = Gudang::orderByRaw( "FIELD(jenis, 'baik','canvass','motor','bad_stock','tukar_guling','banded')" )->whereIn('id_depo',$depo_user);

        if($this->user->hasRole('Salesman')){
            $list_stock_gudang = Gudang::where('id', $this->user->salesman->tim->depo->id_gudang);
        }

        $perPage = $request->has('per_page') ? $perPage = $request->per_page : $perPage = 'all';

        $list_stock_gudang = $perPage == 'all' ? $list_stock_gudang->get() : $list_stock_gudang->paginate((int)$perPage);

        if ($list_stock_gudang) {
            return GudangResource::collection($list_stock_gudang);
        }

        return $this->dataNotFound('stock barang');
    }

    public function list_barang($id)
    {
        $gudang = Gudang::findOrFail($id);

        if (!$gudang) {
            return $this->dataNotFound('gudang');
        }

        $list_barang_terdaftar = Stock::where('id_gudang', $id)->pluck('id_barang')->toArray();
        // hanya barang yg tidak ada di gudang
        $barang = Barang::whereNotIn('id', $list_barang_terdaftar)->orderBy('kode_barang')->get();
        return BarangResource::collection($barang);
    }

    public function store(Request $request)
    {
        if(!$this->user->can('Tambah Stock Barang')) {
            return $this->Unauthorized();
        }

        $this->validate($request, [
            'id_gudang' => 'required|numeric|min:0|max:9999999999',
            'id_barang' => 'required|numeric|min:0|max:9999999999',
            'qty'       => 'required|min:0|numeric|max:9999999999',
            'qty_pcs'   => 'required|numeric|min:0|max:9999999999'
        ]);

        $input  = $request->all();
        $input['created_by'] = $this->user->id;

        $id_barang  = $request->id_barang;
        $id_gudang  = $request->id_gudang;
        $stock      = Stock::where('id_barang', $id_barang)->where('id_gudang', $id_gudang)->first();
        if ($stock) {
            return response()->json(['message' => 'Stock sudah ada']);
        }

        try {
            Stock::create($input);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

        return $this->storeTrue('stock barang');
    }

    public function show($id, Request $request)
    {   
        if(!$this->user->can('Edit Stock Barang')) {
            return $this->Unauthorized();
        }

        $stock = Stock::where('id_gudang', $id);
        $perPage = $request->has('per_page') ? $perPage = $request->per_page : $perPage = 'all';
        $stock = $perPage == 'all' ? $stock->get() : $stock->paginate((int)$perPage);

        $stock = $stock->sortBy(function($stock){
            return $stock->kode_barang;
        });

        if ($stock) {
            return StockResource::collection($stock);
        }

        return $this->dataNotFound('stock barang');
    }

    public function show_detail($id)
    {
        if(!$this->user->can('Edit Stock Barang')) {
            return $this->Unauthorized();
        }

        $stock = Stock::find($id);
        if ($stock) {
            return new StockDetailResource($stock);
        }
        return $this->dataNotFound('stock promo');
    }

    public function update(Request $request, $id)
    {
        if(!$this->user->can('Update Stock Barang')) {
            return $this->Unauthorized();
        }

        $this->validate($request, [
            'id_gudang' => 'required|numeric|min:0|max:9999999999',
            'id_barang' => 'required|numeric|min:0|max:9999999999',
            'qty' => 'required|numeric',
            'qty_pcs' => 'required|numeric',
        ]);

        $stock = Stock::find($id);
        if (!$stock) {
            return $this->dataNotFound('stock barang');
        }

        $input = $request->all();
        $input['updated_by'] = $this->user->id;
        $stock->update($input);
        return response()->json([
            'message' => 'Data Stock telah berhasil diubah.',
            'data' => $stock
        ], 201);
    }

    public function destroy($id)
    {
        if(!$this->user->can('Hapus Stock Barang')) {
            return $this->Unauthorized();
        }

        $stock = Stock::find($id);
        if($stock) {
            $data = ['deleted_by' => $this->user->id];
            $stock->update($data);
            return $stock->delete() ? $this->destroyTrue('stock barang') : $this->destroyFalse('stock barang');
        }

        return $this->dataNotFound('stock barang');
    }

    public function restore($id)
    {
        if(!$this->user->can('Hapus Stock Barang')) {
            return $this->Unauthorized();
        }

        $stock = Stock::withTrashed()->find($id);
        if (!$stock) {
            return $this->dataNotFound('stock barang');
        }

        $data = ['deleted_by' => null];
        $stock->update($data);
        $stock->restore();

        return response()->json([
            'message' => 'Data Stock berhasil dikembalikan.'
        ], 200);
    }

    public function riwayatBarang(Request $request)
    {
        if (!$this->user->can('Menu Riwayat Barang')) {
            return $this->Unauthorized();
        }
        
        $this->validate($request, [
            'id_barang'     => 'required|exists:barang,id',
            'id_gudang'     => 'required|exists:gudang,id',
            'tanggal_awal'  => 'nullable|date',
            'tanggal_akhir' => 'nullable|date'
        ]);

        $id_barang      = $request->id_barang;
        $id_gudang      = $request->id_gudang;
        $tanggal_awal   = ($request->has('tanggal_awal') && $request->tanggal_awal <> '') ?  $request->tanggal_awal : date('Y-m-d');
        $tanggal_akhir  = ($request->has('tanggal_akhir') && $request->tanggal_akhir <> '') ?  $request->tanggal_akhir : date('Y-m-d');
        $penerimaan_barang = ViewPenerimaanBarang::where('id_barang', $id_barang)
                    ->where('id_gudang', $id_gudang)
                    ->where('id_barang', $id_barang)
                    ->where('status', 1)
                    ->whereBetween('tanggal', [$tanggal_awal, $tanggal_akhir]);
        $adjustment_barang = ViewAdjusmentBarang::where('id_barang', $id_barang)
                    ->where('id_gudang', $id_gudang)
                    ->where('id_barang', $id_barang)
                    ->where('status', 'approved')
                    ->whereBetween('tanggal', [$tanggal_awal, $tanggal_akhir])
                    ->get();
        $mutasi_masuk = ViewMutasiMasuk::where('id_barang', $id_barang)
                    ->where('id_gudang', $id_gudang)
                    ->where('id_barang', $id_barang)
                    ->where('status', 'received')
                    ->whereBetween('tanggal', [$tanggal_awal, $tanggal_akhir]);

        $mutasi_keluar = ViewMutasiKeluar::where('id_barang', $id_barang)
                    ->where('id_gudang', $id_gudang)
                    ->where('id_barang', $id_barang)
                    ->whereIn('status', ['approved', 'received'])
                    ->whereBetween('tanggal', [$tanggal_awal, $tanggal_akhir]);

        $penjualan = ViewPenjualan::where('id_barang', $id_barang)
                    ->where('id_gudang', $id_gudang)
                    ->where('id_barang', $id_barang)
                    ->whereIn('status', ['delivered', 'loaded', 'approved'])
                    ->whereBetween('tanggal', [$tanggal_awal, $tanggal_akhir])
                    ->union($penerimaan_barang)
                    ->union($mutasi_masuk)
                    ->union($mutasi_keluar);
                    // ->union($adjustment_barang);

        $data   = $penjualan->get();
        $data   = $data->concat($adjustment_barang);
        // dd($data->toArray());
        $stock  = Stock::where('id_gudang', $id_gudang)->where('id_barang', $id_barang)->first();
        foreach ($data as $key => $dt) {
            switch ($dt->tipe) {
                case 'penjualan':
                    $data[$key]->in     = "0/0";
                    $data[$key]->out    = $dt->qty."/".$dt->qty_pcs;
                    $data[$key]->note   = $dt->kode." ".$dt->status;
                    break;
                case 'adjustment':
                    if ($dt->qty > 0 || $dt->qty_pcs > 0) {
                        $data[$key]->in     = $dt->qty."/".$dt->qty_pcs;
                        $data[$key]->out    = "0/0";
                    } else {
                        $data[$key]->in     = "0/0";
                        $data[$key]->out    = abs($dt->qty)."/".abs($dt->qty_pcs);
                    }
                    $data[$key]->no     = $dt->kode;
                    $data[$key]->note   = $dt->kode;
                    break;
                case 'mutasi masuk':
                    $data[$key]->in     = $dt->qty."/".$dt->qty_pcs;
                    $data[$key]->out    = "0/0";
                    $data[$key]->note   = $dt->dari_gudang." Ke ".$dt->ke_gudang;
                    break;
                case 'mutasi keluar':
                    $data[$key]->in     = "0/0";
                    $data[$key]->out    = $dt->qty."/".$dt->qty_pcs;
                    $data[$key]->note   = $dt->dari_gudang." Ke ".$dt->ke_gudang;
                    break;
                default:
                    $data[$key]->in     = $dt->qty."/".$dt->qty_pcs;
                    $data[$key]->out    = "0/0";
                    $data[$key]->note   = $dt->kode;
                    break;
            }
        }

        $perTipe = $data->groupBy('tipe');
        return response()->json(['data' => $perTipe, 'stock' => $stock], 200);
    }

    public function sisa_stock($id)
    {
        $stock = Stock::find($id);
        if (!$stock) {
            return $this->dataNotFound('stock barang');
        }

        $last_week      = Carbon::now()->subWeek()->format('Y-m-d');

        $id_barang      = $stock->id_barang;
        $id_gudang      = $stock->id_gudang;
        $isi            = $stock->barang->isi;
        $stock_waiting  = DB::table('v_penjualan')
                            ->select(DB::raw('SUM(qty) as qty, SUM(qty_pcs) as pcs'))
                            ->where('id_gudang', '=', $id_gudang)
                            ->where('id_barang', '=', $id_barang)
                            ->where('tanggal', '>=', $last_week)
                            ->where('status', '=', 'waiting')
                            ->get();

        $total_buffer   = $stock->qty * $isi + $stock->qty_pcs;
        $qty_buffer     = floor($total_buffer/$isi);
        $pcs_buffer     = $total_buffer % $isi;

        $qty_waiting    = 0;
        $pcs_waiting    = 0;
        $total_waiting  = 0;

        if (count($stock_waiting) > 0) {
            $total_waiting  = $stock_waiting[0]->qty * $isi + $stock_waiting[0]->pcs;
            $qty_waiting    = floor($total_waiting/$isi);
            $pcs_waiting    = $total_waiting % $isi;
        }

        $total_sisa = $total_buffer - $total_waiting;
        $qty_sisa   = $total_sisa > 0 ? floor($total_sisa/$isi) : 0;
        $pcs_sisa   = $total_sisa % $isi;

        return response()->json([
            'qty_buffer'    => $qty_buffer,
            'pcs_buffer'    => $pcs_buffer,
            'qty_waiting'   => $qty_waiting,
            'pcs_waiting'   => $pcs_waiting,
            'qty_sisa'      => $qty_sisa,
            'pcs_sisa'      => $pcs_sisa
        ]);
    }
}
