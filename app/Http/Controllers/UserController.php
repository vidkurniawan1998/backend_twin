<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;
use App\Models\User;
use App\Http\Resources\User as UserCollection;
use App\Models\KepalaGudang;
use App\Models\Driver;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    protected $jwt;

    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
    }

    public function index(Request $request)
    {
        if ($this->user->can('Menu User')):
            $keyword = $request->has('keyword') ? $request->keyword:'';
            $status = $request->has('status') ? $request->status:'';
            $list_user = User::with(['perusahaan', 'roles', 'depo', 'gudang'])
                        ->when($keyword <> '', function($q) use ($keyword) {
                            return $q->where('name', 'like', "%{$keyword}%")
                                    ->orWhere('email', 'like', "%{$keyword}%");
                        })
                        ->when($status <> '', function($q) use ($status) {
                            return $q->where('status', $status);
                        })
                        ->orderBy('role', 'asc');

            $perPage = $request->has('per_page') ? $perPage = $request->per_page : $perPage = 'all';

            $list_user = $perPage == 'all' ? $list_user->get() : $list_user->paginate((int)$perPage);

            if ($list_user) {
                return UserCollection::collection($list_user);
            }
            return response()->json([
                'message' => 'Data Pengguna tidak ditemukan!'
            ], 404);
        else:
            return $this->Unauthorized();
        endif;
    }

    public function update_status($id)
    {
        $statusArr = [
            'active' => 'non_active',
            'non_active' => 'active'
        ];

        $user = User::find($id);

        if(!$user)
        {
            return response()->json(['message' => 'Data Tidak Ditemukan'], 404);
        }

        try
        {
            $user->status = $statusArr[$user->status];
            $user->save();
        }
        catch (\Exception $e)
        {
            return response()->json(['message' => 'Data Gagal Diubah'], 500);
        }

        return response()->json(['message' => 'Data Berhasil Diubah'], 200);
    }

    public function store(Request $request)
    {
        if ($this->user->can('Tambah User')):
            $this->validate($request, [
                'name'      => 'required|max:255|min:2',
                'email'     => 'required|email|unique:users|max:190',
                'password'  => 'required|confirmed|min:6|max:30',
                'phone'     => 'nullable|unique:users|max:15',
                'status'    => 'in:active,non_active,need_activation',
                'roles'     => 'required',
                'nik'       => 'max:16|nullable',
                'depo'      => 'required',
                'perusahaan'=> 'required'
            ]);


            $input = $request->except(['password_confirmation', 'roles', 'depo']);
            $input['status'] = 'active';
            $input['password'] = app('hash')->make($input['password']);
            // $input = ['created_by' => $this->user->id];

            try {
                $user = User::create($input);
                $user->perusahaan()->attach($request->perusahaan);
                $user->roles()->attach($request->roles);
                $user->depo()->attach($request->depo);
                $user->gudang()->attach($request->gudang);

                if($user->hasRole('Kepala Gudang')) {
                    $data_kepala_gudang['user_id'] = $user->id;
                    $kepala_gudang = KepalaGudang::create($data_kepala_gudang);
                }
                elseif($user->hasRole('Driver')) {
                    $data_driver['user_id'] = $user->id;
                    $driver = Driver::insert($data_driver);
                }
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 400);
            }

            return response()->json([
                'message' => 'Data Pegguna berhasil disimpan.'
            ], 201);
        else:
            return $this->Unauthorized();
        endif;
    }

    public function show($id)
    {
        $user = User::find($id);

        if($this->user->hasRole('Salesman') || $this->user->hasRole('Salesman Canvass')){
            if($this->user->id != $id){
                return response()->json([
                    'message' => 'Anda tidak berhak untuk mengubah profil pengguna lain!'
                ], 400);
            }
        }

        if ($user) {
            return $user;
        }
        return response()->json([
            'message' => 'Data Pengguna tidak ditemukan!'
        ], 404);
    }

    public function update(Request $request, $id)
    {
        if ($this->user->can('Update User')):
            $this->validate($request, [
                'name'      => 'required|max:255|min:2',
                'email'     => 'required|email|max:190|unique:users,email,' . $id,
                'phone'     => 'nullable|max:15|unique:users,phone,' . $id,
                'status'    => 'required|in:active,non_active,need_activation',
                'roles'     => 'required',
                'nik'       => 'max:16|nullable',
                'depo'      => 'required',
                'perusahaan'=> 'required'
            ]);

            $user = User::find($id);
            $input = $request->except(['password_confirmation', 'roles', 'depo']);

            if ($user) {
                $user->update($input);
                $user->perusahaan()->sync($request->perusahaan);
                $user->roles()->sync($request->roles);
                $user->depo()->sync($request->depo);
                $user->gudang()->sync($request->gudang);
                return response()->json([
                    'message' => 'Data Pengguna telah berhasil diubah.',
                    'data' => $user
                ], 201);
            }

            return response()->json([
                'message' => 'Data Pengguna tidak ditemukan.'
            ], 404);
        else:
            return $this->Unauthorized();
        endif;
    }

    public function destroy($id)
    {
        if ($this->user->can('Hapus User')):
            $user = User::find($id);

            if($user) {
                $user->delete();
                return response()->json([
                    'message' => 'Data Pengguna berhasil dihapus.'
                ], 200);
            }

            return response()->json([
                'message' => 'Data Pengguna tidak ditemukan!'
            ], 404);
        else:
            return $this->Unauthorized();
        endif;
    }

    public function restore($id)
    {
        if ($this->user->can('Tambah User')):
            $user = User::withTrashed()->find($id);

            if($user) {
                $user->restore();
                return response()->json([
                    'message' => 'Data Pengguna berhasil dikembalikan.'
                ], 200);
            }

            return response()->json([
                'message' => 'Data Pengguna tidak ditemukan!'
            ], 404);
        else:
            return $this->Unauthorized();
        endif;
    }

    public function change_password(Request $request, $id)
    {
        // if ($this->user->can('Update User')):
            $user = User::findOrFail($id);
            $this->validate($request, [
                'password' => 'required|confirmed|min:6|max:30',
            ]);

            $input = $request->except(['password_confirmation']);

            $input['password'] = app('hash')->make($input['password']);

            if ($user) {
                $user->update($input);

                return response()->json([
                    'message' => 'Password Pengguna telah berhasil diubah.'
                ], 201);
            }

            return response()->json([
                'message' => 'Data Pengguna tidak ditemukan.'
            ], 404);
        // else:
        //     return $this->Unauthorized();
        // endif;
    }

    public function changeMyPassword(Request $request){
        $input = $request->except(['password_confirmation']);
        $user = User::findOrFail($this->user->id);

        if (Hash::check($input['old_password'], $user->password)) {
            $this->validate($request, [
                'password' => 'required|confirmed|min:6|max:15',
            ]);

            $input['password'] = app('hash')->make($input['password']);

            if ($user) {
                $user->update($input);

                return response()->json([
                    'message' => 'Password berhasil diperbarui.'
                ], 201);
            }
        }

        return response()->json([
            'message' => 'Password lama tidak valid.'
        ], 404);
    }

    public function addRole(Request $request)
    {
        if ($this->user->can('Update user')) {
            $this->validate($request, [
                'user_id'=> 'required|exists:users,id',
                'roles'  => 'required'
            ]);

            $user = User::find($request->user_id);
            try {
                $user->roles()->attach($request->roles);
                return response()->json(['message' => 'Berhasil menambahkan role user'], 201);
            } catch (\Exception $e) {
                // dd($e->getMessage());
                return response()->json(['message' => 'Gagal menambahkan user role'], 400);
            }
        } else {
            abort(403, 'Unauthorized action.');
        }
    }
}
