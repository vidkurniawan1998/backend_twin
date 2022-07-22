<?php

namespace App\Http\Controllers;

use App\Models\Depo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tymon\JWTAuth\JWTAuth;
use App\Models\DetailPelunasanPembelian;
use App\Models\FakturPembelian;
use App\Traits\ExcelStyle;
use App\Helpers\Helper;

class DetailPelunasanPembelianController extends Controller
{
    use ExcelStyle;

    protected $jwt;

    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
        $this->user = $this->jwt->user(); 
    }
    public function index(Request $request) {
        
    }

    public function show($id) {
    }

    public function store(Request $request) {
        if ($this->user->can('Tambah Detail Pelunasan Pembelian')):

            $this->validate($request, [
                'id_faktur_pembelian' => 'required|numeric|min:0',
                'tipe' => 'required|in:tunai,transfer,bilyet_giro,saldo_retur,lainnya',
                'nominal' => 'required|numeric|min:0',
                'jatuh_tempo' => 'nullable|date',
                'tanggal' => 'nullable|date'
            ]);

            $input = $request->all();
            $input['status'] = 'waiting';
            $input['created_by'] = $this->user->id;
            $input['tanggal'] = $request->has('tanggal') ? $request->tanggal:date('Y-m-d');

            if($request->tipe == 'tunai'){
                $input['bank'] = null;
                $input['no_rekening'] = null;
                $input['no_bg'] = null;
                $input['jatuh_tempo_bg'] = null;
            }
            elseif($request->tipe == 'transfer'){
                $input['no_bg'] = null;
                $input['jatuh_tempo_bg'] = null;
            }
            elseif($request->tipe == 'bilyet_giro'){
                // $input['no_rekening'] = null;
            }
            elseif($request->tipe == 'saldo_retur'){
                $input['bank'] = null;
                $input['no_rekening'] = null;
                $input['no_bg'] = null;
                $input['jatuh_tempo_bg'] = null;
            }
            elseif($request->tipe == 'lainnya'){
                $input['bank'] = null;
                $input['no_rekening'] = null;
                $input['no_bg'] = null;
                $input['jatuh_tempo_bg'] = null;
            }

            try {
                $detail_pelunasan_pembelian = DetailPelunasanPembelian::create($input);

            } catch (Exception $e) {
                return response()->json(['error' => $e->getMessage()], 400);
            }

            return response()->json([
                'message' => 'Data Pelunasan Pembelian berhasil disimpan.'
            ], 201);
        else:
            return $this->Unauthorized();
        endif;
    }

    public function update(Request $request, $id) {
        if ($this->user->can('Update Detail Pelunasan Pembelian')):
            if($request->status == 'approved') {
                return response()->json([
                    'message' => 'Anda tidak boleh mengubah data Pelunasan Pembelian yang telah disetujui.'
                ], 422);
            }
            $this->validate($request, [
                'id_faktur_pembelian' => 'required|numeric|min:0',
                'tipe' => 'required|in:tunai,transfer,bilyet_giro,saldo_retur,lainnya',
                'nominal' => 'required|numeric|min:0',
                'jatuh_tempo' => 'nullable|date',
                'tanggal' => 'nullable|date'
            ]);

            $input = $request->all();
            $input['status'] = 'waiting';
            $input['updated_by'] = $this->user->id;
            $input['tanggal'] = $request->has('tanggal') ? $request->tanggal:date('Y-m-d');

            if($request->tipe == 'tunai'){
                $input['bank'] = null;
                $input['no_rekening'] = null;
                $input['no_bg'] = null;
                $input['jatuh_tempo_bg'] = null;
            }
            elseif($request->tipe == 'transfer'){
                $input['no_bg'] = null;
                $input['jatuh_tempo_bg'] = null;
            }
            elseif($request->tipe == 'bilyet_giro'){
                // $input['no_rekening'] = null;
            }
            elseif($request->tipe == 'saldo_retur'){
                $input['bank'] = null;
                $input['no_rekening'] = null;
                $input['no_bg'] = null;
                $input['jatuh_tempo_bg'] = null;
            }
            elseif($request->tipe == 'lainnya'){
                $input['bank'] = null;
                $input['no_rekening'] = null;
                $input['no_bg'] = null;
                $input['jatuh_tempo_bg'] = null;
            }

            $detail_pelunasan_pembelian = DetailPelunasanPembelian::find($id);
             $detail_pelunasan_pembelian->update($input);            


            return response()->json([
                'message' => 'Data Pelunasan Pembelian telah berhasil diubah.'
            ], 201);
        else:
            return $this->Unauthorized();
        endif;
    }


    public function destroy($id, Request $request)
    {
        if ($this->user->can('Hapus Detail Pelunasan Pembelian')):
            $detail_pelunasan_pembelian = DetailPelunasanPembelian::find($id);

            if(!$detail_pelunasan_pembelian) {
                return response()->json([
                    'message' => 'Data Detail Pelunasan Pembelian tidak ditemukan.'
                ], 422);
            }

            if($detail_pelunasan_pembelian->status == 'approved') {
                return response()->json([
                    'message' => 'Anda tidak boleh menghapus data barang pada pembelian yang telah disetujui.'
                ], 422);
            }

            if($detail_pelunasan_pembelian) {
                $data = ['deleted_by' => $this->user->id];
                $detail_pelunasan_pembelian->update($data);
                $detail_pelunasan_pembelian->delete();

                return response()->json([
                    'message' => 'Data Pelunasan Pembelian berhasil dihapus.',
                ], 200);
            }

            return response()->json([
                'message' => 'Data Pelunasan Pembelian tidak ditemukan!'
            ], 404);
        else:
            return $this->Unauthorized();
        endif;
    }

     public function approve($id) {
        if ($this->user->can('Approve Detail Pelunasan Pembelian')):
            $detail_pelunasan_pembelian = DetailPelunasanPembelian::find($id);
            if($detail_pelunasan_pembelian->status == 'approved') {
                return response()->json([
                    'message' => 'Data Pelunasan Pembelian yang telah disetujui.'
                ], 422);
            }
            try{
                DB::beginTransaction();
                $input = array();
                $input['status'] = 'approved';

                $input['approved_by'] = $this->user->id;
                $input['approved_at'] = \Carbon\Carbon::now()->toDateTimeString();
                $detail_pelunasan_pembelian->update($input);

                $pembelian      = FakturPembelian::find($detail_pelunasan_pembelian->id_faktur_pembelian);
                $sum_pelunasan = DetailPelunasanPembelian::where('id_faktur_pembelian', $detail_pelunasan_pembelian->id_faktur_pembelian)
                                 ->where('status', 'approved')->sum('nominal');
                $grand_total   = (int)$pembelian->grand_total; 


                if(round($sum_pelunasan) >= round($grand_total)){ 
                    $pembelian->update(['tanggal_bayar' => \Carbon\Carbon::now()->toDateTimeString()]);
                }

                $logData = [
                    'action'        => 'Approve Pelunasan Pembelian',
                    'description'   => 'No Invoice: '.$detail_pelunasan_pembelian->faktur_pembelian->no_invoice,
                    'user_id'       => $this->user->id
                ];

                $this->log($logData);
                DB::commit();

                return response()->json([
                    'message' => 'Data Pelunasan Pembelian telah berhasil disetujui.'
                ], 201);    
             } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Data Pelunasan Penjualan gagal disetujui.',
                ], 400);
            }
           
        else:
            return $this->Unauthorized();
        endif;
    }

    public function reject($id) {
        if ($this->user->can('Approve Detail Pelunasan Pembelian')):
            $detail_pelunasan_pembelian = DetailPelunasanPembelian::find($id);
            
            if($detail_pelunasan_pembelian->status == 'approved') {
                return response()->json([
                    'message' => 'Data Pelunasan Pembelian yang telah disetujui.'
                ], 422);
            }

            $input['status'] = 'rejected';
            // $input['approved_by'] = $this->user->id;
            // $input['approved_at'] = \Carbon\Carbon::now()->toDateTimeString();

            $detail_pelunasan_pembelian->update($input);

            return response()->json([
                'message' => 'Pelunasan Pembelian telah berhasil ditolak.'
            ], 201);   
        else:
            return $this->Unauthorized();
        endif;
    }

    public function cancel_approval($id) {
        if ($this->user->can('Approve Detail Pelunasan Pembelian')):
            try{
                DB::beginTransaction();
                $detail_pelunasan_penjualan = DetailPelunasanPembelian::find($id);

                $input['status'] = 'waiting';
                $input['approved_by'] = null;
                $input['approved_at'] = null;

                $detail_pelunasan_penjualan->update($input);

                $pembelian = FakturPembelian::find($detail_pelunasan_penjualan->id_faktur_pembelian);
                $pembelian->update(['tanggal_bayar' => null]);

                return response()->json([
                    'message' => 'Pelunasan Penjualan telah berhasil dibatalkan.'
                ], 201);  
            }
            catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Pelunasan Penjualan gagal dibatalkan.',
                ], 400);
            }
        else:
            return $this->Unauthorized();
        endif;
    }

}
