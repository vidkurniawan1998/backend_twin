<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;
use App\Models\Perusahaan;
use App\Http\Resources\Perusahaan as PerusahaanResources;

class PerusahaanController extends Controller
{
    //
    protected $jwt;

    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
    }

    public function index(Request $request)
    {
        //Better using if cannot for authorization validation
        if ($this->user->cannot('Menu Perusahaan')) //berdasarkan permission login cek di insomnia
        {
            return $this->Unauthorized();
        }
        $keyword = $request->has('keyword') ? $request->keyword : '';
        $list_perusahaan = Perusahaan::when($keyword <> '', function ($q) use ($keyword) {
            return $q->where('kode_perusahaan', 'like', "%{$keyword}%")
                ->orWhere('nama_perusahaan', 'like', "%{$keyword}%")
                ->orWhere('npwp', 'like', "%{$keyword}%")
                ->orWhere('nama_pkp', 'like', "%{$keyword}%")
                ->orWhere('alamat_pkp', 'like', "%{$keyword}%");
        })
            ->orderByDesc('id');

        $per_page = $request->has('per_page') ? $per_page = $request->per_page : $per_page = 'all';
        $list_perusahaan = $per_page == 'all' ? $list_perusahaan->get() : $list_perusahaan->paginate((int)$per_page);

        return PerusahaanResources::collection($list_perusahaan);

        //Use response function on controller
        $this->dataNotFound('perusahaan');
    }

    public function store(Request $request)
    {
        if ($this->user->cannot('Tambah Perusahaan')) //berdasarkan permission login cek di insomnia
        {
            return $this->Unauthorized();
        }

        $this->validate($request, [
            'kode_perusahaan' => 'required|max:50|unique:perusahaan',
            'nama_perusahaan' => 'required|max:255',
            'npwp' => 'required',
            'nama_pkp' => 'required|max:255',
            'alamat_pkp' => 'required'
        ]);

        $input = $request->all();

        try {
            Perusahaan::create($input);
        } catch (\Throwable $th) {
            $this->storeFalse('perusahaan');
        }

        $this->storeTrue('perusahaan');
    }

    public function edit($id)
    {
        if ($this->user->can('Edit Perusahaan')) :
            $perusahaan = Perusahaan::find($id);
            if ($perusahaan) {
                return new PerusahaanResources($perusahaan);
            }

            return $this->dataNotFound('perusahaan');
        else :
            return $this->Unauthorized();
        endif;
    }


    public function update(Request $request, $id)
    {
        if ($this->user->cannot('Update Perusahaan')) //berdasarkan permission login cek di insomnia
        {
            return $this->Unauthorized();
        }

        $perusahaan = Perusahaan::find($id);
        //wtf, is this work?
        $this->validate($request, [
            'kode_perusahaan'   => 'required|max:50|unique:perusahaan,kode_perusahaan',
            'nama_perusahaan'   => 'required|max:255',
            'npwp'  => 'required',
            'nama_pkp'  => 'required|max:255',
            'alamat_pkp' => 'required'
        ]);

        //better using request only
        $input = $request->all();

        if ($perusahaan) {
            $perusahaan->update($input);
            return response()->json([
                'message' => 'Data Perusahaan Telah berhasil Diubah.'
            ], 201);
        }

        $this->dataNotFound('perusahaan');
    }

    public function destroy($id)
    {
        if ($this->user->cannot('Delete Perusahaan')) //berdasarkan permission login cek di insomnia
        {
            return $this->Unauthorized();
        }
        $perusahaan = Perusahaan::find($id);

        if ($perusahaan) {
            //again, try and catch
            $perusahaan->delete();

            return response()->json([
                'message' => 'Data Perusahaan Berhasil Dihapus.'
            ], 200);
        }

        return response()->json([
            'message' => 'Data Perusahaan Tidak Ditemukan'
        ], 422);
    }

    public function restore($id)
    {
        if ($this->user->cannot('Tambah Perusahaan')) //berdasarkan permission login cek di insomnia
        {
            return $this->Unauthorized();
        }
        $perusahaan = Perusahaan::withTrashed()->find($id);

        if ($perusahaan) {
            $data = ['deleted_at' => null];
            $perusahaan->update($data);

            $perusahaan->restore();

            return response()->json([
                'message' => 'Data Perusahaan Berhasil Dikembalikan.'
            ], 200);
        }

        return response()->json([
            'message' => 'Data Perusahaan Gagal Dikembalikan.'
        ], 422);
    }
}
