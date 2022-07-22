<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Imports\HariEfektifImport;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tymon\JWTAuth\JWTAuth;
use App\Models\HariEfektif;
use App\Helpers\Helper;

class HariEfektifController extends Controller
{
    protected $jwt;

    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
    }

    public function index(Request $request)
    {
        if (!$this->user->can('Import Hari Efektif')){ return $this->Unauthorized();}
        $data = HariEfektif::get();
        return response()->json($data);
    }

    public function importHariEfektif(Request $request)
    {
        if (!$this->user->can('Import Hari Efektif')){ return $this->Unauthorized();}

        $this->validate($request, [
            'file' => 'required'
        ]);

        $allowExtension = ['xls', 'xlsx'];
        $file       = $request->file;
        $extension  = $file[0]->getClientOriginalExtension();
        if (!in_array($extension, $allowExtension)) {
            return response()->json(['message' => 'hanya mendukung file xls, xlsx'], 422);
        }
        $rows = Excel::toArray(new HariEfektifImport, $file[0]);
        if (count($rows[0]) > 0) {
            DB::beginTransaction();
            try {
                $n=0;
                $start = 0;
                DB::table('hari_efektif')->delete();
                foreach ($rows[0] as $key => $row) {
                    if($n==0){
                        if($row[0]=='id'){
                            $start = 1;
                        }
                        else if($row[0]=='tanggal'){
                            $start = 0;
                        }
                    }
                    else{
                        if(!is_numeric($row[$start])){
                        $tanggal  = $row[$start];
                        }
                        else{
                            $UNIX_DATE = ($row[$start] - 25569) * 86400;
                            $tanggal   = gmdate("Y-m-d", $UNIX_DATE);
                        }
                        HariEfektif::create([
                            'tanggal' => $tanggal,
                            'minggu'  => $row[$start+1],
                            'bulan'   => $row[$start+2]
                        ]);
                    }
                    $n++;
                }
                DB::commit();
                return response()->json(['message' => 'import berhasil :-)'], 200);
            } catch (\Exception $e) {
                DB::rollback();
                return response()->json(['message' => 'Opss... gagal. '.json_encode($e->getMessage())], 400);
            }
        } else {
            return response()->json(['message' => 'file excel kosong :-('], 422);
        }
    }
}
