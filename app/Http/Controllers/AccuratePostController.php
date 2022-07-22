<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
// use Tymon\JWTAuth\JWTAuth;
use App\Models\Penjualan;
use Carbon\Carbon;

class AccuratePostController extends Controller
{
    protected  $signatureSecretKey, $session, $host, $headers;
    // protected  $headers = ['Accept' => 'application/json', 'Authorization' => 'bearer 7471bea5-054c-45e5-9afa-ff8c414673f6', 'Content-Type' => 'application/x-www-form-urlencoded'];
    // private $signatureSecretKey = "633be4e1e2f9c871ecaf37746a7ff29d";
    // private $session = "bda4f9e5-3102-4aeb-8b3e-cc192c670767";
    // private $host = "https://public.accurate.id/accurate/api";

    public function __construct()
    {
        $this->signatureSecretKey = config('accurate.signatureSecretKey');
        $this->session = config('accurate.session');
        $this->host = config('accurate.host');
        $this->headers = ['Accept' => 'application/json', 'Authorization' => 'bearer ' . config('accurate.authBearer'), 'Content-Type' => 'application/x-www-form-urlencoded'];
    }

    public function signature($parameter){
        // Urutkan berdasarkan nama
        ksort($parameter);
        // Trim bagian nilai
        $parameter = array_map('trim', $parameter);

        // Deretkan menjadi satu baris
        $data = '';
        foreach ( $parameter as $nama => $nilai ) {
            // Abaikan nilai kosong
            if ($nilai == '') {
                continue;
            }
            if ($data != '') {
                $data .= '&';
            }
            // URL Encode pada nama dan nilai
            $data .= rawurlencode($nama) . '=' . rawurlencode($nilai);
        }

        // HMACSHA256
        $hash = hash_hmac('sha256', $data, $this->signatureSecretKey, true );
        $signature = base64_encode($hash);

        $res = $parameter;
        $res['sign'] = $signature;
        return $res;
    }


    // ======================================= OPEN DB =======================================
    // list database
    public function get_db(Request $request){
        $client = new Client();
        $url = 'https://accurate.id/api/db-list.do';
        
        $_ts = gmdate('Y-m-d\TH:i:s\Z');
        $parameter = [];
        $parameter['_ts'] = $_ts;
        $parameter['session']= $this->session;

        $res =  $this->signature($parameter);

        $result = $client->request('POST', $url, ['headers' => $this->headers, 'form_params' => $res]);
        return $result->getBody();
    }

    // localhost:8000/accurate_post/open_db?id=118944
    // open db to get SESSION
    public function open_db(Request $request){
        $client = new Client();
        $url = 'https://accurate.id/api/open-db.do';
        
        $_ts = gmdate('Y-m-d\TH:i:s\Z');
        $parameter = [];
        $parameter['id'] = $request->id;
        $parameter['_ts'] = $_ts;
        $parameter['session']= $this->session;

        $res =  $this->signature($parameter);

        $result = $client->request('POST', $url, ['headers' => $this->headers, 'form_params' => $res]);
        return $result->getBody();
    }


    // ======================================= BRANCH =======================================
    public function branch_list(){
        $client = new Client();
        $url = $this->host . '/branch/list.do';
        
        $_ts = gmdate('Y-m-d\TH:i:s\Z');
        $parameter = [];
        $parameter['_ts'] = $_ts;
        $parameter['session']= $this->session;

        $res =  $this->signature($parameter);

        $result = $client->request('POST', $url, ['headers' => $this->headers, 'form_params' => $res]);
        return $result->getBody();
    }

    public function branch_save(Request $request){
        $client = new Client();
        $url = $this->host . '/branch/save.do';
        
        $_ts = gmdate('Y-m-d\TH:i:s\Z');
        $parameter = $request->all();
        $parameter['_ts'] = $_ts;
        $parameter['session']= $this->session;

        $res =  $this->signature($parameter);

        $result = $client->request('POST', $url, ['headers' => $this->headers, 'form_params' => $res]);
        return $result->getBody();
    }

    // ======================================= CUSTOMER =======================================
    public function customer_list(Request $request){
        // localhost:8000/accurate_post/customer_list?fields=id,name,customerNo,customerBranchName&keywords=Indomaret
        $client = new Client();
        $url = $this->host . '/customer/list.do';
        
        $_ts = gmdate('Y-m-d\TH:i:s\Z');
        $parameter = $request->all();
        $parameter['_ts'] = $_ts;
        $parameter['session']= $this->session;

        $res =  $this->signature($parameter);

        $result = $client->request('POST', $url, ['headers' => $this->headers, 'form_params' => $res]);
        return $result->getBody();
    }

