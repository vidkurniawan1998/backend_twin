<?php

namespace App\Http\Controllers;

use App\Imports\BarangImport;
use App\Imports\HargaBarangImport;
use App\Imports\LockOrderOutletImport;
use App\Imports\SosroImport;
use App\Imports\UpdateBarangImport;
use App\Imports\UpdateHargaBarangImport;
use App\Imports\UpdateTokoImport;
use App\Models\Barang;
use App\Models\Depo;
use App\Models\DetailPenjualan;
use App\Models\HargaBarang;
use App\Models\HargaBarangAktif;
use App\Models\ImportKino;
use App\Models\Penjualan;
use App\Models\Promo;
use App\Models\Reference;
use App\Models\Salesman;
use App\Models\Stock;
use App\Models\Tim;
use App\Models\Toko;
use Carbon\Carbon as Carbon;
use Illuminate\Http\Request;
use App\Imports\TokoImport;
use App\Imports\KetentuanTokoImport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use \Maatwebsite\Excel\Facades\Excel;
use Tymon\JWTAuth\JWTAuth;


class ImportController extends Controller
{
     protected $jwt;

     public function __construct(JWTAuth $jwt)
     {
         $this->jwt = $jwt;
         $this->user = $this->jwt->user();
     }

    public function toko(Request $request)
    {
        Excel::import(new TokoImport, $request->file('file'));

        return response()->json([
            'message' => 'Import data toko berhasil!'
        ], 200);

    }

    public function ketentuan_toko(Request $request)
    {
        Excel::import(new KetentuanTokoImport, $request->file('file'));

        return response()->json([
            'message' => 'Import data toko berhasil!'
        ], 200);

    }

    public function barang(Request $request)
    {
        Excel::import(new BarangImport, $request->file('file'));
        return response()->json([
            'message' => 'Import data barang berhasil!'
        ]);
    }

    public function harga_barang(Request $request)
    {
        Excel::import(new HargaBarangImport, $request->file('file'));
        return response()->json([
            'message' => 'Import data barang berhasil!'
        ]);
    }

