<?php

namespace App\Http\Controllers;

use App\Models\PrincipalBridging;
use DB;
use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;

class BridgingController extends Controller
{
    protected $jwt;

    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
    }

    public function principal()
    {
        $principal = PrincipalBridging::get();
        return response()->json($principal->toArray(), 200);
    }
}
