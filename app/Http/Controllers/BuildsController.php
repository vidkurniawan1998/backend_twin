<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Perusahaan;
use Tymon\JWTAuth\JWTAuth;

class BuildsController extends Controller
{
    public function view_service_level_query_builder(Request $request)
    {
        $tanggal_awal = date('2021-11-29');
        $tanggal_akhir = date('2021-11-30');

        $data = Perusahaan::join('penjualan', 'perusahaan.id', 'penjualan.id_perusahaan')
            ->join('tim', 'penjualan.id_tim', 'tim.id')
            ->join('users as sales', 'penjualan.id_salesman', 'sales.id')
            ->join('users as admin', 'penjualan.created_by', 'admin.id')
            ->select(
                'perusahaan.nama_perusahaan',
                'perusahaan.kode_perusahaan',
                'penjualan.tanggal',
                'penjualan.id',
                'penjualan.no_invoice',
                'penjualan.tipe_pembayaran',
                'penjualan.due_date',
                'penjualan.status',
                'tim.nama_tim',
                'penjualan.id_salesman',
                'sales.name as nama_sales',
                'admin.name as nama_input'
            )
            ->whereNotNull('no_invoice')
            ->whereBetween('penjualan.tanggal', [$tanggal_awal, $tanggal_akhir])
            ->orderByDesc('penjualan.tanggal')
            ->limit(10)->get();

        return response()->json($data);
    }
}
