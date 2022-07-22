<?php


namespace App\Http\Controllers;

use App\Http\Resources\SharingPromo as SharingPromoResources;
use App\Http\Requests\SharingPromosStoreRequest;
use App\Models\SharingPromo;
use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;

class SharingPromoController extends Controller
{
    protected $jwt, $user;
    public function __construct(JWTAuth $jwt){
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
    }

    public function index(Request $request)
    {
        if ($this->user->can('Menu Sharing Promo')) :
            $perPage    = $request->per_page;
            $keyword    = $request->keyword;

            $sharing_promo = SharingPromo::with('promo')
                ->when($keyword <> '', function ($q) use ($keyword) {
                    $q->whereHas('promo', function ($q) use ($keyword) {
                        $q->where('no_promo', 'like', "%{$keyword}%")
                            ->orWhere('nama_promo', 'like', "%{$keyword}%")
                            ->orWhere('keterangan', 'like', "%$keyword%");
                    });
                })->orderBy('id', 'desc');

            $sharing_promo = $perPage == 'all' ? $sharing_promo->get() : $sharing_promo->paginate($perPage);
            return SharingPromoResources::collection($sharing_promo);
        else:
            return $this->Unauthorized();
        endif;
    }

    public function store(SharingPromosStoreRequest $request)
    {
        if ($this->user->can('Tambah Sharing Promo')) :
            $data = $request->all();
            return SharingPromo::create($data) ? $this->storeTrue('sharing promo') : $this->storeFalse('sharing promo');
        else:
            return $this->Unauthorized();
        endif;
    }

    public function edit($id)
    {
        if ($this->user->can('Edit Sharing Promo')) :
            $sharing_promo = SharingPromo::find($id);
            if ($sharing_promo) {
                $sharing_promo = SharingPromo::with('promo')->find($id);
                return new SharingPromoResources($sharing_promo);
            }

            return $this->dataNotFound('sharing promo');
        else:
            return $this->Unauthorized();
        endif;
    }

    public function update(SharingPromosStoreRequest $request, $id)
    {
        if ($this->user->can('Update Sharing Promo')) :
            $sharing_promo = SharingPromo::find($id);
            if ($sharing_promo) {
                $data = $request->all();
                return $sharing_promo->update($data) ? $this->updateTrue('sharing promo') : $this->updateFalse('sharing promo');
            }

            return $this->dataNotFound('sharing promo');
        else:
            return $this->Unauthorized();
        endif;
    }

    public function destroy($id)
    {
        if ($this->user->can('Delete Sharing Promo')) :
            $sharing_promo = SharingPromo::find($id);
            if ($sharing_promo) {
                return $sharing_promo->delete() ? $this->destroyTrue('sharing promo') : $this->destroyFalse('sharing promo');
            }

            return $this->dataNotFound('sharing promo');
        else:
            return $this->Unauthorized();
        endif;
    }
}
