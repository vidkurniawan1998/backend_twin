<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;
use App\Models\User;
use Illuminate\Support\Collection;

class AuthController extends Controller
{
    protected $jwt;

    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
    }

    public function login(Request $request)
    {
        $auth = "";
        if ($request->has('email')) {
            $auth = 'email';
            $request['email'] = str_replace(' ', '', $request['email']);
        } elseif ($request->has('phone')) {
            $auth = 'phone';
            $request['phone'] = str_replace(' ', '', $request['phone']);
        }
        
        $this->validate($request, [
            'email' => 'required_without:phone|email|max:190',
            'phone'     => 'required_without:email|max:190',
            // 'email'    => 'required|email|max:190',
            'password' => 'required|min:6|max:30',
        ]);
        
        try {

            if($auth == 'email'){
                $user   = User::where('email', $request->email)->first();
            }
            elseif($auth == 'phone'){
                $user   = User::where('phone', $request->phone)->first();
            }

            // $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'message' => 'Pengguna tidak terdaftar'
                ], 404);
            }

            if ($user->status != 'active') {
                return response()->json([
                    'message' => 'Pegguna tidak aktif, harap hubungi Administrator'
                ], 404);
            }

            // if (! $token = $this->jwt->attempt($request->only('email', 'password'))) {
            //     return response()->json(['Email atau Password yang anda masukkan salah'], 404);
            // }

            if (!$token = $this->jwt->attempt([
                $auth => $request->$auth,
                'password' => $request->password]))
            {
                return response()->json(['message' => 'Pengguna tidak ditemukan!'], 404);
            }

        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {

            return response()->json(['Token expired, harap login ulang'], 500);

        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {

            return response()->json(['token_invalid'], 500);

        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {

            return response()->json(['token_absent' => $e->getMessage()], 500);

        }

        $permissions = new Collection();
        foreach($user->roles as $role) 
        {
            $permissions->push($role->permissions->pluck('name'));
        }
        
        $res = [];
        if($user->hasRole('Salesman') || $user->hasRole('Salesman Canvass')){
            if($user->salesman->tim->tipe == 'canvass'){
                $res = [
                    'user' => $this->jwt->user(),
                    'token' => $token,
                    'id_gudang' => $user->salesman->tim->canvass->id_gudang_canvass,
                    'nama_gudang' => $user->salesman->tim->canvass->gudang_canvass->nama_gudang,
                    'salesman' => $user->salesman->tim,
                    'gudang_canvass' => $user->salesman->tim->canvass->gudang_canvass,
                    'permissions' => $permissions->collapse()->toArray()
                ];
            } else{
                $res = [
                    'user' => $this->jwt->user(),
                    'token' => $token,
                    'id_gudang' => $user->salesman->tim->depo->id_gudang,
                    'nama_gudang' => $user->salesman->tim->depo->gudang->nama_gudang,
                    'salesman' => $user->salesman->tim,
                    'permissions' => $permissions->collapse()->toArray()
                ];
            }
        } else{
            $res = [
                'user' => $this->jwt->user(),
                'token' => $token,
                'permissions' => $permissions->collapse()->toArray(),
            ];
        }
        
        $res['roles'] = $user->roles->pluck('name');
        return response()->json($res);
        
    }

    public function register(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|max:255|min:2',
            'email' => 'required|email|unique:users|max:190',
            'password' => 'required|confirmed|min:6|max:30',
            'phone' => 'required|unique:users|min:6|max:15',
            'status' => 'in:active,non_active,need_activation',
            'role' => 'required|in:pegawai,accounting,kepala_gudang,salesman,pimpinan,admin',
            'nik' => 'max:12',
        ]);

        $input = $request->except(['password_confirmation']);
        $input['status'] = 'active';
        $input['password'] = app('hash')->make($input['password']);

        try {
            $user = User::create($input);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

        return response()->json([
            'message' => 'Data Pegguna berhasil terdaftar.'
        ], 201);

    }

    public function logout(Request $request)
    {
        $this->jwt->parseToken()->invalidate();
		
        return response()->json([
            'message' => 'Token removed.'
        ], 200);
    }

    public function me()
    {
        return response()->json([
            'user' => $this->jwt->user()
        ], 200);
    }

}