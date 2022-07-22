<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;
use App\Models\Tim;
use App\Http\Resources\Tim as TimResource;
use App\Helpers\Helper;

class TimController extends Controller
{
    protected $jwt;

    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
    }

    public function index(Request $request)
    {
        if ($this->user->can('Menu Tim')):
            $depo_user = Helper::depoIDByUser($this->user->id);
            $keyword = $request->has('keyword') ? $request->keyword:'';
            $list_tim = Tim::when($keyword <> '', function($q) use ($keyword){
                return $q->where('nama_tim', 'like', "%{$keyword}%")
                        ->orWhere('tipe', 'like', "%{$keyword}%");
            })->whereIn('id_depo',$depo_user)->orderBy('nama_tim', 'asc');

            // Jika yg login SS, tampilkan tim yang berada dibawahnya saja
            if($this->user->can('Penjualan Tim')){
                $list_tim = $list_tim->where('id_sales_supervisor', $this->user->id);
            }

            if($this->user->can('Penjualan Tim Koordinator')){
                $list_tim = $list_tim->where('id_sales_koordinator', $this->user->id);
            }

            $perPage = $request->has('per_page') ? $perPage = $request->per_page : $perPage = 'all';

            $list_tim = $perPage == 'all' ? $list_tim->get() : $list_tim->paginate((int)$perPage);

            if ($list_tim) {
                return TimResource::collection($list_tim);
            }
            return response()->json([
                'message' => 'Data Tim tidak ditemukan!'
            ], 404);
        else:
            return $this->Unauthorized();
        endif;
    }

    public function store(Request $request)
    {
        if ($this->user->can('Tambah Tim')):
            $this->validate($request, [
                'nama_tim' => 'required|max:255|unique:tim,nama_tim,NULL,id,id_depo,'.$request->id_depo,
                'tipe' => 'required|in:to,canvass',
                'id_depo' => 'required|numeric|min:0|max:9999999999',
                'id_sales_koordinator' => 'required|exists:users,id',
                'id_sales_supervisor' => 'required|exists:users,id'
            ]);

            $input = $request->all();
            $input['created_by'] = $this->user->id;

            try {
                Tim::create($input);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 400);
            }

            return response()->json([
                'message' => 'Data Tim berhasil disimpan.'
            ], 201);
        else:
            return $this->Unauthorized();
        endif;
    }

    public function show($id)
    {
        if ($this->user->can('Edit Tim')):
            $tim = Tim::find($id);

            if ($tim) {
                return new TimResource($tim);
            }
            return response()->json([
                'message' => 'Data Tim tidak ditemukan!'
            ], 404);
        else:
            return $this->Unauthorized();
        endif;
    }

    public function update(Request $request, $id)
    {
        if ($this->user->can('Update Tim')):
            $tim = Tim::find($id);

            $this->validate($request, [
                'nama_tim' => 'required|max:255|unique:tim,nama_tim,' .$id.',id,id_depo,'.$request->id_depo,
                'tipe' => 'required|in:to,canvass',
                'id_depo' => 'required|numeric|min:0|max:9999999999',
                'id_sales_koordinator' => 'required|exists:users,id',
                'id_sales_supervisor' => 'required|exists:users,id'
            ]);

            $input = $request->all();
            $input['updated_by'] = $this->user->id;

            if ($tim) {
                $tim->update($input);

                return response()->json([
                    'message' => 'Data Tim telah berhasil diubah.'
                ], 201);
            }

            return response()->json([
                'message' => 'Data Tim tidak ditemukan.'
            ], 404);
        else:
            return $this->Unauthorized();
        endif;
    }

    public function destroy($id)
    {
        if ($this->user->can('Hapus Tim')):
            $tim = Tim::find($id);

            if($tim) {
                $data = ['deleted_by' => $this->user->id];
                $tim->update($data);

                $tim->delete();

                return response()->json([
                    'message' => 'Data Tim berhasil dihapus.'
                ], 200);
            }

            return response()->json([
                'message' => 'Data Tim tidak ditemukan!'
            ], 404);
        else:
            return $this->Unauthorized();
        endif;
    }

    public function restore($id)
    {
        if ($this->user->can('Tambah Tim')):
            $tim = Tim::withTrashed()->find($id);

            if($tim) {
                $data = ['deleted_by' => null];
                $tim->update($data);

                $tim->restore();

                return response()->json([
                    'message' => 'Data Tim berhasil dikembalikan.'
                ], 200);
            }

            return response()->json([
                'message' => 'Data Tim tidak ditemukan!'
            ], 404);
        else:
            return $this->Unauthorized();
        endif;
    }

    public function list(Request $request)
    {
        $id_depo = $request->has('id_depo') ? $request->id_depo : null;
        if (!$id_depo) {
            $id_depo    = Helper::depoIDByUser($this->user->id);
        } else {
            if (!is_array($id_depo)) {
                $id_depo = [$id_depo];
            }
        }

        $list_tim   = Tim::whereIn('id_depo', $id_depo)->orderBy('nama_tim', 'asc')->get();

        if ($list_tim) {
            return response()->json(['data'=> $list_tim->toArray()], 200);
        }

        return response()->json([
            'message' => 'Data tim tidak ditemukan!'
        ], 404);
    }

}
