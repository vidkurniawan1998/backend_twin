<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Principal;
use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;
use App\Models\Segmen;
use App\Http\Resources\Segmen as SegmenResource;

class SegmenController extends Controller
{
    protected $jwt;

    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
    }

    public function index(Request $request)
    {
        if ($this->user->can('Menu Segmen')):
            $keyword    = $request->has('keyword') ? $request->keyword:'';
            $id_perusahaan = $request->has('id_perusahaan') ? $request->id_perusahaan:'';
            $id_brand = [];
            if ($id_perusahaan) {
                if (!is_array($id_perusahaan)) {
                    $id_perusahaan = [$id_perusahaan];
                }
                $principal  = Principal::whereIn('id_perusahaan', $id_perusahaan)->get()->pluck('id');
                $id_brand   = Brand::whereIn('id_principal', $principal)->get()->pluck('id');
            }

            $list_segmen = Segmen::when($id_brand, function ($q) use ($id_brand){
                    $q->whereIn('id_brand', $id_brand);
                })
                ->when($keyword, function($q) use ($keyword){
                    return $q->where('nama_segmen', 'like', "%{$keyword}%");
                })->orderBy('id', 'desc');
            $perPage = $request->has('per_page') ? $perPage = $request->per_page : $perPage = 'all';
            $list_segmen = $perPage == 'all' ? $list_segmen->get() : $list_segmen->paginate((int)$perPage);

            if ($list_segmen) {
                return SegmenResource::collection($list_segmen);
            }

            return response()->json([
                'message' => 'Data Segmen tidak ditemukan!'
            ], 404);
        else:
            return $this->Unauthorized();
        endif;
    }


    public function show($id)
    {
        if ($this->user->can('Edit Segmen')):
            $segmen = Segmen::find($id);

            if ($segmen) {
                return new SegmenResource($segmen);
            }

            return response()->json([
                'message' => 'Data Segmen tidak ditemukan!'
            ], 404);
        else:
            return $this->Unauthorized();
        endif;
    }

    public function store(Request $request)
    {
        if ($this->user->can('Tambah Segmen')):
            $this->validate($request, [
                'nama_segmen'   => 'required|max:255|unique:segmen,nama_segmen,NULL,id,id_brand,'.$request->id_brand,
                'id_brand'      => 'numeric|min:0|max:9999999999'
            ]);

            $input = $request->all();
            $input['created_by'] = $this->user->id;

            try {
                $segmen = Segmen::create($input);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 400);
            }

            $list_segmen = Segmen::orderBy('id', 'asc');
            $perPage = $request->has('per_page') ? $perPage = $request->per_page : $perPage = 'all';
            $list_segmen = $perPage == 'all' ? $list_segmen->get() : $list_segmen->paginate((int)$perPage);
            $new_list_segmen = SegmenResource::collection($list_segmen);

            return response()->json([
                'message' => 'Data Segmen berhasil disimpan.',
                'data' => $new_list_segmen
            ], 201);
        else:
            return $this->Unauthorized();
        endif;
    }

    public function update(Request $request, $id)
    {
        if ($this->user->can('Update Segmen')):
            $segmen = Segmen::find($id);

            $this->validate($request, [
                'nama_segmen' => 'required|max:255|unique:segmen,nama_segmen,' . $id . ',id,id_brand,'.$request->id_brand,
                'id_brand' => 'numeric|min:0|max:9999999999'
            ]);

            $input = $request->all();
            $input['updated_by'] = $this->user->id;

            if ($segmen) {
                $segmen->update($input);

                $list_segmen = Segmen::orderBy('id', 'asc');
                $perPage = $request->has('per_page') ? $perPage = $request->per_page : $perPage = 'all';
                $list_segmen = $perPage == 'all' ? $list_segmen->get() : $list_segmen->paginate((int)$perPage);
                $new_list_segmen = SegmenResource::collection($list_segmen);

                return response()->json([
                    'message' => 'Data Segmen telah berhasil diubah.',
                    'data' => $new_list_segmen
                ], 201);
            }

            return response()->json([
                'message' => 'Data Segmen tidak ditemukan.'
            ], 404);
        else:
            return $this->Unauthorized();
        endif;
    }

    public function destroy($id, Request $request)
    {
        if ($this->user->can('Hapus Segmen')):
            $segmen = Segmen::find($id);

            if($segmen) {
                $data = ['deleted_by' => $this->user->id];
                $segmen->update($data);
                $segmen->delete();

                $list_segmen = Segmen::orderBy('id', 'asc');
                $perPage = $request->has('per_page') ? $perPage = $request->per_page : $perPage = 'all';
                $list_segmen = $perPage == 'all' ? $list_segmen->get() : $list_segmen->paginate((int)$perPage);
                $new_list_segmen = SegmenResource::collection($list_segmen);

                return response()->json([
                    'message' => 'Data Segmen berhasil dihapus.',
                    'data' => $new_list_segmen
                ], 200);
            }

            return response()->json([
                'message' => 'Data Segmen tidak ditemukan!'
            ], 404);
        else:
            return $this->Unauthorized();
        endif;
    }

    public function restore($id)
    {
        if ($this->user->can('Tambah Segmen')):
            $segmen = Segmen::withTrashed()->find($id);

            if($segmen) {
                $data = ['deleted_by' => null];
                $segmen->update($data);

                $segmen->restore();

                return response()->json([
                    'message' => 'Data Segmen berhasil dikembalikan.'
                ], 200);
            }

            return response()->json([
                'message' => 'Data Segmen tidak ditemukan!'
            ], 404);
        else:
            return $this->Unauthorized();
        endif;
    }

}
