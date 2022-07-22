<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;
use App\Models\Kendaraan;

class KendaraanController extends Controller
{
    protected $jwt;

    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
    }

    public function index(Request $request){
        if ($this->user->can('Menu Kendaraan')):
            // $list_kendaraan = Kendaraan::orderBy('jenis')->orderBy('no_pol_kendaraan');
            $list_kendaraan = Kendaraan::orderBy('id', 'DESC');
            if($request->keyword != ''){
                $keyword = $request->keyword;
                $list_kendaraan = $list_kendaraan->where(function ($query) use ($keyword){
                    $query->where('no_pol_kendaraan', 'like', '%' . $keyword . '%')
                    ->orWhere('jenis', 'like', '%' . $keyword . '%')
                    ->orWhere('merk', 'like', '%' . $keyword . '%')
                    ->orWhere('body_no', 'like', '%' . $keyword . '%')
                    ->orWhere('tahun', 'like', '%' . $keyword . '%')
                    ->orWhere('peruntukan', 'like', '%' . $keyword . '%')
                    ->orWhere('keterangan', 'like', '%' . $keyword . '%');
                });
            }
    
            $perPage = $request->has('per_page') ? $perPage = $request->per_page : $perPage = 'all';
            $list_kendaraan = $perPage == 'all' ? $list_kendaraan->get() : $list_kendaraan->paginate((int)$perPage);
    
            if ($list_kendaraan) {
                return $list_kendaraan;
            }
            return response()->json([
                'message' => 'Data Kendaraan tidak ditemukan!'
            ], 404);
        else: 
            return $this->Unauthorized();
        endif;
    }

    public function list_delivery(Request $request){
        $list_kendaraan = Kendaraan::orderBy('no_pol_kendaraan')->where('peruntukan', 'delivery');

        if($request->keyword != ''){
            $keyword = $request->keyword;
            $list_kendaraan = $list_kendaraan->where(function ($query) use ($keyword){
                $query->where('no_pol_kendaraan', 'like', '%' . $keyword . '%')
                ->orWhere('jenis', 'like', '%' . $keyword . '%')
                ->orWhere('merk', 'like', '%' . $keyword . '%')
                ->orWhere('body_no', 'like', '%' . $keyword . '%')
                ->orWhere('tahun', 'like', '%' . $keyword . '%')
                ->orWhere('peruntukan', 'like', '%' . $keyword . '%')
                ->orWhere('keterangan', 'like', '%' . $keyword . '%');
            });
        }

        $perPage = $request->has('per_page') ? $perPage = $request->per_page : $perPage = 'all';
        $list_kendaraan = $perPage == 'all' ? $list_kendaraan->get() : $list_kendaraan->paginate((int)$perPage);

        if ($list_kendaraan) {
            return $list_kendaraan;
        }
        return response()->json([
            'message' => 'Data Kendaraan tidak ditemukan!'
        ], 404);
    }

    public function store(Request $request){
        if ($this->user->can('Tambah Kendaraan')):
            $this->validate($request, [
                'no_pol_kendaraan' => 'required|max:11|unique:kendaraan',
                'jenis' => 'required|in:truck,pickup,minibus,sepeda_motor',
                'merk' => 'required|max:255',
                'body_no ' => 'max:10',
                'tahun' => 'numeric|min:1900|max:2100',
                'samsat' => 'date',
                'peruntukan' => 'in:delivery,canvass,other'
            ]);
    
            $input = $request->all();
            $input['no_pol_kendaraan'] = strtoupper($request->no_pol_kendaraan);
            $input['body_no'] = strtoupper($request->body_no);
            $input['created_by'] = $this->user->id;
    
            try {
                $kendaraan = Kendaraan::create($input);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 400);
            }
    
            return response()->json([
                'message' => 'Data Kendaraan berhasil disimpan.'
            ], 201);
            return response()->json([
                'message' => 'Data Kendaraan tidak ditemukan!'
            ], 404);
        else: 
            return $this->Unauthorized();
        endif;
    }

    public function show($id){
        if ($this->user->can('Edit Kendaraan')):
            $kendaraan = Kendaraan::find($id);
    
            if ($kendaraan) {
                return $kendaraan;
            }
            return response()->json([
                'message' => 'Data Kendaraan tidak ditemukan!'
            ], 404);
        else: 
            return $this->Unauthorized();
        endif;
    }

    public function update(Request $request, $id){
        if ($this->user->can('Update Kendaraan')):

        $kendaraan = Kendaraan::find($id);
            $this->validate($request, [
                'no_pol_kendaraan' => 'required|max:11|unique:kendaraan,no_pol_kendaraan,' . $id,
                'jenis' => 'required|in:truck,pickup,minibus,sepeda_motor',
                'merk' => 'required|max:255',
                'body_no ' => 'max:10',
                'tahun' => 'numeric|min:1900|max:2100',
                'samsat' => 'date',
                'peruntukan' => 'in:delivery,canvass,other'
            ]);
    
            $input = $request->all();
            $input['updated_by'] = $this->user->id;
    
            if ($kendaraan) {
                $kendaraan->update($input);
        
                return response()->json([
                    'message' => 'Data Kendaraan telah berhasil diubah.'
                ], 201);
            }
        
            return response()->json([
                'message' => 'Data Kendaraan tidak ditemukan.'
            ], 404);
        else: 
            return $this->Unauthorized();
        endif;
    }

    public function destroy($id){
        if ($this->user->can('Hapus Kendaraan')):
            $kendaraan = Kendaraan::find($id);
            
            if($kendaraan) {
                $data = ['deleted_by' => $this->user->id];
                $kendaraan->update($data);
                $kendaraan->delete();
    
                return response()->json([
                    'message' => 'Data Kendaraan berhasil dihapus.'
                ], 200);
            }
    
            return response()->json([
                'message' => 'Data Kendaraan tidak ditemukan!'
            ], 404);
        else: 
            return $this->Unauthorized();
        endif;
    }

    public function restore($id){
        if ($this->user->can('Tambah Kendaraan')):
            $kendaraan = Kendaraan::withTrashed()->find($id);
            
            if($kendaraan) {
                $data = ['deleted_by' => null];
                $kendaraan->update($data);
                $kendaraan->restore();
    
                return response()->json([
                    'message' => 'Data Kendaraan berhasil dikembalikan.'
                ], 200);
            }
    
            return response()->json([
                'message' => 'Data Kendaraan tidak ditemukan!'
            ], 404);
        else: 
            return $this->Unauthorized();
        endif;
    }
}