    public function harga_barang_aktif()
    {
        $barang = Barang::select('id')->get();
        DB::beginTransaction();
        try {
            foreach ($barang as $data) {
                $tipe = HargaBarang::where('id_barang',$data['id'])->selectRaw('DISTINCT(tipe_harga)')->get();
                foreach ($tipe as $sub) {
                    $harga = HargaBarang::where('tipe_harga',$sub['tipe_harga'])
                    ->where('id_barang',$data['id'])
                    ->orderBy('created_at','DESC')
                    ->orderBy('updated_at','DESC')
                    ->limit(1)
                    ->get();
                    $sub = $harga[0];
                    $input = [
                        'id_harga_barang' => $sub['id'],
                        'id_barang' => $sub['id_barang'],
                        'harga_non_ppn' => $sub['harga_non_ppn'],
                        'tipe_harga' => $sub['tipe_harga'],
                        'harga' => $sub['harga'],
                        'ppn' => $sub['ppn'],
                        'ppn_value' => $sub['ppn_value'],
                        'created_by' => $sub['created_by'],
                        'deleted_by' => null,
                        'created_at' => $sub['created_at'],
                        'updated_at' => $sub['updated_at']
                    ];
                    HargaBarangAktif::updateOrCreate(['id_barang' => $sub['id_barang'], 'tipe_harga' => $sub['tipe_harga']],$input)->save();
                }
            }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'message' => $th
            ]);
        }
        return response()->json([
            'message' => 'Update Harga Barang Aktif Berhasil'
        ]);
    }

    public function update_toko(Request $request)
    {
        Excel::import(new UpdateTokoImport, $request->file('file'));
        return response()->json([
            'message' => 'Update Data Toko Berhasil'
        ]);
    }

    public function update_harga_barang(Request $request)
    {
        Excel::import(new UpdateHargaBarangImport, $request->file('file'));
        return response()->json([
            'message' => 'Update Harga Barang Berhasil'
        ]);
    }

    public function update_barang(Request $request)
    {
        Excel::import(new UpdateBarangImport(), $request->file('file'));
        return response()->json([
            'message' => 'Update Data Barang Berhasil'
        ]);
    }

    public function penjualan_kino_all()
    {
        $cabang = ['101202201', '101202202'];
        $array_detail = [];
        foreach ($cabang as $cb) {
            $files = Storage::disk('ftp_kino')->allFiles("$cb/Trx");
            foreach ($files as $file) {
                $imported   = ImportKino::where('txt_name', $file)->where('cabang', $cb)->first();
                if ($imported) {
                    continue;
                }
                $data       = Storage::disk('ftp_kino')->get($file);
                $array_data = explode("\r\n", $data);
                foreach ($array_data as $trans) {
                    $array = explode("|", $trans);
                    if (count($array) > 1) {
                        $array_detail[] = [
                            'txt_name' => $file,
                            'no_reff' => $array[0],
                            'cust_no' => $array[1],
                            'cust_no_to' => $array[2],
                            'sls_no' => $array[3],
                            'tanggal' => substr($array[4], '0', 4). '-' .substr($array[4], '4', 2). '-' .substr($array[4], '6', 2),
                            'time_in' => $array[5],
                            'p_code' => $array[6],
                            'qty' => intval($array[7]),
                            'sell_price' => floatval($array[8]),
                            'top' => $array[9],
                            'flag_noo' => $array[10],
                            'cabang' => $array[11],
                            'kode_diskon' => $array[12],
                            'diskon_percent' => floatval($array[13]),
                            'diskon_value' => floatval($array[14]),
                            'kode_promo' => $array[15],
                            'promo_value' => floatval($array[16]),
                            'promo_percent' => floatval($array[17]),
                            'flag_promo' => $array[18],
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now(),
                        ];
                    }
                }
            }
        }

        if (count($array_detail) > 0) {
            ImportKino::insert($array_detail);
        }
    }

    public function penjualan_kino(Request $request)
    {
        $this->penjualan_kino_all();
        $messages = [
            'id_depo.required'  => 'Depo tidak ditemukan',
            'id_depo.in'        => 'Depo tidak ditemukan',
            'id_user.required'  => 'User tidak ditemukan',
            'kode_eksklusif.required'  => 'Kode eksklusif tidak ditemukan',
            'tanggal.required'  => 'Tanggal tidak valid',
        ];

        $this->validate($request, [
            'id_depo'       => 'required|in:19,20',
            'id_user'       => 'required',
            'kode_eksklusif'=> 'required',
            'tanggal'       => 'required'
        ], $messages);

        $id_depo        = $request->id_depo;
        $tanggal        = $request->tanggal;
        $kode_eksklusif = $request->kode_eksklusif;

        $array_detail   = ImportKino::where('sls_no', $kode_eksklusif)->where('tanggal', '=', $tanggal)->get();
        $data           = $array_detail->groupBy('no_reff');
        $gagal          = [];
        try {
            DB::beginTransaction();
            foreach ($data as $key => $dt) {
                if ($key === '') {
                    continue;
                }

                $cek_penjualan = Penjualan::where('po_manual', '=', $key)->where('id_depo', '=', $id_depo)->first();
                if ($cek_penjualan) {
                    continue;
                }

                $po         = $key;
                $cust_no    = $dt[0]['cust_no'];
                $sls_no     = $dt[0]['sls_no'];
                $top        = (int) filter_var($dt[0]['top'], FILTER_SANITIZE_NUMBER_INT);
                $id_cabang  = $request->id_depo;
                $tanggal    = $dt[0]['tanggal'];
                $thn        = substr($tanggal, 0, 4);
                $bln        = substr($tanggal, 4, 2);
                $hari       = substr($tanggal, 6, 2);
                $toko       = Toko::where('id_principal', '=', 25)->where('kode_eksklusif', '=', $cust_no)->first();
                $salesman   = Salesman::where('kode_eksklusif', '=', $sls_no)->first();
                $depo       = Depo::find($id_cabang);

                if (!$toko) {
                    $gagal[] = [
                        'order' => $key,
                        'remark'=> 'Outlet tidak ditemukan'
                    ];
                    throw new \Exception('Outlet tidak ditemukan');
                }

                if (!$salesman) {
                    $gagal[] = [
                        'order' => $key,
                        'remark'=> 'Salesman tidak ditemukan'
                    ];
                    throw new \Exception('Salesman tidak ditemukan');
                }

                if (!$depo) {
                    $gagal[] = [
                        'order' => $key,
                        'remark'=> 'Depo tidak ditemukan'
                    ];
                    throw new \Exception('Depo tidak ditemukan');
                }


                $header_penjualan = [
                    'tanggal'           => $thn . '-' . $bln . '-' . $hari,
                    'po_manual'         => $po,
                    'id_toko'           => $toko->id,
                    'top'               => $top,
                    'id_salesman'       => $salesman->user_id,
                    'id_tim'            => $salesman->tim->id,
                    'tipe_pembayaran'   => $top == '' || $top == 0 ? 'cash' : 'credit',
                    'tipe_harga'        => $toko->ketentuan_toko->tipe_harga,
                    'status'            => 'waiting',
                    'id_gudang'         => $depo->id_gudang,
                    'id_depo'           => $depo->id,
                    'id_perusahaan'     => $depo->id_perusahaan
                ];

                $penjualan = Penjualan::create($header_penjualan);

                foreach ($dt as $detail) {
                    $barang     = Barang::where('item_code', '=', $detail['p_code'])->first();
                    if (!$barang) {
                        $gagal[] = [
                            'order' => $key,
                            'remark' => 'Barang tidak ditemukan'
                        ];
                        throw new \Exception('Barang tidak ditemukan');
                    }

                    $stock      = Stock::where('id_gudang', '=', $penjualan->id_gudang)->where('id_barang', '=', $barang->id)->first();
                    if (!$stock) {
                        $gagal[] = [
                            'order' => $key,
                            'remark' => 'Stock tidak ditemukan'
                        ];
                        throw new \Exception('Stock tidak ditemukan');
                    }

                    $harga_jual = HargaBarang::where('id_barang', $barang->id)->where('tipe_harga', $penjualan->tipe_harga)->latest()->first();
                    if (!$harga_jual) {
                        $gagal[] = [
                            'order' => $key,
                            'remark' => 'Harga jual tidak ditemukan'
                        ];
                        throw new \Exception('Harga jual tidak ditemukan');
                    }

                    $harga_beli = HargaBarang::where('id_barang', $barang->id)->where('tipe_harga', 'DBP')->latest()->first();
                    if (!$harga_beli) {
                        $gagal[] = [
                            'order' => $key,
                            'remark' => 'Harga beli tidak ditemukan'
                        ];
                        throw new \Exception('Harga beli tidak ditemukan');
                    }

                    $pcs        = $detail['qty'];
                    $qty        = $pcs / $barang->isi >= 1 ? floor($pcs / $barang->isi) : 0;
                    $qty_pcs    = $pcs % $barang->isi;
                    $detail_penjualan [] = new DetailPenjualan([
                        'id_stock'      => $stock->id,
                        'id_harga'      => $harga_jual->id,
                        'qty'           => $qty,
                        'qty_pcs'       => $qty_pcs,
                        'order_qty'     => $qty,
                        'order_pcs'     => $qty_pcs,
                        'qty_approve'   => $qty,
                        'qty_pcs_approve' => $qty_pcs,
                        'qty_loading'   => 0,
                        'qty_pcs_loading' => 0,
                        'harga_dbp'     => $harga_beli->harga,
                        'harga_jual'    => $harga_jual->harga,
                        'disc_persen'   => 0,
                        'disc_rupiah'   => 0,
                        'id_promo'      => 0,
                        'kode_promo'    => ''
                    ]);
                }
                $penjualan->detail_penjualan()->saveMany($detail_penjualan);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $gagal[] = [
                'order' => $e->getLine(),
                'remark'=> $e->getMessage(),
                'line'  => $e->getLine(),
            ];
        }

        return response()->json([
            'data'      => $gagal,
            'jumlah'    => count($gagal)
        ], 200);
    }

    public function penjualan_sosro(Request $request)
    {
        if (!$this->user->can('Import Order Sosro')) {
            return $this->Unauthorized();
        }

        $this->validate($request, [
            'file'      => 'required'
        ]);

        $logData = [
            'action' => 'Upload Penjualan Sosro',
            'description' => '',
            'user_id' => $this->user->id
        ];

        $this->log($logData);

        $file = $request->file('file');
        $allowExtension = ['xls', 'xlsx'];
        $extension = $file->getClientOriginalExtension();
        if (!in_array($extension, $allowExtension)) {
            return response()->json(['message' => 'hanya mendukung file xls, xlsx'], 400);
        }

        $dataImport = Excel::toCollection(new SosroImport, $file);
        if ($dataImport->count() === 0) {
            return response()->json(['message' => 'data kosong'], 400);
        }

        $id_promo   = Reference::where('code', 'promo_sosro')->first();
        if (!$id_promo) {
            return response()->json(['message' => 'Default Promo ID Belum Di Atur'], 400);
        }

        $promo      = Promo::find($id_promo->value);
        $data       = [];
        $gagal      = [];
        foreach ($dataImport[0] as $dt) {
            $data[] = [
                'tanggal'       => Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dt[0]))->format("Y-m-d"),
                'jam'           => str_replace("'", "", $dt[1]),
                'nomor'         => str_replace("'", "", $dt[2]),
                'nama_tim'      => $dt[3],
                'salesman'      => $dt[4],
                'kode_outlet'   => $dt[5],
                'kode_produk'   => $dt[8],
                'produk'        => $dt[9],
                'qty'           => $dt[10],
                'harga'         => $dt[11],
                'discount_1'    => $dt[13],
                'discount_2'    => $dt[14],
                'keterangan'    => $dt[16],
                'reason'        => $dt[17],
                'tipe_pembayaran' => strtolower($dt[18]),
                'top'           => $dt[19],
                'delivered_at'  => Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dt[20]))->format("Y-m-d"),
                'no_invoice'    => str_replace("'", "", $dt[21])
            ];
        }


        $data       = collect($data);
        $group_order= $data->groupBy('nomor');
        DB::beginTransaction();
        try {
            foreach ($group_order as $key => $go) {
                if ($key == "") {
                    continue;
                }

                $toko   = Toko::where('no_acc', '=', $go[0]['kode_outlet'])->first();
                if (!$toko) {
                    $gagal[] = [
                        'order' => $key,
                        'remark' => 'Outlet tidak ditemukan'
                    ];
                    throw new \Exception('Outlet tidak ditemukan');
                }

                $tim    = Tim::where('nama_tim', $go[0]['nama_tim'])->first();
                if (!$tim) {
                    $gagal[] = [
                        'order' => $key,
                        'remark' => 'Tim tidak ditemukan'
                    ];
                    throw new \Exception('Tim tidak ditemukan');
                }

                $depo   = Depo::find($tim->id_depo);
                $id_salesman = $tim->salesman->user_id ?? null;
                if ($id_salesman === null) {
                    $gagal[] = [
                        'order' => $key,
                        'remark'=> 'Salesman tidak ditemukan'
                    ];
                    throw new \Exception('Salesman tidak ditemukan');
                }

                $cek_penjualan = Penjualan::where('po_manual', '=', $key)->where('id_depo', '=', $depo->id)->first();
                if ($cek_penjualan) {
                    $gagal[] = [
                        'order' => $key,
                        'remark'=> 'Order duplikat, sudah pernah di import'
                    ];
                    throw new \Exception('Order duplikat, sudah pernah di import');
                }

//                $tipe_pembayaran = $toko->ketentuan_toko->k_t ?? 'tunai';
                $tipe_harga      = $toko->tipe_harga ?? 'GT';
                $header_penjualan = [
                    'tanggal'           => $go[0]['tanggal'],
                    'po_manual'         => $key,
                    'id_toko'           => $toko->id,
                    'id_tim'            => $tim->id,
                    'id_salesman'       => $id_salesman,
                    'tanggal_invoice'   => $go[0]['tanggal'],
                    'tipe_pembayaran'   => $go[0]['tipe_pembayaran'] === 'tunai' ? 'cash' : 'credit',
                    'tipe_harga'        => $tipe_harga,
                    'keterangan'        => $go[0]['keterangan'],
                    'id_gudang'         => $depo->id_gudang,
                    'id_depo'           => $depo->id,
                    'id_perusahaan'     => $depo->id_perusahaan,
                    'created_by'        => $this->user->id,
                    'status'            => 'delivered',
                    'delivered_at'      => $go[0]['delivered_at'],
                    'top'               => $go[0]['top'],
                    'due_date'          => Carbon::parse($go[0]['delivered_at'])->addDays($go[0]['top'])->toDateString(),
                    'no_invoice'        => $go[0]['no_invoice'],
                    'created_at'        => $go[0]['tanggal']." ".$go[0]['jam'],
                    'import'            => '1'
                ];

                $penjualan = Penjualan::create($header_penjualan);
                $detail_penjualan = [];
                foreach ($go as $detail) {
                    $detail['qty_pcs'] = 0;
                    $isi    = 1/$detail['qty']; // bagi diskon karena excel total diskon per karton
                    $barang = Barang::where('item_code', '=', $detail['kode_produk'])->first();
                    $harga  = $detail['harga'];
                    if (!$barang) { // Jika stock uom karton tidak ditemukan maka cek uom pcs/btl
                        $barang = Barang::where('pcs_code', '=', $detail['kode_produk'])->first();
                        if (!$barang) {
                            $penjualan->delete();
                            $gagal[] = [
                                'order' => $key,
                                'remark'=> 'Kode barang ' . $detail['produk'] . ' tidak ditemukan'
                            ];
                            throw new \Exception('Kode barang ' . $detail['kode_produk'] . ' tidak ditemukan');
                        }

                        $detail['qty_pcs']  = $detail['qty']; //set qty sebagai qty pcs di twin
                        $isi                = $barang->isi / $detail['qty']; //isi untuk promo default twin per karton
                        $detail['qty']      = 0;
                        $harga              = $harga * $barang->isi;
                    }

                    $stock      = Stock::where('id_barang', $barang->id)->where('id_gudang', $depo->id_gudang)->first();
                    if (!$stock) {
                        throw new \Exception('Stock ' . $detail['produk'] . ' tidak ditemukan');
                    }

                    $harga_jual = HargaBarang::where('id_barang', $barang->id)->where('tipe_harga', $tipe_harga)->latest()->first();
                    $harga_beli = HargaBarang::where('id_barang', $barang->id)->where('tipe_harga', 'DBP')->latest()->first();
                    $diskon     = ($detail['discount_1'] + $detail['discount_2']) * $isi;
                    $detail_penjualan [] = new DetailPenjualan(
                        [
                            'id_stock'          => $stock->id,
                            'id_harga'          => $harga_jual->id,
                            'qty'               => $detail['qty'],
                            'qty_pcs'           => $detail['qty_pcs'],
                            'order_qty'         => $detail['qty'],
                            'order_pcs'         => $detail['qty_pcs'],
                            'qty_approve'       => $detail['qty'],
                            'qty_pcs_approve'   => $detail['qty_pcs'],
                            'qty_loading'       => $detail['qty'],
                            'qty_pcs_loading'   => $detail['qty_pcs'],
                            'harga_dbp'         => $harga_beli->harga,
//                            'harga_jual'        => $harga_jual->harga,
                            'harga_jual'        => $harga,
                            'disc_persen'       => 0,
                            'disc_rupiah'       => $diskon,
                            'id_promo'          => $diskon > 0 ? $promo->id : 0,
                            'kode_promo'        => $diskon > 0 ? $promo->nama_promo : ''
                        ]
                    );
                }

                $penjualan->detail_penjualan()->saveMany($detail_penjualan);
            }
            DB::commit();
        }catch (\Exception $e) {
            DB::rollBack();
            $gagal[] = [
                'order' => $e->getLine(),
                'remark'=> $e->getMessage(),
                'line'  => $e->getLine(),
            ];
        }

        return response()->json([
            'data'      => $gagal,
            'jumlah'    => count($gagal)
        ], 200);
    }

    public function whitelist_outlet(Request $request)
    {
        if (!$this->user->can('Import Whitelist Outlet')) {
            return $this->Unauthorized();
        }

        $this->validate($request, [
            'file'      => 'required'
        ]);

        $file = $request->file('file');
        $allowExtension = ['xls', 'xlsx'];
        $extension = $file->getClientOriginalExtension();
        if (!in_array($extension, $allowExtension)) {
            return response()->json(['message' => 'hanya mendukung file xls, xlsx'], 400);
        }

        Excel::import(new LockOrderOutletImport, $request->file('file'));
        return response()->json([
            'message' => 'Update Data Toko Berhasil'
        ]);
    }
}
