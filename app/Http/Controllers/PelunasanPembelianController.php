<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Traits\ExcelStyle;

use App\Helpers\Helper;
use App\Models\FakturPembelian;
use App\Models\Principal;
use App\Http\Resources\FakturPembelian as FakturPembelianResources;
use App\Http\Resources\PelunasanPembelian as PelunasanPembelianResources;
use Carbon\Carbon;
use Tymon\JWTAuth\JWTAuth;
use Illuminate\Support\Facades\DB;


class PelunasanPembelianController extends Controller
{
    use ExcelStyle;
    protected $jwt;
    public function __construct(JWTAuth $jwt){
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
    }

    public function index(Request $request)
    {
        if ($this->user->can('Menu Pelunasan Pembelian')):

            $id_user        = $this->user->id;
            $id_perusahaan  = $request->has('id_perusahaan') && count($request->id_perusahaan)>0 ? $request->id_perusahaan : Helper::perusahaanByUser($id_user);
            $id_principal   = $request->has('id_principal') && count($request->id_principal) > 0 ? $request->id_principal : [];
            $status         = $request->status;
            $tanggal        = $request->has('tanggal') && $request->due_date!='' && $request->due_date!='0000-00-00' ? $request->due_date 
                              : Carbon::today()->toDateString();

            $data = FakturPembelian::with(['principal','detail_faktur_pembelian','detail_pelunasan_pembelian'])
                ->where('status','approved')
                ->when(count($id_perusahaan) > 0, function ($q) use ($id_perusahaan) {
                    $q->whereIn('id_perusahaan', $id_perusahaan);
                })
                ->when(count($id_principal) > 0, function ($q) use ($id_principal) {
                    $q->whereIn('id_principal', $id_principal);
                });

            if($status == 'due_date'){ // semua pelunasan yang jatuh tempo hari ini (atau sesuai tanggal inputan)
                $data = $data->where('tanggal_jatuh_tempo', $tanggal);
            } elseif ($status == 'lunas'){ // yang dilunasi hari ini (atau sesuai tanggal inputan)
                $data = $data->whereDate('tanggal_bayar', '<=', $tanggal);
            } elseif($status == 'belum_lunas'){ // yang belum dilunasi hari ini (atau sesuai tanggal inputan)
                $data = $data->whereNull('tanggal_bayar')->whereDate('tanggal_invoice', '<=', $tanggal);
            } elseif($status == 'over_due'){
                $data = $data->whereNull('tanggal_bayar')->where('tanggal_jatuh_tempo', '<=',  $tanggal);
            } else {
                $data = $data->where('tanggal','<=', $tanggal);
            }

            if($request->has('keyword') && $request->keyword != ''){
                $keyword = $request->keyword;
                $data = $data->where(function ($q) use ($keyword) {
                    $q->where('id', $keyword)
                        ->orWhere('no_invoice', 'like', '%' . $keyword . '%')
                        ->orWhere('tanggal', 'like', '%' . $keyword . '%')
                        ->orWhereHas('principal', function ($query) use ($keyword){
                            $query->where('nama_principal', 'like', '%' . $keyword . '%')
                                    ->orWhere('alamat', 'like', '%' . $keyword . '%');
                    });
                });
            }
            $data = $data->orderBy('id', 'desc');
           $perPage = $request->has('per_page') ? $perPage = $request->per_page : $perPage = 10;
           $data = $perPage == 'all' ? $data->get() : $data->paginate((int)$perPage);
           return PelunasanPembelianResources::collection($data);
        else:
            return $this->Unauthorized();
        endif;
    }

    public function show($id)
    {
        if ($this->user->can('Menu Pelunasan Pembelian')):
            $detailPelunasan = DB::table('faktur_pembelian')
            ->join('detail_pelunasan_pembelian','detail_pelunasan_pembelian.id_faktur_pembelian','=','faktur_pembelian.id')
            ->select('detail_pelunasan_pembelian.tanggal',
                    'faktur_pembelian.id as id_faktur_pembelian',
                    'tipe',
                    'nominal',
                    'bank',
                    'no_rekening',
                    'no_bg',
                    'jatuh_tempo_bg',
                    'keterangan',
                    'detail_pelunasan_pembelian.status',
                    'detail_pelunasan_pembelian.id')
            ->where('faktur_pembelian.id',$id)
            ->where('detail_pelunasan_pembelian.deleted_at',null)
            ->get();
            return response()->json($detailPelunasan);
        else:
            return $this->Unauthorized();
        endif;
    }


