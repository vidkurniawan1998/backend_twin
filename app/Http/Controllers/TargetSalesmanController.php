<?php


namespace App\Http\Controllers;

use App\Helpers\Helper;
use App\Http\Requests\TargetSalesmanStoreRequest;
use App\Models\Penjualan;
use App\Models\Salesman;
use App\Models\ReturPenjualan;
use App\Models\TargetSalesman;
use App\Http\Resources\TargetSalesman as TargetSalesmanResources;
use App\Traits\ExcelStyle;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tymon\JWTAuth\JWTAuth;

class TargetSalesmanController extends Controller
{
    use ExcelStyle;
    protected $user, $jwt, $modul;
    public function __construct(JWTAuth $jwt)
    {
        $this->jwt      = $jwt;
        $this->user     = $this->jwt->user();
        $this->modul    = 'target salesman';
    }

    public function index(Request $request)
    {
        if (!$this->user->can('Menu Target Salesman')) {
            return $this->Unauthorized();
        }

        $keyword        = $request->keyword;
        $perPage        = $request->per_page ?? 'all';
        $id_perusahaan  = $request->has('id_perusahaan') && $request->id_perusahaan !== ''
            ? [$request->id_perusahaan] : Helper::perusahaanByUser($this->user->id);
        $id_depo        = $request->has('id_depo') && count($request->id_depo) > 0
            ? $request->id_depo : Helper::depoIDByUser($this->user->id, $id_perusahaan);
        $id_salesman    = $request->has('id_salesman') && $request->id_salesman !== 'all'
            ? $request->id_salesman : [];
        $mulai_tanggal  = $request->mulai_tanggal;
        $sampai_tanggal = $request->sampai_tanggal;

        $target = TargetSalesman::with(['perusahaan', 'depo', 'user', 'salesman.tim'])
            ->whereHas('user', function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%");
            })
            ->when($id_perusahaan <> '', function ($q) use ($id_perusahaan) {
                $q->whereIn('id_perusahaan', $id_perusahaan);
            })
            ->when(count($id_depo) > 0, function ($q) use ($id_depo) {
                $q->whereIn('id_depo', $id_depo);
            })
            ->when(count($id_salesman) > 0, function ($q) use ($id_salesman) {
                $q->whereIn('id_user', $id_salesman);
            })
            ->when(($mulai_tanggal <> '' && $sampai_tanggal <> ''), function ($q) use ($mulai_tanggal, $sampai_tanggal) {
                $q->where(function ($q) use ($mulai_tanggal, $sampai_tanggal) {
                        $q->whereRaw("'{$mulai_tanggal}' BETWEEN mulai_tanggal AND sampai_tanggal")
                            ->orWhereRaw("'{$sampai_tanggal}' BETWEEN mulai_tanggal AND sampai_tanggal");
                    })
                    ->orWhere(function ($q) use ($mulai_tanggal, $sampai_tanggal) {
                        $q->whereBetween('mulai_tanggal', [$mulai_tanggal, $sampai_tanggal])
                            ->orWhereBetween('sampai_tanggal', [$mulai_tanggal, $sampai_tanggal]);
                });
            })
            ->orderBy('id', 'desc');

