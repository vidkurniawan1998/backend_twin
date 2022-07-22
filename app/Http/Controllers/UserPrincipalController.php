<?php


namespace App\Http\Controllers;


use App\Http\Resources\UserPrincipal;
use App\Models\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;

class UserPrincipalController extends Controller
{
    protected $jwt, $user;

    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
    }

    public function index(Request $request)
    {
        if (!$this->user->can('Menu Akses Principal')) {
            return $this->Unauthorized();
        }

        $keyword    = $request->keyword;
        $per_page   = $request->per_page ?? 'all';
        $data       = User::with('principal')
            ->when($keyword <> '', function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%");
            })
            ->orderBy('name');
        $data = $per_page === 'all' ? $data->get() : $data->paginate($per_page);
        return UserPrincipal::collection($data);
    }

    public function store(Request $request)
    {
        if ($this->user->can('Tambah Akses Principal')) {
            return $this->Unauthorized();
        }

        $messages = [
            'user_id.required'  => 'user wajib isi',
            'user_id.exists'    => 'user tidak ditemukan',
            'principal_id.required' => 'principal wajib isi',
            'principal_id.array'=> 'principal tidak valid'
        ];

        $this->validate($request, [
            'user_id'       => 'required|exists:users,id',
            'principal_id'  => 'required|array|min:1'
        ], $messages);

        $user = User::find($request->user_id);
        return $user->principal()->sync($request->principal_id)
            ? $this->storeTrue('akses principal')
            : $this->storeFalse('akses principal');
    }

    public function edit(Request $request, $id)
    {
        if (!$this->user->can('Edit Akses Principal')) {
            return $this->Unauthorized();
        }

        $user = User::find($id);
        if (!$user) {
            return $this->dataNotFound('user');
        }

        return new UserPrincipal($user->load('principal'));
    }

    public function update(Request $request)
    {
        if (!$this->user->can('Update Akses Principal')) {
            return $this->Unauthorized();
        }

        $messages = [
            'id.required'           => 'user wajib isi',
            'id.exists'             => 'user tidak ditemukan',
            'principal_id.required' => 'principal wajib isi',
            'principal_id.array'    => 'principal tidak valid'
        ];

        $this->validate($request, [
            'id'            => 'required|exists:users,id',
            'principal_id'  => 'required|array|min:1'
        ], $messages);

        $user = User::find($request->id);
        return $user->principal()->sync($request->principal_id)
            ? $this->updateTrue('akses principal')
            : $this->updateFalse('akses principal');
    }

    public function destroy(Request $request, $id)
    {
        if (!$this->user->can('Delete Akses Principal')) {
            return $this->Unauthorized();
        }

        $user = User::find($id);
        return $user->principal()->detach()
            ? $this->destroyTrue('akses principal')
            : $this->destroyFalse('akses principal');
    }
}