<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;
use App\Models\Principal;
use App\Http\Resources\Principal as PrincipalResources;

class PrincipalController extends Controller
{
    protected $jwt;

    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
    }

    public function index(Request $request)
    {
        if ($this->user->can('Menu Principal')):
            $perusahaan = Helper::perusahaanByUser($this->user->id);
            $keyword = $request->has('keyword') ? $request->keyword:'';
            $list_principal = Principal::with('perusahaan')
                ->when($keyword <> '', function($q) use ($keyword){
                    return $q->where('nama_principal', 'like', "%{$keyword}%")
                            ->orWhere('alamat', 'like', "%{$keyword}%")
                            ->orWhere('kode_pos', 'like', "%{$keyword}%")
                            ->orWhere('telp', 'like', "%{$keyword}%");
                })
                ->whereIn('id_perusahaan', $perusahaan)
                ->orderBy('id', 'desc');

            $perPage = $request->has('per_page') ? $perPage = $request->per_page : $perPage = 'all';

            $list_principal = $perPage == 'all' ? $list_principal->get() : $list_principal->paginate((int)$perPage);

            if ($list_principal) {
                return PrincipalResources::collection($list_principal);
            }
            return response()->json([
                'message' => 'Data Principal tidak ditemukan!'
            ], 404);
        else:
            return $this->Unauthorized();
        endif;
    }

    public function store(Request $request)
    {
        if ($this->user->can('Tambah Principal')):
            $this->validate($request, [
                'nama_principal' => 'required|max:255|unique:principal',
                'kode_pos' => 'max:5',
                'telp' => 'max:20'
            ]);

            $input = $request->all();
            $input['created_by'] = $this->user->id;

            try {
                $principal = Principal::create($input);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 400);
            }

            return response()->json([
                'message' => 'Data Principal berhasil disimpan.'
            ], 201);
        else:
            return $this->Unauthorized();
        endif;
    }

    public function show($id)
    {
        if ($this->user->can('Edit Principal')):
            $principal = Principal::find($id);
            if ($principal) {
                return $principal;
            }
            return response()->json([
                'message' => 'Data Principal tidak ditemukan!'
            ], 404);
        else:
            return $this->Unauthorized();
        endif;
    }

    public function update(Request $request, $id)
    {
        if ($this->user->can('Update Principal')):
            $principal = Principal::find($id);

            $this->validate($request, [
                'nama_principal' => 'required|max:255|unique:principal,nama_principal,' . $id,
                'kode_pos' => 'max:5',
                'telp' => 'max:20'
            ]);

            $input = $request->all();
            $input['updated_by'] = $this->user->id;

            if ($principal) {
                $principal->update($input);

                return response()->json([
                    'message' => 'Data Principal telah berhasil diubah.'
                ], 201);
            }

            return response()->json([
                'message' => 'Data Principal tidak ditemukan.'
            ], 404);
        else:
            return $this->Unauthorized();
        endif;
    }

    public function destroy($id)
    {
        if ($this->user->can('Hapus Principal')):
            $principal = Principal::find($id);

            if($principal) {
                $data = ['deleted_by' => $this->user->id];
                $principal->update($data);

                $principal->delete();

                return response()->json([
                    'message' => 'Data Principal berhasil dihapus.'
                ], 200);
            }

            return response()->json([
                'message' => 'Data Principal tidak ditemukan!'
            ], 404);
        else:
            return $this->Unauthorized();
        endif;
    }

    public function restore($id)
    {
        if ($this->user->can('Tambah Principal')):
            $principal = Principal::withTrashed()->find($id);

            if($principal) {
                $data = ['deleted_by' => null];
                $principal->update($data);

                $principal->restore();

                return response()->json([
                    'message' => 'Data Principal berhasil dikembalikan.'
                ], 200);
            }

            return response()->json([
                'message' => 'Data Principal tidak ditemukan!'
            ], 404);
        else:
            return $this->Unauthorized();
        endif;
    }

    public function list(Request $request)
    {
        $id_perusahaan  = $request->has('id_perusahaan') ? $request->id_perusahaan : [];
        $id_perusahaan  = is_array($id_perusahaan) ? $id_perusahaan : [$id_perusahaan];
        $perusahaan     = Helper::perusahaanByUser($this->user->id);
        $id_principal   = Helper::principalByUser($this->user->id);
        $principal      = Principal::whereIn('id_perusahaan', $perusahaan)
                        ->when(count($id_perusahaan) > 0, function ($q) use ($id_perusahaan){
                            return $q->whereIn('id_perusahaan', $id_perusahaan);
                        })
                        ->when(count($id_principal) > 0, function ($q) use ($id_principal) {
                            $q->whereIn('id', $id_principal);
                        })
                        ->get();

        return PrincipalResources::collection($principal);
    }

}
