<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;
use App\Models\Brand;
use App\Http\Resources\Brand as BrandResource;

class BrandController extends Controller
{
    protected $jwt;

    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
    }

    public function index(Request $request)
    {
        if ($this->user->can('Menu Brand')) :
            $id_perusahaan  = $request->has('id_perusahaan') && $request->id_perusahaan ? [$request->id_perusahaan] : Helper::perusahaanByUser($this->user->id);
            $id_principal   = $request->has('id_principal') ? $request->id_principal : [];
            $id_principal   = $id_principal != '' ? $id_principal : [];
            $id_principal   = is_array($id_principal) ? $id_principal : [$id_principal];
            $keyword        = $request->has('keyword') ? $request->keyword : '';

            $list_brand     = Brand::when($keyword <> '', function ($q) use ($keyword) {
                $q->where('nama_brand', 'like', "%{$keyword}%");
            })
                ->when(count($id_principal) > 0, function ($q) use ($id_principal) {
                    return $q->whereIn('id_principal', $id_principal);
                })
                ->whereHas('principal', function ($q) use ($id_perusahaan) {
                    $q->whereIn('id_perusahaan', $id_perusahaan);
                })
                ->orderBy('id', 'asc');
            $perPage = $request->has('per_page') ? $perPage = $request->per_page : $perPage = 'all';
            $list_brand = $perPage == 'all' ? $list_brand->get() : $list_brand->paginate((int)$perPage);
            if ($list_brand) {
                return BrandResource::collection($list_brand);
            }

            return response()->json([
                'message' => 'Data Brand tidak ditemukan!'
            ], 404);
        else :
            return $this->Unauthorized();
        endif;
    }


    public function show($id)
    {
        if ($this->user->can('Edit Brand')) :
            $brand = Brand::find($id);
            if ($brand) {
                return new BrandResource($brand);
            }

            return response()->json([
                'message' => 'Data Brand tidak ditemukan!'
            ], 404);
        else :
            return $this->Unauthorized();
        endif;
    }

    public function store(Request $request)
    {
        if ($this->user->can('Tambah Brand')) :
            $this->validate($request, [
                'nama_brand' => 'required|max:255|unique:brand,nama_brand,NULL,id,id_principal,' . $request->id_principal,
                'id_principal' => 'numeric|min:0|max:9999999999'
            ]);

            $input = $request->all();
            $input['created_by'] = $this->user->id;

            try {
                $brand = Brand::create($input);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 400);
            }

            $list_brand = Brand::orderBy('id', 'asc');
            $perPage = $request->has('per_page') ? $perPage = $request->per_page : $perPage = 'all';
            $list_brand = $perPage == 'all' ? $list_brand->get() : $list_brand->paginate((int)$perPage);
            $new_list_brand = BrandResource::collection($list_brand);

            return response()->json([
                'message' => 'Data Brand berhasil disimpan.',
                'data' => $new_list_brand
            ], 201);
        else :
            return $this->Unauthorized();
        endif;
    }

    public function update(Request $request, $id)
    {
        if ($this->user->can('Update Brand')) :
            $brand = Brand::find($id);

            $this->validate($request, [
                'nama_brand' => 'required|max:255|unique:brand,nama_brand,' . $id . ',id,id_principal,' . $request->id_principal,
                'id_principal' => 'numeric|min:0|max:9999999999'
            ]);

            $input = $request->all();
            $input['updated_by'] = $this->user->id;

            if ($brand) {
                $brand->update($input);
                return response()->json([
                    'message' => 'Data Brand telah berhasil diubah.',
                    'data' => $this->index($request)
                ], 201);
            }

            return response()->json([
                'message' => 'Data Brand tidak ditemukan.'
            ], 404);
        else :
            return $this->Unauthorized();
        endif;
    }

    public function destroy($id, Request $request)
    {
        if ($this->user->can('Hapus Brand')) :
            $brand = Brand::find($id);
            if ($brand) {
                $data = ['deleted_by' => $this->user->id];
                $brand->update($data);
                $brand->delete();

                $list_brand = Brand::orderBy('id', 'asc');
                $perPage = $request->has('per_page') ? $perPage = $request->per_page : $perPage = 'all';
                $list_brand = $perPage == 'all' ? $list_brand->get() : $list_brand->paginate((int)$perPage);
                $new_list_brand = BrandResource::collection($list_brand);

                return response()->json([
                    'message' => 'Data Brand berhasil dihapus.',
                    'data' => $new_list_brand
                ], 200);
            }

            return response()->json([
                'message' => 'Data Brand tidak ditemukan!'
            ], 404);
        else :
            return $this->Unauthorized();
        endif;
    }

    public function restore($id)
    {
        if ($this->user->can('Tambah Brand')) :
            $brand = Brand::withTrashed()->find($id);

            if ($brand) {
                $data = ['deleted_by' => null];
                $brand->update($data);

                $brand->restore();

                return response()->json([
                    'message' => 'Data Brand berhasil dikembalikan.'
                ], 200);
            }

            return response()->json([
                'message' => 'Data Brand tidak ditemukan!'
            ], 404);
        else :
            return $this->Unauthorized();
        endif;
    }
}
