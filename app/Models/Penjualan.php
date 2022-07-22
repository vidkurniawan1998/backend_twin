<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\DetailPelunasanPenjualan;

class Penjualan extends Model
{
    use SoftDeletes;

    protected $table = 'penjualan';

    protected $fillable = [
        'po_manual',
        'no_invoice',
        'no_pajak',
        'id_toko',
        'top',
        'id_salesman',
        'id_tim',
        'tanggal',
        'tanggal_invoice',
        'tipe_pembayaran',
        'tipe_harga',
        'keterangan',
        'status',
        'pending_status',
        'due_date',
        'paid_at',
        'id_pengiriman',
        'id_retur',
        'print_count',
        'approved_at',
        'approved_by',
        'loading_at',
        'loading_by',
        'delivered_at',
        'delivered_by',
        'latitude',
        'longitude',
        'created_by',
        'updated_by',
        'deleted_by',
        'id_gudang',
        'id_depo',
        'id_perusahaan',
        'tanggal_jadwal',
        'driver_id',
        'checker_id',
        'import',
        'created_at',
        'remark_close',
        'closed_by',
        'id_mitra',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'delivered_at'
    ];

    protected $casts = [
        'top' => 'integer',
        'grand_total' => 'float',
        'id_mitra'=> 'integer'
    ];

    public function detail_penjualan(){
        return $this->hasMany('App\Models\DetailPenjualan', 'id_penjualan');
    }

    public function detail_pelunasan_penjualan(){
        return $this->hasMany('App\Models\DetailPelunasanPenjualan', 'id_penjualan');
    }

    public function detail_penjualan_terkirim(){ // hanya menampilkan item yang terkirim, qty dan qty_pcs = 0 tidak tampil
        return $this->hasMany('App\Models\DetailPenjualan', 'id_penjualan')->where('qty', '!=', 0)->orWhere('qty_pcs', '!=', 0);
    }

    public function pengiriman(){
        return $this->belongsTo('App\Models\Pengiriman', 'id_pengiriman')->withTrashed();
    }

    public function tim(){
        return $this->belongsTo('App\Models\Tim', 'id_tim', 'id')->withTrashed();
    }

    public function driver(){
        return $this->belongsTo('App\Models\Driver', 'driver_id')->withTrashed();
    }

    public function checker(){
        return $this->belongsTo('App\Models\User', 'checker_id')->withTrashed();
    }

    public function retur_barang(){
        return $this->belongsTo('App\Models\ReturBarang', 'id_retur')->withTrashed();
    }

    public function toko(){
        return $this->belongsTo('App\Models\Toko', 'id_toko')->withTrashed();
    }

    public function salesman(){
        return $this->belongsTo('App\Models\Salesman', 'id_salesman');
    }

    public function mitra()
    {
        return $this->belongsTo('App\Models\Mitra', 'id_mitra', 'id');
    }

    public function gudang(){
        // // jika salesman canvas, arahkan ke gudang canvassnya, bukan ke gudang baik
        // return $this->belongsTo('App\Models\Gudang', 'dari_gudang')->withTrashed();

        // if($this->tim->tipe == 'canvass'){
        //     $id_gudang = optional($this->tim->canvass)->id_gudang_canvass;
        // }
        // else{
        //     $id_gudang = optional($this->tim->depo)->id_gudang;
        // }

        // if ($this->id_gudang) {
        //     $id_gudang = $this->id_gudang;
        // }

        return $this->belongsTo('App\Models\Gudang', 'id_gudang')->withTrashed();
    }

    public function depo() {
        return $this->belongsTo('App\Models\Depo', 'id_depo')->withTrashed();
    }

    public function getNamaGudangAttribute(){
        if($this->salesman->tim->tipe == 'canvass'){
            $nama_gudang = optional($this->salesman->tim->canvass->gudang_canvass)->nama_gudang;
        }
        else{
            $nama_gudang = optional($this->salesman->tim->depo->gudang)->nama_gudang;
        }

        return $nama_gudang;
    }

    public function getNamaTimAttribute(){
        return optional($this->tim)->nama_tim;
    }

    public function getNamaTokoAttribute(){
        return optional($this->toko)->nama_toko;
    }

    public function getNoAccAttribute(){
        return optional($this->toko)->no_acc;
    }

    public function getCustNoAttribute(){
        return optional($this->toko)->cust_no;
    }

    // public function getDueDateAttribute(){
    //     // $due_date = \Carbon\Carbon::parse($this->tanggal);
    //     // if($this->tipe_pembayaran == 'credit'){
    //     //     $due_date = $due_date->addDays(15);
    //     // }
    //     // elseif($this->tipe_pembayaran == 'cash'){
    //     //     $due_date = $due_date->addDays(1);
    //     // }
    //     // return $due_date->toDateString();

    //     if($this->due_date == ''){
    //         $due_date = \Carbon\Carbon::parse($this->tanggal);
    //         if($this->tipe_pembayaran == 'credit'){
    //             $due_date = $due_date->addDays(15);
    //         }
    //         elseif($this->tipe_pembayaran == 'cash'){
    //             $due_date = $due_date->addDays(1);
    //         }
    //         return $due_date->toDateString();
    //     }
    //     else{
    //         return $this->due_date;
    //     }
    // }

