<?php

namespace App\Http\Controllers;

use App\Http\Requests\PromoStoreRequest;
use App\Http\Requests\PromoUpdateRequest;
use App\Models\Penjualan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\JWTAuth;
use App\Models\Promo;
use App\Http\Resources\Promo as PromoResource;
use App\Helpers\Helper;

class PromoController extends Controller
{
    protected $jwt;
    public function __construct(JWTAuth $jwt){
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
    }

    public function index(Request $request)
    {
        if ($this->user->can('Menu Promo')):
            $user_id        = $this->user->id;
            $id_perusahaan  = $request->has('id_perusahaan') && $request->id_perusahaan <> '' ? [$request->id_perusahaan] : Helper::perusahaanByUser($user_id);
            $depo_user      = $request->has('depo') && $request->depo ? $request->depo : Helper::depoIDByUser($user_id, $id_perusahaan);
            $jenis_salesman = $request->has('jenis_salesman') ? $request->jenis_salesman : '';
            $keyword        = $request->has('keyword') ? $request->keyword:'';
            $status         = $request->has('status') ? $request->status:'';
            $list_promo     = Promo::with('depo', 'perusahaan')
                        ->whereHas('depo', function ($query) use ($depo_user){
                            $query->whereIn('depo_id',$depo_user);
                        })
                        ->when($jenis_salesman <> '', function ($q) use ($jenis_salesman) {
                            if ($jenis_salesman === 'all') {
                                $q->whereIn('salesman', ['all', 'to', 'canvass']);
                            } elseif ($jenis_salesman === 'to') {
                                $q->whereIn('salesman', ['all', 'to']);
                            } else {
                                $q->whereIn('salesman', ['all', 'canvass']);
                            }
                        })
                        ->when($this->user->hasRole('Salesman'), function ($q) {
                            $q->whereIn('salesman', ['all', 'to']);
                        })
                        ->when($this->user->hasRole('Salesman Canvass'), function ($q) {
                            $q->whereIn('salesman', ['all', 'canvass']);
                        })
                        ->when($status <> '' && $status <> 'all', function ($q) use ($status) {
                            $q->where('status', $status);
                        })
                        ->when($keyword <> '', function ($q) use ($keyword){
                            $q->where(function ($q) use ($keyword) {
                                $q->where('nama_promo', 'like', '%' . $keyword . '%')
                                    ->orWhere('no_promo', 'like', '%' . $keyword . '%')
                                    ->orWhere('keterangan', 'like', '%' . $keyword . '%');
                            });
                        })->where('id', '<>', 0);

            if (!$this->user->can('Semua Status Promo'))
            {
                $list_promo = $list_promo->where('status', 'active');
            }

            if ($this->user->hasRole('Salesman'))
            {
                $id_principal = $this->user->salesman->id_principal;
                if ($id_principal) {
                    $list_promo = $list_promo->where('id_principal', '=', $id_principal);
                }
            }

            $list_promo->orderBy('id', 'desc');

            $perPage = $request->has('per_page') ? $perPage = $request->per_page : $perPage = 'all';
            $list_promo = $perPage == 'all' ? $list_promo->get() : $list_promo->paginate((int)$perPage);

            if ($list_promo) {
                return PromoResource::collection($list_promo);
            }
            return response()->json([
                'message' => 'Data Promo tidak ditemukan!'
            ], 404);
        else:
            return $this->Unauthorized();
        endif;
    }

    public function aktif(Request $request)
    {
        $id_penjualan   = $request->has('id_penjualan') && $request->id_penjualan <> '' ? $request->id_penjualan :'';
        $id_principal   = '';
        $depo_user      = [];
        if ($id_penjualan <> '') {
            $penjualan      = Penjualan::find($id_penjualan);
            $id_principal   = $penjualan->salesman->id_principal;
            $id_perusahaan  = [$penjualan->id_perusahaan];
            $depo_user      = Helper::depoIDByUser($this->user->id, $id_perusahaan);
        } else {
            $id_perusahaan  = Helper::perusahaanByUser($this->user->id);
            $depo_user      = Helper::depoIDByUser($this->user->id, $id_perusahaan);
        }

        $list_promo     = Promo::orderBy('id', 'desc')->where('status', 'active')->where('id', '<>', 0)
            ->when($id_principal <> '' && $id_principal <> null, function ($q) use ($id_principal) {
                $q->where('id_principal', $id_principal);
            })
            ->whereHas('depo', function ($query) use ($depo_user){
                $query->whereIn('depo_id',$depo_user);
            })
            ->get();

        if ($list_promo) {
            return PromoResource::collection($list_promo);
        }

        return $this->dataNotFound('promo');
    }

