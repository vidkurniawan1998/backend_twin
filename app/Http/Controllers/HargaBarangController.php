<?php

namespace App\Http\Controllers;

use App\Http\Resources\ListHargaBarang as ListHargaBarangResource;
use App\Models\HargaBarang;
use App\Models\HargaBarangAktif;
use App\Models\Barang;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;

class HargaBarangController extends Controller
{
    protected $jwt;

    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
	}

	public function update(Request $request, $id)
    {
		if ($this->user->cannot('Update Harga Barang')) return $this->Unauthorized();
        $data = $request->all();
        DB::beginTransaction();
        try {
            foreach ($data as $input) {
                if ($input['harga'] == 0) continue;
                $input['tipe_harga'] = strtolower($input['tipe_harga']);
                $input['harga_non_ppn']  = number_format((float)$input['harga_non_ppn'], 2, '.', '');
                $input['ppn_value']  = number_format((float)$input['ppn_value'], 2, '.', '');
                $input['harga']  = number_format((float)$input['harga'], 2, '.', '');
                $input['ppn']  = number_format((float)$input['ppn'], 2, '.', '');
                $validator           = $input;
                $input['created_by'] = $this->user->id;

                #RESTRICTION
                if($input['ppn'] != 10) {
                    return response()->json([
                        'message' => 'Untuk saat ini sistem hanya bisa mengakomodir ppn 10%!'
                    ], 400);
                }

                ##VALIDATION##
                $err = $input['harga']-($input['harga_non_ppn']+$input['ppn_value']);
                if(abs($err)>0.1) return $this->updateFalse('harga barang '.$input['tipe_harga'].' (harga tidak sama dengan dpp + ppn) '.$err.' ');
                $err = $input['ppn_value']-($input['harga_non_ppn']*$input['ppn']/100);
                if(abs($err)>0.1) return $this->updateFalse('harga barang '.$input['tipe_harga'].' (dpp tidak sama dengan harga - ppn) '.$err.' ');

                $hargaBarangChecker = HargaBarang::where('id_barang', $input['id_barang'])->where('tipe_harga', $input['tipe_harga'])->latest()->first();
			    if ($hargaBarangChecker !== null) {
                    if ($hargaBarangChecker->harga == $input['harga']) {
                        continue;
                    }
                }
                $hargaBarang = HargaBarang::create($input);
                if($hargaBarang->wasRecentlyCreated){
                    $input['id_harga_barang'] = $hargaBarang->id;
                    HargaBarangAktif::updateOrCreate(['id_barang' => $input['id_barang'], 'tipe_harga' => $input['tipe_harga']],$input)->save();
                }
            }
            DB::commit();
    		return response()->json([
    			'message' => 'Data Harga Barang berhasil diubah.'
    		], 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal update harga barang'. $th
            ], 400);
        }
	}


	public function hargaByIdBarang(Request $request, $id)
	{
		$this->validate($request, [
			'tipe_harga' => 'required'
		]);

		$barang = Barang::find($id);
		if ($barang) {
			$tipe_harga = $request->tipe_harga;
			$harga = HargaBarang::whereIdBarang($id)->whereTipeHarga($tipe_harga)->orderBy('updated_at', 'desc')->get();
			if ($harga) {
				return ListHargaBarangResource::collection($harga);
			} else {
				return response()->json(['message' => 'Harga barang tidak ditemukan'], 404);
			}
		} else {
			return response()->json(['message' => 'Data barang tidak ditemukan'], 404);
		}
	}
}
