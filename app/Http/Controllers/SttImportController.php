<?php

namespace App\Http\Controllers;

use App\Imports\SttImport;
use App\Imports\StockImport;
use App\Models\SttBridging;
use App\Models\StockBridging;
use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;
use \Maatwebsite\Excel\Facades\Excel;

class SttImportController extends Controller
{
    protected $jwt;
    
    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
    }

    public function import(Request $request)
    {
        $this->validate($request, [
            'file' => 'required'
        ]);
        
        SttBridging::where('user_id', $this->user->id)->delete();
        $allowExtension = ['xls', 'xlsx'];
        foreach ($request->file as $key => $file) {
            $fileName  = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $file->getClientOriginalExtension();
            if (!in_array($extension, $allowExtension)) {
                return response()->json(['message' => 'hanya mendukung file xls, xlsx'], 400);
            }
            
            if ($fileName === 'stt') {
                Excel::import(new SttImport, $file);
            } elseif ($fileName === 'stock') {
                Excel::import(new StockImport, $file);
            }
        }

        return response()->json(['message' => 'import berhasil'], 201);
    }
}