    public function store(PromoStoreRequest $request)
    {
        if ($this->user->can('Tambah Promo')):
            $input = $request->except('disc_persen', 'depo');
            $input['created_by'] = $this->user->id;
            // DISKON BERTINGKAT (max 4)
            $arr    = $request->disc_persen;
            $disc   = 0;
            $nominal= 1;
            $depo   = $request->has('depo') ? $request->depo : [];
            for ($i = 0; $i < count($arr); $i++) {
                $input['disc_'.($i+1)] = $arr[$i];
                $value = $nominal * ($arr[$i]/100);
                $nominal-=$value;
                $disc+=$value;
            }

            $input['disc_rupiah']   = $input['disc_rupiah_distributor'] + $input['disc_rupiah_principal'];
            $input['disc_persen']   = $disc*100;
            $input['status_klaim']  = ''.$input['status_klaim'];
            $barang                 = $request->barang;
            $promoBarang= [];
            foreach ($barang as $brg) {
                $promoBarang[$brg['id']] = [
                    'volume' => $brg['volume'],
                    'bonus_pcs' => $brg['bonus_pcs']
                ];
            }

            $toko = $request->toko;
            $promoToko = [];
            foreach ($toko as $tk) {
                $promoToko[] = $tk['id'];
            }

            DB::beginTransaction();
            try {
                $promo = Promo::create($input);
                $promo->depo()->attach($depo);
                $promo->promo_barang()->attach($promoBarang);
                $promo->promo_toko()->attach($promoToko);
                DB::commit();
                return $this->storeTrue('promo');
            } catch (\Exception $e) {
                DB::rollBack();
                return $this->storeFalse('promo');
            }
        else:
            return $this->Unauthorized();
        endif;
    }

    public function show($id)
    {
        if ($this->user->can('Edit Promo')):
            $promo = Promo::with(['perusahaan', 'depo', 'promo_toko', 'promo_barang'])->find($id);
            if ($promo) {
                return new PromoResource($promo);
            }
            return $this->dataNotFound('promo');
        else:
            return $this->Unauthorized();
        endif;
    }

    public function update(PromoUpdateRequest $request, $id)
    {
        if ($this->user->can('Update Promo')):
            $promo = Promo::find($id);
            if ($promo) {
                $input = $request->except('disc_persen', 'depo');
                $input['updated_by'] = $this->user->id;

                // DISKON BERTINGKAT
                $arr = $request->disc_persen;
                $disc   = 0;
                $nominal= 1;
                for ($i=0; $i < count($arr); $i++) {
                    $input['disc_'.($i+1)] = $arr[$i];
                    $value = $nominal * ($arr[$i]/100);
                    $nominal-=$value;
                    $disc+=$value;
                }

                $input['disc_rupiah']   = $input['disc_rupiah_distributor'] + $input['disc_rupiah_principal'];
                $input['disc_persen']   = $disc*100;
                $input['status_klaim']  = ''.$input['status_klaim'];
                $depo  = $request->has('depo') ? $request->depo:[];

                $barang     = $request->barang;
                $promoBarang= [];
                foreach ($barang as $brg) {
                    $promoBarang[$brg['id']] = [
                        'volume' => $brg['volume'],
                        'bonus_pcs' => $brg['bonus_pcs']
                    ];
                }

                $toko = $request->toko;
                $promoToko = [];
                foreach ($toko as $tk) {
                    $promoToko[] = $tk['id'];
                }

                DB::beginTransaction();
                try {
                    $promo->update($input);
                    // REMOVE PIVOT
                    $promo->depo()->detach();
                    $promo->promo_barang()->detach();
                    $promo->promo_toko()->detach();
                    //INSERT NEW PIVOT
                    $promo->depo()->attach($depo);
                    $promo->promo_barang()->attach($promoBarang);
                    $promo->promo_toko()->attach($promoToko);
                    DB::commit();
                    return $this->updateTrue('promo');
                } catch (\Exception $e) {
                    DB::rollBack();
                    return $this->updateFalse('promo');
                }
            }

            return $this->dataNotFound('promo');
        else:
            return $this->Unauthorized();
        endif;
    }

    public function destroy($id)
    {
        if ($this->user->can('Hapus Promo')):
            $promo = Promo::find($id);
            if($promo) {
                $data = ['deleted_by' => $this->user->id];
                $promo->update($data);
                $promo->delete();

                return response()->json([
                    'message' => 'Data Promo berhasil dihapus.'
                ], 200);
            }

            return $this->dataNotFound('promo');
        else:
            return $this->Unauthorized();
        endif;
    }

    public function restore($id)
    {
        if ($this->user->can('Tambah Promo')):
            $promo = Promo::withTrashed()->find($id);
            if($promo) {
                $data = ['deleted_by' => null];
                $promo->update($data);
                $promo->restore();

                return response()->json([
                    'message' => 'Data Promo berhasil dikembalikan.'
                ], 200);
            }

            return $this->dataNotFound('promo');
        else:
            return $this->Unauthorized();
        endif;
    }

    public function duplicate($id)
    {
        $promo = Promo::find($id);
        if (!$promo) {
            return $this->dataNotFound('promo');
        }

        $clone = $promo->replicate();
        $clone->push();

        foreach ($promo->depo as $depo) {
            $clone->depo()->attach($depo);
        }

        foreach ($promo->promo_toko as $toko) {
            $clone->promo_toko()->attach($toko);
        }

        foreach ($promo->promo_barang as $barang) {
            $clone->promo_barang()->attach($barang);
        }

        return $this->storeTrue('promo');
    }

}
