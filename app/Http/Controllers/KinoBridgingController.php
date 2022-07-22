<?php

namespace App\Http\Controllers;

use App\Models\Gudang;
use App\Models\KinoBridging;
use App\Imports\KinoSTTImport;
use App\Imports\KinoRTRImport;
use App\Imports\KinoStockImport;
use App\Models\LogStock;
use App\Traits\KinoCodeCompany;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;
use \Maatwebsite\Excel\Facades\Excel;

class KinoBridgingController extends Controller
{
    use KinoCodeCompany;
    protected $jwt;

    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
    }

    public function import(Request $request)
    {
        DB::table('kino_bridging')->delete();
        $this->validate($request, [
            'file' => 'required'
        ]);

        $arrayFileName = ['stt', 'rtr', 'stock'];
        $allowExtension = ['xls', 'xlsx'];
        foreach ($request->file('file') as $key => $file) {
            $fileName  = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $file->getClientOriginalExtension();
            if (!in_array($extension, $allowExtension)) {
                continue;
            }

            if (in_array($fileName, $arrayFileName)) {
                if ($fileName === 'stt') {
                    Excel::import(new KinoSTTImport, $file);
                } elseif ($fileName === 'rtr') {
                    Excel::import(new KinoRTRImport, $file);
                } else {
                    Excel::import(new KinoStockImport, $file);
                }
            }
        }

        $content = [];
        $rows   = KinoBridging::where('cdist', '<>', '')->get();
        $rows   = $rows->groupBy('cdist', true);
        $length = 0;
        foreach ($rows as $key => $kinos) {
            $content[$key] = "";
            foreach($kinos as $kino) {
                $transDate  = date('d-m-Y', strtotime($kino->dtran));
                $item       = $kino->citem;
                $content[$key].= $kino->cflag.";";
                $content[$key].= $kino->cdist.";";
                $content[$key].= $kino->ctran.";";
                $content[$key].= $transDate.";";
                $content[$key].= $kino->outlet.";";
                $content[$key].= $kino->csales.";";
                $content[$key].= $kino->ccompany.";";
                $content[$key].= $item.";";
                $content[$key].= $kino->cgudang1.";";
                $content[$key].= $kino->cgudang2.";";
                $content[$key].= $kino->ctypegd1.";";
                $content[$key].= $kino->ctypegd2.";";
                $content[$key].= $kino->njumlah.";";
                $content[$key].= $kino->lbonus.";";
                $content[$key].= $kino->unit.";";
                $content[$key].= $kino->nisi.";";
                $content[$key].= $kino->nharga.";";
                $content[$key].= $kino->ndisc1.";";
                $content[$key].= $kino->ndisc2.";";
                $content[$key].= $kino->ndisc3.";";
                $content[$key].= $kino->ndisc4.";";
                $content[$key].= $kino->ndisc5.";";
                $content[$key].= $kino->ndisc6.";";
                $content[$key].= $kino->ndiscg1.";";
                $content[$key].= $kino->ndiscg2.";";
                $content[$key].= $kino->fppn.";";
                $content[$key].= $kino->netsales;
                $content[$key].= "\n";
            }
            $length+=strlen($content[$key]);
        }

        DB::table('kino_bridging')->delete();
        return response(json_encode($content), 200);
    }

    public function export(Request $request)
    {
        $messages = [
            'id_depo.required'      => 'depo wajib isi',
            'start_date.required'   => 'tanggal wajib isi',
            'end_date.required'     => 'tanggal wajib isi',
            'id_principal.required' => 'principal wajib isi'
        ];

        $this->validate($request, [
            'id_depo'       => 'required|exists:depo,id',
            'start_date'    => 'required|date',
            'end_date'      => 'required|date',
            'id_principal'  => 'required|exists:principal,id'
        ], $messages);

        $id_depo        = $request->id_depo;
        $dari_tanggal   = $request->start_date;
        $sampai_tanggal = $request->end_date;
        $id_principal   = $request->id_principal;

        $string_data = "";
        $ccompany    = "";

        // PENJUALAN
        $penjualan = DB::table('penjualan')
            ->join('detail_penjualan', 'penjualan.id', 'detail_penjualan.id_penjualan')
            ->join('stock', 'detail_penjualan.id_stock', 'stock.id')
            ->join('gudang', 'stock.id_gudang', 'gudang.id')
            ->join('barang', 'stock.id_barang', 'barang.id')
            ->join('segmen', 'barang.id_segmen', 'segmen.id')
            ->join('brand', 'segmen.id_brand', 'brand.id')
            ->join('principal', 'brand.id_principal', 'principal.id')
            ->join('promo', 'detail_penjualan.id_promo', 'promo.id')
            ->join('toko', 'penjualan.id_toko', 'toko.id')
            ->join('ketentuan_toko', 'ketentuan_toko.id_toko', 'toko.id')
            ->join('salesman', 'penjualan.id_salesman', 'salesman.user_id')
            ->join('tim', 'penjualan.id_tim', 'tim.id')
            ->join('users', 'salesman.user_id', 'users.id')
            ->join('depo', 'penjualan.id_depo', 'depo.id')
            ->where('penjualan.id_depo', $id_depo)
            ->where('penjualan.status', '!=', 'closed')
            ->where('brand.id_principal', $id_principal);

        $penjualan = $penjualan->whereNull('penjualan.deleted_at');
        $penjualan = $penjualan->select(
            DB::raw('"A" as tipe'),
            'penjualan.id',
            'penjualan.po_manual',
            'depo.nama_depo',
            'penjualan.no_invoice',
            'toko.no_acc',
            'penjualan.tanggal',
            DB::raw('DATE(penjualan.delivered_at) as delievered_at'),
            'tim.nama_tim',
            'salesman.kode_eksklusif',
            'barang.kode_barang',
            'gudang.nama_gudang',
            'gudang.jenis',
            DB::raw('"GU" as ctypegd1'),
            'detail_penjualan.id_promo',
            'detail_penjualan.harga_jual',
            'detail_penjualan.qty',
            'detail_penjualan.qty_pcs',
            DB::raw('(detail_penjualan.qty * barang.isi) + detail_penjualan.qty_pcs as in_pcs'),
            'detail_penjualan.disc_persen',
            'detail_penjualan.disc_rupiah',
            DB::raw('detail_penjualan.harga_jual/barang.isi as harga_pcs'),
            'barang.isi',
            'promo.disc_1',
            'promo.disc_2',
            'promo.disc_3',
            'promo.disc_4',
            'promo.disc_5',
            'promo.disc_6',
            'promo.disc_rupiah_distributor',
            'promo.disc_rupiah_principal',
            'barang.extra'
        )->whereBetween('penjualan.tanggal', [$dari_tanggal, $sampai_tanggal])
            ->whereNull('penjualan.deleted_at')
            ->orderBy('penjualan.created_at')
            ->get();

        foreach ($penjualan as $pj) {
            if($pj->in_pcs == 0) {
                continue;
            }

            $sum_carton         = $pj->qty + ($pj->qty_pcs / $pj->isi);
            $price_before_tax   = $pj->harga_jual / 1.1;
            $subtotal           = $price_before_tax * $sum_carton;
            $disc_rupiah        = 0;
            $discount           = 0;
            $no_invoice         = $pj->id;
            // if($no_invoice == null || $no_invoice == '') {
            //     if($pj->po_manual != '') {
            //         $no_invoice = $pj->po_manual;
            //     } else {
            //         $no_invoice = $pj->id;
            //     }
            // }

            $discount = 0;
            if ($pj->id_promo) {
                $disc_rupiah    = ($pj->disc_rupiah / 1.1) * $sum_carton;
                $disc_persen    = ($pj->disc_persen / 100) * $subtotal;
                $discount       = $disc_rupiah + $disc_persen;
            }

            $ppn    = ($subtotal - $discount) / 10;
            $total  = $subtotal - $discount + $ppn;


            $ccompany       = $this->ccompany2($pj->nama_depo, $id_principal);
            $tanggal        = $pj->tanggal;
            $tipe_gudang    = $pj->jenis == 'baik' ? 'GU':'GB';
            $in_pcs         = $pj->in_pcs;
            $harga_pcs      = round($pj->harga_pcs);
            $penjualan_value= round($total);
            $disc_nom_pcs   = round($disc_rupiah * 1.1 / $in_pcs);
            $bonus          = $penjualan_value == 0 ? 1:0;
            $kode_barang    = $pj->extra == '1' ? str_replace(' EXT', '', $pj->kode_barang) : $pj->kode_barang;
            $kode_salesman  = $pj->kode_eksklusif <> '' ? $pj->kode_eksklusif:$pj->nama_tim;
            $disc_5         = $pj->extra == '1' ? 100:$pj->disc_5;

            $string_data .= "C;"; //cflag
            $string_data .= "$ccompany;"; // codecompany
            $string_data .= $no_invoice.";"; // no trans
            $string_data .= date('d-m-Y', strtotime($tanggal)).";"; // transdate
            $string_data .= $pj->no_acc.";"; // outlet
            $string_data .= $kode_salesman.";"; // csales
            $string_data .= "$ccompany;"; //codecompany
            $string_data .= $kode_barang.";"; //kode barang
            $string_data .= $pj->nama_gudang.";"; //nama gudang
            $string_data .= ";"; //nama gudang 2
            $string_data .= "$tipe_gudang;"; //tipe gudang
            $string_data .= ";"; //tipe gudang 2
            $string_data .= "$in_pcs;"; //in pcs
            $string_data .= $bonus.";"; //bonus
            $string_data .= "PCS;"; //unit
            $string_data .= "1;"; //isi
            $string_data .= "$harga_pcs;"; //harga
            $string_data .= $pj->disc_1.";"; //disc1
            $string_data .= $pj->disc_2.";"; //disc2
            $string_data .= $pj->disc_3.";"; //disc3
            $string_data .= $pj->disc_4.";"; //disc4
            $string_data .= $disc_5.";"; //disc5
            $string_data .= $pj->disc_6.";"; //disc6
            $string_data .= $disc_nom_pcs.";"; //ndiscg1
            $string_data .= "0;"; //ndiscg2
            $string_data .= "1;"; //ppnflag
            $string_data .= "$penjualan_value;"; //total
            $string_data .= "\n"; //enter
        }
        // END PENJUALAN

        // RETUR
        $retur = DB::table('retur_penjualan')
            ->join('detail_retur_penjualan', 'retur_penjualan.id', 'detail_retur_penjualan.id_retur_penjualan')
            ->join('depo', 'retur_penjualan.id_depo', 'depo.id')
            ->join('toko', 'retur_penjualan.id_toko', 'toko.id')
            ->join('barang', 'detail_retur_penjualan.id_barang', 'barang.id')
            ->join('segmen', 'barang.id_segmen', 'segmen.id')
            ->join('brand', 'segmen.id_brand', 'brand.id')
            ->join('salesman', 'retur_penjualan.id_salesman', 'salesman.user_id')
            ->join('gudang', 'retur_penjualan.id_gudang', 'gudang.id')
            ->join('tim', 'retur_penjualan.id_tim', 'tim.id');
        $retur = $retur->whereNull('retur_penjualan.deleted_at')
            ->where('retur_penjualan.status', '=', 'approved')
            ->where('brand.id_principal', $id_principal)
            ->where('retur_penjualan.id_depo', $id_depo)
            ->whereBetween('retur_penjualan.sales_retur_date', [$dari_tanggal, $sampai_tanggal]);

        $retur = $retur->select(
            DB::raw('"D" as tipe'),
            DB::raw('DATE(retur_penjualan.sales_retur_date) as tanggal'),
            'retur_penjualan.id',
            'retur_penjualan.no_invoice',
            'toko.no_acc',
            'depo.nama_depo',
            'barang.kode_barang',
            'barang.isi',
            'tim.nama_tim',
            'salesman.kode_eksklusif',
            'gudang.nama_gudang',
            'gudang.jenis',
            'detail_retur_penjualan.qty_dus',
            'detail_retur_penjualan.qty_pcs',
            'detail_retur_penjualan.harga',
            'detail_retur_penjualan.disc_persen',
            'detail_retur_penjualan.disc_nominal',
            DB::raw('(detail_retur_penjualan.qty_dus * barang.isi) + detail_retur_penjualan.qty_pcs as in_pcs')
        )->get();

        foreach ($retur as $rtr) {
            $ccompany       = $this->ccompany2($rtr->nama_depo, $id_principal);
            $tanggal        = date('d-m-Y', strtotime($rtr->tanggal));
            $tipe_gudang    = $rtr->jenis == 'baik' ? 'GU':'GB';
            $in_pcs         = $rtr->in_pcs;
            $harga_pcs      = $rtr->harga * 1.1 / $rtr->isi;
            $retur_value    = $harga_pcs * $in_pcs;
            $kode_salesman  = $rtr->kode_eksklusif <> '' ? $rtr->kode_eksklusif:$rtr->nama_tim;

            $string_data .= "D;"; //cflag
            $string_data .= "$ccompany;"; // codecompany
            $string_data .= $rtr->id.";"; // no trans
            $string_data .= $tanggal.";"; // transdate
            $string_data .= $rtr->no_acc.";"; // outlet
            $string_data .= $kode_salesman.";"; // csales
            $string_data .= "$ccompany;"; //codecompany
            $string_data .= $rtr->kode_barang.";"; //kode barang
            $string_data .= $rtr->nama_gudang.";"; //nama gudang
            $string_data .= ";"; //nama gudang 2
            $string_data .= "$tipe_gudang;"; //tipe gudang
            $string_data .= ";"; //tipe gudang 2
            $string_data .= "$in_pcs;"; //in pcs
            $string_data .= "0;"; //bonus
            $string_data .= "PCS;"; //unit
            $string_data .= "1;"; //isi
            $string_data .= round($harga_pcs).";"; //harga
            $string_data .= "0;"; //disc1
            $string_data .= "0;"; //disc2
            $string_data .= "0;"; //disc3
            $string_data .= "0;"; //disc4
            $string_data .= "0;"; //disc5
            $string_data .= "0;"; //disc6
            $string_data .= "0;"; //ndiscg1
            $string_data .= "0;"; //ndiscg2
            $string_data .= "1;"; //ppnval
            $string_data .= round($retur_value).";"; //total
            $string_data .= "\n"; //enter
        }
        // END RETUR

        // STOCK
        $barang = DB::table('barang')
            ->join('segmen', 'barang.id_segmen', 'segmen.id')
            ->join('brand', 'segmen.id_brand', 'brand.id')
            ->where('brand.id_principal', $id_principal)
            ->select('barang.id', 'barang.kode_barang', 'barang.isi', 'barang.extra')
            ->get();
        $id_barang = $barang->pluck('id')->toArray();
        $gudang = Gudang::with('depo')->where('id_depo', $id_depo)->get();

        $string_barang  = implode(',', $id_barang);
        $harga_barang   = DB::select("SELECT a.id_barang, a.harga FROM harga_barang a WHERE
                          created_at = (
                              SELECT MAX(created_at) FROM harga_barang b WHERE a.id_barang = b.id_barang AND b.tipe_harga = 'dbp' AND id_barang IN ($string_barang)
                            ) AND tipe_harga = 'dbp' AND id_barang IN ($string_barang)");
        $harga_brg      = collect($harga_barang);
        foreach ($gudang as $gdg) {
            $stock = LogStock::where('tanggal', '<=', $sampai_tanggal)
                ->where('id_gudang', $gdg->id)
                ->whereIn('id_barang', $id_barang)
                ->select('id_barang', 'referensi', 'status', DB::raw('SUM(qty_pcs) as qty_pcs'))
                ->groupBy('id_barang', 'referensi', 'status')->get();

            $ccompany   = $this->ccompany2($gdg->depo->nama_depo, $id_principal);
            $nama_gudang= $gdg->nama_gudang;
            $tipe_gudang= $gdg->jenis == 'baik' ? 'GU':'GB';
            foreach ($id_barang as $id) {
                $saldo_akhir    = 0;
                $stock_awal     = $stock->where('id_barang', $id)->where('referensi', 'stock awal');
                if (!$stock_awal->isEmpty()) {
                    $saldo_akhir += $stock_awal->first()->qty_pcs;
                }

                $penerimaan = $stock->where('id_barang', $id)->where('referensi', 'penerimaan barang');
                if (!$penerimaan->isEmpty()) {
                    $saldo_akhir += $penerimaan->first()->qty_pcs;
                }

                $mutasi_masuk = $stock->where('id_barang', $id)
                    ->where('referensi', 'mutasi masuk')
                    ->where('status', 'received');
                if (!$mutasi_masuk->isEmpty()) {
                    $saldo_akhir += $mutasi_masuk->first()->qty_pcs;
                }

                $penjualan = $stock->where('id_barang', $id)
                    ->where('referensi', 'penjualan')
                    ->where('status', 'delivered');
                if (!$penjualan->isEmpty()) {
                    $saldo_akhir -= $penjualan->first()->qty_pcs;
                }

                $mutasi_keluar = $stock->where('id_barang', $id)
                    ->where('referensi', 'mutasi keluar')
                    ->where('status', 'received');
                if (!$mutasi_keluar->isEmpty()) {
                    $saldo_akhir -= $mutasi_keluar->first()->qty_pcs;
                }

                $adjustment = $stock->where('id_barang', $id)
                    ->where('referensi', 'adjustment');
                if (!$adjustment->isEmpty()) {
                    $saldo_akhir += $adjustment->first()->qty_pcs;
                }

                $retur = $stock->where('id_barang', $id)->where('referensi', 'retur');
                if (!$retur->isEmpty()) {
                    $saldo_akhir += $retur->first()->qty_pcs;
                }

                $brg        = $barang->where('id', $id)->first();
                $kode_barang= $brg->kode_barang ?? '';
                $kode_barang= $brg->extra == '1' ? str_replace(' EXT', '', $kode_barang):$kode_barang;
                $isi        = $brg->isi ?? '';
                $harga      = $harga_brg->where('id_barang', $id)->first();
                $harga_pcs  = $harga->harga / $isi;
                $stock_value= $harga_pcs * $saldo_akhir;
                $bonus      = $brg->extra == '1' ? 1:0;

                $string_data .= "H;"; //cflag
                $string_data .= "$ccompany;"; // codecompany
                $string_data .= ";"; // no trans
                $string_data .= date('d-m-Y', strtotime($sampai_tanggal)).";"; // transdate
                $string_data .= ";"; // outlet
                $string_data .= ";"; // csales
                $string_data .= "$ccompany;"; //codecompany
                $string_data .= "$kode_barang;"; //kode barang
                $string_data .= "$nama_gudang;"; //nama gudang
                $string_data .= ";"; //nama gudang 2
                $string_data .= "$tipe_gudang;"; //tipe gudang
                $string_data .= ";"; //tipe gudang 2
                $string_data .= "$saldo_akhir;"; //in pcs
                $string_data .= "$bonus;"; //bonus
                $string_data .= "PCS;"; //unit
                $string_data .= "1;"; //isi
                $string_data .= round($harga_pcs).";"; //harga
                $string_data .= "0;"; //disc1
                $string_data .= "0;"; //disc2
                $string_data .= "0;"; //disc3
                $string_data .= "0;"; //disc4
                $string_data .= "0;"; //disc5
                $string_data .= "0;"; //disc6
                $string_data .= "0;"; //ndiscg1
                $string_data .= "0;"; //ndiscg2
                $string_data .= "1;"; //ppn
                $string_data .= round($stock_value).";"; //total
                $string_data .= "\n"; //enter
            }
        }
        // END STOCk

        return response()->json([$ccompany => $string_data], 200);
    }
}