    public function customer_detail(Request $request){ // parameter : id
        // localhost:8000/accurate_post/customer_detail?id=3489
        $client = new Client();
        $url = $this->host . '/customer/detail.do';
        
        $_ts = gmdate('Y-m-d\TH:i:s\Z');
        $parameter = $request->all();
        $parameter['_ts'] = $_ts;
        $parameter['session']= $this->session;

        $res =  $this->signature($parameter);

        $result = $client->request('POST', $url, ['headers' => $this->headers, 'form_params' => $res]);
        return $result->getBody();
    }

    // ======================================= EMPLOYEE =======================================
    public function employee_list(Request $request){
        // localhost:8000/accurate_post/employee_list?fields=id,name,position&salesmanFilter=true
        $client = new Client();
        $url = $this->host . '/employee/list.do';
        
        $_ts = gmdate('Y-m-d\TH:i:s\Z');
        $parameter = $request->all();
        $parameter['_ts'] = $_ts;
        $parameter['session']= $this->session;

        $res =  $this->signature($parameter);

        $result = $client->request('POST', $url, ['headers' => $this->headers, 'form_params' => $res]);
        return $result->getBody();
    }

    public function employee_detail(Request $request){ // parameter : id
        
        $client = new Client();
        $url = $this->host . '/employee/detail.do';
        
        $_ts = gmdate('Y-m-d\TH:i:s\Z');
        $parameter = $request->all();
        $parameter['_ts'] = $_ts;
        $parameter['session']= $this->session;

        $res =  $this->signature($parameter);

        $result = $client->request('POST', $url, ['headers' => $this->headers, 'form_params' => $res]);
        return $result->getBody();
    }

    // ======================================= EMPLOYEE =======================================
    public function tax_list(Request $request){
        
        $client = new Client();
        $url = $this->host . '/tax/list.do';
        
        $_ts = gmdate('Y-m-d\TH:i:s\Z');
        $parameter = $request->all();
        $parameter['_ts'] = $_ts;
        $parameter['session']= $this->session;

        $res =  $this->signature($parameter);

        $result = $client->request('POST', $url, ['headers' => $this->headers, 'form_params' => $res]);
        return $result->getBody();
    }

    public function tax_detail(Request $request){ // parameter : id
        
        $client = new Client();
        $url = $this->host . '/tax/detail.do';
        
        $_ts = gmdate('Y-m-d\TH:i:s\Z');
        $parameter = $request->all();
        $parameter['_ts'] = $_ts;
        $parameter['session']= $this->session;

        $res =  $this->signature($parameter);

        $result = $client->request('POST', $url, ['headers' => $this->headers, 'form_params' => $res]);
        return $result->getBody();
    }

    // ======================================= PAYMENT TERM =======================================
    public function payment_term_list(Request $request){
        
        $client = new Client();
        $url = $this->host . '/payment-term/list.do';
        
        $_ts = gmdate('Y-m-d\TH:i:s\Z');
        $parameter = $request->all();
        $parameter['_ts'] = $_ts;
        $parameter['session']= $this->session;

        $res =  $this->signature($parameter);

        $result = $client->request('POST', $url, ['headers' => $this->headers, 'form_params' => $res]);
        return $result->getBody();
    }

    public function payment_term_detail(Request $request){ // parameter : id
        
        $client = new Client();
        $url = $this->host . '/payment-term/detail.do';
        
        $_ts = gmdate('Y-m-d\TH:i:s\Z');
        $parameter = $request->all();
        $parameter['_ts'] = $_ts;
        $parameter['session']= $this->session;

        $res =  $this->signature($parameter);

        $result = $client->request('POST', $url, ['headers' => $this->headers, 'form_params' => $res]);
        return $result->getBody();
    }


    // ======================================= ITEM =======================================
    public function item_list(Request $request){
        // localhost:8000/accurate_post/item_list?fields=id,no,name,itemType
        $client = new Client();
        $url = $this->host . '/item/list.do';
        
        $_ts = gmdate('Y-m-d\TH:i:s\Z');
        $parameter = $request->all();
        $parameter['_ts'] = $_ts;
        $parameter['session']= $this->session;

        $res =  $this->signature($parameter);

        $result = $client->request('POST', $url, ['headers' => $this->headers, 'form_params' => $res]);
        return $result->getBody();
    }

