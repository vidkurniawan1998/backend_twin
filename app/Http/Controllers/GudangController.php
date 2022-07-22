<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;
use App\Models\Gudang;
use App\Helpers\Helper;
use App\Http\Resources\Gudang as GudangResource;

class GudangController extends Controller
{
    protected $jwt;

    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
    }

    public function index(Request $request)
    {
        if ($this->user->can('Menu Gudang')) :
            $id_gudang = '';
            $id_user = $this->user->id;
            if ($this->user->hasRole('Salesman')) {
                $id_gudang = [$this->user->salesman->tim->depo->id_gudang];
            } elseif ($this->user->hasRole('Salesman Canvass')) {
                $id_gudang = [$this->user->salesman->tim->canvass->id_gudang_canvass];
            } else {
                if ($this->user->can('Gudang By Depo')):
                    $depo       = Helper::depoIDByUser($id_user);
                    $id_gudang  = Helper::gudangByDepo($depo)->pluck('id');
                else:
                    $id_gudang  = Helper::gudangByUser($id_user);
                endif;
            }

            $keyword = $request->has('keyword') ? $request->keyword : '';
            $list_gudang = Gudang::when($keyword <> '', function ($q) use ($keyword) {
                return $q->where('nama_gudang', 'like', "%{$keyword}%");
            })->when($id_gudang, function ($q) use ($id_gudang) {
                return $q->whereIn('id', $id_gudang);
            })->orderBy('jenis', 'asc');

            if ($request->jenis != '') {
                $list_gudang = $list_gudang->whereIn('jenis', explode(",", $request->jenis));
            }

            $perPage = $request->has('per_page') ? $perPage = $request->per_page : $perPage = 'all';
            $list_gudang = $perPage == 'all' ? $list_gudang->get() : $list_gudang->paginate((int)$perPage);

            if ($list_gudang) {
                return GudangResource::collection($list_gudang);
            }

            return $this->dataNotFound('gudang');
        else :
            return $this->Unauthorized();
        endif;
    }

    public function store(Request $request)
    {
        if ($this->user->can('Tambah Gudang')) :
            $this->validate($request, [
                'nama_gudang'   => 'required|max:100|unique:gudang',
                'kode_gudang'   => 'max:5|unique:gudang',
                'jenis'         => 'required|in:baik,bad_stock,canvass,motor,tukar_guling,banded'
            ]);

            $input = $request->all();
            $input['created_by'] = $this->user->id;

            try {
                Gudang::create($input);
                return $this->storeTrue('gudang');
            } catch (\Exception $e) {
                return $this->storeFalse('gudang');
            }
        else :
            return $this->Unauthorized();
        endif;
    }

    public function show($id)
    {
        if ($this->user->can('Edit Gudang')) :
            $gudang = Gudang::find($id);

            if ($gudang) {
                return $gudang;
            }

            return $this->dataNotFound('gudang');
        else :
            return $this->Unauthorized();
        endif;
    }

    public function update(Request $request, $id)
    {
        if ($this->user->can('Update Gudang')) :
            $this->validate($request, [
                'nama_gudang'   => 'required|max:100|unique:gudang,nama_gudang,' . $id,
                'kode_gudang'   => 'max:5|unique:gudang,kode_gudang,' . $id,
                'jenis'         => 'required|in:baik,bad_stock,canvass,motor,tukar_guling,banded'
            ]);

            $gudang = Gudang::find($id);
            if (!$gudang) {
                return $this->dataNotFound('gudang');
            }
            $input = $request->all();
            $input['updated_by'] = $this->user->id;
            return $gudang->update($input) ? $this->updateTrue('gudang') : $this->updateFalse('gudang');
        else :
            return $this->Unauthorized();
        endif;
    }

    public function destroy($id)
    {
        if ($this->user->can('Hapus Gudang')) :
            $gudang = Gudang::find($id);

            if ($gudang) {
                $data = ['deleted_by' => $this->user->id];
                $gudang->update($data);
                return $gudang->delete() ? $this->destroyTrue('gudang') : $this->destroyFalse('gudang');
            }

            return $this->dataNotFound('gudang');
        else :
            return $this->Unauthorized();
        endif;
    }

    public function restore($id)
    {
        if ($this->user->can('Tambah Gudang')) :
            $gudang = Gudang::withTrashed()->find($id);

            if ($gudang) {
                $data = ['deleted_by' => null];
                $gudang->update($data);

                $gudang->restore();

                return response()->json([
                    'message' => 'Data Gudang berhasil dikembalikan.'
                ], 200);
            }

            return $this->dataNotFound('gudang');
        else :
            return $this->Unauthorized();
        endif;
    }

    public function list_gudang_baik()
    {
        if ($this->user->can('List Gudang Baik & Kanvas')) {
            $gudang = Gudang::whereIn('jenis', ['baik', 'canvass'])->get()->sortBy('nama_gudang');
        } else {
            $gudang = Gudang::where('jenis', 'baik')->get()->sortBy('nama_gudang');
        }

        return GudangResource::collection($gudang);
    }

    public function gudang_by_user(Request $request)
    {
        $id_user    = $this->user->id;
        $jenis      = $request->input('jenis', '');
        $gudang     = [];
        if ($this->user->can('Gudang By Depo')):
            $depo       = Helper::depoIDByUser($id_user);
            $id_gudang  = Helper::gudangByDepo($depo)->pluck('id');
            $gudang     = Gudang::whereIn('id', $id_gudang)
                        ->when($jenis <> '', function ($q) use ($jenis) {
                          return $q->where('jenis', $jenis);
                        })->get();
        else:
            $id_gudang  = Helper::gudangByUser($id_user);
            $gudang     = Gudang::whereIn('id', $id_gudang)
                        ->when($jenis <> '', function ($q) use ($jenis) {
                            return $q->where('jenis', $jenis);
                        })->get();
        endif;

        return GudangResource::collection($gudang);
    }

    public function list_gudang_baik_depo()
    {
        $id_depo = Helper::depoIDByUser($this->user->id);
        if ($this->user->can('List Gudang Baik & Kanvas')) {
            $gudang = Gudang::whereIn('jenis', ['baik', 'canvass'])->whereIn('id_depo', $id_depo->toArray())->get()->sortBy('nama_gudang');
        } else {
            $gudang = Gudang::where('jenis', 'baik')->whereIn('id_depo', $id_depo->toArray())->get()->sortBy('nama_gudang');
        }

        return GudangResource::collection($gudang);
    }

    public function gudang_by_depo(Request $request)
    {
        if ($this->user->can('Menu Gudang')) :
            $id_depo = Helper::depoIDByUser($this->user->id);
            $gudang  = Helper::gudangByDepo($id_depo);

            if ($gudang) {
                return response()->json([
                    'data' => $gudang
                ]);
            }

            return $this->dataNotFound('gudang');

        else :
            return $this->Unauthorized();
        endif;
    }
}
