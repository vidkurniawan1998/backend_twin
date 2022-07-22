<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\DaerahService;
use App\Models\Provinsi;
use App\Models\Kabupaten;
use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Http\Resources\Provinsi as ProvinsiResource;
use App\Http\Resources\Kabupaten as KabupatenResource;
use App\Http\Resources\Kecamatan as KecamatanResource;
use App\Http\Resources\Kelurahan as KelurahanResource;


/**
 * @resource Region
 *
 * API for region
 */
class DaerahController extends Controller
{
    protected $daerahService;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(DaerahService $daerahService)
    {
        $this->daerahService = $daerahService;
    }

    /**
     * Index
     *
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $result = [];

        if ($request->has('type') && in_array($request->type, ['provinsi', 'kabupaten', 'kecamatan', 'kelurahan'])) {
            $result = $this->daerahService->list($request->type, $request->parent_id);
        }

        return $result;
    }

    public function provinsi() {
        $provinsi = Provinsi::get();

        return ProvinsiResource::collection($provinsi);
    }

    public function kabupaten() {
        $kabupaten = Kabupaten::orderBy('nama_kabupaten')->get();

        return KabupatenResource::collection($kabupaten);        
    }

    public function kecamatan() {
        $kecamatan = Kecamatan::orderBy('nama_kecamatan')->get(); 

        return KecamatanResource::collection($kecamatan);
    }

    public function kelurahan() {
        $kelurahan = Kelurahan::orderBy('nama_kelurahan')->get();

        return KelurahanResource::collection($kelurahan);
    }
}