    public function item_detail(Request $request){ // parameter : id
        // localhost:8000/accurate_post/item_detail?id=50
        $client = new Client();
        $url = $this->host . '/item/detail.do';
        
        $_ts = gmdate('Y-m-d\TH:i:s\Z');
        $parameter = $request->all();
        $parameter['_ts'] = $_ts;
        $parameter['session']= $this->session;

        $res =  $this->signature($parameter);

        $result = $client->request('POST', $url, ['headers' => $this->headers, 'form_params' => $res]);
        return $result->getBody();
    }

    // ======================================= SALES INVOICE =======================================
    public function sales_invoice_save(Request $request){
        // localhost:8000/accurate_post/sales_invoice_save?id_penjualan=10003318

        $penjualan = Penjualan::where('id', $request->id_penjualan)->with(['toko', 'salesman.tim.depo.gudang', 'detail_penjualan.stock.barang'])->firstOrFail();
        $po_date = Carbon::parse($penjualan->tanggal)->format('d/m/Y');
        $delivery_date = Carbon::parse($penjualan->tanggal)->addDays(1)->format('d/m/Y');

        $nama_tim = $penjualan->salesman->tim->nama_tim;

        $nama_gudang = $penjualan->salesman->tim->depo->gudang->nama_gudang;
        if($nama_gudang == 'Grosir (Kapal)'){
            $nama_gudang = 'G. Grosir Denpasar';
            return response()->json([
                // 's' => false,
                // 'd' => ['SKIP SALES TO, FITUR POST FAKTUR PENJUALAN SEMENTARA HANYA BERLAKU UNTUK HCO.']
                's' => false,
                'd' => ['Posted to Accurate']
            ], 200);
        }
        elseif($nama_gudang == 'A Indomie (Kapal)'){
            $nama_gudang = 'GD. A Kapal';
        }


        if($penjualan->tipe_pembayaran == 'credit'){
            $payment_term = 'NET 14';
        }
        else{
            $payment_term = 'C.O.D';
        }



        $client = new Client();
        $url = $this->host . '/sales-invoice/save.do';
        
        $_ts = gmdate('Y-m-d\TH:i:s\Z');
        $parameter = $request->all();
        $parameter['_ts'] = $_ts;
        $parameter['session'] = $this->session;


        $parameter['customerNo'] = $penjualan->toko->cust_no;

        $i = 0;
        foreach($penjualan->detail_penjualan as $item){
            if($item->qty > 0){
                $parameter['detailItem['.$i.'].itemNo'] = str_replace(' ', '', $item->stock->barang->kode_barang);
                $parameter['detailItem['.$i.'].departmentName'] = $nama_tim;
                // $parameter['detailItem['.$i.'].itemCashDiscount'] = round($item->discount,2);
                $parameter['detailItem['.$i.'].itemCashDiscount'] = $item->discount;
                $parameter['detailItem['.$i.'].itemUnitName'] = 'CAR';
                $parameter['detailItem['.$i.'].quantity'] = $item->qty;
                $parameter['detailItem['.$i.'].salesmanListNumber[0]'] = $nama_tim;
                // $parameter['detailItem['.$i.'].unitPrice'] = round(($item->harga_barang->harga / 1.1),2);
                $parameter['detailItem['.$i.'].unitPrice'] = ($item->harga_barang->harga / 1.1);
                $parameter['detailItem['.$i.'].useTax1'] = 'true';
                $parameter['detailItem['.$i.'].detailNotes'] = $item->promo->nama_promo;
                // detailItem[n].useTax2
                // detailItem[n].useTax3
                $parameter['detailItem['.$i.'].warehouseName'] = $nama_gudang;
                $i++;
            }
            if($item->qty_pcs > 0){
                $parameter['detailItem['.$i.'].itemNo'] = str_replace(' ', '', $item->stock->barang->kode_barang);
                $parameter['detailItem['.$i.'].departmentName'] = $nama_tim;
                // $parameter['detailItem['.$i.'].itemCashDiscount'] = round($item->discount,2);
                $parameter['detailItem['.$i.'].itemCashDiscount'] = $item->discount;
                $parameter['detailItem['.$i.'].itemUnitName'] = 'PCS';
                $parameter['detailItem['.$i.'].quantity'] = $item->qty_pcs;
                $parameter['detailItem['.$i.'].salesmanListNumber[0]'] = $nama_tim;
                // $parameter['detailItem['.$i.'].unitPrice'] = round((($item->harga_barang->harga / $item->stock->barang->isi) / 1.1),2);
                $parameter['detailItem['.$i.'].unitPrice'] = (($item->harga_barang->harga / $item->stock->barang->isi) / 1.1);
                $parameter['detailItem['.$i.'].useTax1'] = 'true';                
                $parameter['detailItem['.$i.'].detailNotes'] = $item->promo->nama_promo;
                // detailItem[n].useTax2
                // detailItem[n].useTax3
                $parameter['detailItem['.$i.'].warehouseName'] = $nama_gudang;
                $i++;
            }
        }

        // $parameter['detailItem[0].itemNo'] = '11111';
        // // $parameter['detailItem[0].detailName'] = 'Penjualan';
        // $parameter['detailItem[0].quantity'] = 1;
        // $parameter['detailItem[0].unitPrice'] = $penjualan->grand_total;
        // $parameter['detailItem[0].useTax1'] = 'true';
        // // detailItem[n].useTax2
        // // detailItem[n].useTax3


        // if($penjualan->disc_final > 0){
        //     $parameter['detailItem['.$i.'].itemNo'] = '22222';
        //     $parameter['detailItem['.$i.'].departmentName'] = $nama_tim;
        //     // $parameter['detailItem['.$i.'].detailName'] = 'Potongan Harga';
        //     $parameter['detailItem['.$i.'].itemCashDiscount'] = $penjualan->disc_final;
        //     $parameter['detailItem['.$i.'].quantity'] = 1;
        //     $parameter['detailItem['.$i.'].salesmanListNumber[0]'] = $nama_tim;
        //     $parameter['detailItem['.$i.'].unitPrice'] = $penjualan->disc_final;
        //     // detailItem[n].useTax1
        //     // detailItem[n].useTax2
        //     // detailItem[n].useTax3
        //     $parameter['detailItem['.$i.'].warehouseName'] = $nama_gudang;
        //     $i++;
        // }
        

        $parameter['branchName'] = 'DENPASAR';
        // cashDiscPercent
        // cashDiscount
        $parameter['currencyCode'] = 'IDR';
        $parameter['description'] = 'POST DARI APLIKASI KPM';
        // id
        $parameter['tax1Name'] = 'Pajak Pertambahan Nilai';
        $parameter['inclusiveTax'] = 'false'; // true
        $parameter['taxable'] = 'true';
        $parameter['number'] = $request->id_penjualan;
        $parameter['paymentTermName'] = $payment_term;
        // saveAsStatusType
        $parameter['shipDate'] = $delivery_date;
        // $parameter['shipDate'] = '05/09/2019';
        $parameter['transDate'] = $po_date;
        // $parameter['transDate'] = '05/09/2019';


        $res =  $this->signature($parameter);

        $result = $client->request('POST', $url, ['headers' => $this->headers, 'form_params' => $res]);
        return $result->getBody();
    }

