<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Resources\NpwpExternal as NpwpExternalResources;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tymon\JWTAuth\JWTAuth;
use App\Models\NpwpExternal;
use App\Helpers\Helper;

class NpwpExternalController extends Controller
{
    protected $jwt;

    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
    }

    public function index(Request $request)
    {
        // if (!$this->user->can('Import NPWP External')){ return $this->Unauthorized();}
        $keyword = $request->has('keyword') ? $request->keyword:'';
        $data    = NpwpExternal::when($keyword <> '', function($q) use ($keyword){
            return $q->where('nama_toko', 'like', "%{$keyword}%")
            ->orWhere('kode_outlet', 'like', "%{$keyword}%")
            ->orWhere('npwp', 'like', "%{$keyword}%")
            ->orWhere('nama_pkp', 'like', "%{$keyword}%")
            ->orWhere('alamat_pkp', 'like', "%{$keyword}%");
        })->orderBy('id','DESC');
        $per_page = $request->has('per_page') ? $per_page = $request->per_page : $per_page = 'all';
        $data = $per_page == 'all' ? $data->get() : $data->paginate((int)$per_page);
        return NpwpExternalResources::collection($data);
    }

    public function destroy($id)
    {
        // if (!$this->user->can('Hapus toko')) {
        //     return $this->Unauthorized();
        // }

        $npwp_external = NpwpExternal::find($id);
        if (!$npwp_external) {
            return $this->dataNotFound('npwp external');
        }
        return $npwp_external->delete() ? $this->destroyTrue('npwp external') : $this->destroyFalse('npwp external');
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'kode_outlet' => 'required|string|unique:npwp_external',
            'nama_toko'   => 'required|string',
            'npwp'        => 'required|string',
            'nama_pkp'    => 'required|string',
            'alamat_pkp'  => 'required|string'
        ]);

        $data = $request->only(['kode_outlet', 'nama_toko', 'npwp', 'nama_pkp', 'alamat_pkp']);
        return NpwpExternal::create($data) ? $this->storeTrue('npwp external'):$this->storeFalse('npwp external');
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'nama_toko'    => 'required|string',
            'npwp'        => 'required|string',
            'nama_pkp'    => 'required|string',
            'alamat_pkp'  => 'required|string'
        ]);
        $npwp_checker = NpwpExternal::where('kode_outlet',$request->kode_outlet);
        
        if($npwp_checker->count('id')>1){
            return response()->json([
                'message' => "Gagal mengupdate data kode outlet berganda!"
            ], 422);
        }
        if($npwp_checker->count('id')==1){
            if($npwp_checker->first()->id != $id){
                return response()->json([
                    'message' => "Gagal mengupdate data kode outlet harus unik!"
                ], 422);
            }
        }

        $npwp_external = NpwpExternal::find($id);
        if ($npwp_external) {
            $data = $request->only(['kode_outlet', 'nama_toko', 'npwp', 'nama_pkp', 'alamat_pkp']);
            return $npwp_external->update($data) ? $this->updateTrue('npwp external'):$this->updateFalse('npwp external');
        }
    }

    public function import(Request $request)
    {
        // if (!$this->user->can('Import NPWP External')){ return $this->Unauthorized();}

        // $this->validate($request, [
        //     'file' => 'required'
        // ]);

        if($request->reformat){
            DB::table('npwp_external')->delete();
        }
        $file  = $request->file;
        $error = [];
        DB::beginTransaction();
        try{
            for ($i=1; $i <count($file) ; $i++) { 
                $data = $file[$i];
                $npwp_external = NpwpExternal::create([
                    'kode_outlet'   => $data[0],
                    'nama_toko'     => $data[1],
                    'npwp'          => $data[2],
                    'nama_pkp'      => $data[3],
                    'alamat_pkp'    => $data[4]
                ]);                
                if(count($data)<5 || !$npwp_external){
                    $error [] = $data[0] ? $data[0] : '';
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['message' => 'Opss... gagal. error outlet '.implode(" , ",$error).' '.json_encode($e->getMessage())], 400);
        }
       
    }
}