        $target = $perPage === 'all' ? $target->get() : $target->paginate($perPage);
        return TargetSalesmanResources::collection($target);
    }

    public function store(TargetSalesmanStoreRequest $request)
    {
        if (!$this->user->can('Tambah Target Salesman')) {
            return $this->Unauthorized();
        }

        $input = $request->only(['id_perusahaan', 'id_depo', 'mulai_tanggal', 'sampai_tanggal', 'hari_kerja', 'target', 'salesman']);
        $data = [];

        foreach ($input['salesman'] as $key => $salesman) {
            $data[] = [
                'id_perusahaan'     => $input['id_perusahaan'],
                'id_depo'           => $salesman['id_depo'],
                'id_user'           => $salesman['id_user'],
                'mulai_tanggal'     => $input['mulai_tanggal'],
                'sampai_tanggal'    => $input['sampai_tanggal'],
                'hari_kerja'        => $input['hari_kerja'],
                'target'            => $salesman['target'],
                'input_by'          => $this->user->id,
                'created_at'        => Carbon::now(),
                'updated_at'        => Carbon::now()
            ];
        }

        return TargetSalesman::insert($data) ? $this->storeTrue($this->modul) : $this->storeFalse($this->modul);
    }

    public function edit($id)
    {
        if (!$this->user->can('Edit Target Salesman')) {
            return $this->Unauthorized();
        }

        $target = TargetSalesman::find($id);
        if (!$target) {
            return $this->dataNotFound($this->modul);
        }

        return new TargetSalesmanResources($target->load('perusahaan', 'depo', 'salesman'));
    }

    public function update(Request $request, $id)
    {
        if (!$this->user->can('Update Target Salesman')) {
            return $this->Unauthorized();
        }

        $this->validate($request, [
            'target' => 'required|numeric|min:0',
        ]);

        $target = TargetSalesman::find($id);
        if (!$target) {
            return $this->dataNotFound($this->modul);
        }

        $input = $request->only(['target']);

        return $target->update($input) ? $this->updateTrue($this->modul) : $this->updateFalse($this->modul);
    }

    public function destroy($id)
    {
        if (!$this->user->can('Delete Target Salesman')) {
            return $this->Unauthorized();
        }

        $target = TargetSalesman::find($id);
        if (!$target) {
            return $this->dataNotFound($this->modul);
        }

        return $target->delete() ? $this->destroyTrue($this->modul) : $this->destroyFalse($this->modul);
    }

    public function report(Request $request)
    {
    
        $tanggal    = $request->has('tanggal') && $request->tanggal != '' ? $request->tanggal : Carbon::now()->firstOfMonth()->toDateString();
        $id_salesman[] = $request->has('id_salesman') && $request->id_salesman != '' ? $request->id_salesman : $this->user->id;

        if ($this->user->can('Penjualan Tim')) {
            $id_salesman  = Helper::salesBySupervisor($this->user->id);
        }

        if ($this->user->can('Penjualan Tim Koordinator')) {
            $id_salesman = Helper::salesByKoordinator($this->user->id);
        }
        
        $data   = [];
        foreach($id_salesman as $id_salesman) {
            $salesman = Salesman::find($id_salesman);
            $target = TargetSalesman::where('id_user', '=', $id_salesman)->whereDate('mulai_tanggal', $tanggal)->get();
            foreach ($target as $tgt) {
                $penjualan  = Penjualan::where('id_salesman', $id_salesman)->whereDate('delivered_at', '>=', $tanggal)->whereDate('delivered_at', '<=', $tgt->sampai_tanggal)->get()->sum('grand_total');
                $retur      = ReturPenjualan::where('id_salesman', $id_salesman)->whereDate('approved_at', '>=', $tanggal)->whereDate('approved_at', '<=', $tgt->sampai_tanggal)->get()->sum('grand_total');
                $net        = $penjualan - $retur;
                $target_nom = $tgt->target;
                $data[] = [
                    'nama_tim' => $salesman->tim->nama_tim,
                    'nama_salesman' => $salesman->user->name,
                    'target' => round($target_nom),
                    'retur' => round($retur),
                    'penjualan' => round($penjualan),
                    'net' => round($net),
                    'ach' => $target_nom > 0 ? round(($net / $target_nom * 100), 2) : 0
                ];
            }   
        }
        
        return response()->json($data, 200);
    }

    public function reportExcel(Request $request)
    {
        if (!$this->user->can('Download Target Salesman')) {
            return $this->Unauthorized();
        }

        $keyword        = $request->keyword;
        $id_perusahaan  = $request->has('id_perusahaan') && $request->id_perusahaan !== ''
            ? [$request->id_perusahaan] : Helper::perusahaanByUser($this->user->id);
        $id_depo        = $request->has('id_depo') && count($request->id_depo) > 0
            ? $request->id_depo : Helper::depoIDByUser($this->user->id, $id_perusahaan);
        $id_salesman    = $request->has('id_salesman') && $request->id_salesman !== 'all'
            ? $request->id_salesman : [];
        $mulai_tanggal  = $request->mulai_tanggal;
        $sampai_tanggal = $request->sampai_tanggal;

        $target = TargetSalesman::with(['perusahaan', 'depo', 'user', 'salesman.tim'])
            ->whereHas('user', function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%");
            })
            ->when($id_perusahaan <> '', function ($q) use ($id_perusahaan) {
                $q->whereIn('id_perusahaan', $id_perusahaan);
            })
            ->when(count($id_depo) > 0, function ($q) use ($id_depo) {
                $q->whereIn('id_depo', $id_depo);
            })
            ->when(count($id_salesman) > 0, function ($q) use ($id_salesman) {
                $q->whereIn('id_user', $id_salesman);
            })
            ->when(($mulai_tanggal <> '' && $sampai_tanggal <> ''), function ($q) use ($mulai_tanggal, $sampai_tanggal) {
                $q->where(function ($q) use ($mulai_tanggal, $sampai_tanggal) {
                    $q->whereRaw("'{$mulai_tanggal}' BETWEEN mulai_tanggal AND sampai_tanggal")
                        ->orWhereRaw("'{$sampai_tanggal}' BETWEEN mulai_tanggal AND sampai_tanggal");
                })
                    ->orWhere(function ($q) use ($mulai_tanggal, $sampai_tanggal) {
                        $q->whereBetween('mulai_tanggal', [$mulai_tanggal, $sampai_tanggal])
                            ->orWhereBetween('sampai_tanggal', [$mulai_tanggal, $sampai_tanggal]);
                    });
            })
            ->orderBy('id', 'desc');

        $target = $target->get();

        // SUMMARY
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Summary');
        $i = 1;
        $sheet->setCellValue('A' . $i, 'Target Salesman');
        $sheet->mergeCells('A' . $i . ':C' . $i);
        $sheet->getStyle('A' . $i . ':A' . $i)->applyFromArray($this->fontSize(18));
        $i++;

        $contentStart = $i;
        $i = $i + 2;
        $cells      = ['A', 'B', 'C', 'D', 'E'];
        $columns    = ['No', 'Depo', 'Tim', 'Nama Salesman', 'Target'];

        // COLUMN WIDTH
        foreach ($cells as $cell) {
            $sheet->getColumnDimension($cell)->setAutoSize(true);
        }

        // HEADER
        $sheet->getStyle('A' . $i . ':' . end($cells) . $i)->getFont()->setBold(true);
        $sheet->getStyle('A' . $i . ':' . end($cells) . $i)->applyFromArray($this->horizontalCenter());
        $sheet->getStyle('A' . $i . ':' . end($cells) . $i)->applyFromArray($this->border());

        foreach ($columns as $key => $column) {
            $sheet->setCellValue($cells[$key] . $i, $column);
        }
        // END HEADER

        $i++;
        $start  = $i;
        $no     = 1;
        foreach ($target as $key => $tgt) {
            $sheet->setCellValue('A' . $i , $no);
            $sheet->setCellValue('B' . $i , $tgt->depo->nama_depo);
            $sheet->setCellValue('C' . $i , $tgt->salesman->tim->nama_tim);
            $sheet->setCellValue('D' . $i , $tgt->user->name);
            $sheet->setCellValue('E' . $i , $tgt->target);
            $i++;
            $no++;
        }

        $i -= 1;
        $sheet->getStyle('E' . $start . ':E' . $i)->getNumberFormat()->setFormatCode('_(* #,##0_);_(* (#,##0);_(* "-"??_);_(@_)');
        $sheet->getStyle('A' . $contentStart . ':' . end($cells) . $i)->applyFromArray($this->fontSize(14));
        $sheet->getStyle('A' . $start . ':' . end($cells) . $i)->applyFromArray($this->border());
        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        $content = ob_get_contents();
        ob_end_clean();
        $fileName = "target_salesman.xlsx";
        Storage::disk('local')->put('excel/' . $fileName, $content);
        $file = url('/excel/' . $fileName);
        return $file;
    }
}
