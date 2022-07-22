<?php


namespace App\Http\Controllers;


use App\Helpers\Helper;
use App\Http\Requests\PerusahaanStoreRequest;
use App\Http\Requests\PerusahaanUpdateRequest;
use App\Models\Perusahaan;
use App\Http\Resources\Perusahaan as PerusahaanResources;
use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;
 
class PerusahaanController extends Controller
{
    protected $jwt;
    public function __construct(JWTAuth $jwt){
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
    }

    public function index(Request $request)
    {
        if ($this->user->can('Menu Perusahaan')):
            $perPage    = $request->per_page;
            $keyword    = $request->keyword;

            $perusahaan = Perusahaan::when($keyword <> '', function ($q) use ($keyword) {
                return $q->where('kode_perusahaan', 'like', "%$keyword%")
                    ->orWhere('nama_perusahaan', 'like', "%$keyword%");
            })->orderBy('id', 'desc');

            $perusahaan = $perPage == 'all' ? $perusahaan->get() : $perusahaan->paginate($perPage);
            return PerusahaanResources::collection($perusahaan);
        else:
            return $this->Unauthorized();
        endif;
    }

    /**
     * Tambah perusahaan baru
     * @param PerusahaanStoreRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(PerusahaanStoreRequest $request)
    {
        if ($this->user->can('Tambah Perusahaan')):
            $data = $request->only(['kode_perusahaan', 'nama_perusahaan', 'npwp', 'nama_pkp', 'alamat_pkp']);
            return Perusahaan::create($data) ? $this->storeTrue('perusahaan') : $this->storeFalse('perusahaan');
        else:
            return $this->Unauthorized();
        endif;
    }

    /**
     * Ambil data perusahaan berdasarkan id
     * @param $id
     * @return PerusahaanResources|\Illuminate\Http\JsonResponse
     */
    public function edit($id)
    {
        if ($this->user->can('Edit Perusahaan')):
            $perusahaan = Perusahaan::find($id);
            if ($perusahaan) {
                return new PerusahaanResources($perusahaan);
            }

            return $this->dataNotFound('perusahaan');
        else:
            return $this->Unauthorized();
        endif;
    }

    /**
     * Update data perusahaan
     * @param PerusahaanUpdateRequest $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(PerusahaanUpdateRequest $request, $id)
    {
        if ($this->user->can('Update Perusahaan')):
            $perusahaan = Perusahaan::find($id);
            if ($perusahaan) {
                $data = $request->only(['kode_perusahaan', 'nama_perusahaan', 'npwp', 'nama_pkp', 'alamat_pkp']);
                return $perusahaan->update($data) ? $this->updateTrue('perusahaan'):$this->updateFalse('perusahaan');
            }

            return $this->dataNotFound('perusahaan');
        else:
            return $this->Unauthorized();
        endif;
    }

    /**
     * Hapus data perusahaan
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        if ($this->user->can('Delete Perusahaan')):
            $perusahaan = Perusahaan::find($id);
            if ($perusahaan) {
                return $perusahaan->delete() ? $this->destroyTrue('perusahaan') : $this->destroyFalse('perusahaan');
            }

            return $this->dataNotFound('perusahaan');
        else:
            return $this->Unauthorized();
        endif;
    }

    public function getList()
    {
        $perusahaan = Perusahaan::all();
        return PerusahaanResources::collection($perusahaan);
    }

    public function getListByAccess()
    {
        $id_perusahaan  = Helper::perusahaanByUser($this->user->id);
        $perusahaan     = Perusahaan::whereIn('id', $id_perusahaan)->get();
        return PerusahaanResources::collection($perusahaan);
    }
}
