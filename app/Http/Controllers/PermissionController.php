<?php 

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\Permission as PermissionResource;
use App\Models\Permission;
use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;

class PermissionController extends Controller
{
    protected $jwt;

    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
    }

    public function index(Request $request)
    {
        if ($this->user->can('Menu Permission')):
            $keyword = $request->keyword ?? '';
            $perPage = $request->per_page ?? 'all';
            $data = Permission::latest();
            
            if ($keyword <> '') {
                $data->where('name', 'like', '%'.$keyword.'%');
            }
            
            $listPermissions = $perPage === 'all' ? $data->get():$data->paginate((int) $perPage);
            if ($listPermissions) {
                return PermissionResource::collection($listPermissions);
            }

            return response()->json([
                'message' => 'Data Permission tidak ditemukan!'
            ], 404);
        else :
            return $this->Unauthorized();
        endif;
    }

    public function store(Request $request)
    {
        if ($this->user->can('Tambah Permission')):
            $this->validate($request, [
                'name' => 'required|unique:permissions'
            ]);

            $permission = new Permission();
            $permission->name = $request->name;
            $permission->description = $request->description;
            if ($permission->save()) {
                return response()->json(['message' => 'Permission '.$request->name.' berhasil ditambahkan'], 201);
            } else {
                return response()->json(['message' => 'Permission '.$request->name.' gagal ditambahkan'], 400);
            }
        else:
            return $this->Unauthorized();
        endif;
    }

    public function edit($id)
    {
        if ($this->user->can('Edit Permission')):
            $permission = Permission::find($id);
            if ($permission) {
                return new PermissionResource($permission);
            } else {
                return response()->json([
                    'message' => 'Data Permission tidak ditemukan!'
                ], 404);
            }
        else:
            return $this->Unauthorized();
        endif;
    }

    public function update(Request $request, $id)
    {
        if ($this->user->can('Update Permission')):
            $this->validate($request, [
                'name' => 'required'
            ]);

            $permission = Permission::find($id);
            $permission->name = $request->name;
            $permission->description = $request->description;
            if ($permission->update()) {
                return response()->json(['message' => 'Permission '.$request->name.' berhasil diubah'], 201);
            } else {
                return response()->json(['message' => 'Permission '.$request->name.' gagal diubah'], 400);
            }
        else:
            return $this->Unauthorized();
        endif;
    }

    public function destroy($id)
    {
        if ($this->user->can('Hapus Permission')):
            $permission = Permission::find($id);
            if ($permission) {
                $permission->delete();
                return response()->json(['message' => 'Permission berhasil dihapus'], 200);
            } else {
                return response()->json([
                    'message' => 'Data Permission tidak ditemukan!'
                ], 404);
            }
        else:
            return $this->Unauthorized();
        endif;
    }
}