    public function getOverDueAttribute(){
        if($this->paid_at){
            $od = "LUNAS";
        }
        else{
            $due_date = \Carbon\Carbon::parse($this->due_date);
            $today = \Carbon\Carbon::today();
            $od = $due_date->diffInDays($today, false);
        }
        return $od;
    }

    public function getWeekAttribute(){
        $tanggal = $this->delivered_at;

        if ($tanggal == null || $tanggal == '') {
            $tanggal = $this->tanggal;
        }

        return \Carbon\Carbon::parse($tanggal)->weekOfYear;
    }

    public function getSumCartonAttribute(){
        return $this->detail_penjualan->sum('sum_carton');
    }

    public function getSkuAttribute(){
        return $this->detail_penjualan->unique('id_stock')->count('id_stock');
    }

    public function getTotalQtyAttribute()
    {
        return $this->detail_penjualan->sum('qty');
    }

    public function getTotalPcsAttribute()
    {
        return $this->detail_penjualan->sum('qty_pcs');
    }

    public function getTotalQtyOrderAttribute()
    {
        return $this->detail_penjualan->sum('order_qty');
    }

    public function getTotalPcsOrderAttribute()
    {
        return $this->detail_penjualan->sum('order_pcs');
    }

    public function getTotalAttribute()
    {
        return $this->detail_penjualan->sum('subtotal');
    }

    public function getTotalAfterTaxAttribute()
    {
        return $this->detail_penjualan->sum('subtotal_after_tax');
    }

    public function getTotalOrderAttribute()
    {
        return $this->detail_penjualan->sum('subtotal_order');
    }

    public function getNetTotalAttribute()
    {
        return $this->detail_penjualan->sum('net');
    }

    public function getDiscTotalAttribute()
    {
        return $this->detail_penjualan->sum('discount');
    }

    public function getDiscTotalAfterTaxAttribute()
    {
        return $this->detail_penjualan->sum('discount_after_tax');
    }

    public function getDppAttribute()
    {
        $dpp = $this->detail_penjualan->sum('dpp');
        return floatval($dpp);
    }

    public function getPpnAttribute()
    {
        $ppn = $this->detail_penjualan->sum('ppn');
        return floatval($ppn);
    }

    public function getGrandTotalAttribute()
    {
        $grand_total = ($this->total - $this->disc_total) + $this->ppn;
        return $grand_total;
    }

    public function getGrandTotalOrderAttribute()
    {
        $grand_total = $this->detail_penjualan->sum('subtotal_order');
        return $grand_total;
    }

    public function getDiscFinalAttribute(){
        // $detail_penjualan = DetailPenjualan::where('id_penjualan', $this->id)->get();
        // $grand_total = $this->grand_total;
        // if($this->tipe_pembayaran == 'cash'){
        //     $disc_final =  $this->disc_total + $grand_total * 0.01;
        // }
        // else{
        //     $disc_final = $this->disc_total;
        // }

        $disc_final = $this->disc_total;

        return $disc_final;
    }

    public function pembayaran()
    {
        return $this->hasMany('App\Models\DetailPelunasanPenjualan', 'id_penjualan', 'id')
            ->where('status', '!=', 'rejected');
    }

    public function pelunasan_waiting()
    {
        return $this->hasMany('App\Models\DetailPelunasanPenjualan', 'id_penjualan', 'id')
            ->where('status', '=', 'waiting');
    }

    public function getJumlahLunasAttribute(){
        $jumlah_lunas = $this->pembayaran->sum('nominal');
        return $jumlah_lunas;
    }

    public function getJumlahWaitingAttribute(){
        $pelunasan_waiting = $this->pelunasan_waiting->sum('nominal');
        return $pelunasan_waiting;
    }

    public function getJumlahBelumBayarAttribute(){
        $jumlah_belum_dibayar = $this->grand_total - $this->jumlah_lunas;
        return $jumlah_belum_dibayar;
    }

    public function getPiutangAttribute(){
        $jumlah_lunas_approved = DetailPelunasanPenjualan::where('id_penjualan', $this->id)->where('status', 'approved')->sum('nominal');
        $piutang = $this->grand_total - $this->jumlah_lunas_approved;

        return $piutang;
    }

    public function getNamaDriverAttribute(){
        if ($this->driver_id != null) {
            return $this->driver->user->name;
        }
    }

    public function getNamaCheckerAttribute(){
        if ($this->checker != null) {
            return $this->checker->name;
        }
    }

    public function pajak() {
        return $this->hasOne('App\Models\PenjualanPajak', 'id_penjualan');
    }

    public function perusahaan()
    {
        return $this->belongsTo('App\Models\Perusahaan', 'id_perusahaan', 'id');
    }
    
    public function scopeRelationData($query) {
        return $query->with(['salesman', 'salesman.user', 'tim', 'toko', 'toko.ketentuan_toko', 'perusahaan', 'driver', 'checker', 'detail_penjualan', 'detail_penjualan.stock', 'gudang']);
    }
}
