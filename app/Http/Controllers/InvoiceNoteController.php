<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;
use App\Models\InvoiceNote;
use App\Models\RiwayatInvoiceNote;
use App\Http\Resources\InvoiceNote as InvoiceNoteResources;
use App\Http\Resources\InvoiceNoteEdit as InvoiceNoteEditResources;
use Illuminate\Support\Facades\DB;



class InvoiceNoteController extends Controller
{

    protected $jwt;
    public function __construct(JWTAuth $jwt){
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
    }

    public function index(Request $request)
    {
        if ($this->user->can('Menu Invoice Note')):
            $id_user        = $this->user->id;
            $id_perusahaan  = $request->has('id_perusahaan') && count($request->id_perusahaan)>0 ?
                                         $request->id_perusahaan : Helper::perusahaanByUser($id_user);
            $id_depo        = $request->has('id_depo') && count($request->id_depo) > 0 ? $request->id_depo : [];
            $id_salesman    = $request->id_salesman;
            $due_date       = $request->due_date;
            $keyword        = $request->keyword;
            $status         = $request->status;

            $data = InvoiceNote::with(['penjualan','penjualan.toko','riwayat_invoice_note'])

            ->when($id_perusahaan <> [], function ($q) use ($id_perusahaan) {
                return $q->whereHas('penjualan', function ($q) use ($id_perusahaan) {
                    return $q->whereIn('id_perusahaan', $id_perusahaan);
                });
            })
            ->when($id_depo <> [], function ($q) use ($id_depo) {
                return $q->whereHas('penjualan', function($q) use ($id_depo){
                    return $q->whereIn('id_depo',$id_depo);
                });
            })
            ->when($id_salesman > 0, function ($q) use ($id_salesman) {
                return $q->whereHas('penjualan', function($q) use ($id_salesman){
                    return $q->where('id_salesman','=',$id_salesman);
                });
            })
            ->when($due_date > 0, function ($q) use ($due_date) {
                return $q->where('tanggal','<=',$due_date);
            })
            ->when($status <> 'all', function ($q) use ($status) {
                return $q->where('status','=',$status);
            })
            ->when($keyword <> '', function ($q) use ($keyword) {
                $q->where('no_invoice', 'like', "%{$keyword}%")
                    ->orWhere('id', 'like', "%{$keyword}%")
                    ->orWhere('tanggal', 'like', "%{$keyword}%")
                    ->orWhereHas('toko', function ($q) use ($keyword) {
                        $q->where('nama_toko', 'like', "%{$keyword}%")
                          ->orWhere('cust_no', 'like', "%{$keyword}%")
                          ->orWhere('alamat', 'like', "%{$keyword}%");
                    });
            })

            ->orderBy('status','ASC')
            ->orderBy('tanggal','ASC')
            ->orderBy('id','ASC');

            $perPage = $request->has('per_page') ? $perPage = $request->per_page : $perPage = 10;
            $data = $perPage == 'all' ? $data->get() : $data->paginate((int)$perPage);

            //return response()->json($data);
            return InvoiceNoteResources::collection($data);
        else:
            return $this->Unauthorized();
        endif;
    }

    public function show(Request $request) {
         if ($this->user->can('Edit Invoice Note')):
             $id = $request->all();
             $data = InvoiceNote::with(['penjualan','penjualan.toko','riwayat_invoice_note'])
             ->whereIn('id',$id)
             ->get();
            //return response()->json($data);
             return InvoiceNoteEditResources::collection($data);
        else:
            return $this->Unauthorized();
        endif;
    }

    public function store(Request $request)
    {
      if ($this->user->can('Tambah Invoice Note')):
        $request=$request->all();
        try {
            foreach ($request as $input) {
                $input['created_by'] = $this->user->id;
                $add_invoice_note = InvoiceNote::create($input);
            }
             return response()->json([
              'message' => "Tambah data berhasil"
            ], 201);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
      else:
          return $this->Unauthorized();
      endif;
    }

    public function update(Request $request) {
        if ($this->user->can('Update Invoice Note')):
            $request = $request->all();
            $input = [];
            foreach ($request as $row) {
                $input['tanggal']    = $row['tanggal'];
                $input['keterangan'] = $row['keterangan'];
                $input['keterangan_reschedule'] = $row['keterangan_reschedule'];
                $input['status']     = 'belum_dikunjungi';
                $input['updated_by'] = $this->user->id;

                $detail_riwayat = InvoiceNote::where('id','=',$row['id'])->get();
                $detail_riwayat = $detail_riwayat->all();

                $input_new = [];
                $input_new['id_invoice_note'] = $detail_riwayat[0]->id;
                $input_new['no_invoice'] = $detail_riwayat[0]->no_invoice;
                $input_new['tanggal']    = $detail_riwayat[0]->tanggal;
                $input_new['keterangan'] = $detail_riwayat[0]->keterangan;
                $input_new['keterangan_reschedule'] = $detail_riwayat[0]->keterangan_reschedule;
                $input_new['status']     = $detail_riwayat[0]->status;
                $input_new['updated_at'] = $detail_riwayat[0]->updated_at;
                $input_new['updated_by'] = $this->user->id;
                $input_new['created_by'] = $this->user->id;

                $detail_save_riwayat = RiwayatInvoiceNote::create($input_new);
                $detail_invoice = InvoiceNote::find($row['id']);
                $detail_invoice->update($input);
                $detail_invoice = '';
            }
            return response()->json([
                'message' => 'Data Invoice Note telah berhasil diubah.'
            ], 201);
        else:
            return $this->Unauthorized();
        endif;
    }

     public function kunjungan(Request $request) {
        if ($this->user->can('Menu Invoice Note')):
            $request = $request->all();
            $input   = array('status' => 'dikunjungi');
            InvoiceNote::whereIn('id',$request)->update($input);
            return response()->json([
                'message' => 'Data Invoice Note telah berhasil diubah.'
            ], 201);
        else:
            return $this->Unauthorized();
        endif;
    }

    public function destroy($id, Request $request)
    {
        if ($this->user->can('Delete Invoice Note')):
            $idArray = explode(',', $id);
            $detail = InvoiceNote::whereIn('id',$idArray);
            $detail->update(array('deleted_by'=>$this->user->id));
            $detail_invoice_note = $detail->delete();

            if(!$detail_invoice_note) {
                return response()->json([
                    'message' => 'Delete invoice note tidak berhasil.'
                ], 422);
            }

            if($detail_invoice_note) {
                return response()->json([
                    'message' => 'Data invoice note berhasil dihapus.',
                ], 200);
            }

            return response()->json([
                'message' => 'Data invoice note tidak ditemukan!'
            ], 404);
        else:
            return $this->Unauthorized();
        endif;
    }

}
