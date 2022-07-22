<?php


namespace App\Http\Controllers;
use App\Models\GrupTokoLogistik;
use App\Models\Toko;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\GrupTokoLogistik as GrupTokoLogistikResources;

class GrupTokoLogistikController extends Controller
{
    protected $user, $modul;

    public function __construct()
    {
        $this->user = Auth::user();
        $this->modul= 'grup toko logistik';
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $keyword    = $request->keyword;
        $perPage    = $request->per_page ?? 'all';
        $grup_toko  = GrupTokoLogistik::with(['toko', 'toko.depo'])
            ->when($keyword <> '', function ($q) use ($keyword) {
                $q->where('nama_grup', 'like', "%{$keyword}%")
                    ->orWhereHas('toko', function ($q) use ($keyword) {
                        $q->where('nama_toko', 'like', "%{$keyword}%")
                            ->orWhere('no_acc', 'like', "%{$keyword}%");
                    });
            })
            ->orderBy('id', 'desc');
        $grup_toko  = $perPage === 'all' ? $grup_toko->get() : $grup_toko->paginate($perPage);
        return GrupTokoLogistikResources::collection($grup_toko);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'nama_grup' => 'required',
            'id_toko'   => 'required|array'
        ]);

        DB::beginTransaction();
        try {
            $input['nama_grup']  = $request->nama_grup;
            $input['created_by'] = $this->user->id;
            $grup       = GrupTokoLogistik::create($input);
            $id_toko    = $request->id_toko;
            foreach ($id_toko as $key => $id) {
                $toko = Toko::find($id);
                if (!$toko) {
                    throw new ModelNotFoundException('Data toko tidak ditemukan');
                }

                $toko->update(['id_grup_logistik' => $grup->id]);
            }
            DB::commit();
            return $this->storeTrue($this->modul);
        }catch (\Exception $e) {
            DB::rollBack();
            return $this->storeFalse($this->modul);
        }

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $grup_toko = GrupTokoLogistik::find($id);
        if (!$grup_toko) {
            return $this->dataNotFound($this->modul);
        }

        $data = [
            'nama_grup' => $request->nama_grup
        ];

        return $grup_toko->update($data) ? $this->updateTrue($this->modul) : $this->updateFalse($this->modul);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $grup_toko = GrupTokoLogistik::find($id);
        if (!$grup_toko) {
            return $this->dataNotFound('Grup toko logistik');
        }

        try {
            DB::beginTransaction();
            Toko::where('id_grup_logistik', $grup_toko->id)->update(['id_grup_logistik' => null]);
            $grup_toko->delete();
            DB::commit();
            return $this->destroyTrue($this->modul);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->destroyFalse($this->modul);
        }
    }

    public function deleteGrupToko($id_toko)
    {
        $toko = Toko::find($id_toko);
        return $toko->update(['id_grup_logistik' => null]) ? $this->updateTrue($this->modul) : $this->updateFalse($this->modul);
    }
}