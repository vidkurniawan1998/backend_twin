<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\Models\Barang;
use App\Http\Resources\ListHargaBarang as ListHargaBarangResource;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Style\Paper;
use Tymon\JWTAuth\JWTAuth;
use App\Models\DetailPenjualan;
use App\Models\Penjualan;
use App\Models\Stock;
use App\Models\HargaBarang;
use App\Models\Promo;
use App\Http\Resources\DetailPenjualan as DetailPenjualanResource;
use App\Http\Resources\HargaBarang as HargaBarangResource;
use App\Http\Resources\Stock as StockResource;
use App\Models\Gudang;
use App\Http\Resources\PenjualanWithDetail as PenjualanWithDetailResource;
use DB;
use Illuminate\Support\Arr;

class DetailPenjualanController extends Controller
{
    protected $jwt;

    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
    }

    public function index()
    {
        if ($this->user->can('Menu Detail Penjualan')):
            $list_detail_penjualan = DetailPenjualan::all();
            if ($list_detail_penjualan) {
                return DetailPenjualanResource::collection($list_detail_penjualan);
            }
            return response()->json([
                'message' => 'Data Detail Penjualan tidak ditemukan!'
            ], 404);
        else:
            return $this->Unauthorized();
        endif;
    }

    public function detail($id)
    {
        $list_detail_penjualan = DetailPenjualan::relationData()->where('id_penjualan', $id)->get()->sortBy('kode_barang');
        if ($list_detail_penjualan) {
            return DetailPenjualanResource::collection($list_detail_penjualan);
        }
        return response()->json([
            'message' => 'Data Detail Penjualan tidak ditemukan!'
        ], 404);
    }

    public function list_barang(Request $request){
        // IMPORTANT!!
        // jika AUTH != 'salesman' wajib menyertakan parameter id_penjualan;
        // REV JAN 2020 : UNTUK SALESMAN CANVASS set id gudang canvassnya
        $id_gudang = 0;
        $id_stock_sudah = [];
        $id_barang = [];
        if ($this->user->can('Penjualan Salesman')){
            if($this->user->salesman->tim->tipe == 'canvass'){
                $id_gudang = $this->user->salesman->tim->canvass->id_gudang_canvass;
            }
            else{
                $id_gudang = $this->user->salesman->tim->depo->id_gudang;
            }

            $id_principal   = $this->user->salesman->id_principal;
            if ($id_principal != null) {
                $id_barang = Barang::with(['segmen', 'segmen.brand'])
                ->whereHas('segmen', function ($q) use ($id_principal) {
                    $q->whereHas('brand', function ($q) use ($id_principal) {
                        $q->where('id_principal', $id_principal);
                    });
                })
                ->select('id')->get();
            }

            $id_stock_sudah = [];
        }
        else{
            $penjualan = Penjualan::find($request->id_penjualan);
            // $id_gudang = $penjualan->salesman->tim->depo->id_gudang;

            if($penjualan->salesman->tim->tipe == 'canvass'){
                $id_gudang = $penjualan->salesman->tim->canvass->id_gudang_canvass;
            }
            else{
                $id_gudang = $penjualan->salesman->tim->depo->id_gudang;
            }

            $id_stock_sudah = DetailPenjualan::where('id_penjualan', $request->id_penjualan)->pluck('id_stock')->toArray();
        }

        $gudang = Gudang::find($id_gudang);
        if ($gudang) {
            $message = "#Barang salesman: ".$this->user->name." Gudang: ".$gudang->nama_gudang;
            $this->sendMessageBot(basename(__FILE__), __FUNCTION__, $message);
        }

        if (count($id_barang) > 0) {
            $list_barang = Stock::where('id_gudang', $id_gudang)
                ->whereIn('id_barang', $id_barang->pluck('id'))
                ->get()
                ->except($id_stock_sudah)
                ->sortBy('kode_barang');
        } else {
            $list_barang = Stock::where('id_gudang', $id_gudang)
                ->get()
                ->except($id_stock_sudah)
                ->sortBy('kode_barang');
        }

        return StockResource::collection($list_barang);
    }

    public function list_barang_edit(Request $request) // $request->id_penjualan harus isi
    {
        $penjualan = Penjualan::find($request->id_penjualan);

        $list_barang = Stock::where('id_gudang', $penjualan->id_gudang)->get()->sortBy('kode_barang');

        return StockResource::collection($list_barang);
    }

    public function list_barang_by_gudang($id_gudang)
    {
        $list_barang = Stock::where('id_gudang', $id_gudang)->whereHas('barang', function ($q) {
            return $q->where('status', 1);
        })->get()->sortBy('kode_barang');
        return StockResource::collection($list_barang);
    }

    public function harga_barang($id_stock)
    {
        $stock = Stock::find($id_stock);

        $rbp    = HargaBarang::where('id_barang', $stock->id_barang)->where('tipe_harga', 'rbp')->latest()->first();
        $hcobp  = HargaBarang::where('id_barang', $stock->id_barang)->where('tipe_harga', 'hcobp')->latest()->first();
        $wbp    = HargaBarang::where('id_barang', $stock->id_barang)->where('tipe_harga', 'wbp')->latest()->first();
        $tipe_harga = [];

        if($rbp) {
            $tipe_harga = Arr::prepend($tipe_harga, $rbp->id);
        }

        if($hcobp) {
            $tipe_harga = Arr::prepend($tipe_harga, $hcobp->id);
        }

        if($wbp) {
            $tipe_harga = Arr::prepend($tipe_harga, $wbp->id);
        }

        $list_harga = HargaBarang::whereIn('id', $tipe_harga)->get();

        return HargaBarangResource::collection($list_harga);
    }

    public function store(Request $request)
    {
        if ($this->user->can('Tambah Detail Penjualan')):
            $penjualan = Penjualan::find($request->id_penjualan);

            if(!$penjualan) {
                return response()->json([
                    'message' => 'Data Penjualan tidak ditemukan.'
                ], 422);
            }

            if($penjualan->status != 'waiting') {
                return response()->json([
                    'message' => 'Anda tidak boleh menambahkan data barang pada penjualan yang telah disetujui.'
                ], 422);
            }

            $this->validate($request, [
                'id_stock'  => 'required|numeric|min:0|max:9999999999',
                'qty'       => 'numeric|min:0|max:9999999999',
                'qty_pcs'   => 'numeric|min:0|max:9999999999',
                'id_promo'  => 'min:0|max:9999999999'
            ]);

            //get id barang pd tabel stock
            $stock          = Stock::find($request->id_stock);
            $extra          = 0;
            $disc_persen    = 0;
            $disc_rupiah    = 0;
            $kode_promo     = '';
            $id_mitra       = $penjualan->id_mitra;
            $barang_mitra   = $stock->barang->id_mitra == null ? 0 : $stock->barang->id_mitra;
            $kelipatan_order= $stock->barang->kelipatan_order;
            $order_pcs      = $request->qty_pcs;

            if ($id_mitra <> $barang_mitra) {
                return response()->json(['message' => 'Barang dan toko mitra tidak sesuai'], 422);
            }

            if (($order_pcs % $kelipatan_order) > 0) {
                return response()->json(['message' => 'Volume order pcs tidak sesuai, gunakan kelipatan: '.$kelipatan_order], 422);
            }

            if ($penjualan->id_gudang <> null) {
                if ($penjualan->id_gudang <> $stock->id_gudang) {
                    return response()->json(['message' => 'Stock dan gudang tidak sesuai, hubungi IT Support'], 422);
                }
            }

            if($request->id_promo) {
                $promo = Promo::find($request->id_promo);
                if($promo->status != 'active') {
                    return response()->json(['message' => 'Promo sudah non aktif, refresh data promo'], 422);
                }

                $depo   = $promo->depo;
                if ($depo->count() > 0) {
                    $depo = $depo->where('id', $penjualan->id_depo)->count();
                    if ($depo == 0) {
                        return response()->json(['message' => 'Promo tidak berlaku untuk depo penjualan, refresh data promo'], 422);
                    }
                }

                $barang = $promo->promo_barang;
                if ($barang->count() > 0) {
                    $barang = $barang->where('id', $stock->barang->id);
                    if ($barang->count() == 0) {
                        return response()->json(['message' => 'Item tidak berlaku untuk promo ini, silahkan coba promo lain'], 422);
                    } else {
                        $extra_barang   = $barang->first()->pivot;
                        $volume         = $extra_barang['volume'];
                        $bonus_pcs      = $extra_barang['bonus_pcs'];
                        if ($volume > 0) {
                            $order  = ($request->qty * $stock->isi) + $request->qty_pcs;
                            if ($order < $volume) {
                                return response()->json(['message' => 'Qty pembelian tidak cukup untuk mendapatkan promo'], 422);
                            }
                            $extra = $bonus_pcs * floor(($order/$volume));
                        } else {
                            $volume         = $promo->volume_extra;
                            $all_barang     = $promo->promo_barang->pluck('id');
                            $stocks         = Stock::select('id')->where('id_gudang', $penjualan->id_gudang)->whereIn('id_barang', $all_barang)->get();
                            $all_id_stock   = $stocks->pluck('id');
                            $detail_penjualan = DetailPenjualan::where('id_penjualan', $penjualan->id)->whereIn('id_stock', $all_id_stock)->get();
                            $order = $detail_penjualan->sum('sum_pcs') + ($request->qty * $stock->barang->isi) + $request->qty_pcs;
                            if ($order < $volume) {
                                return response()->json(['message' => 'Qty pembelian tidak cukup untuk mendapatkan promo'], 422);
                            }
                            
                            if($volume > 0) {
                                $extra = $promo->pcs_extra * floor(($order/$volume));
                            }
                        }
                    }
                }

                $toko   = $promo->promo_toko;
                if ($toko->count() > 0) {
                    $toko = $toko->where('id', $penjualan->id_toko)->count();
                    if ($toko == 0) {
                        return response()->json(['message' => 'Toko tidak ikut serta pada promo ini, silahkan coba promo lain'], 422);
                    }
                }

                $disc_persen = $promo->disc_persen;
                $disc_rupiah = $promo->disc_rupiah;
                $kode_promo  = $promo->nama_promo;
            }

            if ($disc_persen != 100) {
                $detail_penjualan = DetailPenjualan::where('id_penjualan', $request->id_penjualan)->where('id_stock', $request->id_stock)->first();
                if ($detail_penjualan) {
                    return response()->json(['message' => 'Barang duplikat'], 400);
                }
            }

            $input = $request->all();
            $input['id_promo']   = $request->id_promo == '' ? 0:$request->id_promo;
            $input['order_qty']  = $request->qty;
            $input['order_pcs']  = $request->qty_pcs;
            $input['created_by'] = $this->user->id;
            $input['disc_persen']= $disc_persen;
            $input['disc_rupiah']= $disc_rupiah;
            $input['kode_promo'] = $kode_promo;

            if($request->qty == 0 && $request->qty_pcs == 0){
                return response()->json([
                    'message' => 'Jumlah barang tidak boleh kosong.'
                ], 400);
            }

            $tipe_harga = $penjualan->tipe_harga;
            $harga      = HargaBarang::where('id_barang', $stock->id_barang)->where('tipe_harga', $tipe_harga)->whereDate('created_at', '<=', $penjualan->tanggal)->latest()->first();
            $harga_dbp  = HargaBarang::where('id_barang', $stock->id_barang)->where('tipe_harga', 'dbp')->whereDate('created_at', '<=', $penjualan->tanggal)->latest()->first();

            if (!$harga) {
                return response()->json([
                   'message' => 'Harga barang belum diatur'
                ], 400);
            }

            if (!$harga_dbp) {
                return response()->json([
                    'message' => 'Harga barang belum diatur'
                ], 400);
            }

            $input['id_harga']      = $harga->id;
            $input['harga_jual']    = $harga->harga;
            $input['harga_dbp']     = $harga_dbp->harga;

            try {
                $detail_penjualan = DetailPenjualan::create($input);
                if ($request->id_promo) {
                    $promo  = Promo::find($request->id_promo);
                    if ($promo->id_barang) {
                        $harga = HargaBarang::where('id_barang', $promo->id_barang)->where('tipe_harga', $tipe_harga)->whereDate('created_at', '<=', $penjualan->tanggal)->latest()->first();
                        $input = [];
                        $stock = Stock::where('id_barang', $promo->id_barang)->where('id_gudang', $penjualan->id_gudang)->first();
                        $input['id_penjualan'] = $request->id_penjualan;
                        $input['id_stock']  = $stock->id;
                        $input['qty']       = 0;
                        $input['qty_pcs']   = $extra;
                        $input['order_qty'] = 0;
                        $input['order_pcs'] = $extra;
                        $input['id_harga']  = $harga->id;
                        $input['created_by']= $this->user->id;
                        DetailPenjualan::create($input);
                    }
                }
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 400);
            }

            $list_detail_penjualan = DetailPenjualan::relationData()->where('id_penjualan', $request->id_penjualan)->get()->sortBy('kode_barang');
            $new_list_detail_penjualan = DetailPenjualanResource::collection($list_detail_penjualan);

            return response()->json([
                'message' => 'Data Detail Penjualan berhasil disimpan.',
                'penjualan' => [
                    'sku' => $penjualan->sku,
                    'total' => $penjualan->total,
                    'disc_total' => $penjualan->disc_final,
                    'net_total' => $penjualan->net_total,
                    'total_qty' => $penjualan->total_qty,
                    'total_pcs' => $penjualan->total_pcs,
                    'ppn' => $penjualan->ppn,
                    'grand_total' => $penjualan->grand_total,
                ],
                'data' => $new_list_detail_penjualan
            ], 201);
        else:
            return $this->Unauthorized();
        endif;
    }

    public function show($id)
    {
        $detail_penjualan = DetailPenjualan::relationData()->find($id);

        if ($detail_penjualan) {
            return new DetailPenjualanResource($detail_penjualan);
        }
        return response()->json([
            'message' => 'Data Detail Penjualan tidak ditemukan!'
        ], 404);
    }

    public function update(Request $request, $id)
    {
        if ($this->user->can('Update Detail Penjualan')):
            $detail_penjualan = DetailPenjualan::find($id);
            if(!$detail_penjualan) {
                return response()->json([
                    'message' => 'Data detail penjualan tidak ditemukan!'
                ], 404);
            }

            $penjualan = Penjualan::find($detail_penjualan->id_penjualan);
            if($penjualan->status != 'waiting') {
                return response()->json([
                    'message' => 'Anda tidak boleh mengubah data barang pada Penjualan yang telah disetujui.'
                ], 422);
            }

            $this->validate($request, [
                'id_penjualan' => 'required|numeric|min:0|max:9999999999',
                'id_stock'  => 'required|numeric|min:0|max:9999999999',
                'qty'       => 'numeric|min:0|max:9999999999',
                'qty_pcs'   => 'numeric|min:0|max:9999999999',
                'id_promo'  => 'min:0|max:9999999999',
                'id_harga'  => 'nullable|exists:harga_barang,id'
            ]);

            if ($this->user->can('Approve Penjualan') && !$this->user->hasRole('Salesman Canvass')) {
                if ($request->id_harga == '' || $request->id_harga == null) {
                    return response()->json(['message' => 'Harga wajib isi'], 422);
                }
            }

            $input = $request->all();
            if($this->user->hasRole('Salesman') || $this->user->hasRole('Salesman Canvass')){
                $input['order_qty'] = $request->qty;
                $input['order_pcs'] = $request->qty_pcs;
            }

            $input['id_penjualan']  = $penjualan->id;
            $input['updated_by']    = $this->user->id;

            //jika barang diganti, tentukan harga ulang
            $stock = Stock::find($request->id_stock);
            $extra = 0;
            $id_mitra = $penjualan->id_mitra;
            $barang_mitra = $stock->barang->id_mitra == null ? 0 : $stock->barang->id_mitra;
            $kelipatan_order= $stock->barang->kelipatan_order;
            $order_pcs      = $request->qty_pcs;

            if ($id_mitra <> $barang_mitra) {
                return response()->json(['message' => 'Barang dan toko mitra tidak sesuai'], 422);
            }

            if (($order_pcs % $kelipatan_order) > 0) {
                return response()->json(['message' => 'Volume order pcs tidak sesuai, gunakan kelipatan: '.$kelipatan_order], 422);
            }

            if($detail_penjualan->id_stock != $request->id_stock) {
                //get id barang pd tabel stock
                if ($penjualan->id_gudang <> null) {
                    if ($penjualan->id_gudang <> $stock->id_gudang) {
                        return response()->json(['message' => 'Stock dan gudang tidak sesuai, hubungi IT Support'], 422);
                    }
                }
            }

            // hapus barang extra jika promo berubah, qty berubah
            if (($request->id_promo <> $detail_penjualan->id_promo) || ($request->qty <> $detail_penjualan->qty) || ($request->qty_pcs <> $detail_penjualan->qty_pcs)) {
                $promo      = Promo::find($detail_penjualan->id_promo);
                if($promo !== null) {
                    if ($promo->id_barang != 0 && $promo->id_barang != null && $promo->id_barang != '') {
                        $stock_extra = Stock::where('id_barang', $promo->id_barang)->where('id_gudang', $penjualan->id_gudang)->first();
                        if ($stock_extra !== null) {
                            DetailPenjualan::where('id_penjualan', $penjualan->id)->where('id_stock', $stock_extra->id)->delete();
                        }
                    }
                }
            }

            $disc_persen = 0;
            $disc_rupiah = 0;
            $kode_promo  = '';
            if($request->id_promo) {
                $promo = Promo::find($request->id_promo);
                if($promo->status != 'active') {
                    return response()->json(['message' => 'Promo sudah non aktif, refresh data promo'], 422);
                }

                $depo   = $promo->depo;
                if ($depo->count() > 0) {
                    $depo = $depo->where('id', $penjualan->id_depo)->count();
                    if ($depo == 0) {
                        return response()->json(['message' => 'Promo tidak berlaku untuk depo penjualan, refresh data promo'], 422);
                    }
                }

                $barang = $promo->promo_barang;
                if ($barang->count() > 0) {
                    $barang = $barang->where('id', $stock->barang->id);
                    if ($barang->count() == 0) {
                        return response()->json(['message' => 'Item tidak berlaku untuk promo ini, silahkan coba promo lain'], 422);
                    } else {
                        $extra_barang   = $barang->first()->pivot;
                        $volume         = $extra_barang['volume'];
                        $bonus_pcs      = $extra_barang['bonus_pcs'];
                        if ($volume > 0) {
                            $order  = ($request->qty * $stock->isi) + $request->qty_pcs;
                            if ($order < $volume) {
                                return response()->json(['message' => 'Qty pembelian tidak cukup untuk mendapatkan promo'], 422);
                            }
                            $extra = $bonus_pcs * floor(($order/$volume));
                        } else {
                            $volume         = $promo->volume_extra;
                            $all_barang     = $promo->promo_barang->pluck('id');
                            $stocks         = Stock::select('id')->where('id_gudang', $penjualan->id_gudang)->whereIn('id_barang', $all_barang)->get();
                            $all_id_stock   = $stocks->pluck('id');
                            $dt_penjualan   = DetailPenjualan::where('id_penjualan', $penjualan->id)->whereIn('id_stock', $all_id_stock)->where('id', '!=', $id)->get();
                            $order = $dt_penjualan->sum('sum_pcs') + ($request->qty * $stock->barang->isi) + $request->qty_pcs;
                            if ($order < $volume) {
                                return response()->json(['message' => 'Qty pembelian tidak cukup untuk mendapatkan promo'], 422);
                            }
                            
                            if($volume > 0) {
                                $extra = $promo->pcs_extra * floor(($order/$volume));
                            }
                        }
                    }
                }

                $toko   = $promo->promo_toko;
                if ($toko->count() > 0) {
                    $toko = $toko->where('id', $penjualan->id_toko)->count();
                    if ($toko == 0) {
                        return response()->json(['message' => 'Toko tidak ikut serta pada promo ini, silahkan coba promo lain'], 422);
                    }
                }

                $disc_persen = $promo->disc_persen;
                $disc_rupiah = $promo->disc_rupiah;
                $kode_promo  = $promo->nama_promo;
            }


            // menentukan tipe harga
            $id_harga   = $request->has('id_harga') && $request->id_harga <> '' ? $request->id_harga:null;
            $tipe_harga = $penjualan->tipe_harga;
            $harga_dbp  = HargaBarang::where('id_barang', $stock->id_barang)->where('tipe_harga', 'dbp')->whereDate('created_at', '<=', $penjualan->tanggal)->latest()->first();
            $harga      = null;
            if ($id_harga === null) {
                $harga      = HargaBarang::where('id_barang', $stock->id_barang)->where('tipe_harga', $tipe_harga)->whereDate('created_at', '<=', $penjualan->tanggal)->latest()->first();
                if (!$harga) {
                    return response()->json(['message' => 'Harga tidak ditemukan, silahkan hubungi IT Support'], 422);
                }

                $id_harga = $harga->id;
            } else {
                $harga = HargaBarang::find($id_harga);
                if($harga->id_barang <> $stock->id_barang) {
                    return response()->json(['message' => 'Harga barang tidak sesuai, silahkan hapus item'], 422);
                }
            }

            if (!$harga_dbp) {
                return response()->json([
                    'message' => 'Harga barang belum diatur'
                ], 400);
            }

            // if ($disc_persen != 100) {
            //     $cek_duplikat = DetailPenjualan::where('id', '!=', $id)->where('id_penjualan', $request->id_penjualan)->where('id_stock', $request->id_stock)->first();
            //     if ($cek_duplikat) {
            //         return response()->json(['message' => 'Barang duplikat'], 400);
            //     }
            // }

            $input['id_promo']      = $request->id_promo == '' ? 0:$request->id_promo;
            $input['id_harga']      = $id_harga;
            $input['harga_dbp']     = $harga_dbp->harga;
            $input['harga_jual']    = $harga->harga;
            $input['disc_persen']   = $disc_persen;
            $input['disc_rupiah']   = $disc_rupiah;
            $input['kode_promo']    = $request->id_promo == '' ? '':$kode_promo;

            try {
                $detail_penjualan->update($input); //simpan perubahan
                if ($request->id_promo) {
                    $promo  = Promo::find($request->id_promo);
                    if ($promo->id_barang) {
                        $harga = HargaBarang::where('id_barang', $promo->id_barang)->where('tipe_harga', $tipe_harga)->whereDate('created_at', '<=', $penjualan->tanggal)->latest()->first();
                        $input = [];
                        $stock = Stock::where('id_barang', $promo->id_barang)->where('id_gudang', $penjualan->id_gudang)->first();
                        $input['id_penjualan'] = $request->id_penjualan;
                        $input['id_stock']  = $stock->id;
                        $input['qty']       = 0;
                        $input['qty_pcs']   = $extra;
                        $input['order_qty'] = 0;
                        $input['order_pcs'] = $extra;
                        $input['id_harga']  = $harga->id;
                        $input['created_by']= $this->user->id;
                        DetailPenjualan::create($input);
                    }
                }
            } catch(\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 400);
            }

            $list_detail_penjualan      = DetailPenjualan::relationData()->where('id_penjualan', $request->id_penjualan)->get()->sortBy('kode_barang');
            $new_list_detail_penjualan  = DetailPenjualanResource::collection($list_detail_penjualan);

            return response()->json([
                'message' => 'Data Detail Penjualan telah berhasil diubah.',
                'penjualan' => [
                    'sku'       => $penjualan->sku,
                    'total'     => $penjualan->total,
                    'disc_total'=> $penjualan->disc_final,
                    'net_total' => $penjualan->net_total,
                    'total_qty' => $penjualan->total_qty,
                    'total_pcs' => $penjualan->total_pcs,
                    'ppn'       => $penjualan->ppn,
                    'grand_total' => $penjualan->grand_total,
                ],
                'data' => $new_list_detail_penjualan
            ], 201);
        else:
            return response()->json(['message' => 'Silahkan hapus item jika ingin edit'], 403);
        endif;
    }

    public function destroy($id, Request $request)
    {
        if ($this->user->can('Hapus Detail Penjualan')):
            $detail_penjualan = DetailPenjualan::find($id);

            if(!$detail_penjualan) {
                return response()->json([
                    'message' => 'Data Detail Penjualan tidak ditemukan.'
                ], 422);
            }

            $penjualan = Penjualan::find($detail_penjualan->id_penjualan);

            if($detail_penjualan->penjualan->status != 'waiting') {
                return response()->json([
                    'message' => 'Anda tidak boleh menghapus data barang pada penjualan yang telah disetujui.'
                ], 422);
            }

            if($detail_penjualan) {
                $detail_penjualan->delete();

                $list_detail_penjualan      = DetailPenjualan::relationData()->where('id_penjualan', $detail_penjualan->id_penjualan)->get()->sortBy('kode_barang');
                $new_list_detail_penjualan  = DetailPenjualanResource::collection($list_detail_penjualan);

                return response()->json([
                    'message' => 'Data Detail Penjualan berhasil dihapus.',
                    'data' => $new_list_detail_penjualan,
                    'penjualan' => [
                        'sku' => $penjualan->sku,
                        'total' => $penjualan->total,
                        'disc_total' => $penjualan->disc_final,
                        'net_total' => $penjualan->net_total,
                        'total_qty' => $penjualan->total_qty,
                        'total_pcs' => $penjualan->total_pcs,
                        'ppn' => $penjualan->ppn,
                        'grand_total' => $penjualan->grand_total,
                    ]
                ], 200);
            }

            return response()->json([
                'message' => 'Data Detail Penjualan tidak ditemukan!'
            ], 404);
        else:
            return $this->Unauthorized();
        endif;
    }

    public function generatePDF($id) {

        if ($this->user->can('Download Invoice Penjualan')):
            $res = Penjualan::with([
                'toko', 'salesman.user', 'tim', 'salesman', 'mitra',
                'detail_penjualan' => function($q) {
                        $q->where( function ($q) {
                            $q->where('qty', '!=', 0)->orWhere('qty_pcs', '!=', 0);
                        });
                },
                'detail_penjualan.stock',
                'detail_penjualan.stock.barang',
                'depo'
            ]
            )->find($id);

            $logData = [
                'action' => 'Print Penjualan',
                'description' => 'PO '.$res->id.' Invoice: '.$res->no_invoice,
                'user_id' => $this->user->id
            ];
            $this->log($logData);
            $penjualan = Penjualan::find($id);
            $penjualan->increment('print_count');
            return new PenjualanWithDetailResource($res);
        else:
            return $this->Unauthorized();
        endif;
    }

    public function update_qty(Request $request, $id)
    {
        $this->validate($request, [
            'qty_loading' => 'numeric|min:0|max:9999999999',
            'qty_pcs_loading' => 'numeric|min:0|max:9999999999'
        ]);

        $detail_penjualan = DetailPenjualan::find($id);
        if(!$detail_penjualan) {
            return response()->json([
                'message' => 'Data Detail Pembelian tidak ditemukan.'
            ], 404);
        }

        if ($detail_penjualan->id_promo) {
            return response()->json([
                'message' => 'Penjualan memiliki promo, tolong hubungi Admin'
            ], 400);
        }

        if($this->user->hasRole('Checker Disabled')){
            $detail_penjualan->qty_loading = $request->qty_loading;
            $detail_penjualan->qty_pcs_loading = $request->qty_pcs_loading;
            $detail_penjualan->qty = $request->qty_loading;
            $detail_penjualan->qty_pcs = $request->qty_pcs_loading;
            $detail_penjualan->updated_by = $this->user->id;
            if ($detail_penjualan->save()) {
               return response()->json([
                    'message' => 'Data Berhasil di Update'
                ], 200);
            }
            else
            {
                return response()->json([
                    'message' => 'Update Data Gagal!'
                ], 404);
            }
        }
        else
        {
            return $this->Unauthorized();
        }
    }
    public function update_qty_driver(Request $request, $id)
    {
         $this->validate($request, [
            'qty' => 'numeric|min:0|max:9999999999',
            'qty_pcs' => 'numeric|min:0|max:9999999999'
        ]);

        $detail_penjualan = DetailPenjualan::find($id);

        if(!$detail_penjualan) {
            return response()->json([
                'message' => 'Data Detail Pembeian tidak ditemukan.'
            ], 404);
        }

        if ($detail_penjualan->id_promo) {
            return response()->json([
                'message' => 'Penjualan memiliki promo, tolong hubungi Admin'
            ], 400);
        }

        if($this->user->hasRole('Driver Disabled')){
            $detail_penjualan->qty = $request->qty;
            $detail_penjualan->qty_pcs = $request->qty_pcs;
            $detail_penjualan->updated_by = $this->user->id;
            if ($detail_penjualan->save()) {
               return response()->json([
                    'message' => 'Data Berhasil di Update'
                ], 200);
            }
            else
            {
                return response()->json([
                    'message' => 'Update Data Gagal!'
                ], 404);
            }
        }
        else
        {
            return $this->Unauthorized();
        }
    }

    public function list_harga_barang(Request $request) {
        $id_barang  = Stock::find($request->id_stock)->id_barang ?? null;
        if($id_barang === null) {
            return response()->json([], 200);
        }
        $tipe_harga = $request->tipe_harga;

        $harga      = HargaBarang::where('id_barang', $id_barang)->where('tipe_harga', $tipe_harga)->orderBy('id', 'desc')->get();
        return ListHargaBarangResource::collection($harga);
    }

    public function generateDOC($id)
    {
        $res = Penjualan::with([
                'toko', 'salesman.user', 'salesman', 'tim',
                'detail_penjualan' => function($q) {
                    $q->where( function ($q) {
                        $q->where('qty', '!=', 0)->orWhere('qty_pcs', '!=', 0);
                    });
                },
                'detail_penjualan.stock',
                'detail_penjualan.stock.barang',
                'depo'
            ]
        )->find($id);

        $logData = [
            'action' => 'Download Invoice Penjualan',
            'description' => 'PO '.$res->id.' Invoice: '.$res->no_invoice,
            'user_id' => $this->user->id
        ];

        $this->log($logData);

        $phpWord= new PhpWord();
        $paper  = new Paper();
        $phpWord->setDefaultParagraphStyle([
            'lineHeight' => 1.1,
        ]);
        $paper->setSize('Letter');
        $section= $phpWord->addSection([
            'pageSizeW'     => $paper->getWidth(),
            'pageSizeH'     => $paper->getHeight(),
            'marginLeft'    => 540,
            'marginRight'   => 540,
            'marginTop'     => 360,
            'marginBottom'  => 360
        ]);

        $table = $section->addTable();
        $headerStyle = [
            'name' => 'Times New Roman',
            'size' => 10,
            'bold' => true
        ];

        $subHeaderStyle = [
            'name' => 'Times New Roman',
            'size' => 9
        ];

        $right  = ['align' => 'right'];
        $left   = ['align' => 'left'];
        $center = ['align' => 'center'];

        // HEADER
        $table->addRow();
        $table->addCell(5000)->addText( strtoupper($res->depo->perusahaan->nama_perusahaan), $headerStyle);
        $table->addCell(5000)->addText("KPD YTH\t: ".strtoupper($res->toko->nama_toko), $headerStyle);
        $table->addCell(1400)->addText('INVOICE', $headerStyle, $right);

        // SUBHEADER ALAMAT
        $table->addRow();
        $table->addCell(5000)->addText(strtoupper($res->depo->alamat), $subHeaderStyle);
        $table->addCell(5000)->addText("\t\t ".strtoupper($res->toko->alamat), $subHeaderStyle);
        $table->addCell(1400)->addText($res->no_invoice, [
            'name' => 'Times New Roman',
            'size' => 9,
            'bold' => true,
        ], $right);

        // SUBHEADER SUBALAMAT
        $table->addRow();
        $table->addCell(5000)->addText(strtoupper($res->depo->kabupaten), [
            'name' => 'Times New Roman',
            'size' => 9
        ]);
        $table->addCell(5000)->addText("ACCOUNT\t: ".strtoupper($res->toko->no_acc), $subHeaderStyle);
        $table->addCell(1500)->addText('', $subHeaderStyle);

        // TEL & NO PO
        $table->addRow();
        $table->addCell(5000)->addText(strtoupper($res->depo->telp), [
            'name' => 'Times New Roman',
            'size' => 9
        ]);
        $po     = $res->po_manual === '' ? $res->id : $res->po_manual;
        $po_date= Carbon::parse($res->tanggal)->format('d F Y');
        $table->addCell(5000)->addText("PO\t\t: {$po} ({$po_date})", $subHeaderStyle);
        $delivery_at = $res->delivered_at <> null ?
            strtoupper(Carbon::parse($res->delivered_at)->format('d M y H:i')) : strtoupper(Carbon::now()->format('d M y H:i'));
        $table->addCell(1500)->addText($delivery_at, [
            'name' => 'Times New Roman',
            'size' => 9
        ], ['align' => 'right']);

        // FAX
        $table->addRow();
        $table->addCell(5000)->addText('FAX. '.strtoupper($res->depo->fax), [
            'name' => 'Times New Roman',
            'size' => 9
        ]);
        $table->addCell(5000)->addText("SALESMAN\t: {$res->tim->nama_tim} - {$res->salesman->user->name}", $subHeaderStyle);
        $table->addCell(1400)->addText('CREDIT', [
            'name' => 'Times New Roman',
            'size' => 10,
            'bold' => true
        ], $right);

        // GUDANG
        $table->addRow();
        $table->addCell(5000)->addText('', [
            'name' => 'Times New Roman',
            'size' => 11
        ]);
        $table->addCell(5000)->addText("GUDANG\t: ".strtoupper($res->gudang->nama_gudang), $subHeaderStyle);
        $table->addCell(1400)->addText('', [
            'name' => 'Times New Roman',
            'size' => 10,
            'bold' => true
        ]);

        $section->addText("\n");

        // DETAIL INVOICE
        $table = $section->addTable();
        $table->addRow();
        $headerDetail = [
            'name' => 'Times New Roman',
            'size' => 10,
            'bold' => true
        ];

        $headerDetailDesc = [
            'name' => 'Times New Roman',
            'size' => 10
        ];

        $styleCell = [
            'borderColor' =>'000000',
            'borderSize' => 9,
        ];

        $borderLeftSize = [
            'borderLeftSize' => 9,
            'borderColor' =>'000000'
        ];

        $borderRightSize = [
            'borderRightSize' => 9,
            'borderColor' =>'000000'
        ];

        $borderBottomSize = [
            'borderBottomSize' => 9,
            'borderColor' =>'000000'
        ];

        $table->addCell(600, $styleCell)->addText('NO', $headerDetail, $center);
        $table->addCell(1500, $styleCell)->addText("KODE", $headerDetail, $center);
        $table->addCell(4000, $styleCell)->addText('NAMA BARANG', $headerDetail, $center);
        $table->addCell(1000, $styleCell)->addText("JML", $headerDetail, $center);
        $table->addCell(1400, $styleCell)->addText("HARGA", $headerDetail, $center);
        $table->addCell(1400, $styleCell)->addText("DISK", $headerDetail, $center);
        $table->addCell(1400, $styleCell)->addText("SUBTOTAL", $headerDetail, $center);

        $detail_penjualan       = $res->detail_penjualan;
        $count_detail_penjualan = $detail_penjualan->count();
        $npwp                   = $res->toko->ketentuan_toko->npwp;
        foreach ($detail_penjualan as $key => $detail) {
            $borderBottom = [];
            if ($key+1 === $count_detail_penjualan) {
                $borderRightSize['borderBottomSize']= 9;
                $borderLeftSize['borderBottomSize'] = 9;
                $borderBottom = $borderBottomSize;
            }

            $harga      = $npwp != '' && $npwp != null ? $detail->harga_barang->harga / 1.1 : $detail->harga_barang->harga;
            $discount   = $npwp != '' && $npwp != null ? $detail->discount : $detail->discount_after_tax;
            $subtotal   = $npwp != '' && $npwp != null ? $detail->subtotal : $detail->subtotal_after_tax;

            $table->addRow();
            $table->addCell(750, $borderLeftSize)->addText($key+1, $headerDetailDesc, $center);
            $table->addCell(1500, $borderBottom)->addText($detail->stock->barang->kode_barang, $headerDetailDesc, $left);
            $table->addCell(4100, $borderBottom)->addText(strtoupper($detail->stock->barang->nama_barang), $headerDetailDesc, $left);
            $table->addCell(1000, $borderBottom)->addText($detail->qty.'/'.$detail->qty_pcs, $headerDetailDesc, $center);
            $table->addCell(1400, $borderBottom)->addText(Helper::rupiah($harga), $headerDetailDesc, $right);
            $table->addCell(1400, $borderBottom)->addText(Helper::rupiah($discount), $headerDetailDesc, $right);
            $table->addCell(1400, $borderRightSize)->addText(Helper::rupiah($subtotal), $headerDetailDesc, $right);
        }

        unset($borderRightSize['borderBottomSize']);
        unset($borderLeftSize['borderBottomSize']);


        if ($npwp != '' && $npwp != null) {
            $table->addRow();
            $table->addCell(6350, [
                'gridSpan' => 3,
                'borderLeftSize' => 9,
                'borderColor' =>'000000'
            ])->addText(Helper::terbilang($res->grand_total), $headerDetail);
            $table->addCell(1000)->addText('Total', $headerDetail, $center);
            $table->addCell(2800, ['gridSpan' => 2])->addText('SUBTOTAL', $headerDetail);
            $table->addCell(1400, $borderRightSize)->addText(Helper::rupiah($res->total_after_tax), $headerDetail, $right);

            $table->addRow();
            $table->addCell(6350, [
                'gridSpan' => 3,
                'borderLeftSize' => 9,
                'borderColor' =>'000000'
            ])->addText('Catatan: '.$res->keterangan, $headerDetail);
            $table->addCell(1000)->addText($res->total_qty.'/'.$res->total_pcs, $headerDetail, $center);
            $table->addCell(2800, ['gridSpan' => 2])->addText('DISKON', $headerDetail);
            $table->addCell(1400, $borderRightSize)->addText(Helper::rupiah($res->disc_total), $headerDetail, $right);

            $table->addRow();
            $table->addCell(6350, [
                'gridSpan' => 3,
                'borderLeftSize' => 9,
                'borderColor' =>'000000'
            ])->addText('', $headerDetail);
            $table->addCell(1000)->addText('', $headerDetail, $center);
            $table->addCell(2800, ['gridSpan' => 2])->addText('DPP', $headerDetail);
            $table->addCell(1400, $borderRightSize)->addText(Helper::rupiah($res->grand_total - $res->ppn), $headerDetail, $right);

            $table->addRow();
            $table->addCell(6350, [
                'gridSpan' => 3,
                'borderLeftSize' => 9,
                'borderColor' =>'000000'
            ])->addText('', $headerDetail);
            $table->addCell(1000)->addText('', $headerDetail, $center);
            $table->addCell(2800, ['gridSpan' => 2])->addText('PPN', $headerDetail);
            $table->addCell(1400, $borderRightSize)->addText(Helper::rupiah($res->ppn), $headerDetail, $right);

            $table->addRow();
            $table->addCell(6350, [
                'gridSpan' => 3,
                'borderLeftSize' => 9,
                'borderBottomSize' => 9,
                'borderColor' =>'000000',
            ])->addText('', $headerDetail);
            $table->addCell(1000, $borderBottomSize)->addText('', $headerDetail, $center);
            $table->addCell(2800, [
                'gridSpan' => 2,
                'borderBottomSize' => 9,
                'borderColor' =>'000000'
            ])->addText('GRAND TOTAL', $headerDetail);
            $borderRightSize['borderBottomSize']= 9;
            $table->addCell(1400, $borderRightSize)->addText(Helper::rupiah($res->grand_total), $headerDetail, $right);

        } else {
            $table->addRow();
            $table->addCell(6350, [
                'gridSpan' => 3,
                'borderLeftSize' => 9,
                'borderColor' =>'000000'
            ])->addText('Harga sudah termasuk PPn', $headerDetail);

            $table->addCell(1000)->addText('Total', $headerDetail, $center);
            $table->addCell(2800, ['gridSpan' => 2])->addText('SUBTOTAL', $headerDetail);
            $table->addCell(1400, $borderRightSize)->addText(Helper::rupiah($res->total_after_tax), $headerDetail, $right);

            $table->addRow();
            $table->addCell(6350, [
                'gridSpan' => 3,
                'borderLeftSize' => 9,
                'borderColor' =>'000000'
            ])->addText(Helper::terbilang($res->grand_total), $headerDetail);

            $table->addCell(1000)->addText($res->total_qty.'/'.$res->total_pcs, $headerDetail, $center);
            $table->addCell(2800, ['gridSpan' => 2])->addText('DISKON', $headerDetail);
            $table->addCell(1400, $borderRightSize)->addText(Helper::rupiah($res->disc_total_after_tax), $headerDetail, $right);

            $table->addRow();
            $table->addCell(6350, [
                'gridSpan'          => 3,
                'borderBottomSize'  => 9,
                'borderLeftSize'    => 9,
                'borderColor'       =>'000000'
            ])->addText('Catatan: '.$res->keterangan, $headerDetail);

            $table->addCell(1000, ['borderBottomSize' => 9, 'borderColor' =>'000000'])->addText("", $headerDetail, $center);
            $table->addCell(2800, ['gridSpan' => 2, 'borderBottomSize' => 9, 'borderColor' =>'000000'])->addText('GRAND TOTAL', $headerDetail);
            $borderRightSize['borderBottomSize']= 9;
            $table->addCell(1400, $borderRightSize)->addText(Helper::rupiah($res->grand_total), $headerDetail, $right);
        }

        $section->addText("\n");

        // APPROVAL
        $approval = [
            'name' => 'Times New Roman',
            'size' => 9,
        ];
        $table = $section->addTable();
        $table->addRow();
        $table->addCell(2315)->addText( 'Bag. Invoice', $approval, $center);
        $table->addCell(2315)->addText( 'Spv. Sales', $approval, $center);
        $table->addCell(2315)->addText( 'Bag. Gudang', $approval, $center);
        $table->addCell(2315)->addText( 'Pengirim', $approval, $center);
        $table->addCell(2315)->addText( 'Penerima', $approval, $center);
        $section->addText("\n");
        $section->addText("\n");
        $table = $section->addTable();
        $table->addRow();
        $table->addCell(2315)->addText( "(   Accounting    )", $approval, $center);
        $table->addCell(2315)->addText( '(                                )', $approval, $center);
        $table->addCell(2315)->addText( '(                                )', $approval, $center);
        $table->addCell(2315)->addText( '(                                )', $approval, $center);
        $table->addCell(2315)->addText( '(                                )', $approval, $center);

        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        ob_start();
        $writer->save('php://output');
        $content = ob_get_contents();
        ob_end_clean();
        $fileName = $res->id.".doc";
        Storage::disk('local')->put("excel/" . $fileName, $content);
        $file = url('/excel/' . $fileName);
        return response()->json($file, 200);
    }
}
