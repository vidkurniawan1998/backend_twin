<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tymon\JWTAuth\JWTAuth;
use App\Models\NpwpExternal;
use App\Helpers\Helper;

class KonversiPajakController extends Controller
{
    protected $jwt;

    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
    }

    public function dateConverter($EXCEL_DATE)
    {
        if(is_numeric($EXCEL_DATE)){
            $UNIX_DATE  = ($EXCEL_DATE - 25569) * 86400;
            $EXCEL_DATE = 25569 + ($UNIX_DATE  /  86400);
            $UNIX_DATE  = ($EXCEL_DATE - 25569) * 86400;
            return gmdate('d/m/Y', $UNIX_DATE);
        }
        else{
            $TIME = strtotime($EXCEL_DATE);
            return date('d/m/Y',$TIME);
        }

    }

    public function numberToString($num, $param=2)
    {
        $number = number_format($num, 2, '.', '');
        if($param == 0){
            $explode = explode('.', $number);
            return $explode[0];
        }
        else{
            return $number;
        }
    }

    public function pajakCop()
    {   
        $data   = [];
        $data[] = [
            'FK',
            'KD_JENIS_TRANSAKSI',
            'FG_PENGGANTI',
            'NOMOR_FAKTUR',
            'MASA_PAJAK',
            'TAHUN_PAJAK',
            'TANGGAL_FAKTUR',
            'NPWP',
            'NAMA',
            'ALAMAT_LENGKAP',
            'JUMLAH_DPP',
            'JUMLAH_PPN',
            'JUMLAH_PPNBM',
            'ID_KETERANGAN_TAMBAHAN',
            'FG_UANG_MUKA',
            'UANG_MUKA_DPP',
            'UANG_MUKA_PPN',
            'UANG_MUKA_PPNBM',
            'REFERENSI'
        ];
        //SECCOND COP
        $data[] = [
            'LT',
            'NPWP',
            'NAMA',
            'JALAN',
            'BLOK',
            'NOMOR',
            'RT',
            'RW',
            'KECAMATAN',
            'KELURAHAN',
            'KABUPATEN',
            'PROPINSI',
            'KODE_POS',
            'NOMOR_TELEPON',
            '',
            '',
            '',
            ''
        ];
        //THIRD COP
        $data[] = [
            'OF',
            'KODE_OBJEK',
            'NAMA',
            'HARGA_SATUAN',
            'JUMLAH_BARANG',
            'HARGA_TOTAL',
            'DISKON',
            'DPP',
            'PPN',
            'TARIF_PPNBM',
            'PPNBM',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            ''
        ];
        return $data;
    }

    public function convert(Request $request)
    {   
        $file            = $request->file;
        $format          = $request->format;
        $npwp_external   = NpwpExternal::get(); //get npwp from database
        $data            = $this->pajakCop();   //generate cop pajak
        $faktur_header   = $format['faktur_header'];
        $fg_pengganti    = intval($format['fg_penganti']);
        $faktur_nomor    = intval($format['faktur_nomor'])+1;
        $number          = 0;
        $dpp_all         = 0;
        $ppn_all         = 0;
        $n               = 2;
        $n_cop           = 0;

        switch ($format['tipe_converter']) {
            case 'intravis':
                $param_convert = 'NUMBER';
                break;
            case 'sky':
                $param_convert = 'FAKTUR';
                break;
            case 'dms':
                $param_convert = 'INVOICE_NO';
                break;
            case 'twin':
                $param_convert = 'number';
                break;
            default:
                $param_convert = 'error';
                break;
        }
        array_multisort(array_column($file, $param_convert), SORT_ASC, $file);

        foreach ($file as $key=>$d) {
            
            switch ($format['tipe_converter']) {
                case 'intravis':
                    $d = $this->convertFromIntravis($d);
                    break;
                case 'sky':
                    $d = $this->convertFromSky($d);
                    break;
                case 'dms':
                    $d = $this->convertFromDMS($d);
                    break;
                case 'twin':
                    $d = $this->convertFromTwin($d);
                    break;
                default:
                    return response()->json([
                        'message' => "Error converter tidak ditemukan!"
                    ], 422);
                    break;
            }

            if(count($d)==0){ continue; }
            if(floatval($d['harga_trans'])>1000000){
                return response()->json([
                    'message' => "Error terdapat keanehan data! ref :".$d['number']
                ], 422);
            }

            $temp_number    = preg_replace("/[^0-9]/", "", $d['number']);
            $temp_number    = floatval($temp_number);

            if($temp_number>$number){
                $number = $temp_number;

                //nomor pajak
                $nomor_pajak        = ''.$faktur_header.''.$faktur_nomor++;

                //nomor pajak asli, gunakan no pajak asli jika fg pengganti != 0
                $nomor_pajak_asli   = str_replace('-','',$d['no_pajak']);
                $nomor_pajak_asli   = str_replace('.','',$nomor_pajak_asli);
                $nomor_pajak_asli   = str_replace("'",'',$nomor_pajak_asli);

                //referensi no_invoice | tanggal | tim | no_po
                //jika ada tidak ada referensi ditulis dengan kosong tanpa '|'
                $referensi      = $d['number'];
                $referensi      = $d['transdate'] != '' ? $referensi.' | '.$d['transdate'] : $referensi;
                $referensi      = $d['team']      != '' ? $referensi.' | '.$d['team']      : $referensi;
                $referensi      = $d['no_po']     != '' ? $referensi.' | '.$d['no_po']     : $referensi;

                //data diambil dari databese npwp external
                $data_external = $npwp_external->where('kode_outlet',$d['out_code'])->first();

                //jika NPWP kosong diisi dengan format 000000000000000 
                $d['npwp']      = $d['npwp']             == null || trim($d['npwp']) == '' ? '000000000000000' : $d['npwp'];
                $no_ktp         = trim($d['no_ktp'])     == '' || $d['no_ktp']     == null ? '5171033101720008' : $d['no_ktp'];
                $nama_ktp       = trim($d['nama_ktp'])   == '' || $d['nama_ktp']   == null ? 'WIDARTO SELAMAT' : $d['nama_ktp'];
                $alamat_ktp     = trim($d['alamat_ktp']) == '' || $d['alamat_ktp'] == null ? 
                                'PENAMPARANBr/linkPENAMPARAN, 000/000, PADANGSAMBIAN, DENPASAR BARAT' : $d['alamat_ktp'];
                
                if($data_external){
                    $NPWP  = $data_external['npwp'] == null || trim($data_external['npwp']) == '' ? '000000000000000' : $data_external['npwp'];
                    $nama_pkp       = $data_external['npwp'] == null || trim($data_external['npwp']) == '' ? "{$no_ktp} #NIK#NAMA#{$nama_ktp}" : $data_external['nama_pkp'];
                    $alamat_pkp     = $data_external['npwp'] == null || trim($data_external['npwp']) == '' ? $alamat_ktp : $data_external['alamat_pkp'];
                }
                else{
                    $NPWP       = '000000000000000';
                    $nama_pkp   = "{$no_ktp} #NIK#NAMA#{$nama_ktp}";
                    $alamat_pkp = $alamat_ktp;
                }

                $data[] = [
                    'FK',
                    '01', //kode jenis transaksi
                    $fg_pengganti,
                    $fg_pengganti != 0 && $d['no_pajak']!= null ? $nomor_pajak_asli : $nomor_pajak,
                    ''.$d['bulan'].'',
                    ''.$d['tahun'].'',
                    $d['transdate'],
                    //jika tidak disediakan data npwp dan pkp cari ke database jika tidak ditemukan baru cari gunakan default val
                    $d['nama_pkp'] == '' || $d['nama_pkp'] == null ? $NPWP       : $d['npwp'],
                    $d['nama_pkp'] == '' || $d['nama_pkp'] == null ? $nama_pkp   : $d['nama_pkp'],
                    $d['nama_pkp'] == '' || $d['nama_pkp'] == null ? $alamat_pkp : $d['alamat_pkp'],
                    //algoritmanya diisi dulu di array untuk cop pajak per invoicenya 
                    //nanti diganti di saat pengecekan di iterasi berikutnya
                    //======================================
                    'TERDAPAT ERROR PADA PERHITUNGAN DPP', 
                    'TERDAPAT ERROR PADA PERHITUNGAN PPN',
                    //======================================
                    0,
                    '',
                    0,
                    0,
                    0,
                    0,
                    $referensi
                ];

                if($n>3){
                    //saat perhitungan nya selesai di tambah baru diganti, 
                    //n>3 agar tidak diisi pada iterasi pertama 0,1,2 adalah cop fix untuk pajak
                    $data[$n-$n_cop][10] = $this->numberToString($dpp_all,0);
                    $data[$n-$n_cop][11] = $this->numberToString($ppn_all,0);
                }

                $dpp_all      = 0;
                $ppn_all      = 0;
                $n_cop        = 0;
                $n++;
            }
            if($temp_number>=$number){

                //jika tidak ada pejualan dalam pcs satuan penjualan di ubah menjadi satuan carton
                $harga_satuan = $d['qty_pcs'] == 0 ? $d['harga_trans'] : $d['harga_trans'] / $d['volume'];
                $qty          = $d['qty_pcs'] == 0 ? $d['qty_dus']     : $d['qty_dus'] * $d['volume'] + $d['qty_pcs'];

                $data[] = [
                    'OF',
                    $d['item_code'],
                    $d['segmen'],
                    $this->numberToString($harga_satuan),
                    $qty,
                    $this->numberToString($d['subtotal']),
                    $this->numberToString($d['diskon']),
                    $this->numberToString(floatval($d['subtotal'])-floatval($d['diskon'])),
                    $this->numberToString($d['ppn']),
                    0,
                    0,
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    ''
                ];
                $n_cop++;
                $n++;
            }
            else{
                return response()->json([
                    'message' => "Error data tidak berurutan!"
                ], 422);
            }

            $dpp_all  += floatval($this->numberToString((floatval($d['subtotal'])-floatval($d['diskon']))));
            $ppn_all  += floatval($this->numberToString(floatval($d['ppn'])));

            //diinput untuk perhitungan data terakhir
            if($key == count($file)-1){
                $data[$n-$n_cop][10] = $this->numberToString($dpp_all,0);
                $data[$n-$n_cop][11] = $this->numberToString($ppn_all,0);
            }
        }
        return $data;

    }

    public function convertFromIntravis($file)
    {
        if(!array_key_exists('NUMBER', $file)){ return []; }
        $d               = [];
        $d['transdate']  = $this->dateConverter($file['TRANSDATE']);
        $d['no_pajak']   = array_key_exists('NoPajak',$file) ? $file['NoPajak'] : '';
        $d['number']     = array_key_exists('NUMBER',$file) ? $file['NUMBER'] : $file[' NUMBER '];
        $d['team']       = array_key_exists('Team',$file) ? $file['Team'] : $file[' Team '];
        $d['no_po']      = array_key_exists('NoPO',$file) ? $file['NoPO'] : $file[' NoPO '];
        $d['out_code']   = array_key_exists('OutCode',$file) ? $file['OutCode'] : $file[' OutCode '];
        $d['bulan']      = array_key_exists('Bulan',$file) ? $file['Bulan'] : $file[' Bulan '];
        $d['tahun']      = array_key_exists('Tahun',$file) ? $file['Tahun'] : $file[' Tahun '];
        $d['item_code']  = array_key_exists('ItemCode',$file) ? $file['ItemCode'] : $file[' ItemCode '];
        $d['segmen']     = array_key_exists('Segmen',$file) ? $file['Segmen'] : $file[' Segmen '];
        $d['qty_pcs']    = array_key_exists('QtyPCS',$file) ? $file['QtyPCS'] : $file[' QtyPCS '];
        $d['qty_dus']    = array_key_exists('QtyDus',$file) ? $file['QtyDus'] : $file[' QtyDus '];
        $d['harga_trans']= array_key_exists('HargaTrans',$file) ? $file['HargaTrans'] : $file[' HargaTrans ']; //di cek lagi benda ini
        $d['volume']     = array_key_exists('Volume',$file) ? $file['Volume'] : $file[' Volume '];;
        $d['subtotal']   = array_key_exists('SubTotal',$file) ? $file['SubTotal'] : $file[' SubTotal '];;
        $d['diskon']     = array_key_exists('Diskon',$file) ? $file['Diskon'] : $file[' Diskon '];
        $d['ppn']        = array_key_exists('PPn',$file) ? $file['PPn'] : $file[' PPn '];
        $d['no_ktp']     = null;
        $d['nama_ktp']   = null;
        $d['alamat_ktp'] = null;
        $d['nama_pkp']   = null;
        $d['alamat_pkp'] = null;
        $d['npwp']       = null;
        return $d;
    }

    public function convertFromSky($file)
    {
        if(!array_key_exists('FAKTUR', $file)){ return []; }
        #no pajak penganti selain 0 untuk sky dimatikan
        $trans_date      = $this->dateConverter($file['TANGGAL']);
        $tanggal_pecah   = explode('/', $trans_date);
        $bulan           = $tanggal_pecah[1];
        $tahun           = $tanggal_pecah[2];
        $number          = str_replace("'","",$file['FAKTUR']);
        $d               = [];
        $d['transdate']  = $trans_date;
        $d['no_pajak']   = null;
        $d['number']     = $number;
        $d['team']       = $file['SALESMAN'];
        $d['no_po']      = null;
        $d['out_code']   = $file['KD OUTLET'];
        $d['bulan']      = $bulan;
        $d['tahun']      = $tahun;
        $d['item_code']  = $file['KD PRODUK'];
        $d['segmen']     = $file['PRODUK'];
        $d['qty_pcs']    = $file['QTY'];
        $d['qty_dus']    = 0;
        $d['harga_trans']= floatval($file['HARGA'])/1.1;
        $d['volume']     = 1;
        $d['subtotal']   = floatval($d['qty_pcs'])*floatval($d['harga_trans']);
        $d['diskon']     = (floatval($file['DISKON 1'])+floatval($file['DISKON 2']))/1.1;
        $d['ppn']        = (floatval($d['subtotal'])-floatval($d['diskon']))*0.1;
        $d['no_ktp']     = null;
        $d['nama_ktp']   = null;
        $d['alamat_ktp'] = null;
        $d['nama_pkp']   = null;
        $d['alamat_pkp'] = null;
        $d['npwp']       = null;
        return $d;
    }

    public function convertFromDMS($file)
    {
        if(!array_key_exists('INVOICE_NO', $file)){ return []; }
        #no pajak penganti selain 0 untuk DMS dimatikan
        $trans_date      = $this->dateConverter($file['INVOICE_DATE']);
        $tanggal_pecah   = explode('/', $trans_date);
        $bulan           = $tanggal_pecah[1];
        $tahun           = $tanggal_pecah[2];
        $d               = [];
        $d['transdate']  = $trans_date;
        $d['no_pajak']   = null;
        $d['number']     = $file['INVOICE_NO']; //diurutkan berdasarkan apa?
        $d['team']       = $file['SALESMAN_ID'];
        $d['no_po']      = null;
        $d['out_code']   = $file['CUSTCODE1'];
        $d['bulan']      = $bulan;
        $d['tahun']      = $tahun;
        $d['item_code']  = $file['PRODUCT_CODE'];
        $d['segmen']     = $file['PRODUCT_NAME'];
        $d['qty_pcs']    = $file['INVOICE_QTY'];
        $d['qty_dus']    = 0;
        $d['harga_trans']= floatval($file['PRD_UNIT_PRICE']);
        $d['volume']     = 1;
        $d['subtotal']   = floatval($d['qty_pcs'])*floatval($d['harga_trans']);
        $d['diskon']     = floatval($file['INVOICE_TOTALLINEDISC']);
        $d['ppn']        = (floatval($d['subtotal'])-floatval($d['diskon']))*0.1;
        $d['no_ktp']     = null;
        $d['nama_ktp']   = null;
        $d['alamat_ktp'] = null;
        $d['nama_pkp']   = null;
        $d['alamat_pkp'] = null;
        $d['npwp']       = null;
        return $d;
    }

    public function convertFromTwin($file)
    {
        if(!array_key_exists('number', $file)){ return []; }
        $trans_date      = $this->dateConverter($file['transdate']);
        $tanggal_pecah   = explode('/', $trans_date);
        $bulan           = $tanggal_pecah[1];
        $tahun           = $tanggal_pecah[2];
        $d               = [];
        $d['transdate']  = $trans_date;
        $d['no_pajak']   = array_key_exists('no_pajak', $file) ? $file['no_pajak'] : array_key_exists(' no_pajak ', $file) ? $file[' no_pajak '] : null;
        $d['number']     = array_key_exists('number', $file) ? $file['number'] : $file[' number '];
        $d['team']       = array_key_exists('team', $file) ? $file['team'] : $file[' team '];
        $d['no_po']      = array_key_exists('nopo', $file) ? $file['nopo'] : $file[' nopo '];
        $d['out_code']   = array_key_exists('outcode', $file) ? $file['outcode'] : $file[' outcode '];
        $d['bulan']      = array_key_exists('bulan', $file) ? $file['bulan'] : $file[' bulan '];
        $d['tahun']      = array_key_exists('tahun', $file) ? $file['tahun'] : $file[' tahun '];
        $d['item_code']  = array_key_exists('itemcode', $file) ? $file['itemcode'] : '';
        $d['segmen']     = array_key_exists('segmen', $file) ? $file['segmen'] : $file[' segmen '];
        $d['qty_pcs']    = array_key_exists('qtypcs', $file) ? $file['qtypcs'] : $file[' qtypcs '];
        $d['qty_dus']    = array_key_exists('qtydus', $file) ? $file['qtydus'] : $file[' qtydus '];
        $d['harga_trans']= array_key_exists('hargatrans', $file) ? $file['hargatrans'] : $file[' hargatrans ']; //di cek lagi benda ini
        $d['volume']     = array_key_exists('volume', $file) ? $file['volume'] : $file[' volume '];
        $d['subtotal']   = array_key_exists('subtotal', $file) ? $file['subtotal'] : $file[' subtotal '];
        $d['diskon']     = array_key_exists('diskon', $file) ? $file['diskon'] : $file[' diskon '];
        $d['ppn']        = array_key_exists('ppn', $file) ? $file['ppn'] : $file[' ppn '];
        $d['no_ktp']     = array_key_exists('no_ktp', $file) ? $file['no_ktp'] : $file[' no_ktp '];
        $d['nama_ktp']   = array_key_exists('nama_ktp', $file) ? $file['nama_ktp'] : $file[' nama_ktp '];
        $d['alamat_ktp'] = array_key_exists('alamat_ktp', $file) ? $file['alamat_ktp'] : $file[' alamat_ktp '];
        $d['nama_pkp']   = $file['NamaPKP'];
        $d['alamat_pkp'] = $file['AlamatPKP'];
        $d['npwp']       = array_key_exists('npwp', $file) ? $file['npwp'] : array_key_exists(' npwp ', $file) ? $file[' npwp '] : null;
        return $d;
    }
}
