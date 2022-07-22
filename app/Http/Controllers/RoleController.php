<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Helpers\Helper;
use App\Http\Resources\Role as RoleResource;
use App\Models\Role;
use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;

class RoleController extends Controller
{
    protected $jwt;

    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
    }

    public function index(Request $request)
    {
        if ($this->user->can('Menu Role')):
            $keyword = $request->keyword ?? '';
            $perPage = $request->per_page ?? 'all';
            $data = Role::latest();
            
            if ($keyword <> '') {
                $data->where('name', 'like', '%'.$keyword.'%');
            }
            
            $listRoles = $perPage === 'all' ? $data->get():$data->paginate((int) $perPage);
            if ($listRoles) {
                return RoleResource::collection($listRoles);
            }

            return response()->json([
                'message' => 'Data Role tidak ditemukan!'
            ], 404);
        else :
            return $this->Unauthorized();
        endif;
    }

    public function store(Request $request)
    {
        if ($this->user->can('Tambah Role')):
            $this->validate($request, [
                'name' => 'required|unique:roles',
                'permissions' => 'required'
            ]);
    
            $role = new Role();
            $role->name = $request->name;
            $role->save();
            
            try {
                $role->permissions()->attach($request->permissions);
                return response()->json(['message' => 'Role '.$request->name.' berhasil ditambahkan'], 201);
            } catch (\Illuminate\Database\QueryException $e) {
                return response()->json(['message' => 'Role '.$request->name.' gagal ditambahkan'], 400);
            }
        else:
            return $this->Unauthorized();
        endif;
    }

    public function edit($id)
    {
        if ($this->user->can('Edit Role')):
            $role = Role::with('permissions')->find($id);
            if ($role) {
                return new RoleResource($role);
            } else {
                return response()->json([
                    'message' => 'Data Role tidak ditemukan!'
                ], 404);
            }
        else:
            return $this->Unauthorized();
        endif;
    }

    public function update(Request $request, $id)
    {
        if ($this->user->can('Update Role')):
            $this->validate($request, [
                'name' => 'required', 
                'permissions' => 'required'
            ]);

            try {
                $role = Role::find($id);
                $role->name = $request->name;
                $role->update();
                $role->permissions()->detach();
                $role->permissions()->attach($request->permissions);
                return response()->json(['message' => 'Role '.$role->name.' berhasil diubah'], 201);
            } catch (\Illuminate\Database\QueryException $e) {
                return response()->json(['message' => 'Role '.$request->name.' gagal diubah'], 400);
            }
        else:
            return $this->Unauthorized();
        endif;
    }

    public function destroy($id)
    {
        if ($this->user->can('Hapus Role')):
            $role = Role::find($id);
            if ($role) {
                $role->delete();
                return response()->json(['message' => 'Role berhasil dihapus'], 200);
            } else {
                return response()->json(['message' => 'Role gagal dihapus'], 400);
            }
        else :
            return $this->Unauthorized();
        endif;
    }
}
