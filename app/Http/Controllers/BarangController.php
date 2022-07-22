<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Gudang;
use App\Models\Stock;
use App\Models\PosisiStock;
use App\Imports\TipeBarangImport;
use App\Http\Resources\Barang as BarangResource;
use App\Http\Resources\BarangSimple as BarangSimpleResource;
use App\Http\Resources\BarangByBrand as BarangByBrandResource;
use DB;
use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;
use App\Helpers\Helper;
use App\Models\StockAwal;
use \Maatwebsite\Excel\Facades\Excel;

class BarangController extends Controller
{
    protected $jwt;

    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
    }

    public function index(Request $request)
    {

        $id_perusahaan  = $request->has('perusahaan') && $request->perusahaan != 'all' ? $request->perusahaan : null;
        $id_principal   = $request->has('id_principal') && $request->id_principal != 'all' ? $request->id_principal : null;
        $id_brand       = $request->has('id_brand') && $request->id_brand != 'all' ? $request->id_brand : null;
        $id_depo        = $request->has('depo') && $request->depo != 'all' ? $request->depo : null;

        if ($this->user->can('Menu Barang')) :
            $extra = $request->has('extra') ? $request->extra : 0;
            $list_barang = Barang::with('depo', 'perusahaan', 'segmen.brand.principal')->when($extra <> '' && $extra <> 0, function ($q) use ($extra) {
                return $q->where('extra', 1);
            })
                ->when($id_perusahaan <> null, function ($q) use ($id_perusahaan) {
                    $q->whereHas('perusahaan', function ($q) use ($id_perusahaan) {
                        $q->where('id_perusahaan', $id_perusahaan);
                    });
                })
                ->when($id_depo <> null, function ($q) use ($id_depo) {
                    $q->whereHas('depo', function ($q) use ($id_depo) {
                        $q->whereIn('id', $id_depo);
                    });
                })
                ->when($id_brand <> null || $id_principal <> null, function ($q) use ($id_brand, $id_principal) {
                    $q->whereHas('segmen', function ($q) use ($id_brand, $id_principal) {
                        $q->when($id_brand <> null, function ($q) use ($id_brand) {
                            $q->where('id_brand', $id_brand);
                        })->whereHas('brand', function ($q) use ($id_principal) {
                            $q->when($id_principal <> null, function ($q) use ($id_principal) {
                                $q->where('id_principal', $id_principal);
                            });
                        });
                    });
                })
                ->orderBy('kode_barang', 'asc');

            $depo_user      = Helper::depoIDByUser($this->user->id);
            $list_barang    = $list_barang->whereHas('depo', function ($query) use ($depo_user) {
                return $query->whereIn('depo_id', $depo_user);
            });

            if ($request->has('keyword')) {
                $keyword = $request->keyword;
                $list_barang = $list_barang->where(function ($query) use ($keyword) {
                    $query->where('kode_barang', 'like', '%' . $keyword . '%')
                        ->orWhere('barcode', 'like', '%' . $keyword . '%')
                        ->orWhere('nama_barang', 'like', '%' . $keyword . '%')
                        ->orWhere('deskripsi', 'like', '%' . $keyword . '%');
                });
            }

            $perPage = $request->has('per_page') ? $perPage = $request->per_page : $perPage = 'all';
            $list_barang = $perPage == 'all' ? $list_barang->get() : $list_barang->paginate((int)$perPage);

            // return response()->json($list_barang);

            if ($list_barang) {
                return BarangResource::collection($list_barang);
            }
            return response()->json([
                'message' => 'Data Barang tidak ditemukan!'
            ], 404);
        else :
            return $this->Unauthorized();
        endif;
    }

    public function list_simple(Request $request)
    {
        $list_barang    = Barang::orderBy('kode_barang', 'asc');
        if ($request->has('keyword')) {
            $keyword = $request->keyword;
            $list_barang = $list_barang->where(function ($query) use ($keyword) {
                $query->where('kode_barang', 'like', '%' . $keyword . '%')
                    ->orWhere('barcode', 'like', '%' . $keyword . '%')
                    ->orWhere('nama_barang', 'like', '%' . $keyword . '%')
                    ->orWhere('deskripsi', 'like', '%' . $keyword . '%');
            });
        }

        $perPage = $request->has('per_page') ? $perPage = $request->per_page : $perPage = 5;
        $list_barang = $perPage == 'all' ? $list_barang->get() : $list_barang->paginate((int)$perPage);

        if ($list_barang) {
            return BarangSimpleResource::collection($list_barang);
        }
        return response()->json([
            'message' => 'Data Barang tidak ditemukan!'
        ], 404);
    }

    public function list_by_mitra(Request $request)
    {
        $id_mitra       = $request->has('id_mitra') ? is_array($request->id_mitra) ? count($request->id_mitra) > 0 ?  $request->id_mitra
            : [] : [$request->id_mitra] : [];
        $list_barang    = Barang::orderBy('kode_barang', 'asc')
            ->when(count($id_mitra) > 0, function ($q) use ($id_mitra) {
                return $q->whereIn('id_mitra', $id_mitra);
            });
        if ($request->has('keyword')) {
            $keyword = $request->keyword;
            $list_barang = $list_barang->where(function ($query) use ($keyword) {
                $query->where('kode_barang', 'like', '%' . $keyword . '%')
                    ->orWhere('barcode', 'like', '%' . $keyword . '%')
                    ->orWhere('nama_barang', 'like', '%' . $keyword . '%')
                    ->orWhere('deskripsi', 'like', '%' . $keyword . '%');
            });
        }
        return BarangByBrandResource::collection($list_barang->get());
    }

    public function list_by_brand(Request $request)
    {
        $id_brand    = $request->has('id_brand') ? $request->id_brand : [];
        $id_brand    = is_array($id_brand) ? $id_brand : [$id_brand];
        $order       = $request->has('order') && count($request->order) > 0 ? $request->order : [];

        $rawOrder    = DB::raw(sprintf('FIELD(id, %s)', implode(',', $order)));
        $list_barang = Barang::with(['segmen', 'segmen.brand'])
            ->whereHas('segmen', function ($q) use ($id_brand) {
                $q->whereHas('brand', function ($q) use ($id_brand) {
                    return $q->whereIn('id', $id_brand)->orderBy('id');
                });
            })
            ->when(count($order) > 0, function ($q) use ($order) {
                return $q->whereIn('id', $order);
            })
            ->when(count($order) > 0, function ($q) use ($rawOrder) {
                return $q->orderByRaw($rawOrder);
            })
            ->get();
        return BarangByBrandResource::collection($list_barang);
    }

    public function store(Request $request)
    {
        if ($this->user->can('Tambah Barang')) :
            $this->validate($request, [
                'kode_barang'     => 'required|max:40|unique:barang,kode_barang,NULL,id,id_perusahaan,' . $request->id_perusahaan,
                'barcode'         => 'max:20',
                'id_perusahaan'   => 'required|exists:perusahaan,id',
                'nama_barang'     => 'required|max:255',
                'berat'           => 'required|numeric|min:0|max:999999999',
                'isi'             => 'required|numeric|min:0|max:99999',
                'satuan'          => 'max:20',
                'id_segmen'       => 'required|exists:segmen,id',
                'depo'            => 'required',
                'item_code'       => 'nullable',
                'pcs_code'        => 'nullable',
                'kelipatan_order' => 'required|min:1',
                // 'tipe'            => 'required'
            ]);

            $input = $request->except(['depo']);
            $input['tipe'] = $request->has('tipe') ? $request->tipe : 'exist';
            $input['created_by'] = $this->user->id;
            $depo = $request->depo;

            DB::beginTransaction();
            try {
                $barang = Barang::create($input);
                $gudang = Gudang::whereHas('depo', function ($q) use ($depo) {
                    return $q->whereIn('id_depo', $depo);
                })->get();
                $barang->depo()->attach(array_unique($request->depo));

                foreach ($gudang as $key => $gdg) {
                    $stock = [
                        'id_gudang'     => $gdg->id,
                        'id_barang'     => $barang->id,
                        'qty'           => 0,
                        'qty_pcs'       => 0,
                        'created_by'    => $this->user->id,
                        'updated_by'    => $this->user->id
                    ];

                    $stock = Stock::create($stock);
                    PosisiStock::create([
                        'tanggal'   => date('Y-m-d'),
                        'id_stock'  => $stock->id
                    ]);

                    StockAwal::create([
                        'tanggal'   => date('Y-m-d'),
                        'id_stock'  => $stock->id,
                        'qty_stock' => 0,
                        'qty_pcs_stock' => 0,
                        'qty_pending' => 0,
                        'qty_pcs_pending' => 0,
                        'harga' => 0,
                        'qty_mutasi_pending' => 0,
                        'qty_pcs_mutasi_pending' => 0,
                    ]);
                }
                DB::commit();
            } catch (\Exception $e) {
                DB::rollback();
                return response()->json(['error' => $e->getMessage()], 400);
            }

            return response()->json([
                'message' => 'Data Barang berhasil disimpan.'
            ], 201);
        else :
            return $this->Unauthorized();
        endif;
    }

    public function show($id)
    {
        if ($this->user->can('Edit Barang')) :
            $barang = Barang::find($id);
            if ($barang) {
                return new BarangResource($barang);
            }
            return response()->json([
                'message' => 'Data Barang tidak ditemukan!'
            ], 404);
        else :
            return $this->Unauthorized();
        endif;
    }

    public function update(Request $request, $id)
    {

        if ($this->user->can('Update Barang')) :
            $barang = Barang::find($id);

            $this->validate($request, [
                'kode_barang'     => 'required|max:40|unique:barang,kode_barang,' . $id . ',id,id_perusahaan,' . $request->id_perusahaan,
                'id_perusahaan'   => 'required|exists:perusahaan,id',
                'barcode'         => 'max:20',
                'nama_barang'     => 'required|max:255',
                'berat'           => 'required|numeric|min:0|max:999999999',
                'isi'             => 'required|numeric|min:0|max:99999',
                'satuan'          => 'max:20',
                'depo'            => 'required',
                'id_segmen'       => 'required|exists:segmen,id',
                'item_code'       => 'nullable',
                'pcs_code'        => 'nullable',
                'kelipatan_order' => 'required|min:1',
                'tipe'            => 'required'
            ]);

            $input = $request->all();
            $input['updated_by'] = $this->user->id;

            if ($barang) {
                $barang->update($input);
                $barang->depo()->detach();
                $barang->depo()->attach(array_unique($request->depo));

                return response()->json([
                    'message' => 'Data Barang telah berhasil diubah.'
                ], 201);
            }

            return response()->json([
                'message' => 'Data Barang tidak ditemukan.'
            ], 404);
        else :
            return $this->Unauthorized();
        endif;
    }

    public function destroy($id)
    {
        if ($this->user->can('Hapus Barang')) :
            $barang = Barang::find($id);

            if ($barang) {
                $data = ['deleted_by' => $this->user->id];
                $barang->update($data);

                $barang->delete();

                return response()->json([
                    'message' => 'Data Barang berhasil dihapus.'
                ], 200);
            }

            return response()->json([
                'message' => 'Data Barang tidak ditemukan!'
            ], 404);
        else :
            return $this->Unauthorized();
        endif;
    }

    public function restore($id)
    {
        if ($this->user->can('Tambah Barang')) :
            $barang = Barang::withTrashed()->find($id);

            if ($barang) {
                $data = ['deleted_by' => null];
                $barang->update($data);

                $barang->restore();

                return response()->json([
                    'message' => 'Data Barang berhasil dikembalikan.'
                ], 200);
            }

            return response()->json([
                'message' => 'Data Barang tidak ditemukan!'
            ], 404);
        else :
            return $this->Unauthorized();
        endif;
    }

    public function upload_pic(Request $request, $id)
    { //parameter : gambar
        if ($this->user->can('Update Barang')) :
            $upload_path = 'images/items';

            $barang = Barang::find($id);
            $gambar_lama = $barang->gambar;

            $this->validate($request, [
                'gambar' => 'nullable|image|mimes:jpeg,jpg,bmp,png|max:10240', // max 10 MB
            ]);

            if ($request->hasFile('gambar')) {
                $gambar = $request->file('gambar');
                $ext1 = $gambar->getClientOriginalExtension();

                if ($gambar->isValid()) {
                    // hapus gambar lama
                    if ($gambar_lama != null) {
                        if (file_exists($upload_path . '/' . $barang->gambar)) {
                            unlink($upload_path . '/' . $barang->gambar);
                        }
                    }

                    // upload gambar baru
                    $pic_name = str_replace(' ', '_', $barang->kode_barang) . "_" . \Carbon\Carbon::now()->format('YmdHs') . "." . $ext1;
                    // $success = $pic1->move($upload_path, $pic_name);
                    $gambar->move($upload_path, $pic_name);
                    $barang->update(['gambar' => $pic_name]);

                    if ($barang) {
                        return response()->json([
                            'message' => 'Upload gambar berhasil!',
                            'gambar' => $barang->gambar
                        ], 201);
                    }
                }
            }

            return response()->json([
                'message' => 'Gagal mengunggah file gambar'
            ], 400);
        else :
            return $this->Unauthorized();
        endif;
    }

    public function list(Request $request)
    {
        $extra          = $request->has('extra') ? $request->extra : 0;
        $id_perusahaan  = $request->has('id_perusahaan') ? [$request->id_perusahaan] : Helper::perusahaanByUser($this->user->id);;
        $barang = Barang::select('id', 'kode_barang', 'nama_barang')
            ->when($extra <> 0, function ($q) use ($extra) {
                $q->where('extra', $extra);
            })
            ->when(count($id_perusahaan) > 0, function ($q) use ($id_perusahaan) {
                $q->whereIn('id_perusahaan', $id_perusahaan);
            })
            ->where('status', 1)
            ->orderBy('kode_barang', 'asc')
            ->get();
        return response()->json(['data' => $barang->toArray()], 200);
    }

    public function import_tipe(Request $request)
    {
        if (!$this->user->can('Import Tipe Barang')) {
            return $this->Unauthorized();
        }
        $this->validate($request, [
            'file'      => 'required'
        ]);
        $res  = [];
        $file = $request->file('file');
        $allowExtension = ['xls', 'xlsx'];
        $extension = $file->getClientOriginalExtension();
        if (!in_array($extension, $allowExtension)) {
            return response()->json(['message' => 'hanya mendukung file xls, xlsx'], 400);
        }
        $dataImport = Excel::toCollection(new TipeBarangImport, $file);

        DB::beginTransaction();
        try {
            foreach ($dataImport[0] as $row) {
                $barang = Barang::where('id_perusahaan', $row[0])->where('kode_barang', $row[1])->update(['tipe' => $row[2]]);
                $res[] = [
                    'id_perusahaan' => $row[0],
                    'kode_barang'   => $row[1],
                    'tipe'          => $row[2]
                ];
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'error gagal import'], 400);
        }
        return response()->json(['message' => 'Import Tipe Barang Berhasil', 'data' => $res]);
    }
}
