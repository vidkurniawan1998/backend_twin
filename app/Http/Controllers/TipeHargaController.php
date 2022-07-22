<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\Models\TipeHarga;
use App\Http\Resources\TipeHarga as TipeHargaResources;
use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;

class TipeHargaController extends Controller
{
    protected $jwt;

    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($this->user->can('Menu Tipe Harga')) :
            $id         = $this->user->id;
            $keyword    = $request->has('keyword') ? $request->keyword:'';
            $per_page   = $request->has('per_page') ? $request->per_page:'all';
            $perusahaan = $request->id_perusahaan <> '' ? $request->id_perusahaan : Helper::perusahaanByUser($id)->toArray();
            if (!is_array($perusahaan)) {
                $perusahaan = [$perusahaan];
            }

            $tipe_harga = TipeHarga::with('perusahaan')
                ->whereHas('perusahaan', function ($q) use ($perusahaan) {
                    $q->when($perusahaan, function ($q) use ($perusahaan) {
                       return $q->whereIn('id_perusahaan', $perusahaan);
                    });
                })
                ->when( $keyword <> '', function ($q) use ($keyword){
                    return $q->where('tipe_harga', 'like', $keyword);
                })->orderBy('id', 'desc');

            $tipe_harga = $per_page == 'all' ? $tipe_harga->get() : $tipe_harga->paginate(intval($per_page));

            if ($tipe_harga) {
                return TipeHargaResources::collection($tipe_harga);
            }

            return response()->json([
                'message' => 'Data tipe harga tidak ditemukan!'
            ], 404);
        else:
            return $this->Unauthorized();
        endif;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if ($this->user->can('Tambah Tipe Harga')) :
            $this->validate($request, [
                'tipe_harga' => 'required|unique:tipe_harga'
            ]);

            $input = [
                'tipe_harga' => $request->tipe_harga,
                'created_by' => $this->user->id
            ];

            $tipe_harga = TipeHarga::create($input);
            $tipe_harga->perusahaan()->attach($request->perusahaan);

            if ($tipe_harga) {
                return response()->json([
                    'message' => 'Data Barang berhasil disimpan.',
                    'data' => $this->index($request)
                ], 201);
            } else {
                return response()->json(['error' => $e->getMessage()], 400);
            }
        else:
            return $this->Unauthorized();
        endif;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if ($this->user->can('Edit Tipe Harga')) :
            $tipe_harga = TipeHarga::with('perusahaan')->find($id);
            if ($tipe_harga) {
                return new TipeHargaResources($tipe_harga);
            } else {
                return response()->json([
                    'message' => 'Data tipe harga tidak ditemukan!'
                ], 404);
            }
        else:
            return $this->Unauthorized();
        endif;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if ($this->user->can('Update Tipe Harga')) :
            $this->validate($request, [
                'tipe_harga' => 'required'
            ]);

            $tipe_harga = TipeHarga::find($id);
            $tipe_harga->tipe_harga = $request->tipe_harga;
            if ($tipe_harga->update()) {
                $tipe_harga->perusahaan()->sync($request->perusahaan);
                return response()->json([
                    'message' => 'Data tipe harga berhasil diubah.'
                ], 200);
            } else {
                return response()->json([
                    'message' => 'Data tipe harga gagal diubah'
                ], 400);
            }
        else:
            return $this->Unauthorized();
        endif;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if ($this->user->can('Delete Tipe Harga')) :
            $tipe_harga = TipeHarga::find($id);

            if($tipe_harga) {
                $tipe_harga->delete();
                return response()->json([
                    'message' => 'Data tipe harga berhasil dihapus.'
                ], 200);
            }

            return response()->json([
                'message' => 'Data tipe harga tidak ditemukan!'
            ], 404);
        else:
            return $this->Unauthorized();
        endif;
    }

    public function list()
    {
        $exclude        = [1,8,9,5];
        $id_perusahaan  = Helper::perusahaanByUser($this->user->id);
        $harga = TipeHarga::whereHas('perusahaan', function ($q) use ($id_perusahaan) {
            $q->whereIn('id_perusahaan', $id_perusahaan);
        })->whereNotIn('id', $exclude)->orderBy('id', 'asc')->get()->pluck('tipe_harga');
        return response()->json($harga, 200);
    }
}
