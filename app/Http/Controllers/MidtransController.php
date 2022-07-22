<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MidtransController extends Controller
{
    public function index(Request $request)
    {
        return response()->json($request->input(), 200);
    }
}
