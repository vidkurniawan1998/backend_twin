<?php


namespace App\Http\Controllers;


use App\Helpers\Helper;
use App\Models\Depo;
use App\Models\Mitra;
use App\Models\Penjualan;
use App\Models\Salesman;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;

class JurnalController extends Controller
{
    protected $jwt, $user;

    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
    }

    public function penjualan(Request $request)
    {
        if (!$this->user->can('Laporan Jurnal Penjualan')) {
            return $this->Unauthorized();
        }

        $this->validate($request, [
            'depo'              => 'required',
            'id_salesman'       => 'required',
            'start_date'        => 'required',
            'end_date'          => 'required',
            'tipe_pembayaran'   => 'required'
        ]);

        $depo               = $request->depo;
        $tipe_pembayaran    = $request->tipe_pembayaran;
        $start_date         = $request->start_date;
        $end_date           = $request->end_date;
        $id_salesman        = $request->id_salesman;
        $id_mitra           = $request->id_mitra;

        // GET DATA DEPO
        $list_depo  = Depo::whereIn('id', $depo)->get();
        $nama_depo  = [];
        if ($list_depo) {
            $nama_depo  = $list_depo->pluck('nama_depo')->toArray();
        }

        // GET DATA SALESMAN
        $list_salesman = Salesman::with(['user', 'tim'])->whereIn('user_id', $id_salesman)->get();
        $nama_salesman = [];
        if ($list_salesman) {
            foreach ($list_salesman as $ls) {
                $nama_salesman[] = $ls->tim->nama_tim.' - '.$ls->user->name;
            }
        }

        //CONVERT DATE
        $date_string = '';
        if ($start_date == $end_date) {
            $date_string = Carbon::parse($start_date)->formatLocalized('%d %B %Y');
        } else {
            $date_string = Carbon::parse($start_date)->formatLocalized('%d %B %Y') . ' - ' . Carbon::parse($end_date)->formatLocalized('%d %B %Y');
        }


        $penjualan = Penjualan::with(['detail_penjualan', 'toko', 'toko.ketentuan_toko', 'mitra'])
                    ->when(count($id_salesman) > 0, function ($q) use ($id_salesman) {
                        $q->whereIn('id_salesman', $id_salesman);
                    })
                    ->when(count($depo) > 0, function ($q) use ($depo) {
                        $q->whereIn('id_depo', $depo);
                    })
                    ->when($id_mitra <> 'exclude' && $id_mitra <> 'include', function ($q) use ($id_mitra) {
                        $q->where('id_mitra', $id_mitra);
                    })
                    ->when($id_mitra == 'exclude', function ($q) {
                        $q->where('id_mitra', '=', 0);
                    })
                    ->whereBetween('delivered_at', [$start_date . " 00:00:00", $end_date . " 23:59:59"])
                    ->where('tipe_pembayaran', '=', $tipe_pembayaran)
                    ->where('status', '=', 'delivered')
                    ->get();
        if ($penjualan->count() === 0) {
            return response()->json([], 200);
        }

        $data           = [];
        $grand_total    = $penjualan->sum('grand_total');
        $disc_total     = $penjualan->sum('disc_total');
        $debet          = round( $grand_total + $disc_total);
        $kredit         = 0;

        if ($tipe_pembayaran === 'credit') {
            foreach ($penjualan as $pj) {
                $kredit += $pj->grand_total;
                $data[] = [
                    'tanggal'   => Carbon::parse($pj->delivered_at)->format('d/m'),
                    'total'     => round($pj->grand_total),
                    'no_acc'    => $pj->toko->no_acc. ' / '.$pj->toko->cust_no,
                    'nama_toko' => $pj->toko->nama_toko,
                    'npwp'      => $pj->toko->ketentuan_toko->npwp,
                    'no_invoice'=> $pj->no_invoice,
                ];
            }
        } else {
            $total_cash = round($grand_total);
            $kredit     += $grand_total;
            $data[] = [
                'tanggal'   => Carbon::parse($penjualan[0]->delivered_at)->format('d/m'),
                'total'     => $total_cash,
                'no_acc'    => "10004000",
                'nama_toko' => "PENERIMAAN KAS",
                'npwp'      => '',
                'no_invoice'=> '',
            ];
        }
        
        $nama_perusahaan = $list_depo[0]->perusahaan->nama_perusahaan;
        if(is_numeric($id_mitra)) {
            $mitra = Mitra::where('id', $id_mitra)->first();
            $nama_perusahaan = $mitra->perusahaan;
        }

        $response = [
            'perusahaan'=> $nama_perusahaan,
            'depo'      => implode(',', $nama_depo),
            'salesman'  => implode(',', $nama_salesman),
            'tanggal'   => $date_string,
            'data'      => $data,
            'potongan'  => round($disc_total),
            'dpp'       => round($penjualan->sum('dpp') + $disc_total),
            'ppn'       => round($penjualan->sum('ppn')),
            'debet'     => $debet,
            'kredit'    => $kredit + $disc_total,
            'terbilang' => Helper::penyebut($debet),
            'value'     => $grand_total,
            'no_bpj'    => Carbon::parse($start_date)->format('dm'),
            'tipe_pembayaran' => $request->tipe_pembayaran
        ];

        return response()->json($response, 200);
    }
}
