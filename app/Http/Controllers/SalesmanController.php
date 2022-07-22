<?php

namespace App\Http\Controllers;

use App\Http\Resources\SalesmanSimple as SalesmanSimpleResources;
use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;
use App\Models\Salesman;
use App\Http\Resources\Salesman as SalesmanResource;
use App\Helpers\Helper;

class SalesmanController extends Controller
{
    protected $jwt;

    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
    }

    public function index(Request $request)
    {
        // $list_salesman = Salesman::orderBy('user_id', 'asc');
        if($this->user->can('Menu Salesman')):
            $keyword        = $request->has('keyword') ? $request->keyword:'';
            $tipe_salesman  = $request->has('tipe_salesman') ? $request->tipe_salesman:'';
            $id_perusahaan  = $request->has('id_perusahaan') && $request->id_perusahaan <> '' ? [$request->id_perusahaan]:null;
            $id_depo        = $request->has('depo') && $request->depo > 0 ? $request->depo: Helper::depoIDByUser($this->user->id, $id_perusahaan);
            $user_id        = $this->user->id;

            $list_salesman  = Salesman::join('tim', 'salesman.id_tim', '=', 'tim.id')
                ->when($keyword <> '', function($q) use ($keyword) {
                    return $q->whereHas('user', function ($q) use ($keyword){
                        return $q->where('name', 'like', "%{$keyword}%");
                    });
                })
                ->when($tipe_salesman <> '' && $tipe_salesman <> 'all', function ($q) use ($tipe_salesman) {
                    return $q->whereHas('tim', function ($q) use ($tipe_salesman) {
                       return $q->where('tipe', $tipe_salesman);
                    });
                })
                ->when($id_depo <> '', function($q) use ($id_depo) {
                    return $q->whereHas('tim', function ($q) use ($id_depo) {
                       return $q->whereIn('id_depo', $id_depo);
                    });
                })
                ->orderBy('tim.nama_tim');

            if ($this->user->can('Penjualan Tim')) {
                $salesBySupervisor  = Helper::salesBySupervisor($user_id);
                $list_salesman      = $list_salesman->whereIn('user_id', $salesBySupervisor);
            }

            if ($this->user->can('Penjualan Tim Koordinator')) {
                $salesByKoordinator = Helper::salesByKoordinator($user_id);
                $list_salesman      = $list_salesman->whereIn('user_id', $salesByKoordinator);
            }

            $perPage = $request->has('per_page') ? $perPage = $request->per_page : $perPage = 'all';
            $list_salesman = $perPage == 'all' ? $list_salesman->get() : $list_salesman->paginate((int)$perPage);

            if ($list_salesman) {
                return SalesmanResource::collection($list_salesman);
            }
            return response()->json([
                'message' => 'Data Salesman tidak ditemukan!'
            ], 404);
        else:
            return $this->Unauthorized();
        endif;
    }

    public function store(Request $request)
    {
        if($this->user->can('Tambah Salesman')):
            $this->validate($request, [
                'user_id'       => 'required|unique:salesman|exists:users,id',
                'id_tim'        => 'required|exists:tim,id',
                'kode_eksklusif'=> 'nullable',
                'id_principal'  => 'nullable|exists:principal,id'
            ]);

            $input = $request->all();
            Salesman::create($input);
            return response()->json([
                'message' => 'Data Salesman berhasil disimpan.'
            ], 201);
        else:
            return $this->Unauthorized();
        endif;
    }

    public function list_by_depo(Request $request, $id_depo)
    {
        $list_salesman = Salesman::whereHas('tim', function($q) use($id_depo){
            $q->where('id_depo', $id_depo);
        })->get()->sortBy('tim.nama_tim');

        if ($list_salesman) {
            return SalesmanResource::collection($list_salesman);
        }
        return response()->json([
            'message' => 'Data Salesman tidak ditemukan!'
        ], 404);
    }

    public function show($id)
    {
        if($this->user->can('Edit Salesman')):
            $salesman = Salesman::find($id);

            if ($salesman) {
                return new SalesmanResource($salesman);

            }
            return response()->json([
                'message' => 'Data Salesman tidak ditemukan!'
            ], 404);
        else:
            return $this->Unauthorized();
        endif;
    }

    public function update(Request $request, $id)
    {
        if($this->user->can('Update Salesman')):
            $salesman = Salesman::find($id);

            $this->validate($request, [
                'id_tim'        => 'numeric|min:0|max:9999999999',
                'kode_eksklusif'=> 'nullable',
                'id_principal'  => 'nullable|exists:principal,id'
            ]);

            $input = $request->all();

            if ($salesman) {
                $salesman->update($input);

                return response()->json([
                    'message' => 'Data Salesman telah berhasil diubah.'
                ], 201);
            }

            return response()->json([
                'message' => 'Data Salesman tidak ditemukan.'
            ], 404);
        else:
            return $this->Unauthorized();
        endif;
    }

    public function salesman_active(Request $request)
    {
        $messages = [
            'id_perusahaan.required' => 'id perusahaan wajib isi',
            'id_perusahaan.exists' => 'id perusahaan tidak ditemukan'
        ];

        $this->validate($request, [
            'id_perusahaan' => 'required|exists:perusahaan,id'
        ], $messages);

        $id_perusahaan = $request->id_perusahaan;
        $salesman = Salesman::with(['user:id,name', 'tim:id,nama_tim,tipe,id_depo', 'tim.depo:id,nama_depo'])
            ->whereHas('user', function ($q) use ($id_perusahaan) {
                $q->where('status', 'active')
                    ->whereHas('perusahaan', function ($q) use ($id_perusahaan) {
                        $q->where('perusahaan_id', $id_perusahaan);
                    });
            })
            ->get()
            ->sortBy('tim.id_depo');
        return SalesmanSimpleResources::collection($salesman);
    }

    public function salesman_principal(Request $request)
    {
        $id_depo    = $request->has('id_depo') && $request->id_depo != '' ? [$request->id_depo] : Helper::depoIDByUser($this->user->id);
        $id_user    = $request->has('id_user') && $request->id_user != '' ? $request->id_user : '';
        $salesman   = Salesman::with('tim:id,nama_tim,id_depo')
                    ->whereHas('tim', function ($q) use ($id_depo) {
                        $q->whereIn('id_depo', $id_depo);
                    })
                    ->when($id_user <> '', function ($q) use ($id_user) {
                        $q->where('user_id', $id_user);
                    })
                    ->whereNotNull('kode_eksklusif')
                    ->orderBy('user_id', 'desc')
                    ->get();
        return SalesmanSimpleResources::collection($salesman);
    }
}
