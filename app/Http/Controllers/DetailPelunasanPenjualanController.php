<?php

namespace App\Http\Controllers;

use App\Models\Depo;
use App\Models\Mitra;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tymon\JWTAuth\JWTAuth;
use App\Models\DetailPelunasanPenjualan;
use App\Models\Penjualan;
use App\Models\KetentuanToko;
use App\Models\RiwayatSaldoRetur;
use App\Http\Resources\DetailPelunasanPenjualan as DetailPelunasanPenjualanResource;
use App\Http\Resources\DetailPelunasanPenjualanReport as DetailPelunasanPenjualanReportResource;
use App\Traits\ExcelStyle;
use App\Helpers\Helper;
use App\Models\Salesman;

class DetailPelunasanPenjualanController extends Controller
{
    use ExcelStyle;

    protected $jwt;

    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
    }
    public function index(Request $request)
    {
        $id_mitra = $request->has('id_mitra') && $request->id_mitra != '' ? $request->id_mitra : 'include';
        $list_detail_pelunasan_penjualan = DetailPelunasanPenjualan::with(['penjualan', 'penjualan.toko', 'penjualan.salesman', 'penjualan.tim'])
            ->whereHas('penjualan', function ($q) use ($id_mitra) {
                $q->when($id_mitra <> 'exclude' && $id_mitra <> 'include', function ($q) use ($id_mitra) {
                    $q->where('id_mitra', $id_mitra);
                })
                    ->when($id_mitra == 'exclude', function ($q) {
                        $q->where('id_mitra', '=', 0);
                    });
            })
            ->latest();

        // filter salesman
        if ($request->has('id_salesman') && $request->id_salesman != '' && $request->id_salesman != 'all') {;
            $id_salesman = $request->id_salesman;
            $salesman   = Salesman::find($id_salesman);
            $id_tim     = $salesman->tim->id;
            $list_detail_pelunasan_penjualan = $list_detail_pelunasan_penjualan->whereHas('penjualan', function ($q) use ($id_tim) {
                $q->whereHas('toko.ketentuan_toko', function ($q) use ($id_tim) {
                    $q->where('id_tim', $id_tim);
                });
            });
        }

        if (!$this->user->hasRole('Salesman Canvass')) {
            $list_detail_pelunasan_penjualan = $list_detail_pelunasan_penjualan->whereHas('penjualan', function ($q) {
                $q->where('tipe_pembayaran', 'credit');
            });
        }

        // filter tipe : tunai, transfer, bilyet_giro, saldo_retur, lainnya, semua
        if ($request->has('tipe') && $request->tipe != '' && $request->tipe != 'semua') {
            $list_detail_pelunasan_penjualan = $list_detail_pelunasan_penjualan->where('tipe', $request->tipe);
        }

        // filter status : waiting, approved, rejected, semua
        if ($request->has('status') && $request->status != '' && $request->status != 'semua') {
            $status = $request->status;
            $list_detail_pelunasan_penjualan = $list_detail_pelunasan_penjualan->where('status', $status);
            if (is_array($status)) {
                $status = explode($status, ',');
                $list_detail_pelunasan_penjualan = $list_detail_pelunasan_penjualan->whereIn('status', $status);
            } else {
                $list_detail_pelunasan_penjualan = $list_detail_pelunasan_penjualan->where('status', $status);
            }
        }

        // filter date, atau start_date + end_date
        if ($request->has('date')) {
            $list_detail_pelunasan_penjualan = $list_detail_pelunasan_penjualan->where('tanggal', 'like', $request->date . '%');
        } elseif ($request->has(['start_date', 'end_date'])) {
            if (!$request->has('date_bg') || $request->date_bg == '') {
                $start_date = $request->start_date;
                $end_date = $request->end_date;
                $list_detail_pelunasan_penjualan = $list_detail_pelunasan_penjualan->whereBetween('tanggal', [$start_date, $end_date]);
            }
        }
        if ($request->has('date_bg') && $request->date_bg != '') {
            $date_bg = $request->date_bg;
            $list_detail_pelunasan_penjualan = $list_detail_pelunasan_penjualan->where('jatuh_tempo_bg', $date_bg);
        }

        //Filter By Depo yang dimiliki User (id_depo berupa array) [1,2,3]
        if ($request->depo != null) {
            $id_depo = $request->depo;
        } else {
            $id_depo = Helper::depoIDByUser($this->user->id);
        }

        $list_detail_pelunasan_penjualan = $list_detail_pelunasan_penjualan
            ->whereHas('penjualan', function ($q) use ($id_depo) {
                $q->whereIn('id_depo', $id_depo);
            });
        //End Filter Depo yang dimiliki User (id_depo berupa array) [1,2,3]

        // keyword : id, bank, no_bg, no_rekening, id_penjualan, no_invoice, nama_toko, no_acc, cust_no
        if ($request->has('keyword') && $request->keyword != '') {
            $keyword = $request->keyword;
            $list_detail_pelunasan_penjualan = $list_detail_pelunasan_penjualan->where(function ($q) use ($keyword) {
                $q->where('id_penjualan', $keyword)
                    ->orWhere('bank', 'like', '%' . $keyword . '%')
                    ->orWhere('no_rekening', 'like', '%' . $keyword . '%')
                    ->orWhere('no_bg', 'like', '%' . $keyword . '%')
                    ->orWhere('jatuh_tempo_bg', 'like', '%' . $keyword . '%')
                    ->orWhereHas('penjualan', function ($query) use ($keyword) {
                        $query->where('no_invoice', 'like', '%' . $keyword . '%')
                            ->orWhereHas('toko', function ($query) use ($keyword) {
                                $query->where('nama_toko', 'like', '%' . $keyword . '%')
                                    ->orWhere('no_acc', 'like', '%' . $keyword . '%')
                                    ->orWhere('cust_no', 'like', '%' . $keyword . '%');
                            });
                    });
            });
        }

        $perPage = $request->has('per_page') ? $perPage = $request->per_page : $perPage = 5;
        $list_detail_pelunasan_penjualan = $perPage == 'all' ? $list_detail_pelunasan_penjualan->get() : $list_detail_pelunasan_penjualan->paginate((int)$perPage);

        if ($list_detail_pelunasan_penjualan) {
            return DetailPelunasanPenjualanResource::collection($list_detail_pelunasan_penjualan);
        }
        return response()->json([
            'message' => 'Data Pelunasan Penjualan tidak ditemukan!'
        ], 404);
    }

    public function show($id)
    {

        $detail_pelunasan_penjualan = DetailPelunasanPenjualan::find($id);

        if ($detail_pelunasan_penjualan) {
            return new DetailPelunasanPenjualanResource($detail_pelunasan_penjualan);
        }
        return response()->json([
            'message' => 'Data Detail Penjualan tidak ditemukan!'
        ], 404);
    }

    public function store(Request $request)
    {
        if (!$this->user->can('Tambah Detail Pelunasan Penjualan')) {
            return $this->Unauthorized();
        }

        $this->validate($request, [
            'id_penjualan' => 'required|exists:penjualan,id',
            'tipe' => 'required|in:tunai,transfer,bilyet_giro,saldo_retur,lainnya',
            'nominal' => 'required|numeric|min:0',
            'jatuh_tempo' => 'nullable|date',
            'tanggal' => 'nullable|date'
        ]);

        $input = $request->all();
        $input['status'] = 'waiting';
        $input['created_by'] = $this->user->id;
        $input['tanggal'] = $request->has('tanggal') ? $request->tanggal : date('Y-m-d');

        if ($request->tipe == 'tunai') {
            $input['bank'] = null;
            $input['no_rekening'] = null;
            $input['no_bg'] = null;
            $input['jatuh_tempo_bg'] = null;
        } elseif ($request->tipe == 'transfer') {
            $input['no_bg'] = null;
            $input['jatuh_tempo_bg'] = null;
        } elseif ($request->tipe == 'bilyet_giro') {
            // $input['no_rekening'] = null;
        } elseif ($request->tipe == 'saldo_retur') {
            $input['bank'] = null;
            $input['no_rekening'] = null;
            $input['no_bg'] = null;
            $input['jatuh_tempo_bg'] = null;
        } elseif ($request->tipe == 'lainnya') {
            $input['bank'] = null;
            $input['no_rekening'] = null;
            $input['no_bg'] = null;
            $input['jatuh_tempo_bg'] = null;
        }

        try {
            $detail_pelunasan_penjualan = DetailPelunasanPenjualan::create($input);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

        return response()->json([
            'message' => 'Data Pelunasan Penjualan berhasil disimpan.',
            'data' => new DetailPelunasanPenjualanResource($detail_pelunasan_penjualan)
        ], 201);
    }

    public function update(Request $request, $id)
    {
        if (!$this->user->can('Update Detail Pelunasan Penjualan')) {
            return $this->Unauthorized();
        }

        $this->validate($request, [
            // 'id_penjualan' => 'required|numeric|min:0',
            'tipe' => 'required|in:tunai,transfer,bilyet_giro,saldo_retur,lainnya',
            'nominal' => 'required|numeric|min:0',
            'jatuh_tempo' => 'nullable|date',
            'tanggal' => 'nullable|date'
        ]);

        $detail_pelunasan_penjualan = DetailPelunasanPenjualan::find($id);
        if ($detail_pelunasan_penjualan->status == 'approved') {
            return response()->json([
                'message' => 'Anda tidak boleh mengubah data Pelunasan Penjualan yang telah disetujui.'
            ], 422);
        }

        $input = $request->all();
        $input['id_penjualan'] = $detail_pelunasan_penjualan->id_penjualan;
        $input['created_by'] = $this->user->id;
        $input['tanggal'] = $request->has('tanggal') ? $request->tanggal : date('Y-m-d');

        if ($request->tipe == 'tunai') {
            $input['bank'] = null;
            $input['no_rekening'] = null;
            $input['no_bg'] = null;
            $input['jatuh_tempo_bg'] = null;
        } elseif ($request->tipe == 'transfer') {
            $input['no_bg'] = null;
            $input['jatuh_tempo_bg'] = null;
        } elseif ($request->tipe == 'bilyet_giro') {
            // $input['no_rekening'] = null;
        } elseif ($request->tipe == 'saldo_retur') {
            $input['bank'] = null;
            $input['no_rekening'] = null;
            $input['no_bg'] = null;
            $input['jatuh_tempo_bg'] = null;
        } elseif ($request->tipe == 'lainnya') {
            $input['bank'] = null;
            $input['no_rekening'] = null;
            $input['no_bg'] = null;
            $input['jatuh_tempo_bg'] = null;
        }

        $detail_pelunasan_penjualan->update($input);

        return response()->json([
            'message' => 'Data Pelunasan Penjualan telah berhasil diubah.',
            'data' => new DetailPelunasanPenjualanResource($detail_pelunasan_penjualan)
        ], 201);
    }

    public function destroy($id, Request $request)
    {
        if (!$this->user->can('Hapus Detail Pelunasan Penjualan')) {
            return $this->Unauthorized();
        }

        $detail_pelunasan_penjualan = DetailPelunasanPenjualan::find($id);
        if (!$detail_pelunasan_penjualan) {
            return response()->json([
                'message' => 'Data Detail Pelunasan Penjualan tidak ditemukan.'
            ], 422);
        }

        if ($detail_pelunasan_penjualan->status == 'approved') {
            return response()->json([
                'message' => 'Anda tidak boleh menghapus data barang pada penjualan yang telah disetujui.'
            ], 422);
        }

        if ($detail_pelunasan_penjualan) {
            $data = ['deleted_by' => $this->user->id];
            $detail_pelunasan_penjualan->update($data);
            $detail_pelunasan_penjualan->delete();

            return response()->json([
                'message' => 'Data Pelunasan Penjualan berhasil dihapus.',
            ], 200);
        }

        return response()->json([
            'message' => 'Data Pelunasan Penjualan tidak ditemukan!'
        ], 404);
    }

    public function jumlah_belum_dibayar($id_penjualan)
    {
        $penjualan = Penjualan::find($id_penjualan);
        $jumlah_kredit = $penjualan->grand_total;
        $jumlah_lunas = DetailPelunasanPenjualan::where('id_penjualan', $id_penjualan)->where('status', '!=', 'rejected')->sum('nominal');
        $jumlah_belum_dibayar = $jumlah_kredit - $jumlah_lunas;

        return response()->json([
            'jumlah_kredit' => (int)round($jumlah_kredit, 0),
            'jumlah_lunas' => (int)round($jumlah_lunas, 0),
            'jumlah_belum_dibayar' => (int)round($jumlah_belum_dibayar, 0)
        ], 200);
    }

    public function approve($id)
    {
        if (!$this->user->can('Approve Pelunasan Penjualan')) {
            return $this->Unauthorized();
        }

        $detail_pelunasan_penjualan = DetailPelunasanPenjualan::find($id);
        if ($detail_pelunasan_penjualan->status == 'approved') {
            return response()->json([
                'message' => 'Data Pelunasan Penjualan yang telah disetujui.'
            ], 422);
        }

        $id_penjualan = $detail_pelunasan_penjualan->id_penjualan;
        $penjualan = Penjualan::find($id_penjualan);
        if ($this->user->hasRole('Salesman Canvass') && $penjualan->tipe_pembayaran === 'credit') {
            return $this->Unauthorized();
        }

        try {
            DB::beginTransaction();
            // ketika approval tipe saldo_retur, kurangi saldo_retur di tabel ketentuan toko
            if ($detail_pelunasan_penjualan->tipe == 'saldo_retur') {
                // get saldo retur toko
                $id_toko = $detail_pelunasan_penjualan->penjualan->id_toko;
                $toko = KetentuanToko::find($id_toko);
                $toko ? $saldo_retur = $toko->saldo_retur : $saldo_retur = 0;

                $data_saldo_retur['id_toko'] = $id_toko;
                $data_saldo_retur['saldo_awal'] = $saldo_retur;

                // CEK jika nominal yg diajukan lebih besar dari saldo retur toko tsb, return error
                if ($saldo_retur < $detail_pelunasan_penjualan->nominal) {
                    return response()->json([
                        'message' => 'Saldo retur outlet tidak mencukupi.'
                    ], 422);
                }

                $toko->decrement('saldo_retur', $detail_pelunasan_penjualan->nominal);

                $data_saldo_retur['saldo_akhir']    = $toko->saldo_retur;
                $data_saldo_retur['keterangan']     = $toko->toko->nama_toko . ' menggunakan saldo retur sebesar Rp. ' . $detail_pelunasan_penjualan->nominal . ' untuk pelunasan Invoice ' . $detail_pelunasan_penjualan->penjualan->no_invoice . '.';
                $data_saldo_retur['id_retur_penjualan'] = $id; // GANTI NAMA FIELDNYA JADI ID_FOREIGN_KEY
                RiwayatSaldoRetur::create($data_saldo_retur);
            }

            $input['status'] = 'approved';
            $input['approved_by'] = $this->user->id;
            $input['approved_at'] = Carbon::now()->toDateTimeString();
            $detail_pelunasan_penjualan->update($input);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Data Pelunasan Penjualan gagal disetujui.',
            ], 400);
        }

        try {
            DB::beginTransaction();
            // cek jika detail pelunasan yg sudah terapprove pada suatu nota jumlahnya >= grand_total
            // ubah status paid_at nya
            $sum_pelunasan = DetailPelunasanPenjualan::where('id_penjualan', $id_penjualan)->where('status', 'approved')->sum('nominal');

            $grand_total = round($penjualan->grand_total);
            $selisih = $sum_pelunasan - $grand_total;
            if ($selisih >= 1000) {
                throw new \Exception('Pelunasan lebih');
            }

            if ($selisih >= -1 && $selisih <= 1000) {
                $penjualan->update(['paid_at' => Carbon::now()->toDateTimeString()]);
            }

            $logData = [
                'action'        => 'Approve Pelunasan Penjualan',
                'description'   => 'No Invoice: ' . $detail_pelunasan_penjualan->penjualan->no_invoice,
                'user_id'       => $this->user->id
            ];

            $this->log($logData);
            DB::commit();
            return response()->json([
                'message' => 'Data Pelunasan Penjualan telah berhasil disetujui.',
                'paid_at' => $penjualan->paid_at
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            $detail_pelunasan_penjualan->update(['status' => 'waiting']);
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function reject($id)
    {
        if (!$this->user->can('Reject Pelunasan Penjualan')) {
            return $this->Unauthorized();
        }

        $detail_pelunasan_penjualan = DetailPelunasanPenjualan::find($id);

        if ($detail_pelunasan_penjualan->status == 'approved') {
            return response()->json([
                'message' => 'Data Pelunasan Penjualan yang telah disetujui.'
            ], 422);
        }

        $input['status'] = 'rejected';
        $detail_pelunasan_penjualan->update($input);

        return response()->json([
            'message' => 'Pelunasan Penjualan telah berhasil ditolak.',
            'data' => new DetailPelunasanPenjualanResource($detail_pelunasan_penjualan)
        ], 201);
    }

    public function cancel_approval($id)
    {
        if (!$this->user->can('Cancel Approve Pelunasan Penjualan')) {
            return $this->Unauthorized();
        }

        $detail_pelunasan_penjualan = DetailPelunasanPenjualan::find($id);

        $input['status'] = 'waiting';
        $input['approved_by'] = null;
        $input['approved_at'] = null;

        $detail_pelunasan_penjualan->update($input);

        // ketika cancel_approval tipe saldo_retur, kembalikan saldo_retur di tabel ketentuan toko
        if ($detail_pelunasan_penjualan->tipe == 'saldo_retur') {
            $toko = KetentuanToko::find($detail_pelunasan_penjualan->penjualan->id_toko);
            if ($toko) {
                $toko->increment('saldo_retur', $detail_pelunasan_penjualan->nominal);
            }
        }

        $penjualan = Penjualan::find($detail_pelunasan_penjualan->id_penjualan);
        $penjualan->update(['paid_at' => null]);

        // hapus riwayat saldo retur, find by id_retur_penjualan (id_detail_pelunasan)
        $riwayat_saldo_retur = RiwayatSaldoRetur::where('id_retur_penjualan', $id)->delete();

        return response()->json([
            'message' => 'Pelunasan Penjualan telah berhasil dibatalkan.',
            'data' => new DetailPelunasanPenjualanResource($detail_pelunasan_penjualan)
        ], 201);
    }

    public function download_pembayaran(Request $request)
    {
        $dateText = "";
        $list_detail_pelunasan_penjualan = DetailPelunasanPenjualan::with([
            'penjualan',
            'penjualan.toko:id,no_acc,cust_no,nama_toko',
            'penjualan.toko.ketentuan_toko:id_toko,id_tim',
            'penjualan.toko.ketentuan_toko.tim:id,nama_tim',
            'penjualan.toko.ketentuan_toko.tim.salesman:user_id,id_tim,kode_eksklusif',
            'penjualan.toko.ketentuan_toko.tim.salesman.user:id,name'
        ])->latest();

        // filter salesman
        if ($request->has('id_salesman') && $request->id_salesman != '' && $request->id_salesman != 'all') {
            $salesman = Salesman::find($request->id_salesman);
            $id_tim   = $salesman->tim->id;
            $list_detail_pelunasan_penjualan = $list_detail_pelunasan_penjualan->whereHas('penjualan', function ($q) use ($id_tim) {
                $q->whereHas('toko.ketentuan_toko', function ($q) use ($id_tim) {
                    $q->where('id_tim', $id_tim);
                });
            });
        }

        // filter tipe : tunai, transfer, bilyet_giro, saldo_retur, lainnya, semua
        if ($request->has('tipe') && $request->tipe != '' && $request->tipe != 'semua') {
            $list_detail_pelunasan_penjualan = $list_detail_pelunasan_penjualan->where('tipe', $request->tipe);
        }

        // filter status : waiting, approved, rejected, semua
        if ($request->has('status') && $request->status != '' && $request->status != 'semua') {
            $status = $request->status;
            $status = is_array($status) ? $status : [$status];
            $list_detail_pelunasan_penjualan = $list_detail_pelunasan_penjualan->whereIn('status', $status);
        }

        // filter date, atau start_date + end_date
        if ($request->has('date')) {
            $list_detail_pelunasan_penjualan = $list_detail_pelunasan_penjualan->where('tanggal', 'like', $request->date . '%');
        } elseif ($request->has(['start_date', 'end_date']) && $request->date_bg == '') {
            $start_date = $request->start_date;
            $end_date   = $request->end_date;
            $list_detail_pelunasan_penjualan = $list_detail_pelunasan_penjualan->whereBetween('tanggal', [$start_date, $end_date]);
            $dateText = "{$start_date}-{$end_date}";
        }

        if ($request->has('date_bg') && $request->date_bg != '') {
            $date_bg = $request->date_bg;
            $list_detail_pelunasan_penjualan = $list_detail_pelunasan_penjualan->where('jatuh_tempo_bg', $date_bg);
            $dateText = "bg_{$date_bg}";
        }

        //Filter By Depo yang dimiliki User (id_depo berupa array) [1,2,3]
        if ($request->has('depo') && count($request->depo) > 0) {
            $depo_id = $request->depo;
        } else {
            $depo_id = Helper::depoIDByUser($this->user->id);
        }
        $list_detail_pelunasan_penjualan = $list_detail_pelunasan_penjualan
            ->whereHas('penjualan', function ($q) use ($depo_id) {
                $q->whereIn('id_depo', $depo_id);
            });
        //End Filter Depo yang dimiliki User (id_depo berupa array) [1,2,3]

        // keyword : id, bank, no_bg, no_rekening, id_penjualan, no_invoice, nama_toko, no_acc, cust_no
        if ($request->has('keyword') && $request->keyword != '') {
            $keyword = $request->keyword;
            $list_detail_pelunasan_penjualan = $list_detail_pelunasan_penjualan->where(function ($q) use ($keyword) {
                $q->where('id_penjualan', $keyword)
                    ->orWhere('bank', 'like', '%' . $keyword . '%')
                    ->orWhere('no_rekening', 'like', '%' . $keyword . '%')
                    ->orWhere('no_bg', 'like', '%' . $keyword . '%')
                    ->orWhere('jatuh_tempo_bg', 'like', '%' . $keyword . '%')
                    ->orWhereHas('penjualan', function ($query) use ($keyword) {
                        $query->where('no_invoice', 'like', '%' . $keyword . '%')
                            ->orWhereHas('toko', function ($query) use ($keyword) {
                                $query->where('nama_toko', 'like', '%' . $keyword . '%')
                                    ->orWhere('no_acc', 'like', '%' . $keyword . '%')
                                    ->orWhere('cust_no', 'like', '%' . $keyword . '%');
                            });
                    });
            });
        }

        $list_pembayaran = $list_detail_pelunasan_penjualan->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Pembayaran');

        $i = 1;
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setAutoSize(true);
        $sheet->getColumnDimension('C')->setAutoSize(true);
        $sheet->getColumnDimension('D')->setAutoSize(true);
        $sheet->getColumnDimension('E')->setAutoSize(true);
        $sheet->getColumnDimension('F')->setAutoSize(true);
        $sheet->getColumnDimension('G')->setWidth(18);
        $sheet->getColumnDimension('H')->setAutoSize(true);
        $sheet->getColumnDimension('I')->setWidth(18);
        $sheet->getColumnDimension('J')->setWidth(15);
        $sheet->getColumnDimension('K')->setAutoSize(true);
        $sheet->getColumnDimension('L')->setWidth(15);
        $sheet->getColumnDimension('M')->setWidth(15);
        $sheet->getColumnDimension('N')->setWidth(15);
        $sheet->getColumnDimension('O')->setWidth(15);
        $sheet->getColumnDimension('P')->setWidth(15);
        $sheet->getColumnDimension('Q')->setWidth(35);

        $cells  = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q'];
        $columns = ['No', 'No Invoice', 'No Acc', 'Cust No', 'Nama Toko', 'Salesman Lama', 'Nama Tim Lama', 'Salesman Baru', 'Nama Tim Baru', 'Tipe', 'Nominal', 'Bank', 'No Rekening', 'No BG', 'Jatuh Tempo', 'Status', 'Keterangan'];
        $sheet->getStyle('A' . $i . ':Q' . $i)->getFont()->setBold(true);
        $sheet->getStyle('A' . $i . ':Q' . $i)->applyFromArray($this->horizontalCenter());
        $sheet->getStyle('A' . $i . ':Q' . $i)->applyFromArray($this->border());
        foreach ($columns as $key => $column) {
            $sheet->setCellValue($cells[$key] . $i, $column);
        }

        $sheet->setAutoFilter('A' . $i . ':Q' . $i);

        $i++;
        $start = $i;
        foreach ($list_pembayaran as $key => $pembayaran) {
            $sheet->setCellValue('A' . $i, $key + 1);
            $sheet->setCellValueExplicit('B' . $i, $pembayaran->penjualan->no_invoice, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('C' . $i, $pembayaran->penjualan->toko->no_acc, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('D' . $i, $pembayaran->penjualan->toko->cust_no, DataType::TYPE_STRING);
            $sheet->setCellValue('E' . $i, $pembayaran->penjualan->toko->nama_toko);
            $sheet->setCellValue('F' . $i, $pembayaran->penjualan->salesman->user->name);
            $sheet->setCellValue('G' . $i, $pembayaran->penjualan->nama_tim);
            $sheet->setCellValue('H' . $i, $pembayaran->penjualan->toko->ketentuan_toko->tim->salesman->user->name);
            $sheet->setCellValue('I' . $i, $pembayaran->penjualan->toko->ketentuan_toko->tim->nama_tim);
            $sheet->setCellValue('J' . $i, $pembayaran->tipe);
            $sheet->setCellValue('K' . $i, $pembayaran->nominal);
            $sheet->setCellValue('L' . $i, $pembayaran->bank);
            $sheet->setCellValue('M' . $i, $pembayaran->no_rekening);
            $sheet->setCellValue('N' . $i, $pembayaran->no_bg);
            $sheet->setCellValue('O' . $i, $pembayaran->jatuh_tempo_bg);
            $sheet->setCellValue('P' . $i, $pembayaran->status);
            $sheet->setCellValue('Q' . $i, $pembayaran->keterangan);
            $i++;
        }

        $sheet->getStyle('A1:Q' . $i)->applyFromArray($this->fontSize(14));
        $sheet->setCellValue('J' . $i, 'Total');
        $end = $i - 1;
        $sheet->setCellValue('K' . $i, "=SUBTOTAL(9, K{$start}:K{$end})");
        $sheet->getStyle('J' . $i . ':K' . $i)->applyFromArray($this->fontSize(16));
        $sheet->getStyle('K' . $start . ':K' . $i)->getNumberFormat()->setFormatCode('_(* #,##0_);_(* (#,##0);_(* "-"??_);_(@_)');
        $sheet->getStyle('A' . $start . ':Q' . $i)->applyFromArray($this->border());
        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        $content = ob_get_contents();
        ob_end_clean();
        $fileName = "pembayaran_{$dateText}.xlsx";
        Storage::disk('local')->put('excel/' . $fileName, $content);
        $file = url('/excel/' . $fileName);
        return response()->json($file, 200);
    }

    public function download_pembayaran_custom(Request $request)
    {
        $judul = '-';
        $id_mitra = $request->has('id_mitra') && $request->id_mitra <> '' ? $request->id_mitra : 'include';
        $status = $request->has('status') && $request->status <> '' ? $request->status : 'all';
        if ($request->tipe == 'transfer') {
            $judul = 'BUKTI BANK MASUK';
        } else if ($request->tipe == 'bilyet_giro') {
            $judul = 'BUKTI MEMORIAL';
            if ($status == 'approved') {
                $judul = 'BUKTI BANK MASUK';
            }
        } else if ($request->tipe == 'tunai') {
            $judul = 'BUKTI KAS MASUK PENAGIHAN';
        } else if ($request->tipe == 'saldo_retur') {
            $judul = 'BUKTI SALDO RETUR';
        }
        $judul_laporan = $judul;

        $list_detail_pelunasan_penjualan = DetailPelunasanPenjualan::select(DB::raw('
                                                    detail_pelunasan_penjualan.*,
                                                    toko.nama_toko,
                                                    toko.no_acc,
                                                    toko.cust_no,
                                                    penjualan.id_depo,
                                                    penjualan.no_invoice,
                                                    penjualan.id_salesman,
                                                    penjualan.id_tim,
                                                    penjualan.id_toko,
                                                    penjualan.delivered_at
                                                '))
            ->join('penjualan', 'detail_pelunasan_penjualan.id_penjualan', '=', 'penjualan.id')
            ->join('toko', 'penjualan.id_toko', '=', 'toko.id')
            ->join('ketentuan_toko', 'ketentuan_toko.id_toko', '=', 'toko.id')
            ->when($id_mitra <> 'exclude' && $id_mitra <> 'include', function ($q) use ($id_mitra) {
                $q->where('penjualan.id_mitra', $id_mitra);
            })
            ->when($id_mitra == 'exclude', function ($q) {
                $q->where('penjualan.id_mitra', '=', 0);
            })
            ->orderBy('detail_pelunasan_penjualan.created_at', 'DESC');

        // filter salesman , get name salasmen & team
        $team = 'ALL TEAM';
        if ($request->has('id_salesman') && $request->id_salesman != '' && $request->id_salesman != 'all') {;
            $id_salesman = $request->id_salesman;
            $salesman   = Salesman::find($id_salesman);
            $id_tim     = $salesman->tim->id;
            $list_detail_pelunasan_penjualan = $list_detail_pelunasan_penjualan->where('ketentuan_toko.id_tim', $id_tim);

            $salesman = Salesman::where('user_id', $id_salesman)
                ->join('tim', 'salesman.id_tim', '=', 'tim.id')
                ->first();
            $team = $salesman->nama_tim . ' / ' . $salesman->user->name;
        }

        // KHUSUS CREDIT
        if (!$this->user->hasRole('Salesman Canvass')) {
            $list_detail_pelunasan_penjualan = $list_detail_pelunasan_penjualan->where('penjualan.tipe_pembayaran', 'credit');
        }

        // filter tipe : tunai, transfer, bilyet_giro, saldo_retur, lainnya, semua
        if ($request->has('tipe') && $request->tipe != '' && $request->tipe != 'all') {
            $list_detail_pelunasan_penjualan = $list_detail_pelunasan_penjualan->where('detail_pelunasan_penjualan.tipe', $request->tipe);
        }

        // filter status : waiting, approved, rejected, semua
        if ($status != 'all') {
            $list_detail_pelunasan_penjualan = $list_detail_pelunasan_penjualan->where('detail_pelunasan_penjualan.status', $status);
            if (is_array($status)) {
                $status = explode($status, ',');
                $list_detail_pelunasan_penjualan = $list_detail_pelunasan_penjualan->whereIn('detail_pelunasan_penjualan.status', $status);
            } else {
                $list_detail_pelunasan_penjualan = $list_detail_pelunasan_penjualan->where('detail_pelunasan_penjualan.status', $status);
            }
        }


        // filter date, atau start_date + end_date
        if ($request->has('date')) {
            $list_detail_pelunasan_penjualan = $list_detail_pelunasan_penjualan->where('detail_pelunasan_penjualan.tanggal', 'like', $request->date . '%');
        } elseif ($request->has(['start_date', 'end_date'])) {
            if (!$request->has('date_bg') || $request->date_bg == '') {
                $start_date = $request->start_date;
                $end_date = $request->end_date;
                $list_detail_pelunasan_penjualan = $list_detail_pelunasan_penjualan->whereBetween('detail_pelunasan_penjualan.tanggal', [$start_date, $end_date]);
            }
        }

        if ($request->has('date_bg') && $request->date_bg != '') {
            $date_bg = $request->date_bg;
            $list_detail_pelunasan_penjualan = $list_detail_pelunasan_penjualan->where('jatuh_tempo_bg', $date_bg);
        }

        //Filter By Depo yang dimiliki User (id_depo berupa array) [1,2,3]
        if ($request->has('depo') && count($request->depo) > 0) {
            $id_depo = $request->depo;
        } else {
            $id_depo = Helper::depoIDByUser($this->user->id);
        }

        $list_detail_pelunasan_penjualan = $list_detail_pelunasan_penjualan
            ->whereIn('penjualan.id_depo', $id_depo);
        //End Filter Depo yang dimiliki User (id_depo berupa array) [1,2,3]

        // keyword : id, bank, no_bg, no_rekening, id_penjualan, no_invoice, nama_toko, no_acc, cust_no
        if ($request->has('keyword') && $request->keyword != '') {
            $keyword = $request->keyword;
            $list_detail_pelunasan_penjualan = $list_detail_pelunasan_penjualan->where(function ($q) use ($keyword) {
                $q->where('detail_pelunasan_penjualan.id_penjualan', $keyword)
                    ->orWhere('detail_pelunasan_penjualan.bank', 'like', '%' . $keyword . '%')
                    ->orWhere('detail_pelunasan_penjualan.no_rekening', 'like', '%' . $keyword . '%')
                    ->orWhere('detail_pelunasan_penjualan.no_bg', 'like', '%' . $keyword . '%')
                    ->orWhere('detail_pelunasan_penjualan.jatuh_tempo_bg', 'like', '%' . $keyword . '%')
                    ->orWhere('penjualan.no_invoice', 'like', '%' . $keyword . '%')
                    ->orWhere('toko.nama_toko', 'like', '%' . $keyword . '%')
                    ->orWhere('toko.no_acc', 'like', '%' . $keyword . '%')
                    ->orWhere('toko.cust_no', 'like', '%' . $keyword . '%');
            });
        }

        $list_detail_pelunasan_penjualan = $list_detail_pelunasan_penjualan->get();
        $collection = collect($list_detail_pelunasan_penjualan);
        $total_debet = $collection->sum('nominal');
        $terbilang_total_debet = Helper::terbilang($total_debet);
        $nama_perusahaan = '';
        if (is_numeric($id_mitra)) {
            $mitra = Mitra::find($id_mitra);
            $nama_perusahaan = $mitra->perusahaan;
        } else {
            $depo = Depo::find($id_depo[0]);
            $nama_perusahaan = $depo->perusahaan->nama_perusahaan;
        }

        $data = [];
        if ($request->tipe == 'transfer') {
            $cust_no_tf = array_unique($list_detail_pelunasan_penjualan
                ->where('tipe', 'transfer')
                ->whereBetween('tanggal', [$request->start_date, $request->end_date])
                ->pluck('cust_no')
                ->toArray());

            foreach ($cust_no_tf as $no) {
                $collection = collect($list_detail_pelunasan_penjualan->whereBetween('tanggal', [$request->start_date, $request->end_date]));
                $coll = $collection->where('cust_no', $no);
                if ($coll->first()->tipe == 'transfer') {
                    if (!$coll->isEmpty()) {
                        $data[] = [
                            'cust_no' => $no,
                            'bank' => $coll->first()->bank,
                            'no_rekening' => $coll->first()->no_rekening,
                            'tipe' => $coll->first()->tipe,
                            'debet' => $coll->sum('nominal'),
                            'no_acc' => $coll->first()->no_acc,
                            'nama_toko' => $coll->first()->nama_toko,
                            'nama_tim' => $coll->first()->penjualan->nama_tim,
                            'lokasi_toko' => $coll->first()->penjualan->depo->kabupaten,
                            'no_bg' => $coll->first()->no_bg,
                            'tanggal_approve' => $coll->first()->approved_at,
                            'jatuh_tempo_bg' => $coll->first()->jatuh_tempo_bg,
                            'detail_report' => DetailPelunasanPenjualanReportResource::collection($coll),
                        ];
                    }
                }
            }
        } else if ($request->tipe == 'bilyet_giro') {
            $no_bg = array_unique($list_detail_pelunasan_penjualan->pluck('no_bg')->toArray());
            $cust_no = array_unique($list_detail_pelunasan_penjualan->pluck('cust_no')->toArray());

            foreach ($no_bg as $no) {
                $coll = $collection->where('no_bg', '===', $no, true);
                // if($this->user->id == 102) {
                //     return response()->json($coll->toArray(), 200);
                // }
                if (!$coll->isEmpty()) {
                    $data[] = [
                        'cust_no' => $coll->first()->cust_no,
                        'bank' => $coll->first()->bank,
                        'no_rekening' => $coll->first()->no_rekening,
                        'tipe' => $coll->first()->tipe,
                        'debet' => $coll->sum('nominal'),
                        'no_acc' => $coll->first()->no_acc,
                        'nama_toko' => $coll->first()->nama_toko,
                        'nama_tim' => $coll->first()->penjualan->nama_tim,
                        'lokasi_toko' => $coll->first()->penjualan->depo->kabupaten,
                        'no_bg' => $coll->first()->no_bg,
                        'tanggal' => $coll->first()->tanggal,
                        'tanggal_approve' => $coll->first()->approved_at,
                        'jatuh_tempo_bg' => $coll->first()->jatuh_tempo_bg,
                        'detail_report' => DetailPelunasanPenjualanReportResource::collection($coll),
                    ];
                }
            }
        } else {
            $cust_no = array_unique($list_detail_pelunasan_penjualan->pluck('cust_no')->toArray());
            foreach ($cust_no as $no) {
                $coll = $collection->where('cust_no', $no);
                if (!$coll->isEmpty()) {
                    $data[] = [
                        'cust_no' => $no,
                        'bank' => $coll->first()->bank,
                        'no_rekening' => $coll->first()->no_rekening,
                        'tipe' => $coll->first()->tipe,
                        'debet' => $coll->sum('nominal'),
                        'no_acc' => $coll->first()->no_acc,
                        'nama_toko' => $coll->first()->nama_toko,
                        'nama_tim' => $coll->first()->penjualan->nama_tim,
                        'lokasi_toko' => $coll->first()->penjualan->depo->kabupaten,
                        'no_bg' => $coll->first()->no_bg,
                        'jatuh_tempo_bg' => $coll->first()->jatuh_tempo_bg,
                        'detail_report' => DetailPelunasanPenjualanReportResource::collection($coll),
                    ];
                }
            }
        }

        $collection = collect($data);
        $total_debet = $collection->sum('debet');

        if ($list_detail_pelunasan_penjualan) {
            return response()->json([
                'nama_perusahaan' => $nama_perusahaan,
                'data' => $data,
                'judul' => $judul_laporan,
                'tipe' => $request->tipe,
                'total' => $total_debet,
                'terbilang' => Helper::terbilang($total_debet),
                'team' => $team
            ], 200);
        }
        return response()->json([
            'message' => 'Data Pelunasan Penjualan tidak ditemukan!'
        ], 404);
    }
}