    public function sales_invoice_detail(Request $request){
        // localhost:8000/accurate_post/sales_invoice_detail?id=5100
        // parameter : id=5100

        $client = new Client();
        $url = $this->host . '/sales-invoice/detail.do';
        
        $_ts = gmdate('Y-m-d\TH:i:s\Z');
        $parameter = $request->all();
        $parameter['_ts'] = $_ts;
        $parameter['session']= $this->session;
        
        $res =  $this->signature($parameter);
        
        $result = $client->request('POST', $url, ['headers' => $this->headers, 'form_params' => $res]);
        return $result->getBody();
    }

    public function sales_invoice_list(Request $request){
        // localhost:8000/accurate_post/sales_invoice_list?fields=id,number,transDate,customer,statusName,totalAmount
        // parameter : fields=id,number,transDate,customer,statusName,totalAmount

        $client = new Client();
        $url = $this->host . '/sales-invoice/list.do';
        
        $_ts = gmdate('Y-m-d\TH:i:s\Z');
        $parameter = $request->all();
        $parameter['_ts'] = $_ts;
        $parameter['session']= $this->session;

        $res =  $this->signature($parameter);

        $result = $client->request('POST', $url, ['headers' => $this->headers, 'form_params' => $res]);
        return $result->getBody();
    }



    public function test_post(){
        // return $this->signatureSecretKey;
        return $this->session;
        // return $this->host;

        // return $this->authBearer;
    }


}
