<?php


namespace App\Http\Controllers;

use App\Http\Resources\TokoTanpaLimit as TokoTanpaLimitResources;
use App\Models\TokoNoLimit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Tymon\JWTAuth\JWTAuth;

class TokoNoLimitController extends Controller
{
    protected $jwt, $user, $modul;
    public function __construct(JWTAuth $jwt){
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
        $this->modul = 'toko tanpa limit';
    }

    public function index(Request $request)
    {
        if ($this->user->can('Menu Toko Tanpa Limit')) :
            $perPage    = $request->per_page;
            $keyword    = $request->keyword;

            $toko_tanpa_limit = TokoNoLimit::with('toko')
                ->when($keyword <> '', function ($q) use ($keyword) {
                    $q->whereHas('toko', function ($q) use ($keyword) {
                        $q->where('nama_toko', 'like', "%{$keyword}%");
                    });
                })->orderBy('id', 'desc');

            $toko_tanpa_limit = $perPage == 'all' ? $toko_tanpa_limit->get() : $toko_tanpa_limit->paginate($perPage);
            return TokoTanpaLimitResources::collection($toko_tanpa_limit);
        else:
            return $this->Unauthorized();
        endif;
    }

    public function store(Request $request)
    {
        if ($this->user->can('Tambah Toko Tanpa Limit')) :
            $this->validate($request, [
                'id_toko' => [
                    'required',
                    Rule::unique('toko_no_limit')->where( function ($q) use ($request) {
                        return $q->where('id_toko', $request->id_toko)->where('tipe', $request->tipe);
                    })
                ]
            ]);

            $data = $request->all();
            $data['created_by'] = $this->user->id;
            return TokoNoLimit::create($data) ? $this->storeTrue($this->modul) : $this->storeFalse($this->modul);
        else:
            return $this->Unauthorized();
        endif;
    }

    public function edit($id)
    {
        if ($this->user->can('Tambah Toko Tanpa Limit')) :
            $toko_no_limit = TokoNoLimit::find($id);
            if ($toko_no_limit) {
                $toko_no_limit = TokoNoLimit::with('toko')->find($id);
                return new TokoTanpaLimitResources($toko_no_limit);
            }

            return $this->dataNotFound($this->modul);
        else:
            return $this->Unauthorized();
        endif;
    }

    public function update($request, $id)
    {
        if ($this->user->can('Update Toko Tanpa Limit')) :
            $toko_no_limit = TokoNoLimit::find($id);
            if ($toko_no_limit) {
                $data = $request->all();
                return $toko_no_limit->update($data) ? $this->updateTrue($this->modul) : $this->updateFalse($this->modul);
            }

            return $this->dataNotFound($this->modul);
        else:
            return $this->Unauthorized();
        endif;
    }

    public function destroy($id)
    {
        if ($this->user->can('Delete Toko Tanpa Limit')) :
            $toko_no_limit = TokoNoLimit::find($id);
            if ($toko_no_limit) {
                return $toko_no_limit->delete() ? $this->destroyTrue($this->modul) : $this->destroyFalse($this->modul);
            }

            return $this->dataNotFound($this->modul);
        else:
            return $this->Unauthorized();
        endif;
    }
}
