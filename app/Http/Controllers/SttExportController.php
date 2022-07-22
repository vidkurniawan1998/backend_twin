<?php

namespace App\Http\Controllers;

use App\Exports\DapurKitaExport;
use App\Exports\ReckitExport;
use App\Models\SttBridging;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use \PhpOffice\PhpSpreadsheet\Cell\DataType;
use Tymon\JWTAuth\JWTAuth;
use Storage;
use DB;

class SttExportController extends Controller
{
    protected $jwt;
    protected $border, $horizontalCenter, $verticalCenter;
    
    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
        $this->border = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];

        $this->horizontalCenter = [
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ]
        ];

        $this->verticalCenter = [
            'alignment' => [
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ]
        ];
    }

    public function cellColor($color)
    {
        $cellColor = [
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'color' => ['rgb' => $color]
            ]
        ];

        return $cellColor;
    }

    public function fontSize($size)
    {
        $fontStyle = [
            'font' => [
                'size' => $size
            ]
        ];

        return $fontStyle;
    }

    public function export(Request $request)
    {
        $this->validate($request, [
            'principal' => 'required'
        ]);
        
        $principal  = $request->principal;
        $response   = [];
        switch ($principal) {
            case 'C-004':
                $response   = $this->dapurKita($principal);
                break;
            case 'R-002':
                $response = $this->reckit($principal);
                break;
            case 'P-018':
                $response = $this->forisa($principal);
                break;
            default:
                # code...
                break;
        }

        return response()->json($response, 200);
    }

    public function dapurKita($principal)
    {
        $report = SttBridging::where('vencode', $principal)->where('user_id', $this->user->id)->get();
        // $depo   = SttBridging::select('gudang')->groupBy('gudang')->get();
        $depo   = ['denpasar', 'klungkung', 'singaraja', 'negara'];
        $groupByCodeItem = $report->groupBy('item_code');
        $data = [];
        foreach ($groupByCodeItem as $key => $perCodeItem) {
            $attribute      = $perCodeItem->take(1)[0];
            $groupByDepo    = $perCodeItem->groupBy('depo');
            
            $bridging = [
                'kode'      => $key,
                'supp_kode' => $attribute->kode_supp,
                'barcode'   => $attribute->barcode,
                'nama_brg'  => $attribute->s_code,
                'deskripsi' => $attribute->segmen,
                'isi'       => $attribute->volume,
                'supplier'  => $attribute->pref_vendor
            ];
            
            foreach ($depo as $dp) {
                if (!isset($groupByDepo[$dp])) {
                    $bridging[$dp] = [
                        'dus' => 0,
                        'pcs' => 0
                    ];
                } else {
                    $bridging[$dp] = [
                        'dus' => $groupByDepo[$dp]->sum('qty_dus'),
                        'pcs' => $groupByDepo[$dp]->sum('qty_pcs')
                    ];   
                }
            }
            $data[] = $bridging;
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Dapur Kita');

        $sheet->getColumnDimension('C')->setAutoSize(true);
        $sheet->getColumnDimension('D')->setAutoSize(true);
        $sheet->getColumnDimension('E')->setAutoSize(true);
        $sheet->getColumnDimension('G')->setAutoSize(true);
        
        $i = 1;
        $sheet->setCellValue('H'.$i, 'DENPASAR');
        $sheet->setCellValue('J'.$i, 'KLUNGKUNG');
        $sheet->setCellValue('L'.$i, 'SINGARAJA');
        $sheet->setCellValue('N'.$i, 'NEGARA');
        $sheet->mergeCells('H'.$i.':I'.$i);
        $sheet->mergeCells('J'.$i.':K'.$i);
        $sheet->mergeCells('L'.$i.':M'.$i);
        $sheet->mergeCells('N'.$i.':O'.$i);
        $sheet->getStyle('H'.$i)->applyFromArray($this->horizontalCenter);
        $sheet->getStyle('J'.$i)->applyFromArray($this->horizontalCenter);
        $sheet->getStyle('L'.$i)->applyFromArray($this->horizontalCenter);
        $sheet->getStyle('N'.$i)->applyFromArray($this->horizontalCenter);

        $i++;
        $sheet->setCellValue('A'.$i, 'Kode');
        $sheet->setCellValue('B'.$i, 'SuppCode');
        $sheet->setCellValue('C'.$i, 'Barcode');
        $sheet->setCellValue('D'.$i, 'NamaBrg');
        $sheet->setCellValue('E'.$i, 'Deskripsi');
        $sheet->setCellValue('F'.$i, 'ISI');
        $sheet->setCellValue('G'.$i, 'Supplier');
        $sheet->setCellValue('H'.$i, 'DUS');
        $sheet->setCellValue('I'.$i, 'PCS');
        $sheet->setCellValue('J'.$i, 'DUS');
        $sheet->setCellValue('K'.$i, 'PCS');
        $sheet->setCellValue('L'.$i, 'DUS');
        $sheet->setCellValue('M'.$i, 'PCS');
        $sheet->setCellValue('N'.$i, 'DUS');
        $sheet->setCellValue('O'.$i, 'PCS');        
        $sheet->getStyle('A'.$i.':O'.$i)->getFont()->setBold(true);
        $sheet->getStyle('A'.$i.':O'.$i)->applyFromArray($this->cellColor('EEEEEE'));
        $sheet->getStyle('A'.$i.':O'.$i)->applyFromArray($this->horizontalCenter);

        $i++;
        foreach ($data as $key => $dt) {
            $sheet->setCellValue('A'.$i, $dt['kode']);
            $sheet->setCellValue('B'.$i, $dt['supp_kode']);
            // $sheet->setCellValue('C'.$i, $dt['barcode']);
            $sheet->setCellValueExplicit('C'.$i, $dt['barcode'], DataType::TYPE_STRING);
            $sheet->setCellValue('D'.$i, $dt['nama_brg']);
            $sheet->setCellValue('E'.$i, $dt['deskripsi']);
            $sheet->setCellValue('F'.$i, $dt['isi']);
            $sheet->setCellValue('G'.$i, $dt['supplier']);
            $sheet->setCellValue('H'.$i, $dt['denpasar']['dus']);
            $sheet->setCellValue('I'.$i, $dt['denpasar']['pcs']);
            $sheet->setCellValue('J'.$i, $dt['klungkung']['dus']);
            $sheet->setCellValue('K'.$i, $dt['klungkung']['pcs']);
            $sheet->setCellValue('L'.$i, $dt['singaraja']['dus']);
            $sheet->setCellValue('M'.$i, $dt['singaraja']['pcs']);
            $sheet->setCellValue('N'.$i, $dt['negara']['dus']);
            $sheet->setCellValue('O'.$i, $dt['negara']['pcs']);

            //style cell
            $center = ['F', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O'];
            foreach ($center as $center) {
                $sheet->getStyle($center.$i)->applyFromArray($this->horizontalCenter);
            }

            $i++;
        }

        $sheet->getStyle('A2:O'.($i-1))->applyFromArray($this->border);
        
        $writer     = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        $content = ob_get_contents();
        ob_end_clean();
        $fileName = "Dapur Kita.xlsx";
        Storage::disk('local')->put('excel/'.$fileName, $content);
        $file = url('/excel/'.$fileName);
        return [$file];
    }

    public function reckit($principal)
    {
        $report = SttBridging::where('vencode', $principal)->where('user_id', $this->user->id)->get();
        $data   = [];

        foreach ($report as $key => $rpt) {
            $bridging = [
                'kode_db'           => '3000003041',
                'nama_db'           => 'PT. ARIZONA KARYA MITRA',
                'city'              => strtoupper($rpt->depo),
                'tanggal'           => date('d-M-Y' ,strtotime($rpt->transdate)),
                'invoice'           => $rpt->number,
                'kode_cust'         => $rpt->outlet_code,
                'nama_customer'     => $rpt->outlet_name,
                'nama_sales'        => $rpt->salesman_name,
                'channel_class_1'   => '',
                'channel_class_2'   => '',
                'channel_class_3'   => '',
                'customer_group'    => '',
                'db_sku'            => $rpt->item_code,
                'db_sku_deskripsi'  => $rpt->s_code,
                'brand'             => $rpt->brand,
                'rb_sku'            => $rpt->kode_supp,
                'nama_sku'          => $rpt->segmen,
                'qty'               => $rpt->qty_pcs,
                'karton'            => $rpt->qty_dus,
                'value'             => floatval($rpt->total)
            ];

            $data[] = $bridging;
        }
        
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('AKM');
        $sheet->freezePane('E5');
        
        //COLUMN WIDTH
        $sheet->getColumnDimension('A')->setWidth(1);
        $sheet->getColumnDimension('B')->setWidth(12);
        $sheet->getColumnDimension('C')->setWidth(25);
        $sheet->getColumnDimension('D')->setWidth(13);
        $sheet->getColumnDimension('E')->setWidth(13);
        $sheet->getColumnDimension('F')->setWidth(17);
        $sheet->getColumnDimension('H')->setAutoSize(true);
        $sheet->getColumnDimension('I')->setAutoSize(true);
        $sheet->getColumnDimension('J')->setWidth(18);
        $sheet->getColumnDimension('K')->setWidth(18);
        $sheet->getColumnDimension('L')->setWidth(18);
        $sheet->getColumnDimension('M')->setWidth(18);
        $sheet->getColumnDimension('N')->setAutoSize(true);
        $sheet->getColumnDimension('O')->setAutoSize(true);
        $sheet->getColumnDimension('P')->setAutoSize(true);
        $sheet->getColumnDimension('Q')->setAutoSize(true);
        $sheet->getColumnDimension('R')->setAutoSize(true);
        $sheet->getColumnDimension('S')->setWidth(12);
        $sheet->getColumnDimension('T')->setWidth(12);
        $sheet->getColumnDimension('U')->setAutoSize(true);

        $i = 4;
        $sheet->setCellValue('B'.$i, 'Kode DB');
        $sheet->setCellValue('C'.$i, 'Nama DB');
        $sheet->setCellValue('D'.$i, 'City');
        $sheet->setCellValue('E'.$i, 'Tanggal');
        $sheet->setCellValue('F'.$i, 'Invoice');
        $sheet->setCellValue('G'.$i, 'Kode Cust');
        $sheet->setCellValue('H'.$i, 'Nama Customer');
        $sheet->setCellValue('I'.$i, 'DBSR/Nama Sales');
        $sheet->setCellValue('J'.$i, 'Channel Class 1');
        $sheet->setCellValue('K'.$i, 'Channel Class 2');
        $sheet->setCellValue('L'.$i, 'Channel Class 3');
        $sheet->setCellValue('M'.$i, 'Customer Group');
        $sheet->setCellValue('N'.$i, 'DB SKU');
        $sheet->setCellValue('O'.$i, 'DB SKU Deskripsi');
        $sheet->setCellValue('P'.$i, 'Brand/Merek');
        $sheet->setCellValue('Q'.$i, 'RB SKU');
        $sheet->setCellValue('R'.$i, 'Nama SKU');
        $sheet->setCellValue('S'.$i, 'Qty/Pieces');
        $sheet->setCellValue('T'.$i, 'Case/Karton');
        $sheet->setCellValue('U'.$i, 'Value');
        $sheet->getStyle('B'.$i.':U'.$i)->getFont()->setBold(true);
        $sheet->getStyle('B'.$i.':U'.$i)->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE);
        $sheet->getStyle('B'.$i.':U'.$i)->applyFromArray($this->cellColor('245867'));
        $sheet->getStyle('B'.$i.':U'.$i)->applyFromArray($this->horizontalCenter);

        $i++;
        foreach ($data as $key => $dt) {
            $sheet->setCellValue('B'.$i, $dt['kode_db']);
            $sheet->setCellValue('C'.$i, $dt['nama_db']);
            $sheet->setCellValue('D'.$i, $dt['city']);
            $sheet->setCellValue('E'.$i, $dt['tanggal']);
            $sheet->setCellValue('F'.$i, $dt['invoice']);
            $sheet->setCellValue('G'.$i, $dt['kode_cust']);
            $sheet->setCellValue('H'.$i, $dt['nama_customer']);
            $sheet->setCellValue('I'.$i, $dt['nama_sales']);
            $sheet->setCellValue('J'.$i, $dt['channel_class_1']);
            $sheet->setCellValue('K'.$i, $dt['channel_class_2']);
            $sheet->setCellValue('L'.$i, $dt['channel_class_3']);
            $sheet->setCellValue('M'.$i, $dt['customer_group']);
            $sheet->setCellValue('N'.$i, $dt['db_sku']);
            $sheet->setCellValue('O'.$i, $dt['db_sku_deskripsi']);
            $sheet->setCellValue('P'.$i, $dt['brand']);
            $sheet->setCellValue('Q'.$i, $dt['rb_sku']);
            $sheet->setCellValue('R'.$i, $dt['nama_sku']);
            $sheet->setCellValue('S'.$i, $dt['qty']);
            $sheet->setCellValue('T'.$i, $dt['karton']);
            $sheet->getStyle('U'.$i)->getNumberFormat()->setFormatCode('_(* #,##0_);_(* (#,##0);_(* "-"??_);_(@_)');
            $sheet->setCellValueExplicit('U'.$i, $dt['value'], DataType::TYPE_NUMERIC);
            $i++;
        }
        
        //FORMAT CELL
        $sheet->getStyle('E5:E'.($i-1))->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_YYYYMMDDSLASH);
        $sheet->getStyle('B4:U'.($i-1))->applyFromArray($this->border);
        $sheet->getStyle('B2:U2')->applyFromArray(
            [
                'borders' => [
                    'top' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => '4287f5'],
                    ],
                    'bottom' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DOUBLE,
                        'color' => ['rgb' => '4287f5'],
                    ],
                ]
            ]
        );
        //TOTAL
        $sheet->setCellValue('C2', 'TOTAL');
        $sheet->getStyle('B2:U2')->applyFromArray($this->cellColor('faff6b'));
        $sheet->setCellValue('S2', '=SUM(S5:S'.($i-1).')');
        $sheet->setCellValue('T2', '=SUM(T5:T'.($i-1).')');
        $sheet->getStyle('U2')->getNumberFormat()->setFormatCode('_(* #,##0_);_(* (#,##0);_(* "-"??_);_(@_)');
        $sheet->setCellValue('U2', '=SUM(U5:U'.($i-1).')', DataType::TYPE_NUMERIC);
        
        $sheet->getStyle('C2')->getFont()->setBold(true);
        $sheet->getStyle('S2')->getFont()->setBold(true);
        $sheet->getStyle('T2')->getFont()->setBold(true);
        $sheet->getStyle('U2')->getFont()->setBold(true);
        
        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        $content = ob_get_contents();
        ob_end_clean();
        $fileName = "Reckitt.xlsx";
        Storage::disk('local')->put('excel/'.$fileName, $content);
        $file = url('/excel/'.$fileName);
        return [$file];
    }

    public function forisa($principal)
    {
        $report = SttBridging::where('vencode', $principal)->where('user_id', $this->user->id)->get();
        $groupByDepo = $report->groupBy('depo');
        $file   = [];
        $cdist  = [
            'denpasar'  => ['0001000355', '10/20/40 - GT4/MT2/IS1'],
            'negara'    => ['0001000352', '10/20 - GT4/MT2'],
            'singaraja' => ['0001000354', '10/20 - GT4/MT2'],
            'klungkung' => ['0001000353', '10/20/40 - GT4/MT2/IS1']
        ];

        $data   = [];
        foreach ($groupByDepo as $key => $perDepo) {
            $bridging = [];
            foreach ($perDepo as $depo) {
                $qty_per_pcs = ($depo->qty_dus * $depo->volume) + $depo->qty_pcs;
                $bridging[] = [
                    'dist_channel'  => 10,
                    'kode_customer' => $depo->outlet_code,
                    'nama_customer' => $depo->outlet_name,
                    'tipe_customer' => $depo->cust_type,
                    'alamat_customer' => $depo->address,
                    'kelurahan'     => 0,
                    'kecamatan'     => $depo->kecamatan,
                    'kode_salesman' => $depo->team,
                    'nama_salesman' => $depo->salesman_name,
                    'nomor_faktur'  => $depo->number,
                    'tanggal_faktur'=> date('d-M-Y', strtotime($depo->transdate)),
                    'kode_barang_distributor' => $depo->kode_supp,
                    'satuan'        => 'PC',
                    'qty_jual'      => $qty_per_pcs,
                    'qty_promo'     => '-',
                    'harga_satuan_qty_jual' => $depo->subtotal / $qty_per_pcs,
                    'harga_satuan_sebelum_disc' => $depo->subtotal,
                    'nominal_discount_1' => $depo->diskon,
                    'nominal_discount_2' => 0,
                    'nominal_discount_3' => 0,
                    'nominal_discount_4' => 0,
                    'nominal_rupiah_faktur_setelah_disc' => $depo->subtotal - $depo->diskon,
                    'nominal_ppn' => $depo->ppn,
                    'nominal_rupiah_faktur_plus_ppn' => $depo->total
                ];
            }
        
            $data[] = [
                'title' => [
                    'kode_distributor' => $cdist[$key][0],
                    'nama_distributor' => 'ARIZONA KARYA MITRA - '.strtoupper($key),
                    'dist_channel' => $cdist[$key][1],
                    'depo' => $key
                ],
                'data' => $bridging
            ];
        }

        foreach ($data as $key => $dt) {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Data');
            
            //header
            $i = 1;
            $sheet->setCellValue('A'.$i, 'Data TRANSACTION');
            $sheet->getStyle('A'.$i)->applyFromArray($this->fontSize(16));
            $i+= 2;
            $sheet->setCellValue('A'.$i, 'KODE DISTRIBUTOR:');
            $sheet->setCellValue('B'.$i, $dt['title']['kode_distributor']);
            $i++;
            $sheet->setCellValue('A'.$i, 'NAMA DISTRIBUTOR:');
            $sheet->setCellValue('B'.$i, $dt['title']['nama_distributor']);
            $i++;
            $sheet->setCellValue('A'.$i, 'DIST CHANNEL:');
            $sheet->setCellValue('B'.$i, $dt['title']['dist_channel']);
            
            //HEADER WIDTH
            $sheet->getColumnDimension('A')->setWidth(22);
            $sheet->getColumnDimension('B')->setWidth(16);
            $sheet->getColumnDimension('C')->setAutoSize(true);
            $sheet->getColumnDimension('D')->setWidth(14);
            $sheet->getColumnDimension('E')->setAutoSize(true);
            $sheet->getColumnDimension('F')->setWidth(13);
            $sheet->getColumnDimension('G')->setWidth(13);
            $sheet->getColumnDimension('H')->setWidth(13);
            $sheet->getColumnDimension('I')->setAutoSize(true);
            $sheet->getColumnDimension('J')->setWidth(13);
            $sheet->getColumnDimension('K')->setWidth(13);
            $sheet->getColumnDimension('L')->setAutoSize(true);
            $sheet->getColumnDimension('M')->setWidth(8);
            $sheet->getColumnDimension('N')->setWidth(8);
            $sheet->getColumnDimension('O')->setWidth(10);
            $sheet->getColumnDimension('P')->setWidth(18);
            $sheet->getColumnDimension('Q')->setAutoSize(true);
            $sheet->getColumnDimension('R')->setAutoSize(true);
            $sheet->getColumnDimension('S')->setAutoSize(true);
            $sheet->getColumnDimension('T')->setAutoSize(true);
            $sheet->getColumnDimension('U')->setAutoSize(true);
            $sheet->getColumnDimension('V')->setAutoSize(true);
            $sheet->getColumnDimension('W')->setWidth(18);
            $sheet->getColumnDimension('X')->setAutoSize(true);

            $i = 6;
            $sheet->setCellValue('A'.$i, 'Dist Channel');
            $sheet->setCellValue('B'.$i, 'Kode Customer');
            $sheet->setCellValue('C'.$i, 'Nama Customer');
            $sheet->setCellValue('D'.$i, 'Tipe Customer');
            $sheet->setCellValue('E'.$i, 'Alamat Customer');
            $sheet->setCellValue('F'.$i, 'Keluarahan');
            $sheet->setCellValue('G'.$i, 'Kecamatan');
            $sheet->setCellValue('H'.$i, 'Kode Salesman');
            $sheet->setCellValue('I'.$i, 'Nama Salesman');
            $sheet->setCellValue('J'.$i, 'Nomor Faktur');
            $sheet->setCellValue('K'.$i, 'Tanggal Faktur');
            $sheet->setCellValue('L'.$i, 'Kode Barang Distributor');
            $sheet->setCellValue('M'.$i, 'Satuan');
            $sheet->setCellValue('N'.$i, 'Qty Jual');
            $sheet->setCellValue('O'.$i, 'Qty Promo');
            $sheet->setCellValue('P'.$i, 'Harga Satuan Qty Jual');
            $sheet->setCellValue('Q'.$i, 'Nominal Rupiah Sebelum Disc (RP)');
            $sheet->setCellValue('R'.$i, 'Nominal Discount 1 (RP)');
            $sheet->setCellValue('S'.$i, 'Nominal Discount 2 (RP)');
            $sheet->setCellValue('T'.$i, 'Nominal Discount 3 (RP)');
            $sheet->setCellValue('U'.$i, 'Nominal Discount 4 (RP)');
            $sheet->setCellValue('V'.$i, 'Nominal Rupiah Faktur Setelah Disc (RP)');
            $sheet->setCellValue('W'.$i, 'Nominal PPN (RP)');
            $sheet->setCellValue('X'.$i, 'Nominal Rupiah Faktur + PPN (RP)');
            $sheet->getStyle('A1:X'.$i)->getFont()->setBold(true);
            $sheet->getStyle('A'.$i.':X'.$i)->applyFromArray($this->cellColor('fcfc77'));
            $i++;

            //data
            foreach ($dt['data'] as $dtx) {
                $sheet->setCellValue('A'.$i, $dtx['dist_channel']);
                $sheet->setCellValue('B'.$i, $dtx['kode_customer']);
                $sheet->setCellValue('C'.$i, $dtx['nama_customer']);
                $sheet->setCellValue('D'.$i, $dtx['tipe_customer']);
                $sheet->setCellValue('E'.$i, $dtx['alamat_customer']);
                $sheet->setCellValue('F'.$i, $dtx['kelurahan']);
                $sheet->setCellValue('G'.$i, $dtx['kecamatan']);
                $sheet->setCellValue('H'.$i, $dtx['kode_salesman']);
                $sheet->setCellValue('I'.$i, $dtx['nama_salesman']);
                $sheet->setCellValue('J'.$i, $dtx['nomor_faktur']);
                $sheet->setCellValue('K'.$i, $dtx['tanggal_faktur']);
                $sheet->setCellValue('L'.$i, $dtx['kode_barang_distributor']);
                $sheet->setCellValue('M'.$i, $dtx['satuan']);
                // $sheet->setCellValue('N'.$i, $dtx['qty_jual']);
                $sheet->getStyle('N'.$i)->getNumberFormat()->setFormatCode('_(* #,##0_);_(* (#,##0);_(* "-"??_);_(@_)');
                $sheet->setCellValueExplicit('N'.$i, $dtx['qty_jual'], DataType::TYPE_NUMERIC);
                $sheet->setCellValue('O'.$i, $dtx['qty_promo']);
                // $sheet->setCellValue('P'.$i, $dtx['harga_satuan_qty_jual']);
                $sheet->getStyle('P'.$i)->getNumberFormat()->setFormatCode('_(* #,##0_);_(* (#,##0);_(* "-"??_);_(@_)');
                $sheet->setCellValueExplicit('P'.$i, $dtx['harga_satuan_qty_jual'], DataType::TYPE_NUMERIC);
                // $sheet->setCellValue('Q'.$i, $dtx['harga_satuan_sebelum_disc']);
                $sheet->getStyle('Q'.$i)->getNumberFormat()->setFormatCode('_(* #,##0_);_(* (#,##0);_(* "-"??_);_(@_)');
                $sheet->setCellValueExplicit('Q'.$i, $dtx['harga_satuan_sebelum_disc'], DataType::TYPE_NUMERIC);
                // $sheet->setCellValue('R'.$i, $dtx['nominal_discount_1']);
                $sheet->getStyle('R'.$i)->getNumberFormat()->setFormatCode('_(* #,##0_);_(* (#,##0);_(* "-"??_);_(@_)');
                $sheet->setCellValueExplicit('R'.$i, $dtx['nominal_discount_1'], DataType::TYPE_NUMERIC);
                $sheet->setCellValue('S'.$i, $dtx['nominal_discount_2']);
                $sheet->setCellValue('T'.$i, $dtx['nominal_discount_3']);
                $sheet->setCellValue('U'.$i, $dtx['nominal_discount_4']);
                // $sheet->setCellValue('V'.$i, $dtx['nominal_rupiah_faktur_setelah_disc']);
                $sheet->getStyle('V'.$i)->getNumberFormat()->setFormatCode('_(* #,##0_);_(* (#,##0);_(* "-"??_);_(@_)');
                $sheet->setCellValueExplicit('V'.$i, $dtx['nominal_rupiah_faktur_setelah_disc'], DataType::TYPE_NUMERIC);
                // $sheet->setCellValue('W'.$i, $dtx['nominal_ppn']);
                $sheet->getStyle('W'.$i)->getNumberFormat()->setFormatCode('_(* #,##0_);_(* (#,##0);_(* "-"??_);_(@_)');
                $sheet->setCellValueExplicit('W'.$i, $dtx['nominal_ppn'], DataType::TYPE_NUMERIC);
                // $sheet->setCellValue('X'.$i, $dtx['nominal_rupiah_faktur_plus_ppn']);
                $sheet->getStyle('X'.$i)->getNumberFormat()->setFormatCode('_(* #,##0_);_(* (#,##0);_(* "-"??_);_(@_)');
                $sheet->setCellValueExplicit('X'.$i, $dtx['nominal_rupiah_faktur_plus_ppn'], DataType::TYPE_NUMERIC);
                $i++;
            }
            $sheet->getStyle('K6:K'.($i-1))->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_YYYYMMDDSLASH);
            $depo       = $dt['title']['depo'];
            $kode_depo  = $cdist[$depo][0];
            
            $sheet->getStyle('A6:X'.($i-1))->applyFromArray($this->border);
            $writer = new Xlsx($spreadsheet);
            ob_start();
            $writer->save('php://output');
            $content = ob_get_contents();
            ob_end_clean();
            $fileName = "sell_out_trx_".$kode_depo."_".$depo.".xlsx";
            Storage::disk('local')->put('excel/'.$fileName, $content);
            $file[] = url('/excel/'.$fileName);
        }

        return $file;
    }
}
