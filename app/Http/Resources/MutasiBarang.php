<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Gudang;
use App\Http\Resources\Gudang as GudangResource;
use App\Models\Reference;

class MutasiBarang extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $dari = Gudang::find($this->dari);
        $ke = Gudang::find($this->ke);
        $reference = Reference::where('code', 'print_mutasi_ttd_principal')->select('value')->first()->value;
        $ttd_principal = explode(',', $reference);
        $is_ttd_principal = in_array($this->ke, $ttd_principal) ? true : false;

        return [
            'id' => $this->id,
            'tanggal_mutasi' => $this->tanggal_mutasi,
            'dari' => [
                new GudangResource($dari),
            ],
            'ke' => [
                new GudangResource($ke),
            ],
            'keterangan'  => $this->keterangan,
            'is_approved' => $this->is_approved,
            'status'      => $this->status,

            'total_qty' => $this->total_qty,
            'total_pcs' => $this->total_pcs,

            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            // 'deleted_by' => $this->deleted_by,
            'created_at' => (string) $this->created_at,
            'updated_at' => (string) $this->updated_at,
            'is_ttd_principal' => $is_ttd_principal,
            // 'deleted_at' => (string) $this->deleted_at,
        ];
    }
}
