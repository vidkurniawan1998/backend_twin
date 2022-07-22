<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PosisiStock extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $fisikQty = (int) (($this->saldo_akhir_qty + $this->penjualan_qty + $this->mutasi_pending_qty + $this->pending_qty));
        $fisikPcs = (int) (($this->saldo_akhir_pcs + $this->penjualan_pcs + $this->mutasi_pending_pcs + $this->pending_pcs));
        $isi = $this->stock->isi;

        while($fisikPcs >= $isi) {
            $fisikQty += 1;
            $fisikPcs -= $isi;
        }


        $awalQty = (int) $this->saldo_awal_qty;
        $awalPcs = (int) $this->saldo_awal_pcs;
        while($awalPcs >= $isi) {
            $awalQty += 1;
            $awalPcs -= $isi;
        }

        $penjualanQty = (int) $this->penjualan_qty;
        $penjualanPcs = (int) $this->penjualan_pcs;
        while($penjualanPcs >= $isi) {
            $penjualanQty += 1;
            $penjualanPcs -= $isi;
        }

        $akhirQty = (int) $this->saldo_akhir_qty;
        $akhirPcs = (int) $this->saldo_akhir_pcs;
        while($akhirPcs >= $isi) {
            $akhirQty += 1;
            $akhirPcs -= $isi;
        }

        $adjQty = (int) $this->adjustment_qty;
        $adjPcs = (int) $this->adjustment_pcs;
        $totalAdj = ($adjQty * $isi) + $adjPcs;

        if($totalAdj < 0) {
            $adjQty = $adjQty * -1;
            $adjPcs = $adjPcs * -1;
        }

        while($adjPcs >= $isi) {
            $adjQty += 1;
            $adjPcs -= $isi;
        }

        if($totalAdj < 0) {
            $adjQty = $adjQty * -1;
            $adjPcs = $adjPcs * -1;
        }

        $deliverQty = (int) $this->deliver_qty;
        $deliverPcs = (int) $this->deliver_pcs;
        while($deliverPcs >= $isi) {
            $deliverQty += 1;
            $deliverPcs -= $isi;
        }

        while($fisikPcs < 0) {
            $fisikQty -= 1;
            $fisikPcs += $isi;
        }

        return [
            // 'id' => $this->id,
            'id_gudang' => $this->id_gudang,
            'nama_gudang' => $this->nama_gudang,
            'tanggal' => $this->tanggal,
            'id_stock' => $this->id_stock,
            'kode_barang' => $this->kode_barang,
            'nama_barang' => $this->nama_barang,
            'nama_segmen' => $this->nama_segmen,
            'saldo_awal_qty' => $awalQty,
            'saldo_awal_pcs' => $awalPcs,
            'pembelian_qty' => (int) $this->pembelian_qty,
            'pembelian_pcs' => (int) $this->pembelian_pcs,
            'mutasi_masuk_qty' => (int) $this->mutasi_masuk_qty,
            'mutasi_masuk_pcs' => (int) $this->mutasi_masuk_pcs,
            'adjustment_qty' => $adjQty,
            'adjustment_pcs' => $adjPcs,
            'penjualan_qty' => $penjualanQty,
            'penjualan_pcs' => $penjualanPcs,
            'penjualan_pending_qty' => (int) $this->pending_qty + $deliverQty,
            'penjualan_pending_pcs' => (int) $this->pending_pcs + $deliverPcs,
            'deliver_qty' => $deliverQty,
            'deliver_pcs' => $deliverPcs,
            'mutasi_keluar_qty' => (int) $this->mutasi_keluar_qty,
            'mutasi_keluar_pcs' => (int) $this->mutasi_keluar_pcs,
            'mutasi_pending_qty' => (int) $this->mutasi_pending_qty,
            'mutasi_pending_pcs' => (int) $this->mutasi_pending_pcs,
            'saldo_akhir_qty' => $akhirQty,
            'saldo_akhir_pcs' => $akhirPcs,
            'saldo_fisik_qty' => $fisikQty,
            'saldo_fisik_pcs' => $fisikPcs,
            'harga' => round(($this->harga / 1.1), 2),
            'nilai_stock' => round(($this->nilai_stock / 1.1), 2),
            'nama_brand' => $this->nama_brand,
            'isi' => $this->stock->isi
        ];
    }
}
