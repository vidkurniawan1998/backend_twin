<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\Models\ReturPenjualan;
use App\Http\Resources\ReturSegmen as ReturSegmenResources;
use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;
use Illuminate\Support\Facades\DB;



class ReturSegmenController extends Controller
{

    protected $jwt;
    public function __construct(JWTAuth $jwt){
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
    }

    public function index(Request $request)
    {
        //Testing Variable
        //$request['start_date'] = '2020-01-01';

        if ($this->user->can('Menu Laporan Retur Segmen')):
            $start_date     = $request->start_date. ' 00:00:00';
            $end_date       = $request->end_date.' 23:59:59';
            $id_perusahaan  = $request->id_perusahaan;
            $id_depo        = $request->id_depo;
            $id_tim         = $request->id_tim;

            $retur_segmen   = DB::table('detail_retur_penjualan')
            ->Join('retur_penjualan','retur_penjualan.id','=','detail_retur_penjualan.id_retur_penjualan')
            ->leftJoin('barang','detail_retur_penjualan.id_barang','=','barang.id')
            ->leftJoin('segmen','segmen.id','=','barang.id_segmen')
            ->leftJoin('brand','brand.id','=','segmen.id_brand')
            ->leftJoin('tim','retur_penjualan.id_tim','=','tim.id')
            ->leftJoin('depo','tim.id_depo','=','depo.id')
            ->leftJoin('perusahaan','depo.id_perusahaan','=','perusahaan.id')
            ->select(DB::raw('SUM(qty_dus)      as qty_dus,
                              SUM(qty_pcs)      as qty_pcs,
                              SUM(harga)        as harga,
                              SUM(subtotal)     as subtotal,
                              SUM(disc_nominal) as disc_nominal'),
                              'id_barang',
                              'item_code',
                              'nama_barang',
                              'id_tim',
                              'nama_tim',
                              'kategori_bs',
                              'nama_segmen',
                              'id_segmen')
            ->whereBetween('approved_at', [$start_date, $end_date])
            ->where('perusahaan.id','=', $id_perusahaan)
            ->when($id_depo <> '', function ($q) use ($id_depo) {
                $q->where('depo.id',$id_depo);
            })
            ->when($id_tim <> '', function ($q) use ($id_tim) {
                $q->where('id_tim', $id_tim);
            })
            ->orderBy('id_tim','ASC')
            ->orderBy('id_segmen','ASC')
            ->orderBy('kategori_bs','ASC')
            ->groupBy('id_barang')
            ->get();

            $data = [];
            $grand_total = array('qty_dus' => 0, 'qty_pcs'=>0, 'harga'=>0, 'subtotal'=>0, 'disc_nominal'=>0);
            $temp_kategori_bs='';
            $temp_nama_segmen='';
            $temp_nama_tim='';
            $dump =[
                    'qty_dus'=>0,
                    'qty_pcs'=>0,
                    'harga'=>0,
                    'subtotal'=>0,
                    'disc_nominal'=>0,
                    'id_barang'=>0,
                    'item_code'=>'',
                    'nama_barang'=>'',
                    'id_tim'=>0,
                    'nama_tim'=>'',
                    'kategori_bs'=>'',
                    'nama_segmen'=>'',
                    'id_segmen'=>0
                    ];

            foreach ($retur_segmen as $row) {

                if($temp_nama_tim!=$row->nama_tim){
                    $temp_nama_segmen='';
                    $temp_kategori_bs='';
                    $temp_nama_tim=$row->nama_tim;
                    $dump['banner']=('TEAM');
                    $dump['banner_val']= $row->nama_tim;
                    $dump['tipe']='banner';
                    $data[] = $dump;
                }

                if($temp_nama_segmen!=$row->nama_segmen){
                    $temp_kategori_bs='';
                    $temp_nama_segmen=$row->nama_segmen;
                    $dump['banner']='PRODUK';
                    $dump['banner_val']=$row->nama_segmen;
                    $dump['tipe']='banner';
                    $data[] = $dump;
                }

                if($temp_kategori_bs!=$row->kategori_bs){
                    $temp_kategori_bs=$row->kategori_bs;
                    $dump['banner']='KATEGORI';
                    $dump['banner_val']=$row->kategori_bs;
                    $dump['tipe']='banner';
                    $data[] = $dump;
                }

                $data[] = [
                    'qty_dus'=>$row->qty_dus,
                    'qty_pcs'=>$row->qty_pcs,
                    'harga'=>$row->harga,
                    'subtotal'=>$row->subtotal,
                    'disc_nominal'=>$row->disc_nominal,
                    'id_barang'=>$row->id_barang,
                    'item_code'=>$row->item_code,
                    'nama_barang'=>$row->nama_barang,
                    'id_tim'=>$row->id_tim,
                    'nama_tim'=>$row->nama_tim,
                    'kategori_bs'=>$row->kategori_bs,
                    'nama_segmen'=>$row->nama_segmen,
                    'id_segmen'=>$row->id_segmen,
                    'banner'=>'',
                    'banner_val'=>'',
                    'tipe'=>'item'
                ];
                $grand_total['qty_dus']+=$row->qty_dus;
                $grand_total['qty_pcs']+=$row->qty_pcs;
                $grand_total['harga']+=$row->harga;
                $grand_total['subtotal']+=$row->subtotal;
                $grand_total['disc_nominal']+=$row->disc_nominal;
            }
            $response = array('data' => $data, 'grand_total' => $grand_total);
            return response()->json($response);
        else:
            return $this->Unauthorized();
        endif;
    }
}
