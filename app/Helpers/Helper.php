<?php

namespace App\Helpers;

use App\Models\Depo;
use App\Models\KetentuanToko;
use App\Models\Penjualan;
use App\Models\ReturPenjualan;
use App\Models\Salesman;
use App\Models\Tim;
use App\Models\Toko;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Helper
{
    public static function getDepo()
    {
        return Depo::orderBy('kode_depo')->get();
    }

    public static function salesBySupervisor(Int $id)
    {
        return Salesman::whereHas('tim', function($q) use ($id) {
            $q->where('id_sales_supervisor', $id);
        })->pluck('user_id');
    }

    public static function salesByKoordinator(Int $id)
    {
        return Salesman::whereHas('tim', function($q) use ($id) {
            $q->where('id_sales_koordinator', $id);
        })->pluck('user_id');
    }

    public static function timBySupervisor(Int $id)
    {
        return Tim::where('id_sales_supervisor', '=', $id)->pluck('id');
    }

    public static function timByKoordinator(Int $id)
    {
        return Tim::where('id_sales_koordinator', '=', $id)->pluck('id');
    }

    public static function salesBySupervisorAndDepo(Int $id, $id_depo)
    {
        return Salesman::whereHas('tim', function($q) use ($id, $id_depo) {
            $q->where('id_sales_supervisor', $id)
                ->whereIn('id_depo', $id_depo);
        })->pluck('user_id');
    }

    public static function salesByKoordinatorAndDepo(Int $id, $id_depo)
    {
        return Salesman::whereHas('tim', function($q) use ($id, $id_depo) {
            $q->where('id_sales_koordinator', $id)
                ->whereIn('id_depo', $id_depo);
        })->pluck('user_id');
    }

    public static function listToko(Array $id_tim)
    {
        return Toko::whereHas('ketentuan_toko', function ($query) use ($id_tim){
                $query->whereIn('id_tim', $id_tim);
            })->get();
    }

    public static function perusahaanByUser(Int $user_id)
    {
        return DB::table('user_perusahaan')->select('perusahaan_id')->where('user_id', $user_id)->pluck('perusahaan_id');
    }

    public static function depoIDByUser(Int $user_id, $perusahaan_id = null)
    {
        if ($perusahaan_id <> null) {
            // $perusahaan_id = is_array($perusahaan_id) ? $perusahaan_id : [$perusahaan_id];
            Log::info(json_encode($perusahaan_id));
            $depo = Depo::whereIn('id_perusahaan', $perusahaan_id)->pluck('id');
            return DB::table('user_depo')->select('depo_id')->where('user_id', $user_id)->whereIn('depo_id', $depo)->pluck('depo_id');
        }

        return DB::table('user_depo')->select('depo_id')->where('user_id', $user_id)->pluck('depo_id');
    }

    public static function gudangByDepo($depo)
    {
        return DB::table('gudang')->whereIn('id_depo', $depo->toArray())->get();
    }

    public static function gudangByUser(Int $user_id)
    {
        return DB::table('user_gudang')->select('gudang_id')->where('user_id', $user_id)->pluck('gudang_id');
    }

    //terbilang dalam rupiah
    public static function penyebut($nilai) {
        $nilai = abs($nilai);
        $huruf = array("", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas");
        $temp = "";
        if ($nilai < 12) {
            $temp = " ". $huruf[$nilai];
        } else if ($nilai <20) {
            $temp = Helper::penyebut($nilai - 10). " Belas";
        } else if ($nilai < 100) {
            $temp = Helper::penyebut($nilai/10)." Puluh". Helper::penyebut($nilai % 10);
        } else if ($nilai < 200) {
            $temp = " Seratus" . Helper::penyebut($nilai - 100);
        } else if ($nilai < 1000) {
            $temp = Helper::penyebut($nilai/100) . " Ratus" . Helper::penyebut($nilai % 100);
        } else if ($nilai < 2000) {
            $temp = " Seribu" . Helper::penyebut($nilai - 1000);
        } else if ($nilai < 1000000) {
            $temp = Helper::penyebut($nilai/1000) . " Ribu" . Helper::penyebut($nilai % 1000);
        } else if ($nilai < 1000000000) {
            $temp = Helper::penyebut($nilai/1000000) . " Juta" . Helper::penyebut($nilai % 1000000);
        } else if ($nilai < 1000000000000) {
            $temp = Helper::penyebut($nilai/1000000000) . " Milyar" . Helper::penyebut(fmod($nilai,1000000000));
        } else if ($nilai < 1000000000000000) {
            $temp = Helper::penyebut($nilai/1000000000000) . " Trilyun" . Helper::penyebut(fmod($nilai,1000000000000));
        }
        return $temp;
    }
    public static function terbilang($nilai) {
        if($nilai<0) {
            $hasil = "minus ". trim(Helper::penyebut($nilai) .' Rupiah');
        } else {
            $hasil = trim(Helper::penyebut($nilai) .' Rupiah' );
        }
        return $hasil;
    }

    public static function sisaLimit(Int $id):int
    {
        $ketentuan_toko = KetentuanToko::where('id_toko', $id)->first();
        $list_penjualan_belum_lunas = Penjualan::where('tipe_pembayaran', 'credit')
            ->where('id_toko', $id)
            ->whereIn('status', ['approved', 'delivered'])
            ->whereDate('tanggal', '>=', '2020-08-18')
            ->whereNull('paid_at')
            ->oldest()
            ->get();
        $total_belum_lunas = $list_penjualan_belum_lunas->sum('grand_total');
        return $ketentuan_toko->limit - $total_belum_lunas;
    }

    public static function listOD(Int $id):array
    {
        $od             = [];
        $today          = Carbon::today()->toDateString();
        // belum lunas
        $list_penjualan_belum_lunas = Penjualan::where('tipe_pembayaran', 'credit')
            ->where('id_toko', $id)
            ->whereIn('status', ['approved', 'delivered'])
            ->whereDate('tanggal', '>=', '2020-08-18')
            ->whereNull('paid_at')
            ->oldest()
            ->get();
        // od
        foreach ($list_penjualan_belum_lunas as $belum_lunas) {
            $due_date   = Carbon::parse($belum_lunas->due_date);
            $over_due = $due_date->diffInDays($today, false);
            if ($over_due > 0) {
                $delivered      = Carbon::parse($belum_lunas->delivered_at);
                $umur_piutang   = $delivered->diffInDays($today, false);

                $od[] = [
                    'invoice'       => $belum_lunas->no_invoice,
                    'due_date'      => $belum_lunas->due_date,
                    'over_due'      => $over_due,
                    'umur'          => $umur_piutang,
                    'grand_total'   => round($belum_lunas->grand_total)
                ];
            }
        }

        return $od;
    }

    public static function principalByUser(Int $user_id)
    {
        return DB::table('user_principal')->select('principal_id')
            ->where('user_id', $user_id)->pluck('principal_id');
    }

    public static function rupiah($nominal)
    {
        $nominal = round($nominal);
        return number_format($nominal, 0, ',', '.');
    }

    public static function transactionLimit($except,$module)
    {
        $idDepos = [
            1, //DENPASAR KPM
            7, //HCO KPM
            18, //HCOC KPM
            19, //DENPASAR AKM
        ];
        $idDepoAllowace = DB::table('user_depo')->where('user_id',$except->id)->whereIn('depo_id',$idDepos)->count();
        if($idDepoAllowace == 0) return false;
        if($except->can('Tambah Penjualan Overtime') && $module == 'penjualan.store') return false;
        if($except->can('Approve Penjualan Overtime') && $module == 'penjualan.approve') return false;
        if($except->can('Delete Penjualan Overtime') && $module == 'penjualan.destroy') return false;
        if($except->can('Update Penjualan Overtime') && $module == 'penjualan.update') return false;
        $dt = Carbon::now('Asia/Ujung_Pandang')->toTimeString();
        // GANTI JAMNYA GANTI DISAPMPING DT TU!
        if($dt>'15:00:00') return true;
        return false;
    }

    public static function returValueLimit($id_tim,$id_toko)
    {
        $builder = new Builder();
        $startDate = Carbon::now();
        $firstDay  = Carbon::now()->firstOfMonth();
        $dateFilter = [$firstDay,$startDate];
        DB::enableQueryLog();
        $data = $builder->table('retur_penjualan')
        ->join('detail_retur_penjualan','retur_penjualan.id','detail_retur_penjualan.id_retur_penjualan')
        ->join('barang','barang.id','id_barang')
        ->join('tim','tim.id','id_tim')
        ->selectRaw(
            'tim.tipe,'.
            $builder->request([
                'parameter' => $builder->paramDefault('retur_penjualan'),
                'select'    => 'sum',
                'using'     => 'grand_total_retur',
                'as'        => 'grand_total',
            ])
        )
        ->where('id_tim',$id_tim)
        ->where('id_toko',$id_toko)
        ->where('barang.tipe','bebas_retur')
        ->whereBetween('sales_retur_date',$dateFilter)
        ->get();
        $limit = [
            'to' => 200000,
            'canvas' => 50000,
        ];
        
        if($data[0]->tipe != null){
            return $limit[$data[0]->tipe] < intval($data[0]->grand_total) ? true : false;
        }
        return false;
    }
}
