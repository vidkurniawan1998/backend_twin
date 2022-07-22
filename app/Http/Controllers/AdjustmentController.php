<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\JWTAuth;
use App\Models\Adjustment;
use App\Models\DetailAdjustment;
use App\Models\PosisiStock;
use App\Models\Gudang;
use App\Models\Stock;
use App\Http\Resources\Adjustment as AdjustmentResource;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use \PhpOffice\PhpSpreadsheet\Cell\DataType;
use Carbon\Carbon;
use App\Helpers\Helper;
use Illuminate\Support\Facades\Storage;
use App\Traits\ExcelStyle;


class AdjustmentController extends Controller
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

        if ($this->user->can('Menu Adjustment Barang')) :
            $list_adjustment = Adjustment::with('gudang', 'gudang.depo', 'gudang.depo.perusahaan')->latest();

            // Filter Gudang
            $gudang = $request->get('id_gudang') ?? '';
            if ($gudang <> '' && $gudang <> 'all') {
                $list_adjustment = $list_adjustment->where('id_gudang', $gudang);
            }

            // Filter Date
            if ($request->has('date')) {
                $list_adjustment = $list_adjustment->where('tanggal', $request->date);
            } elseif ($request->has(['start_date', 'end_date'])) {
                $list_adjustment = $list_adjustment->whereBetween('tanggal', [$request->start_date, $request->end_date]);
            }

            // Filter Status
            $status = $request->get('status') ?? '';
            if ($status <> '' && $status <> 'all') {
                $list_adjustment = $list_adjustment->where('status', $status);
            }

            // Filter Keyword (id,no_adjustment,keterangan, nama_gudang)
            $keyword = $request->get('keyword') ?? '';
            if ($keyword <> '') {
                $list_adjustment = $list_adjustment->where(function ($q) use ($keyword) {
                    $q->where('id', $keyword)
                        ->orWhere('id', 'like', '%' . $keyword . '%')
                        ->orWhere('no_adjustment', 'like', '%' . $keyword . '%')
                        ->orWhere('keterangan', 'like', '%' . $keyword . '%')
                        ->orWhereHas('gudang', function ($query) use ($keyword) {
                            $query->where('nama_gudang', 'like', '%' . $keyword . '%');
                        });
                });
            }

            // filter data sesuai depo user
            $id_depo = Helper::depoIDByUser($this->user->id);
            $list_adjustment = $list_adjustment->whereHas('gudang', function ($query) use ($id_depo) {
                $query->whereIn('id_depo', $id_depo);
            });

            $perPage = $request->has('per_page') ? $perPage = $request->per_page : $perPage = 5;
            $list_adjustment = $perPage == 'all' ? $list_adjustment->get() : $list_adjustment->paginate((int)$perPage);
            if ($list_adjustment) {
                return AdjustmentResource::collection($list_adjustment);
            }
            return response()->json([
                'message' => 'Data Adjustment tidak ditemukan!'
            ], 404);
        else :
            return $this->Unauthorized();
        endif;
    }

    public function show($id)
    {
        if ($this->user->can('Edit Adjustment Barang')) :
            $adjustment = Adjustment::find($id);

            if ($adjustment) {
                return new AdjustmentResource($adjustment);
            }
            return response()->json([
                'message' => 'Data Adjustment tidak ditemukan!'
            ], 404);
        else :
            return $this->Unauthorized();
        endif;
    }

    public function store(Request $request)
    {
        if ($this->user->can('Tambah Adjustment Barang')) :
            $this->validate($request, [
                'id_gudang' => 'required|numeric|min:0|max:9999999999',
                // 'tanggal' => 'required|date',
            ]);

            $input = $request->all();
            $input['tanggal'] = Carbon::today()->toDateString();
            $input['status'] = 'waiting';
            $input['no_adjustment'] = null;
            $input['created_by'] = $this->user->id;

            try {
                $adjustment = Adjustment::create($input);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 400);
            }

            return response()->json([
                'message' => 'Data Adjustment berhasil disimpan.',
                'data' => AdjustmentResource::collection($this->index($request))
            ], 201);
        else :
            return $this->Unauthorized();
        endif;
    }

    public function update(Request $request, $id)
    {
        if ($this->user->can('Update Adjustment Barang')) :

            $adjustment = Adjustment::find($id);

            if ($adjustment->status == 'approved') {
                return response()->json([
                    'message' => 'Data Adjustment tidak boleh diubah karena telah disetujui.'
                ], 422);
            }

            $this->validate($request, [
                'id_gudang' => 'required|numeric|min:0|max:9999999999',
                // 'tanggal' => 'required|date',
            ]);

            $input = $request->all();
            $input['no_adjustment'] = $adjustment->no_adjustment;
            $input['tanggal'] = $adjustment->tanggal;
            $input['updated_by'] = $this->user->id;

            if ($adjustment) {
                $adjustment->update($input);

                return response()->json([
                    'message' => 'Data Adjustment telah berhasil diubah.',
                    'data' => new AdjustmentResource($adjustment)
                ], 201);
            }

            return response()->json([
                'message' => 'Data Adjustment tidak ditemukan.'
            ], 404);
        else :
            return $this->Unauthorized();
        endif;
    }

    public function destroy($id, Request $request)
    {
        if ($this->user->can('Hapus Adjustment Barang')) :
            $adjustment = Adjustment::find($id);
            if (!$adjustment) {
                return response()->json([
                    'message' => 'Data Adjustment tidak ditemukan!'
                ], 400);
            }

            if ($adjustment->status == 'approved') {
                return response()->json([
                    'message' => 'Data Adjustment tidak boleh dihapus karena telah disetujui.'
                ], 422);
            }

            if ($adjustment) {
                $data = ['deleted_by' => $this->user->id];
                $adjustment->update($data);
                $adjustment->delete();

                $list_adjustment = Adjustment::latest();

                // Filter Gudang
                if ($request->has('id_gudang')) {
                    if ($request->id_gudang != 'all' && $request->id_gudang != '' && $request->id_gudang != null) {
                        $list_adjustment = $list_adjustment->where('id_gudang', $request->id_gudang);
                    }
                }

                // Filter Date
                if ($request->has('date')) {
                    $list_adjustment = $list_adjustment->where('tanggal', $request->date);
                } elseif ($request->has(['start_date', 'end_date'])) {
                    $list_adjustment = $list_adjustment->whereBetween('tanggal', [$request->start_date, $request->end_date]);
                }

                // Filter Status
                if ($request->has('status') && $request->status != '' && $request->status != 'all') {
                    $list_adjustment = $list_adjustment->where('status', $request->status);
                }

                // Filter Keyword (id,no_adjustment,keterangan, nama_gudang)
                if ($request->has('keyword') && $request->keyword != '') {
                    $keyword = $request->keyword;
                    $list_adjustment = $list_adjustment->where(function ($q) use ($keyword) {
                        $q->where('id', $keyword)
                            ->orWhere('id', 'like', '%' . $keyword . '%')
                            ->orWhere('no_adjustment', 'like', '%' . $keyword . '%')
                            ->orWhere('keterangan', 'like', '%' . $keyword . '%')
                            ->orWhereHas('gudang', function ($query) use ($keyword) {
                                $query->where('nama_gudang', 'like', '%' . $keyword . '%');
                            });
                    });
                }

                $perPage = $request->has('per_page') ? $perPage = $request->per_page : $perPage = 1;
                $list_adjustment = $perPage == 'all' ? $list_adjustment->get() : $list_adjustment->paginate((int)$perPage);

                return response()->json([
                    'message' => 'Data Adjustment berhasil dihapus.',
                    'data' => AdjustmentResource::collection($list_adjustment)
                ], 200);
            }

            return response()->json([
                'message' => 'Data Adjustment tidak ditemukan!'
            ], 404);
        else :
            return $this->Unauthorized();
        endif;
    }

    // public function restore($id) {
    //     if ($this->user->role != 'admin' && $this->user->role != 'pimpinan'){
    //         return response()->json([
    //             'message' => 'Anda tidak berhak untuk mengakses fungsi ini!'
    //         ], 400);
    //     }

    //     $adjustment = Adjustment::withTrashed()->find($id);

    //     if($adjustment) {
    //         $data = ['deleted_by' => null];
    //         $adjustment->update($data);

    //         $adjustment->restore();

    //         return response()->json([
    //             'message' => 'Data Adjustment berhasil dikembalikan.'
    //         ], 200);
    //     }

    //     return response()->json([
    //         'message' => 'Data Adjustment tidak ditemukan!'
    //     ], 404);
    // }

    public function approve(Request $request, $id)
    {
        if ($this->user->can('Approve Adjustment Barang')) :

            $adjustment = Adjustment::find($id);

            if ($adjustment->status == 'approved') {
                return response()->json([
                    'message' => 'Data Adjustment telah disetujui.'
                ], 422);
            }

            $detail_adjustment = DetailAdjustment::where('id_adjustment', $id)->get();

            if ($detail_adjustment->count() <= 0) {
                return response()->json([
                    'message' => 'Data Adjustment Barang masih kosong, isi data barang terlebih dahulu.'
                ], 422);
            }
            
            $message = [];
            foreach ($detail_adjustment as $da) {
                $stock = Stock::find($da->id_stock);
                $volume_stock = ($stock->qty * $stock->isi) + $stock->qty_pcs;
                $volume_adj = ($da->qty_adj * $stock->isi) + $da->pcs_adj;
                $sisa_stock = $volume_stock + $volume_adj;

                if ($volume_adj < 0) {
                    if ($sisa_stock < 0) {
                        $message[]= 'Stock ' .$stock->barang->kode_barang. ' - ' . $stock->barang->nama_barang . ' tidak cukup';
                        // return response()->json([
                        //     'message' => 'Stock ' .$stock->barang->kode_barang. ' - ' . $stock->barang->nama_barang . ' tidak cukup',
                        // ], 422);
                    }
                }
            }
            
            if(count($message) > 0) {
                return response()->json([
                    'message' => implode(" ,", $message),
                ], 422);
            }

            DB::beginTransaction();
            try {
                $logStock = [];
                foreach ($detail_adjustment as $da) {
                    $stock = Stock::find($da->id_stock);
                    $posisi_stock = PosisiStock::where('id_stock', $stock->id)->where('tanggal', Carbon::today()->toDateString())->first();
                    if (!$posisi_stock) {
                        $posisi_stock = PosisiStock::create([
                            'id_stock' => $stock->id,
                            // 'tanggal' => $adjustment->tanggal,
                            'tanggal' => Carbon::today()->toDateString(),
                            'harga' => $stock->dbp,
                            'saldo_awal_qty' => $stock->qty,
                            'saldo_awal_pcs' => $stock->qty_pcs,
                            'saldo_akhir_qty' => $stock->qty,
                            'saldo_akhir_pcs' => $stock->qty_pcs,
                        ]);
                    }

                    // tambah stock gudang
                    $stock->increment('qty', $da->qty_adj);
                    $stock->increment('qty_pcs', $da->pcs_adj);
                    while ($stock->qty_pcs < 0) {
                        $stock->decrement('qty');
                        $stock->increment('qty_pcs', $stock->isi);
                    }

                    // catat riwayat pergerakan stock
                    $posisi_stock->increment('adjustment_qty', $da->qty_adj);
                    $posisi_stock->increment('adjustment_pcs', $da->pcs_adj);
                    $posisi_stock->increment('saldo_akhir_qty', $da->qty_adj);
                    $posisi_stock->increment('saldo_akhir_pcs', $da->pcs_adj);
                    while ($posisi_stock->saldo_akhir_pcs < 0) {
                        $posisi_stock->decrement('saldo_akhir_qty');
                        $posisi_stock->increment('saldo_akhir_pcs', $stock->isi);
                    }

                    $logStock[] = [
                        'tanggal'       => $adjustment->tanggal,
                        'id_barang'     => $stock->id_barang,
                        'id_gudang'     => $stock->id_gudang,
                        'id_user'       => $this->user->id,
                        'id_referensi'  => $da->id,
                        'referensi'     => 'adjustment',
                        'no_referensi'  => $id,
                        'qty_pcs'       => ($da->qty_adj * $stock->isi) + $da->pcs_adj,
                        'status'        => 'approved',
                        'created_at'    => Carbon::now(),
                        'updated_at'    => Carbon::now()
                    ];
                }

                // ======================= GENERATE NOMOR INVOICE =======================
                $kode_depo = Gudang::find($adjustment->id_gudang)->depo->kode_depo;
                $today = Carbon::today()->format('dmy');

                $keyword = '.' . $today . '.' . $kode_depo;
                $list_no_adjustment = \DB::table('adjustment')->where('no_adjustment', 'like', '%' . $keyword)->pluck('no_adjustment');

                if (count($list_no_adjustment) == 0) {
                    $string_no = '00001';
                } else {
                    $arr = [];
                    foreach ($list_no_adjustment as $value) {
                        array_push($arr, (int)substr($value, strrpos($value, '-') + 1));
                    };
                    $new_no = max($arr) + 1;
                    $string_no = sprintf("%05d", $new_no);
                }

                $adjustment->no_adjustment = $string_no . '.' . $today . '.' . $kode_depo;
                // ======================================================================

                $adjustment->status = 'approved';
                $adjustment->save();
                $this->createLogStock($logStock);
                DB::commit();
                return response()->json([
                    'message' => 'Data Adjustment berhasil disetujui.',
                    'no_adjustment' => $adjustment->no_adjustment
                ], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Gagal menyimpan adjustment.'
                ], 400);
            }
        else :
            return $this->Unauthorized();
        endif;
    }

    public function cancel_approval(Request $request, $id) {

        $adjustment = Adjustment::find($id);

        if($adjustment->status == 'waiting') {
            return response()->json([
                'message' => 'Adjustment Barang telah dibatalkan.'
            ], 422);
        }

        $detail_adjustment = DetailAdjustment::where('id_adjustment', $id)->get();

        DB::beginTransaction();
        try {
            $logStock = [];
            foreach ($detail_adjustment as $da) {
                $stock          = Stock::find($da->id_stock);
                $posisi_stock = PosisiStock::where('id_stock', $stock->id)->where('tanggal', Carbon::today()->toDateString())->first();
                if (!$posisi_stock) {
                    $posisi_stock = PosisiStock::create([
                        'id_stock' => $stock->id,
                        // 'tanggal' => $adjustment->tanggal,
                        'tanggal' => Carbon::today()->toDateString(),
                        'harga' => $stock->dbp,
                        'saldo_awal_qty' => $stock->qty,
                        'saldo_awal_pcs' => $stock->qty_pcs,
                        'saldo_akhir_qty' => $stock->qty,
                        'saldo_akhir_pcs' => $stock->qty_pcs,
                    ]);
                }

                // kurangi stock gudang
                $stock->decrement('qty', $da->qty_adj);
                $stock->decrement('qty_pcs', $da->pcs_adj);

                // catat riwayat pergerakan stock
                $posisi_stock->decrement('adjustment_qty', $da->qty_adj);
                $posisi_stock->decrement('adjustment_pcs', $da->pcs_adj);
                $posisi_stock->decrement('saldo_akhir_qty', $da->qty_adj);
                $posisi_stock->decrement('saldo_akhir_pcs', $da->pcs_adj);
            }
            
            $this->deleteLogStock([
                ['referensi', 'adjustment'],
                ['no_referensi', $adjustment->id],
                ['status', 'approved']
            ]);

            $adjustment->status = 'waiting';
            $adjustment->save();
            DB::commit();
            return response()->json([
                'message' => 'Data Adjustment berhasil dibatalkan.',
                'no_adjustment' => $adjustment->no_adjustment
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function laporan_adjustment(Request $request)
    {   

        if ($this->user->can('Menu Laporan Adjustment')):
           $adjustment = DB::table('adjustment')
            ->leftJoin('gudang','gudang.id','=','adjustment.id_gudang')
            ->leftJoin('detail_adjustment','adjustment.id','=','detail_adjustment.id_adjustment')
            ->leftJoin('stock','stock.id','=','detail_adjustment.id_stock')
            ->leftJoin('barang','barang.id','=','stock.id_barang')
            ->leftJoin('segmen','segmen.id','=','barang.id_segmen')
            ->leftJoin('brand','brand.id','=','segmen.id_brand')
            ->leftJoin('depo','depo.id','=','gudang.id_depo')
            ->leftJoin('perusahaan','perusahaan.id','=','depo.id_perusahaan')
            ->where('no_adjustment','>',0)
            ->select(
                'tanggal',
                'no_adjustment',
                'nama_gudang',
                'item_code',
                'kode_barang',
                'nama_barang',
                'nama_segmen',
                'nama_brand',
                'qty_adj',
                'pcs_adj',
                'isi',
                'adjustment.status',
                'depo.id as id_depo',
                'perusahaan.id as id_perusahaan'
            )
            ->whereBetween('tanggal', [$request['start_date'], $request['end_date']])
            ->where('perusahaan.id','=',$request['id_perusahaan']);

            //Filter
            if($request['id_depo']>0){$adjustment     = $adjustment->where('depo.id','=',$request['id_depo']);}
            if($request['status']!='all'){$adjustment = $adjustment->where('adjustment.status','=',$request['status']);}
            if($request['id_gudang']>0){$adjustment   = $adjustment->where('gudang.id','=',$request['id_gudang']);}

            if($request['keyword']!=''){
                $keyword = $request['keyword'];
                $adjustment= $adjustment ->where(function($query) {
                        $query->where('no_adjustment',  'like', '%' . $keyword . '%')
                            ->orWhere('kode_barang', 'like', '%' . $keyword . '%')
                            ->orWhere('nama_gudang', 'like', '%' . $keyword . '%')
                            ->orWhere('item_code',   'like', '%' . $keyword . '%')
                            ->orWhere('nama_barang', 'like', '%' . $keyword . '%');
                            });

                           
            }

            $adjustment = $adjustment->get();
            return response()->json([ 
                "file" => $this->adjustment_barang_excel($adjustment),
                "data" => $adjustment,
            ]);
        else:
            return $this->Unauthorized();
        endif;
    }



    public function adjustment_barang_excel($adjustment)
    {
        //Create Tampilan Excel
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Laporan Adjustment Barang');

        $i = 1;
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
        $sheet->getColumnDimension('C')->setAutoSize(true);
        $sheet->getColumnDimension('D')->setAutoSize(true);
        $sheet->getColumnDimension('E')->setAutoSize(true);
        $sheet->getColumnDimension('F')->setAutoSize(true);
        $sheet->getColumnDimension('G')->setAutoSize(true);
        $sheet->getColumnDimension('H')->setAutoSize(true);
        $sheet->getColumnDimension('I')->setAutoSize(true);
        $sheet->getColumnDimension('J')->setAutoSize(true);
        $sheet->getColumnDimension('L')->setAutoSize(true);
        $sheet->getColumnDimension('L')->setAutoSize(true);

        $cells  = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L'];
        $columns = [
            'Tanggal', 'No Adjustment', 'Nama Gudang', 'Item Code', 'Kode Barang', 'Nama Barang',
            'Nama Segmen', 'Nama Brand', 'Qty', 'Qty Pcs', 'Isi', 'Status'
        ];
        $sheet->getStyle('A' . $i . ':L' . $i)->getFont()->setBold(true);
        $sheet->getStyle('A' . $i . ':L' . $i)->applyFromArray($this->horizontalCenter());
        $sheet->getStyle('A' . $i . ':L' . $i)->applyFromArray($this->border());

        foreach ($columns as $key => $column) {
            $sheet->setCellValue($cells[$key] . $i, $column);
        }

        $sheet->setAutoFilter('A' . $i . ':L' . $i);

        $i++;
        $start = $i;
        foreach ($adjustment as $data) {
            $data = (array) $data;
            $sheet->setCellValue('A' . $i, $data["tanggal"]);
            $sheet->setCellValue('B' . $i, $data["no_adjustment"]);
            $sheet->setCellValue('C' . $i, $data["nama_gudang"]);
            $sheet->setCellValue('D' . $i, $data["item_code"]);
            $sheet->setCellValue('E' . $i, $data["kode_barang"]);
            $sheet->setCellValue('F' . $i, $data["nama_barang"]);
            $sheet->setCellValue('G' . $i, $data["nama_segmen"]);
            $sheet->setCellValue('H' . $i, $data["nama_brand"]);
            $sheet->setCellValue('I' . $i, $data["qty_adj"]);
            $sheet->setCellValue('J' . $i, $data["pcs_adj"]);
            $sheet->setCellValue('K' . $i, $data["isi"]);
            $sheet->setCellValue('L' . $i, $data["status"]);
            $i++;
        }

        $sheet->getStyle('A1:L' . $i)->applyFromArray($this->fontSize(14));
        $end = $i - 1;
        $sheet->getStyle('A' . $start . ':L' . $i)->applyFromArray($this->border());

        $spreadsheet->setActiveSheetIndex(0);

        $today = Carbon::today();
        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        $content = ob_get_contents();
        ob_end_clean();
        $now = $today->toDateString();
        $fileName = "laporan_adjustment_barang_{$now}.xlsx";
        Storage::disk('local')->put('excel/' . $fileName, $content);
        $file = url('/excel/' . $fileName);
        return $file;
    }

}