    public function laporan_pelunasan_download(Request $request)
    {
        if ($this->user->can('Menu Pelunasan Pembelian')):
            $id_user        = $this->user->id;
            $id_perusahaan  = $request->has('id_perusahaan') && count($request->id_perusahaan)>0 ? $request->id_perusahaan : Helper::perusahaanByUser($id_user);
            $id_principal   = $request->has('id_principal') && count($request->id_principal) > 0 ? $request->id_principal : [];
            $status         = $request->has('status') ? $request->status : '';
            $tanggal        = $request->has('due_date') && $request->due_date!='' && $request->due_date!='0000-00-00' ? $request->due_date : Carbon::today()->toString();
            //Ambil data sesuai dengan data yang di filter
            $data = FakturPembelian::with(['principal','detail_faktur_pembelian','detail_pelunasan_pembelian','penerimaan_barang'])
                ->where('status','approved')
                ->when(count($id_perusahaan) > 0, function ($q) use ($id_perusahaan) {
                    $q->whereIn('id_perusahaan', $id_perusahaan);
                })
                ->when(count($id_principal) > 0, function ($q) use ($id_principal) {
                    $q->whereIn('id_principal', $id_principal);
                });

            // $data = $data->get();
            // $data_pelunasan = $data->where('id_principal',1);
            // $res = $data_pelunasan[0]->penerimaan_barang;
            // return response()->json($res);

            if($status == 'due_date'){ // semua pelunasan yang jatuh tempo hari ini (atau sesuai tanggal inputan)
                $data = $data->where('tanggal_jatuh_tempo', $tanggal);
            } elseif ($status == 'lunas'){ // yang dilunasi hari ini (atau sesuai tanggal inputan)
                $data = $data->whereDate('tanggal_bayar', $tanggal);
            } elseif($status == 'belum_lunas'){ // yang belum dilunasi hari ini (atau sesuai tanggal inputan)
                $data = $data->whereNull('tanggal_bayar')->whereDate('tanggal_invoice', '<=', $tanggal);
            } elseif($status == 'over_due'){
                $data = $data->whereNull('tanggal_bayar')->where('tanggal_jatuh_tempo', '<=',  $tanggal);
            } else {
                $data = $data->where('tanggal','<=', $tanggal);
            }

            $today = Carbon::today();

            $data = $data->orderBy('id_principal', 'DESC')->orderBy('tanggal_jatuh_tempo', 'ASC')->get();

            //Untuk Mendapatkan Jumlah Toko
            $id_principal = array_unique($data->pluck('id_principal')->toArray());
            //End Untuk Mendapatkan Jumlah Toko



            //Create Tampilan Excel
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Estimasi Penagihan');

            $i = 1;
            $sheet->getColumnDimension('A')->setWidth(5);
            $sheet->getColumnDimension('B')->setAutoSize(true);
            $sheet->getColumnDimension('C')->setAutoSize(true);
            $sheet->getColumnDimension('D')->setAutoSize(true);
            $sheet->getColumnDimension('E')->setAutoSize(true);
            $sheet->getColumnDimension('F')->setAutoSize(true);
            $sheet->getColumnDimension('G')->setAutoSize(true);

            $cells  = ['A', 'B', 'C', 'D', 'E', 'F','G','H'];
            $columns= ['','Tgl Faktur', 'Tgl JT', 'No Invoice','No Penerimaan Barang', 'Grand Total','Pembayaran', 'Sisa'];
            $sheet->getStyle('A'.$i.':H'.$i)->getFont()->setBold(true);
            $sheet->getStyle('A'.$i.':H'.$i)->applyFromArray($this->horizontalCenter());
            $sheet->getStyle('A'.$i.':H'.$i)->applyFromArray($this->border());
            foreach ($columns as $key => $column) {
                $sheet->setCellValue($cells[$key].$i, $column);
            }

            //create isi berdasarkan pricipal
            foreach ($id_principal as $principal_id) {
                $data_principal = Principal::where('id',$principal_id)->first();
                $data_pelunasan = $data->where('id_principal',$principal_id);
                $nama_principal = $data_principal->nama_principal;
                $i++;
                $sheet->setCellValue('A'.$i, $nama_principal);
                $sheet->mergeCells('A' . $i . ':H' . $i);
                $sheet->getStyle('A'.$i.':H'.$i)->getFont()->setBold(true);
                foreach ($data_pelunasan as $pelunasan) {
                    $data_pb = $pelunasan->penerimaan_barang;
                    $grandTotal      = $pelunasan->grand_total;
                    $pembayaranTotal = $pelunasan->detail_pelunasan_pembelian->where('status','approved')->sum('nominal');
                    $start_merge = $i+1;
                    foreach ($data_pb as $penerimaan_barang) {
                        $i++;
                        $sheet->setCellValue('A'.$i, '');
                        $sheet->setCellValue('B'.$i, $pelunasan->tanggal_invoice);
                        $sheet->setCellValue('C'.$i, $pelunasan->tanggal_jatuh_tempo);
                        $sheet->setCellValue('D'.$i, $pelunasan->no_invoice);
                        $sheet->setCellValue('E'.$i, $penerimaan_barang->no_pb);
                        $sheet->setCellValue('F'.$i, round($grandTotal,0));
                        $sheet->setCellValue('G'.$i, round($pembayaranTotal,0));
                        $sheet->setCellValue('H'.$i, round(($grandTotal-$pembayaranTotal),0));
                    }
                    $sheet->mergeCells('A' . $start_merge . ':A' . $i);
                    $sheet->mergeCells('B' . $start_merge . ':B' . $i);
                    $sheet->mergeCells('C' . $start_merge . ':C' . $i);
                    $sheet->mergeCells('D' . $start_merge . ':D' . $i);
                    $sheet->mergeCells('F' . $start_merge . ':F' . $i);
                    $sheet->mergeCells('G' . $start_merge . ':G' . $i);
                    $sheet->mergeCells('H' . $start_merge . ':H' . $i);
                }
            }

            $sheet->getStyle('A2:H'.$i)->applyFromArray($this->verticalCenter());
            $sheet->getStyle('F3:H'.$i)->getNumberFormat()->setFormatCode('_(* #,##0_);_(* (#,##0);_(* "-"??_);_(@_)');

            //Create Sheet 2
            $spreadsheet->createSheet();
            // Zero based, so set the second tab as active sheet
            $sheet2 = $spreadsheet->setActiveSheetIndex(1);
            $sheet2 = $spreadsheet->getActiveSheet()->setTitle('Detail Penagihan');


            $i = 1;
            $sheet2->getColumnDimension('A')->setWidth(5);
            $sheet2->getColumnDimension('B')->setAutoSize(true);
            $sheet2->getColumnDimension('C')->setAutoSize(true);
            $sheet2->getColumnDimension('D')->setAutoSize(true);
            $sheet2->getColumnDimension('E')->setAutoSize(true);
            $sheet2->getColumnDimension('F')->setAutoSize(true);
            $sheet2->getColumnDimension('G')->setWidth(15);
            $sheet2->getColumnDimension('H')->setAutoSize(true);
            $sheet2->getColumnDimension('I')->setAutoSize(true);
            $sheet2->getColumnDimension('J')->setWidth(25);
            $sheet2->getColumnDimension('K')->setWidth(25);
            $sheet2->getColumnDimension('L')->setWidth(25);
            $sheet2->getColumnDimension('M')->setAutoSize(true);
            $sheet2->getColumnDimension('N')->setWidth(15);
            $sheet2->getColumnDimension('O')->setWidth(15);

            $cells  = ['A', 'B', 'C', 'D', 'E', 'F'];
            $columns= ['No','No Invoice', 'Nama Principal', 'Tgl Bayar','Total', 'Tipe Pembayaran'];
            $sheet2->getStyle('A'.$i.':F'.$i)->getFont()->setBold(true);
            $sheet2->getStyle('A'.$i.':F'.$i)->applyFromArray($this->horizontalCenter());
            $sheet2->getStyle('A'.$i.':F'.$i)->applyFromArray($this->border());
            foreach ($columns as $key => $column) {
                $sheet2->setCellValue($cells[$key].$i, $column);
            }

            $sheet2->setAutoFilter('A'.$i.':F'.$i);

            foreach ($data as $data_detail) {
                foreach ($data_detail['detail_pelunasan_pembelian'] as $detailPelunasan) {
                    $i++;
                    $sheet2->setCellValue('A'.$i, ($i-1));
                    $sheet2->setCellValue('B'.$i, $data_detail['no_invoice']);
                    $sheet2->setCellValue('C'.$i, $data_detail['principal']->nama_principal);
                    $sheet2->setCellValue('D'.$i, $detailPelunasan->tanggal);
                    $sheet2->setCellValue('E'.$i, round($detailPelunasan->nominal,0));
                    $sheet2->setCellValue('F'.$i, $detailPelunasan->tipe);
                }
            }
            $sheet2->getStyle('E2:E'.$i)->getNumberFormat()->setFormatCode('_(* #,##0_);_(* (#,##0);_(* "-"??_);_(@_)');

            $spreadsheet->setActiveSheetIndex(0);

            $writer = new Xlsx($spreadsheet);
            ob_start();
            $writer->save('php://output');
            $content = ob_get_contents();
            ob_end_clean();
            $now = $today->toDateString();
            $fileName = "laporan_pelunasan_pembelian_{$now}.xlsx";
            Storage::disk('local')->put('excel/'.$fileName, $content);
            $file = url('/excel/'.$fileName);
            return $file;

        else:
            return $this->Unauthorized();
        endif;
    }
}
