<?php

namespace App\Http\Controllers;

use App\Models\Depo;
use App\Http\Resources\Depo as DepoResource;
use App\Helpers\Helper;
use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;

class DepoController extends Controller
{
    protected $jwt;

    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
    }

    public function index(Request $request)
    {
        if ($this->user->can('Menu Depo')):
            $keyword        = $request->has('keyword') ? $request->keyword:'';
            $id_perusahaan  = $request->has('id_perusahaan') ? $request->id_perusahaan:'';
            if ($id_perusahaan == '') {
                $id_perusahaan = Helper::perusahaanByUser($this->user->id);
            } else {
                if (!is_array($id_perusahaan)) {
                    $id_perusahaan = [$id_perusahaan];
                }
            }

            $list_depo      = Depo::with('perusahaan')
                ->when($id_perusahaan, function ($q) use ($id_perusahaan) {
                    return $q->whereIn('id_perusahaan', $id_perusahaan);
                })
                ->when($keyword <> '', function($q) use ($keyword){
                    return $q->where('nama_depo', 'like', "%{$keyword}%")
                        ->orWhere('alamat', 'like', "%{$keyword}%");
                })->orderBy('kode_depo');
            $perPage = $request->has('per_page') ? $perPage = $request->per_page : $perPage = 'all';
            $list_depo = $perPage == 'all' ? $list_depo->get() : $list_depo->paginate((int)$perPage);

            if ($list_depo) {
                return DepoResource::collection($list_depo);
            }

            return response()->json([
                'message' => 'Data Depo tidak ditemukan!'
            ], 404);
        else:
            return $this->Unauthorized();
        endif;
    }

    public function store(Request $request)
    {
        if ($this->user->can('Tambah Depo')):
            $this->validate($request, [
                'id_perusahaan' => 'required|exists:perusahaan,id',
                'nama_depo' => 'required|max:255|unique:depo,nama_depo,NULL,id,id_perusahaan,'.$request->id_perusahaan,
                'alamat' => 'required',
                'kabupaten' => 'required',
                'telp' => 'required',
                'fax' => 'required',
            ],
            [
                'id_perusahaan.required' => 'Perusahaan wajib isi',
                'id_perusahaan.exists' => 'Perusahaan tidak ditemukan',
                'alamat.required' => 'Alamat Wajib diisi',
                'kabupaten.required' => 'Kabupaten Wajib diisi',
                'telp.required' => 'Nomor Telepon Wajib diisi',
                'fax.required' => 'Nomor FAX Wajib diisi'
            ]);

            $input = $request->all();
            $input['created_by'] = $this->user->id;

            try {
                Depo::create($input);
                return $this->storeTrue('depo');
            } catch (\Exception $e) {
                return $this->storeFalse('depo');
            }
        else:
            return $this->Unauthorized();
        endif;
    }

    public function show($id)
    {
        if ($this->user->can('Edit Depo')):
            $depo = Depo::find($id);
            if ($depo) {
                return $depo;
            }
            return $this->dataNotFound('depo');
        else:
            return $this->Unauthorized();
        endif;
    }

    public function update(Request $request, $id)
    {
        if ($this->user->can('Update Depo')):
            $this->validate($request, [
                'nama_depo'     => 'required|max:255|unique:depo,nama_depo,'.$id.',id,id_perusahaan,'.$request->id_perusahaan,
                'id_gudang'     => 'required|numeric|min:0|max:9999999999',
                'id_gudang_bs'  => 'required|numeric|min:0|max:9999999999',
                'alamat'        => 'required',
                'kabupaten'     => 'required',
                'telp'          => 'required',
                'fax'           => 'required',
            ],
            [
                'alamat.required'       => 'Alamat Wajib diisi',
                'kabupaten.required'    => 'Kabupaten Wajib diisi',
                'telp.required'         => 'Nomor Telepon Wajib diisi',
                'fax.required'          => 'Nomor FAX Wajib diisi'
            ]);

            $depo = Depo::find($id);
            $input = $request->all();
            $input['kode_depo'] = $depo->kode_depo;
            $input['updated_by'] = $this->user->id;

            if ($depo) {
                $depo->update($input);

                return response()->json([
                    'message' => 'Data Depo telah berhasil diubah.'
                ], 201);
            }

            return response()->json([
                'message' => 'Data Depo tidak ditemukan.'
            ], 404);
        else:
            return $this->Unauthorized();
        endif;
    }

    public function destroy($id)
    {
        if ($this->user->can('Hapus Depo')):
            $depo = Depo::find($id);
            if($depo) {
                $data = ['deleted_by' => $this->user->id];
                $depo->update($data);

                $depo->delete();

                return response()->json([
                    'message' => 'Data Depo berhasil dihapus.'
                ], 200);
            }

            return response()->json([
                'message' => 'Data Depo tidak ditemukan!'
            ], 404);
        else:
            return $this->Unauthorized();
        endif;
    }

    public function restore($id)
    {
        if ($this->user->can('Tambah Depo')):
            $depo = Depo::withTrashed()->find($id);

            if($depo) {
                $data = ['deleted_by' => null];
                $depo->update($data);

                $depo->restore();

                return response()->json([
                    'message' => 'Data Depo berhasil dikembalikan.'
                ], 200);
            }

            return response()->json([
                'message' => 'Data Depo tidak ditemukan!'
            ], 404);
        else:
            return $this->Unauthorized();
        endif;
    }

    public function depo_by_user(Request $request)
    {
        $id_perusahaan  =   $request->has('id_perusahaan') ?
                            is_array($request->id_perusahaan) ?
                            $request->id_perusahaan
                            : [$request->id_perusahaan]
                            : null;
        $depo_id = Helper::depoIDByUser($this->user->id, $id_perusahaan);
        $list_depo = Depo::whereIn('id',$depo_id)->orderBy('kode_depo')->get();

        if ($list_depo) {
            return DepoResource::collection($list_depo);
        }
        return response()->json([
            'message' => 'Data Depo tidak ditemukan!'
        ], 404);
    }

    public function list(Request $request)
    {
        $id_perusahaan  = $request->has('id_perusahaan') ? $request->id_perusahaan : null;
        $filter   = $request->has('filter') ? filter_var($request->filter, FILTER_VALIDATE_BOOLEAN) : true;

        if (!is_array($id_perusahaan) && $id_perusahaan <> null) {
            $id_perusahaan = [$id_perusahaan];
        }

        $id_depo = [];
        if ($filter === false) {
            $id_depo = Helper::depoIDByUser($this->user->id, $id_perusahaan);
        }

        $list_depo      = Depo::with(['perusahaan:id,kode_perusahaan'])
            ->select('id', 'nama_depo', 'id_perusahaan')
            ->when($id_perusahaan <> null, function ($q) use ($id_perusahaan) {
                $q->whereHas('perusahaan', function ($q) use ($id_perusahaan) {
                   $q->whereIn('id_perusahaan', $id_perusahaan);
                });
            })
            ->when($id_depo, function ($q) use ($id_depo) {
                $q->whereIn('id', $id_depo);
            })
            ->orderBy('kode_depo')->get();

        if ($list_depo) {
            return response()->json(['data'=> $list_depo->toArray()], 200);
        }

        return response()->json([
            'message' => 'Data Depo tidak ditemukan!'
        ], 404);
    }

    public function list_by_id(Request $request)
    {
        $id_depo   = $request->has('id_depo') && count($request->id_depo)>0 ? $request->id_depo : [];
        $list_depo = Depo::with(['perusahaan:id,kode_perusahaan'])
        ->select('id', 'nama_depo', 'id_perusahaan')
        ->when(count($id_depo)>0, function ($q) use ($id_depo){
            return $q->whereIn('depo.id',$id_depo);
        })
        ->get();
        if ($list_depo) {
            return response()->json(['data'=> $list_depo->toArray()], 200);
        }
        
        return response()->json([
            'message' => 'Data Depo tidak ditemukan!'
        ], 404);
    }
}
