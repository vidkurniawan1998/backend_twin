<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\Http\Requests\KunjunganSalesStoreRequest;
use App\Models\Depo;
use App\Models\KunjunganSales;
use App\Http\Resources\KunjunganSales as KunjunganSalesResources;
use App\Http\Resources\RiwayatKunjunganSales as RiwayatKunjunganSalesResources;
use App\Models\Salesman;
use App\Models\SelectOption;
use App\Traits\ExcelStyle;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate as Coordinate;
use Tymon\JWTAuth\JWTAuth;

class KunjunganSalesController extends Controller
{
    use ExcelStyle;
    protected $user;
    protected $modul;
    protected $salesman;
    public function __construct(JWTAuth $jwt)
    {
        $this->jwt      = $jwt;
        $this->user     = $this->jwt->user();
        $this->modul    = 'kunjungan sales';
        $this->salesman = ($this->user->hasRole('Salesman') || $this->user->hasRole('Salesman Canvass')) ? true : false; // cek role user salesman
    }

    /**
     * Menampilkan semua data kunjungan salesman
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse| KunjunganSalesResources
     */
    public function index(Request $request)
    {
        if (!$this->user->can('Menu Kunjungan Sales')) {
            return $this->Unauthorized();
        }

        $perPage        = $request->per_page ?? 'all';
        $keyword        = $request->keyword;
        $tanggal        = $request->has('tanggal') ? $request->tanggal : '';
        $status         = $request->has('status') && $request->status !== ''
            ? $request->status : 'all';
        $id_perusahaan  = $request->has('id_perusahaan') && $request->id_perusahaan !== ''
            ? [$request->id_perusahaan] : Helper::perusahaanByUser($this->user->id);
        $id_depo        = $request->has('depo') && count($request->depo) > 0
            ? $request->depo : Helper::depoIDByUser($this->user->id, $id_perusahaan);
        $id_salesman    = $request->has('id_salesman') && $request->id_salesman !== 'all'
            ? $request->id_salesman : 'all';

        $kunjungan  = KunjunganSales::with(['toko', 'user', 'depo'])
            ->whereHas('toko', function($q) use ($keyword) {
                $q->where('nama_toko', 'like', "%{$keyword}%")
                    ->orWhere('no_acc', 'like', "%{$keyword}%")
                    ->orWhere('cust_no', 'like', "%{$keyword}%");
            })
            ->whereIn('id_depo', $id_depo)
            ->when($status !== 'all', function ($q) use ($status) {
                $q->where('status', $status);
            })
            ->when($this->salesman, function ($q) {
                $q->where('id_user', $this->user->id);
            })
            ->when($id_salesman !== 'all', function ($q) use ($id_salesman) {
                $q->where('id_user', $id_salesman);
            })
            ->when($tanggal !== '', function ($q) use ($tanggal) {
                $q->whereDate('created_at', $tanggal);
            })
            ->orderBy('id', 'desc');

        if ($request->has(['start_date', 'end_date'])) {
            $kunjungan = $kunjungan->whereBetween('created_at', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
        }

        $kunjungan = $perPage === 'all' ? $kunjungan->get() : $kunjungan->paginate($perPage);

        if (!$kunjungan) {
            return $this->dataNotFound($this->modul);
        }

        return KunjunganSalesResources::collection($kunjungan);
    }

    /**
     * Tambah data kunjungan salesman, data dari gadget
     * @param KunjunganSalesStoreRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(KunjunganSalesStoreRequest $request)
    {
        if (!$this->user->can('Tambah Kunjungan Sales')) {
            return $this->Unauthorized();
        }

        if (!$this->salesman) {
            return $this->Unauthorized();
        }

        $salesman = Salesman::where('user_id', $this->user->id)->first();
        if (!$salesman) {
            return $this->Unauthorized();
        }

        $requestData                    = $request->only(['id_toko', 'status', 'keterangan', 'latitude', 'longitude']);
        $depo                           = Depo::find($salesman->tim->id_depo);
        $requestData['id_user']         = $this->user->id;
        $requestData['id_depo']         = $depo->id;
        $requestData['id_perusahaan']   = $depo->perusahaan->id;
        return KunjunganSales::create($requestData) ? $this->storeTrue($this->modul) : $this->storeFalse($this->modul);
    }

    /**
     * Menampilkan satu data kunjungan yang sudah diinput
     * @param $id integer
     * @return KunjunganSalesResources|\Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        if (!$this->user->can('Edit Kunjungan Sales')) {
            return $this->Unauthorized();
        }

        $kunjunganSales = KunjunganSales::find($id);
        if (!$kunjunganSales) {
            return $this->dataNotFound($this->modul);
        }

        if ($this->salesman && $kunjunganSales->id_user !== $this->user->id) {
            return $this->dataNotFound($this->modul);
        }

        return new KunjunganSalesResources($kunjunganSales->load('toko'));
    }

    /**
     * Update data kunjungan jika ada revisi data yang diinput
     * @param KunjunganSalesStoreRequest $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(KunjunganSalesStoreRequest $request, $id)
    {
        if (!$this->user->can('Update Kunjungan Sales')) {
            return $this->Unauthorized();
        }

        $kunjunganSales = KunjunganSales::find($id);
        if (!$kunjunganSales) {
            return $this->dataNotFound($this->modul);
        }

        if ($this->salesman && $kunjunganSales->id_user !== $this->user->id) {
            return $this->dataNotFound($this->modul);
        }

        $requestData            = $request->only(['id_toko', 'status', 'keterangan', 'latitude', 'longitude']);
        $requestData['id_user'] = $this->user->id;

        return $kunjunganSales->update($requestData) ? $this->updateTrue($this->modul) : $this->updateFalse($this->modul);
    }

    /**
     * Hapus data kunjungan, permanen karena tidak menggunakan soft delete
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        if (!$this->user->can('Delete Kunjungan Sales')) {
            return $this->Unauthorized();
        }

        $kunjunganSales = KunjunganSales::find($id);
        if (!$kunjunganSales) {
            return $this->dataNotFound($this->modul);
        }

        if ($this->salesman && $kunjunganSales->id_user !== $this->user->id) {
            return $this->dataNotFound($this->modul);
        }

        return $kunjunganSales->delete() ? $this->destroyTrue($this->modul) : $this->destroyFalse($this->modul);
    }

    /**
     * Menampilkan riwayat kunjungan salesman, data sudah menggunakan tabel view
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse| RiwayatKunjunganSalesResources
     */
    public function riwayat(Request $request)
    {
        if (!$this->user->can('Riwayat Kunjungan Sales')) {
            return $this->Unauthorized();
        }

        $perPage = $request->per_page ?? 'all';
        $tanggal = $request->has('tanggal') ? $request->tanggal : '';

        $riwayat = DB::table('v_kunjungan_sales')
            ->when($tanggal <> '', function ($q) use ($tanggal) {
                $q->where('tanggal', $tanggal);
            })
            ->when($this->salesman, function ($q) {
                $q->where('id_user', $this->user->id);
            })
            ->orderBy('tanggal', 'desc');

        $riwayat = $perPage === 'all' ? $riwayat->get() : $riwayat->paginate($perPage);

        if (!$riwayat) {
            return $this->dataNotFound($this->modul);
        }

        return RiwayatKunjunganSalesResources::collection($riwayat);
    }

    public function reportExcel(Request $request)
    {
        if (!$this->user->can('Laporan Kunjungan Sales')) {
            return $this->Unauthorized();
        }

        Carbon::setLocale('id');
        $status         = $request->has('status') && $request->status !== ''
            ? $request->status : 'all';
        $id_perusahaan  = $request->has('id_perusahaan') && $request->id_perusahaan !== ''
            ? [$request->id_perusahaan] : Helper::perusahaanByUser($this->user->id);
        $id_depo        = $request->has('depo') && count($request->depo) > 0
            ? $request->depo : Helper::depoIDByUser($this->user->id, $id_perusahaan);
        $id_salesman    = $request->has('id_salesman') && $request->id_salesman !== 'all'
            ? $request->id_salesman : 'all';

        if ($this->user->can('Salesman By Supervisor')) {
            if ($id_salesman === 'all') {
                $id_salesman = Helper::salesBySupervisor($this->user->id);
            }
        }

        $kunjungan  = KunjunganSales::with(['toko:id,nama_toko,no_acc,cust_no', 'user:id,name', 'user.salesman.tim:id,nama_tim', 'depo:id,nama_depo'])
            ->whereIn('id_depo', $id_depo)
            ->when($status !== 'all', function ($q) use ($status) {
                $q->where('status', $status);
            })
            ->when($this->salesman, function ($q) {
                $q->where('id_user', $this->user->id);
            })
            ->when($id_salesman !== 'all', function ($q) use ($id_salesman) {
                if (is_array($id_salesman)) {
                    $q->whereIn('id_user', $id_salesman);
                } else {
                    $q->where('id_user', $id_salesman);
                }
            })
            ->orderBy('id', 'desc');

        if ($request->has(['start_date', 'end_date'])) {
            $kunjungan = $kunjungan->whereBetween('created_at', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
        }

        $kunjungan              = $kunjungan->get();
        $kunjunganPerSalesman   = $kunjungan->groupBy('id_user');

        // SUMMARY
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Summary');

        $i = 1;
        $sheet->setCellValue('A' . $i, 'Laporan Kunjungan Salesman');
        $sheet->mergeCells('A' . $i . ':C' . $i);
        $sheet->getStyle('A' . $i . ':A' . $i)->applyFromArray($this->fontSize(18));
        $i++;
        $contentStart = $i;

        if ($request->has(['start_date', 'end_date'])) {
            $start_date = Carbon::parse($request->start_date)->translatedFormat('j F Y');
            $end_date   = Carbon::parse($request->end_date)->translatedFormat('j F Y');
            $periode    = $start_date . ' - ' . $end_date;
        } else {
            $periode = Carbon::now()->translatedFormat('j F Y');
        }

        $sheet->setCellValue('A' . $i, 'Periode: '.$periode);
        $sheet->mergeCells('A' . $i . ':C' . $i);

        $i = $i + 2;
        $cells      = ['A', 'B'];
        $columns    = ['No', 'Nama Salesman'];

        $opsi           = SelectOption::where('code', 'call')->get();
        $jumlah_opsi    = count($opsi);
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setAutoSize(true);

        $column_index = Coordinate::columnIndexFromString('B');
        for ($x=0; $x<$jumlah_opsi; $x++) {
            $column_index++;
            $column_string = Coordinate::stringFromColumnIndex($column_index);
            $sheet->getColumnDimension($column_string)->setAutoSize(true);
            $cells[] = $column_string;
            $columns[] = $opsi[$x]->text;
        }

        // EC
        $column_index++;
        $column_string = Coordinate::stringFromColumnIndex($column_index);
        $sheet->getColumnDimension($column_string)->setAutoSize(true);
        $cells[]    = $column_string;
        $columns[]  = 'EC';

        // RASIO
        $column_index++;
        $column_string = Coordinate::stringFromColumnIndex($column_index);
        $sheet->getColumnDimension($column_string)->setAutoSize(true);
        $cells[]    = $column_string;
        $columns[]  = 'Rasio (%)';

        $sheet->getStyle('A' . $i . ':' . end($cells) . $i)->getFont()->setBold(true);
        $sheet->getStyle('A' . $i . ':' . end($cells) . $i)->applyFromArray($this->horizontalCenter());
        $sheet->getStyle('A' . $i . ':' . end($cells) . $i)->applyFromArray($this->border());

        foreach ($columns as $key => $column) {
            $sheet->setCellValue($cells[$key] . $i, $column);
        }

//        $sheet->setAutoFilter('A' . $i . ':E' . $i);
        $i++;
        $start  = $i;
        $no     = 1;
        foreach ($kunjunganPerSalesman as $key => $salesman) {
            $order = $salesman->where('status', 'order')->unique('id_toko')->count();
            $semua = $salesman->unique('id_toko')->count();
            $rasio = round(($order/$semua * 100), 2);
            $sheet->setCellValue('A' . $i, $no);
            $sheet->setCellValue('B' . $i, $salesman[0]->user->name);
            $column_index = Coordinate::columnIndexFromString('B');
            for ($x = 0; $x < $jumlah_opsi; $x++) {
                $column_index++;
                $column_string      = Coordinate::stringFromColumnIndex($column_index);
                $value_opsi         = $opsi[$x]->value;
                $jumlah_kunjungan   = $salesman->unique('id_toko')->where('status', $value_opsi)->count();
                $sheet->setCellValue($column_string . $i, $jumlah_kunjungan);
            }

            // EC
            $column_index++;
            $column_string = Coordinate::stringFromColumnIndex($column_index);
            $sheet->setCellValue($column_string . $i, $order . "/" . $semua);

            // RASIO
            $column_index++;
            $column_string = Coordinate::stringFromColumnIndex($column_index);
            $sheet->setCellValue($column_string . $i, $rasio);
            $i++;
            $no++;
        }

        $end = $i - 1;
        $sheet->getStyle('A' . $contentStart . ':' . end($cells) . $i)->applyFromArray($this->fontSize(14));
        $column_index = Coordinate::columnIndexFromString('B');
        for ($x = 0; $x < $jumlah_opsi; $x++) {
            $column_index++;
            $column_string      = Coordinate::stringFromColumnIndex($column_index);
            $sheet->setCellValue($column_string . $i, "=SUBTOTAL(9, {$column_string}{$start}:{$column_string}{$end})");
        }

        // RATA-RATA RASIO
        $column_index  = $column_index + 2;
        $column_string = Coordinate::stringFromColumnIndex($column_index);
        $sheet->setCellValue($column_string . $i, "=AVERAGE({$column_string}{$start}:{$column_string}{$end})");
        $sheet->getStyle('A' . $start . ':' . end($cells) . $i)->applyFromArray($this->border());
        // END SUMMARY

        // DETAIL
        $spreadsheet->createSheet();
        $sheet = $spreadsheet->setActiveSheetIndex(1);
        $sheet->setTitle('Detail');
        $i = 1;
        $sheet->setCellValue('A' . $i, 'Detail Kunjungan Salesman');
        $sheet->mergeCells('A' . $i . ':C' . $i);
        $sheet->getStyle('A' . $i . ':A' . $i)->applyFromArray($this->fontSize(18));
        $i++;
        $contentStart = $i;
        $sheet->setCellValue('A' . $i, 'Periode: '.$periode);
        $sheet->mergeCells('A' . $i . ':C' . $i);
        $cells      = ['A', 'B', 'C', 'D', 'E', 'F'];
        $columns    = ['No', 'Nama Salesman', 'No Acc', 'Nama Toko', 'Status', 'Keterangan'];
        $i = $i + 2;

        // SET COLUMN WIDTH
        for ($w = 0; $w < count($cells); $w++) {
            if ($w === 0) {
                $sheet->getColumnDimension('A')->setWidth(5);
            } else {
                $sheet->getColumnDimension($cells[$w])->setAutoSize(true);
            }
        }

        //SET HEADER
        $sheet->getStyle('A' . $i . ':F' . $i)->getFont()->setBold(true);
        $sheet->getStyle('A' . $i . ':F' . $i)->applyFromArray($this->horizontalCenter());
        $sheet->getStyle('A' . $i . ':F' . $i)->applyFromArray($this->border());
        foreach ($columns as $key => $column) {
            $sheet->setCellValue($cells[$key] . $i, $column);
        }

        $i++;
        $start  = $i;
        $no     = 1;
        foreach ($kunjunganPerSalesman as $key => $salesman) {
            $sheet->setCellValue('A' . $i, $no);
            $sheet->setCellValue('B' . $i, $salesman[0]->user->name);
            foreach ($salesman as $knj) {
                $sheet->setCellValueExplicit('C' . $i, $knj->toko->no_acc, DataType::TYPE_STRING);
                $sheet->setCellValue('D' . $i, $knj->toko->nama_toko);
                $sheet->setCellValue('E' . $i, $knj->status);
                $sheet->setCellValue('F' . $i, $knj->keterangan);
                $i++;
            }
            $i++;
            $no++;
        }
        $i -= 1;
        $sheet->getStyle('A' . $contentStart . ':F' . $i)->applyFromArray($this->fontSize(14));
        $sheet->getStyle('A' . $start . ':F' . $i)->applyFromArray($this->border());
        $spreadsheet->setActiveSheetIndex(0);

        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        $content = ob_get_contents();
        ob_end_clean();
        $fileName = "laporan_kunjungan_salesman{$request->start_date}_{$this->user->id}.xlsx";
        Storage::disk('local')->put('excel/' . $fileName, $content);
        $file = url('/excel/' . $fileName);
        return $file;
    }
}
