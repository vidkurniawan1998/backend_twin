<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/


$router->get('/', function () use ($router) {
    Cache::flush();
    return $router->app->version();
    //    echo json_encode($_GET);
});


// $router->get('/test_export_excel', function () use ($router) {
//     return Maatwebsite\Excel\Facades\Excel::download(new App\Exports\UsersExport, 'users.xlsx');
// });



// $router->get('/key', function() {
//     return str_random(32);
// });


// $router->post('/register', 'AuthController@register');
$router->post('/login', 'AuthController@login');

$router->group(['prefix' => 'daerah'], function () use ($router) {
    $router->get('/', 'DaerahController@index');
    $router->get('/provinsi', 'DaerahController@provinsi');
    $router->get('/kabupaten', 'DaerahController@kabupaten');
    $router->get('/kecamatan', 'DaerahController@kecamatan');
    $router->get('/kelurahan', 'DaerahController@kelurahan');
});

$router->group(['prefix' => 'builds'], function() use ($router){
    $router->get('/view_service_level_query_builder', 'BuildsController@view_service_level_query_builder');
});

$router->get('/barang/list/simple', 'BarangController@list_simple');

$router->group(['middleware' => 'jwt.auth'], function () use ($router) {

    // $router->post('/test', 'TestController@test');


    $router->group(['prefix' => 'backup'], function () use ($router) {
        $router->get('/compare', 'BackupMasterController@compare');
        $router->get('/{table_request}', 'BackupMasterController@index');
        $router->post('/', 'BackupMasterController@store');
        $router->post('/checker', 'BackupMasterController@checker');
    });

    $router->post('/logout', 'AuthController@logout');
    // $router->post('/refresh', 'AuthController@refresh');
    $router->get('/me', 'AuthController@me');

    //pdf
    $router->get('/penjualan_pdf/{id}', 'DetailPenjualanController@generatePDF');
    $router->get('/penjualan_doc/{id}', 'DetailPenjualanController@generateDOC');
    $router->get('/mutasi_pdf/{id}', 'DetailMutasiBarangController@generatePDF');
    $router->get('/penerimaan_pdf/{id}', 'DetailPenerimaanBarangController@generatePDF');
    $router->get('/pengeluaran_barang_pdf/{id}', 'PengeluaranBarangController@generatePDF');
    // $router->get('/rekap_pengeluaran_barang_pdf/{id}', 'DetailPengeluaranBarangController@generatePDF');

    $router->group(['prefix' => 'user'], function () use ($router) {
        $router->get('/', 'UserController@index');
        $router->post('/', 'UserController@store');
        $router->get('/{id}', 'UserController@show');
        $router->put('/change_my_password', 'UserController@changeMyPassword');
        $router->put('/{id}', 'UserController@update');
        $router->delete('/{id}', 'UserController@destroy');
        $router->post('/{id}/restore', 'UserController@restore');
        $router->put('/change_password/{id}', 'UserController@change_password');
        $router->put('/update_status/{id}', 'UserController@update_status');
        $router->post('/add_role', 'UserController@addRole');
    });

    $router->group(['prefix' => 'user_principal'], function () use ($router) {
        $router->get('/', 'UserPrincipalController@index');
        $router->get('/{id}', 'UserPrincipalController@edit');
        $router->put('/', 'UserPrincipalController@update');
        $router->delete('/{id}', 'UserPrincipalController@destroy');
    });

    $router->group(['prefix' => 'barang'], function () use ($router) {
        $router->get('/', 'BarangController@index');
        $router->post('/', 'BarangController@store');
        $router->get('/{id}', 'BarangController@show');
        $router->put('/{id}', 'BarangController@update');
        $router->delete('/{id}', 'BarangController@destroy');
        $router->post('/{id}/restore', 'BarangController@restore');
        // $router->post('/{id}/harga_barang', 'HargaBarangController@store'); //ubah harga lama
        $router->post('/import/tipe', 'BarangController@import_tipe');
        $router->post('/update_harga/{id}', 'HargaBarangController@update');
        $router->post('/upload_pic/{id}', 'BarangController@upload_pic');
        $router->get('/{id}/harga', 'HargaBarangController@hargaByIdBarang'); // get harga barang by id dan tipe harga
        $router->get('/list/simple', 'BarangController@list');
        $router->get('/list/simple/mitra', 'BarangController@list_by_mitra');
        $router->get('/list/brand', 'BarangController@list_by_brand');
    });

    $router->group(['prefix' => 'tipe_harga'], function () use ($router) {
        $router->get('/', 'TipeHargaController@index');
        $router->post('/', 'TipeHargaController@store');
        $router->get('/{id}', 'TipeHargaController@show');
        $router->put('/{id}', 'TipeHargaController@update');
        $router->delete('/{id}', 'TipeHargaController@destroy');

        $router->get('get/list', 'TipeHargaController@list');
    });

    $router->group(['prefix' => 'gudang'], function () use ($router) {
        $router->get('/', 'GudangController@index');
        $router->post('/', 'GudangController@store');
        $router->get('/get/gudang_by_user', 'GudangController@gudang_by_user');
        $router->get('/{id}', 'GudangController@show');
        $router->put('/{id}', 'GudangController@update');
        $router->delete('/{id}', 'GudangController@destroy');
        $router->post('/{id}/restore', 'GudangController@restore');
        $router->get('/list_gudang/baik', 'GudangController@list_gudang_baik');
        $router->get('/list_gudang/by_depo', 'GudangController@gudang_by_depo');
        $router->get('/list_gudang/baik_depo', 'GudangController@list_gudang_baik_depo');
    });

    $router->group(['prefix' => 'stock'], function () use ($router) {
        $router->get('/', 'StockController@index');
        $router->post('/', 'StockController@store');
        $router->get('/{id}', 'StockController@show');
        $router->get('/detail/{id}', 'StockController@show_detail');
        $router->put('/{id}', 'StockController@update');
        //Get list barang yg blm terdaftar di suatu gudang
        $router->get('/{id}/list_barang', 'StockController@list_barang');
        $router->get('/sisa/{id}', 'StockController@sisa_stock');
    });

    $router->group(['prefix' => 'perusahaan'], function () use ($router) {
        $router->get('/', 'PerusahaanController@index');
        $router->post('/', 'PerusahaanController@store');
        $router->get('/{id}', 'PerusahaanController@edit');
        $router->put('/{id}', 'PerusahaanController@update');
        $router->delete('/{id}', 'PerusahaanController@destroy');

        $router->get('get/list', 'PerusahaanController@getList');
        $router->get('get/list_by_access', 'PerusahaanController@getListByAccess');
    });

    $router->group(['prefix' => 'principal'], function () use ($router) {
        $router->get('/', 'PrincipalController@index');
        $router->post('/', 'PrincipalController@store');
        $router->get('/{id}', 'PrincipalController@show');
        $router->put('/{id}', 'PrincipalController@update');
        $router->delete('/{id}', 'PrincipalController@destroy');
        $router->post('/{id}/restore', 'PrincipalController@restore');

        $router->get('get/list', 'PrincipalController@list');
        $router->get('get/list/{id}', 'PrincipalController@getListByPerusahaan');
    });

    $router->group(['prefix' => 'brand'], function () use ($router) {
        $router->get('/', 'BrandController@index');
        $router->post('/', 'BrandController@store');
        $router->get('/{id}', 'BrandController@show');
        $router->put('/{id}', 'BrandController@update');
        $router->delete('/{id}', 'BrandController@destroy');
        $router->post('/{id}/restore', 'BrandController@restore');
    });

    $router->group(['prefix' => 'segmen'], function () use ($router) {
        $router->get('/', 'SegmenController@index');
        $router->post('/', 'SegmenController@store');
        $router->get('/{id}', 'SegmenController@show');
        $router->put('/{id}', 'SegmenController@update');
        $router->delete('/{id}', 'SegmenController@destroy');
        $router->post('/{id}/restore', 'SegmenController@restore');
    });

    $router->group(['prefix' => 'penerimaan_barang'], function () use ($router) {
        $router->get('/', 'PenerimaanBarangController@index');
        $router->post('/', 'PenerimaanBarangController@store');
        $router->get('/{id}', 'PenerimaanBarangController@show');
        $router->put('/{id}', 'PenerimaanBarangController@update');
        $router->delete('/{id}', 'PenerimaanBarangController@destroy');
        $router->post('/{id}/restore', 'PenerimaanBarangController@restore');
        $router->post('/{id}/approve', 'PenerimaanBarangController@approve');
        $router->post('/{id}/cancel_approval', 'PenerimaanBarangController@cancel_approval');

        $router->get('/get/list', 'PenerimaanBarangController@getList');
    });

    $router->group(['prefix' => 'detail_penerimaan_barang'], function () use ($router) {
        $router->get('/', 'DetailPenerimaanBarangController@index');
        $router->get('/{id}/detail', 'DetailPenerimaanBarangController@detail');
        $router->post('/', 'DetailPenerimaanBarangController@store');
        $router->get('/{id}', 'DetailPenerimaanBarangController@show');
        $router->put('/{id}', 'DetailPenerimaanBarangController@update');
        $router->put('/{id}/update_harga', 'DetailPenerimaanBarangController@update_harga');
        $router->delete('/{id}', 'DetailPenerimaanBarangController@destroy');
        // $router->post('/{id}/restore', 'DetailPenerimaanBarangController@restore');
        $router->get('/get/list_barang', 'DetailPenerimaanBarangController@list_barang_by_id');
    });

    $router->group(['prefix' => 'mutasi_barang'], function () use ($router) {
        $router->get('/', 'MutasiBarangController@index');
        $router->post('/', 'MutasiBarangController@store');
        $router->get('/{id}', 'MutasiBarangController@show');
        $router->put('/{id}', 'MutasiBarangController@update');
        $router->delete('/{id}', 'MutasiBarangController@destroy');
        $router->post('/{id}/restore', 'MutasiBarangController@restore');
        $router->post('/{id}/approve', 'MutasiBarangController@pending');
        $router->post('/deliver/{id}', 'MutasiBarangController@deliver');
        $router->post('/receive/{id}', 'MutasiBarangController@receive');
        $router->post('/{id}/cancel_approval', 'MutasiBarangController@cancel_mutasi');
        $router->get('/{id}/list_barang', 'MutasiBarangController@list_barang'); //Get list stock barang yg yang ada di gudang
        $router->get('/{id}/list_gudang', 'MutasiBarangController@list_gudang'); //Get list gudang untuk option input KE GUDANG
        $router->get('/list/gudang', 'MutasiBarangController@list_gudang_dari'); //Get list gudang dari berdasarkan user yg login
    });

    $router->group(['prefix' => 'detail_mutasi_barang'], function () use ($router) {
        $router->get('/', 'DetailMutasiBarangController@index');
        $router->get('/{id}/detail', 'DetailMutasiBarangController@detail');
        $router->post('/', 'DetailMutasiBarangController@store');
        $router->get('/{id}', 'DetailMutasiBarangController@show');
        $router->put('/{id}', 'DetailMutasiBarangController@update');
        $router->delete('/{id}', 'DetailMutasiBarangController@destroy');
        $router->post('/{id}/restore', 'DetailMutasiBarangController@restore');
    });

    $router->group(['prefix' => 'adjustment'], function () use ($router) {
        $router->get('/', 'AdjustmentController@index');
        $router->get('/laporan_adjustment', 'AdjustmentController@laporan_adjustment');
        $router->post('/laporan_adjustment/excel', 'AdjustmentController@adjustment_barang_excel');
        $router->get('/{id}', 'AdjustmentController@show');
        $router->post('/', 'AdjustmentController@store');
        $router->put('/{id}', 'AdjustmentController@update');
        $router->delete('/{id}', 'AdjustmentController@destroy');
        $router->post('/{id}/approve', 'AdjustmentController@approve');
        $router->post('/{id}/cancel_approval', 'AdjustmentController@cancel_approval');
    });

    $router->group(['prefix' => 'detail_adjustment'], function () use ($router) {
        $router->get('/', 'DetailAdjustmentController@index');
        $router->get('/{id}', 'DetailAdjustmentController@show');
        $router->get('/{id}/detail', 'DetailAdjustmentController@detail');
        $router->post('/', 'DetailAdjustmentController@store');
        $router->put('/{id}', 'DetailAdjustmentController@update');
        $router->delete('/{id}', 'DetailAdjustmentController@destroy');
        // $router->post('/{id}/restore', 'DetailPenerimaanBarangController@restore');
    });



    $router->group(['prefix' => 'kendaraan'], function () use ($router) {
        $router->get('/', 'KendaraanController@index');
        $router->get('/list/delivery', 'KendaraanController@list_delivery'); // get list kendaraan:  peruntukan => delivery
        $router->post('/', 'KendaraanController@store');
        $router->get('/{id}', 'KendaraanController@show');
        $router->put('/{id}', 'KendaraanController@update');
        $router->delete('/{id}', 'KendaraanController@destroy');
        $router->post('/{id}/restore', 'KendaraanController@restore');
    });

    $router->group(['prefix' => 'pengeluaran_barang'], function () use ($router) {
        $router->get('/', 'PengeluaranBarangController@index');
        $router->post('/', 'PengeluaranBarangController@store');
        $router->get('/{id}', 'PengeluaranBarangController@show');
        $router->put('/{id}', 'PengeluaranBarangController@update');
        $router->delete('/{id}', 'PengeluaranBarangController@destroy');
        $router->post('/{id}/restore', 'PengeluaranBarangController@restore');
        $router->post('/{id}/approve', 'PengeluaranBarangController@approve');
        // $router->get('/{id}/list_kendaraan', 'PengeluaranBarangController@list_kendaraan'); //Get list kendaraan untuk option input "ke"
        $router->get('/detail_pengiriman/{id_pengiriman}', 'PengeluaranBarangController@detail_pengiriman');
        $router->post('/load_barang/{id_pengiriman}', 'PengeluaranBarangController@load_barang');
        $router->get('/list_barang/{id_pengiriman}', 'PengeluaranBarangController@list_barang');
        $router->get('/list_invoice/{id_pengiriman}', 'PengeluaranBarangController@list_invoice');
        $router->get('/detail_penjualan/{id_penjualan}', 'PengeluaranBarangController@detail_penjualan');
    });

    $router->group(['prefix' => 'gross_profit'], function () use ($router) {
        // $router->get('/', 'GrossProfitController@index');
        // $router->get('/lite', 'GrossProfitController@gross_profit_lite');
        $router->get('/', 'GrossProfitController@gross_profit_2');
    });

    $router->group(['prefix' => 'detail_pengeluaran_barang'], function () use ($router) {
        $router->get('/', 'DetailPengeluaranBarangController@index');
        $router->get('/{id}/detail', 'DetailPengeluaranBarangController@detail');
        $router->post('/', 'DetailPengeluaranBarangController@store');
        $router->get('/{id}', 'DetailPengeluaranBarangController@show');
        $router->put('/{id}', 'DetailPengeluaranBarangController@update');
        $router->delete('/{id}', 'DetailPengeluaranBarangController@destroy');
        $router->get('/list/stock', 'DetailPengeluaranBarangController@stock_barang'); //Parameter id_pengiriman, Get list stock barang yg yang ada di gudang
    });

    $router->group(['prefix' => 'hari_efektif'], function () use ($router) {
        $router->get('/', 'HariEfektifController@index');
        $router->post('/import', 'HariEfektifController@importHariEfektif');
    });

    // =========================================== SALES ===========================================================

    $router->group(['prefix' => 'toko'], function () use ($router) {
        $router->get('/toko_tanpa_grup_logistik', 'TokoController@toko_tanpa_grup_logistik');
        $router->get('/toko_by_omset', 'TokoController@toko_by_omset');

        $router->get('/', 'TokoController@index');
        $router->post('/', 'TokoController@store');
        $router->get('/{id}', 'TokoController@show');
        $router->put('/{id}', 'TokoController@update');
        $router->delete('/{id}', 'TokoController@destroy');
        $router->post('/{id}/restore', 'TokoController@restore');
        $router->get('/lokasi_penjualan/{id}', 'TokoController@lokasi_penjualan'); // get data gps penjualan by id_toko
        $router->put('/edit_location/{id}', 'TokoController@edit_location');
        $router->get('/get_saldo_retur/{id}', 'TokoController@get_saldo_retur');
        $router->get('/export/data', 'TokoController@export');
        $router->get('/data/location', 'TokoController@location');
        $router->get('/list/simple', 'TokoController@list');

        //        $router->get('/cek_od_ocl/{id}', 'TokoController@cek_od_ocl');
        $router->get('/sisa_limit_od/{id}', 'TokoController@sisa_limit_dan_od');
        $router->post('/duplicate/{id}', 'TokoController@duplicate');
        $router->put('/lock_order/{id}', 'TokoController@updateLockOrder');
    });

    $router->group(['prefix' => 'grup_toko_logistik'], function () use ($router) {
        $router->get('/', 'GrupTokoLogistikController@index');
        $router->post('/', 'GrupTokoLogistikController@store');
        $router->get('/{id}', 'GrupTokoLogistikController@show');
        $router->put('/{id}', 'GrupTokoLogistikController@update');
        $router->delete('/{id}', 'GrupTokoLogistikController@destroy');

        $router->put('/delete_toko/{id_toko}', 'GrupTokoLogistikController@deleteGrupToko');
    });

    $router->group(['prefix' => 'depo'], function () use ($router) {
        $router->get('/', 'DepoController@index');
        $router->post('/', 'DepoController@store');
        $router->get('/depo_by_user', 'DepoController@depo_by_user');
        $router->get('/{id}', 'DepoController@show');
        $router->put('/{id}', 'DepoController@update');
        $router->delete('/{id}', 'DepoController@destroy');
        $router->post('/{id}/restore', 'DepoController@restore');
        $router->get('/list/simple', 'DepoController@list');
        $router->get('/list/by_id', 'DepoController@list_by_id');
        $router->get('/list/simple', 'DepoController@list');
    });

    $router->group(['prefix' => 'tim'], function () use ($router) {
        $router->get('/', 'TimController@index');
        $router->post('/', 'TimController@store');
        $router->get('/{id}', 'TimController@show');
        $router->put('/{id}', 'TimController@update');
        $router->delete('/{id}', 'TimController@destroy');
        $router->post('/{id}/restore', 'TimController@restore');

        $router->get('/list/simple', 'TimController@list');
    });

    $router->group(['prefix' => 'salesman'], function () use ($router) {
        $router->get('/', 'SalesmanController@index');
        $router->post('/', 'SalesmanController@store');
        $router->get('/list/by_depo/{id_depo}', 'SalesmanController@list_by_depo');
        $router->get('/{id}', 'SalesmanController@show');
        $router->put('/{id}', 'SalesmanController@update');

        $router->get('/get/salesman_active', 'SalesmanController@salesman_active');
        $router->get('/get/salesman_principal', 'SalesmanController@salesman_principal');
    });

    $router->group(['prefix' => 'sales_supervisor'], function () use ($router) {
        $router->get('/', 'SalesSupervisorController@index');
        $router->get('/{id}', 'SalesSupervisorController@show');
        // $router->put('/{id}', 'SalesSupervisorController@update');
    });

    $router->group(['prefix' => 'kepala_gudang'], function () use ($router) {
        $router->get('/', 'KepalaGudangController@index');
        $router->get('/{id}', 'KepalaGudangController@show');
        $router->put('/{id}', 'KepalaGudangController@update');
        $router->get('/list/gudang', 'KepalaGudangController@getListGudang');
    });

    $router->group(['prefix' => 'promo'], function () use ($router) {
        $router->get('/', 'PromoController@index');
        $router->get('/list/aktif', 'PromoController@aktif');
        $router->post('/', 'PromoController@store');
        $router->get('/{id}', 'PromoController@show');
        $router->put('/{id}', 'PromoController@update');
        $router->delete('/{id}', 'PromoController@destroy');
        $router->post('/{id}/restore', 'PromoController@restore');
        $router->post('/{id}/duplicate', 'PromoController@duplicate');
    });

    $router->group(['prefix' => 'pembagian_promo'], function () use ($router) {
        $router->get('/', 'PembagianPromoController@index');
    });

    $router->group(['prefix' => 'sharing_promo'], function () use ($router) {
        $router->get('/', 'SharingPromoController@index');
        $router->post('/', 'SharingPromoController@store');
        $router->get('/{id}', 'SharingPromoController@edit');
        $router->put('/{id}', 'SharingPromoController@update');
        $router->delete('/{id}', 'SharingPromoController@destroy');
    });

    $router->group(['prefix' => 'toko_no_limit'], function () use ($router) {
        $router->get('/', 'TokoNoLimitController@index');
        $router->post('/', 'TokoNoLimitController@store');
        $router->get('/{id}', 'TokoNoLimitController@edit');
        $router->put('/{id}', 'TokoNoLimitController@update');
        $router->delete('/{id}', 'TokoNoLimitController@destroy');
    });

    $router->group(['prefix' => 'penjualan'], function () use ($router) {
        $router->get('/', 'PenjualanController@index');
        $router->post('/', 'PenjualanController@store');
        $router->get('/{id}', 'PenjualanController@show');
        $router->put('/{id}', 'PenjualanController@update');
        $router->post('/schedule', 'PenjualanController@setSchedule');
        $router->post('/reschedule', 'PenjualanController@reSchedule');
        $router->post('/unschedule', 'PenjualanController@unSchedule');
        $router->post('/force_close', 'PenjualanController@force_close');
        $router->delete('/{id}', 'PenjualanController@destroy');
        $router->post('/{id}/restore', 'PenjualanController@restore');
        $router->post('/{id}/approve', 'PenjualanController@approve');
        // $router->post('/{id}/cancel', 'PenjualanController@cancel');
        $router->post('/{id}/cancel_approval', 'PenjualanController@cancel_approval');
        $router->post('/{id}/close', 'PenjualanController@close');

        $router->get('/list/toko', 'PenjualanController@list_toko');
        $router->get('/list/penjualan_by_salesman/{id}', 'PenjualanController@penjualan_by_salesman');
        $router->get('/list/promo/{id_penjualan}/{id_stock}/{qty_dus}', 'PenjualanController@list_promo'); // get promo dgn filter toko, item, depo, min_qty_dus , start_date, end_date
        $router->post('/deliver/{id}', 'PenjualanController@deliver');
        $router->post('/undeliver/{id}', 'PenjualanController@undeliver');
        $router->get('/list/penjualan_today', 'PenjualanController@penjualan_today');
        $router->get('/list/tanggal', 'PenjualanController@tanggal_penjualan');
        $router->get('/tanggal/{tanggal}', 'PenjualanController@riwayat_penjualan');
        $router->get('/count/penjualan_today', 'PenjualanController@count_penjualan_today');
        $router->get('/cek/invoice_kosong', 'PenjualanController@cek_invoice_kosong');
        $router->get('/cek/od_ocl/{id}', 'PenjualanController@cek_od_ocl');

        //Distribution Plan
        $router->get('/distribusi/penjualan', 'PenjualanController@index_distribution_plan');
        $router->get('/distribusi/report', 'PenjualanController@report_distribution_plan');
        $router->get('/distribusi/driver', 'PenjualanController@penjualan_driver');
        $router->put('/distribusi/deliver/{id}', 'PenjualanController@distribution_deliver');
        $router->put('/distribusi/undeliver/{id}', 'PenjualanController@distribution_undeliver');
        $router->get('/riwayat/checker', 'PenjualanController@riwayat_checker_date');
        $router->get('/riwayat/driver', 'PenjualanController@riwayat_driver_date');
        $router->get('/riwayat/checker/invoice', 'PenjualanController@riwayat_checker_invoice');
        $router->get('/riwayat/driver/invoice', 'PenjualanController@riwayat_driver_invoice');
        $router->post('/distribusi/bulk_deliver', 'PenjualanController@bulk_deliver');


        //realisasi Program
        $router->post('/web', 'PenjualanController@post_penjualan'); // simpan data penjualan beserta item"nya

        $router->get('/list/invoice_pdf', 'PenjualanController@list_invoice_pdf');
        $router->get('/list/posisi_penjualan', 'PenjualanController@posisiPenjualan');
        $router->get('/report/rekap_pajak', 'PenjualanController@rekapPajak');

        $router->put('/{id}/update_due_date', 'PenjualanController@updateDueDate');

        //Mobile
        $router->put('/{id}/penjualan_by_id_driver', 'PenjualanController@penjualan_by_id_driver');
        $router->put('/{id}/loading', 'PenjualanController@loading');
        $router->put('/{id}/unloading', 'PenjualanController@unloading');
    });

    $router->group(['prefix' => 'detail_penjualan'], function () use ($router) {
        $router->get('/', 'DetailPenjualanController@index');
        $router->get('/list/barang', 'DetailPenjualanController@list_barang'); //Get list stock barang yg yang ada di gudang
        $router->get('/list/barang_edit', 'DetailPenjualanController@list_barang_edit'); //Get list stock barang yg yang ada di gudang (untuk fungsi edit)
        $router->get('/list/barang_by_gudang/{id_gudang}', 'DetailPenjualanController@list_barang_by_gudang');
        $router->get('/{id}/detail', 'DetailPenjualanController@detail');
        $router->post('/', 'DetailPenjualanController@store');
        $router->get('/{id}', 'DetailPenjualanController@show');
        $router->put('/{id}', 'DetailPenjualanController@update');
        $router->delete('/{id}', 'DetailPenjualanController@destroy');
        // $router->post('/{id}/restore', 'DetailPenjualanController@restore');
        $router->get('/list/harga_barang', 'DetailPenjualanController@list_harga_barang');
        $router->get('/{id_stock}/harga_barang', 'DetailPenjualanController@harga_barang');

        //Mobile
        $router->put('/{id}/update_qty', 'DetailPenjualanController@update_qty');
        $router->put('/{id}/update_qty_driver', 'DetailPenjualanController@update_qty_driver');
    });

    $router->group(['prefix' => 'pelunasan_penjualan'], function () use ($router) {
        // SALESMAN ANDROID
        $router->get('/get/belum_lunas', 'PelunasanPenjualanController@get_belum_lunas');
        $router->get('/get/riwayat_pelunasan', 'PelunasanPenjualanController@get_riwayat_pelunasan');
        // WEB
        $router->get('/', 'PelunasanPenjualanController@index');
        $router->get('/{id}', 'PelunasanPenjualanController@show');

        $router->post('/riwayat_penagihan', 'PelunasanPenjualanController@riwayat_penagihan');

        $router->put('lunasi/{id}', 'PelunasanPenjualanController@lunasi');
        $router->put('batalkan/{id}', 'PelunasanPenjualanController@batalkan');

        $router->get('/get/report_pelunasan', 'PelunasanPenjualanController@download_report');
        $router->get('/get/report_pelunasan_excel', 'PelunasanPenjualanController@laporan_pelunasan_download');
    });

    $router->group(['prefix' => 'invoice_note'], function () use ($router) {
        $router->get('/', 'InvoiceNoteController@index');
        $router->get('/detail', 'InvoiceNoteController@show');
        $router->post('/', 'InvoiceNoteController@store');
        $router->put('/', 'InvoiceNoteController@update');
        $router->put('/kunjungan', 'InvoiceNoteController@kunjungan');
        $router->delete('/{id}', 'InvoiceNoteController@destroy');
    });

    $router->group(['prefix' => 'monitoring_stock'], function () use ($router) {
        $router->get('/', 'MonitoringStockController@index');
        $router->get('/get/hari_efektif', 'MonitoringStockController@hari_efektif');
    });

    $router->group(['prefix' => 'suggest_order'], function () use ($router) {
        $router->get('/', 'SuggestOrderController@index');
    });

    $router->group(['prefix' => 'pelunasan_pembelian'], function () use ($router) {
        $router->get('/', 'PelunasanPembelianController@index');
        $router->get('/{id}', 'PelunasanPembelianController@show');
        $router->get('/get/report_pelunasan_excel', 'PelunasanPembelianController@laporan_pelunasan_download');
    });

    $router->group(['prefix' => 'detail_pelunasan_pembelian'], function () use ($router) {
        $router->post('/', 'DetailPelunasanPembelianController@store');
        $router->put('/{id}', 'DetailPelunasanPembelianController@update');
        $router->delete('/{id}', 'DetailPelunasanPembelianController@destroy');

        $router->post('/approve/{id}', 'DetailPelunasanPembelianController@approve');
        $router->post('/reject/{id}', 'DetailPelunasanPembelianController@reject');
        $router->post('/cancel_approval/{id}', 'DetailPelunasanPembelianController@cancel_approval');
    });

    $router->group(['prefix' => 'detail_pelunasan_penjualan'], function () use ($router) {
        $router->get('/', 'DetailPelunasanPenjualanController@index');
        $router->get('/{id}', 'DetailPelunasanPenjualanController@show');
        $router->post('/', 'DetailPelunasanPenjualanController@store');
        $router->put('/{id}', 'DetailPelunasanPenjualanController@update');
        $router->delete('/{id}', 'DetailPelunasanPenjualanController@destroy');

        $router->get('/jumlah_belum_dibayar/{id_penjualan}', 'DetailPelunasanPenjualanController@jumlah_belum_dibayar');

        $router->post('/approve/{id}', 'DetailPelunasanPenjualanController@approve');
        $router->post('/reject/{id}', 'DetailPelunasanPenjualanController@reject');
        $router->post('/cancel_approval/{id}', 'DetailPelunasanPenjualanController@cancel_approval');

        $router->get('/get/report_pembayaran', 'DetailPelunasanPenjualanController@download_pembayaran');
        $router->get('/get/report_pembayaran/custom', 'DetailPelunasanPenjualanController@download_pembayaran_custom');
    });

    $router->group(['prefix' => 'retur_penjualan'], function () use ($router) {
        $router->get('/', 'ReturPenjualanController@index');
        $router->post('/', 'ReturPenjualanController@store');
        $router->get('/klaim_retur', 'ReturPenjualanController@klaim_retur');
        $router->get('/{id}', 'ReturPenjualanController@show');

        $router->get('/get_print/{id}', 'ReturPenjualanController@retur_penjualan_print');

        $router->put('/{id}', 'ReturPenjualanController@update');
        $router->put('/set_faktur_pembelian/{id}', 'ReturPenjualanController@set_faktur_pajak_pembelian');
        $router->delete('/{id}', 'ReturPenjualanController@destroy');
        $router->post('/{id}/restore', 'ReturPenjualanController@restore');
        $router->post('/{id}/approve', 'ReturPenjualanController@approve');
        $router->put('/{id}/unapprove', 'ReturPenjualanController@unapprove');
        $router->put('/{id}/set_claim', 'ReturPenjualanController@setClaim');
        $router->put('/{id}/cancel_claim', 'ReturPenjualanController@cancelClaim');
        $router->put('/{id}/set_verified', 'ReturPenjualanController@verify_retur');
    });

    $router->group(['prefix' => 'detail_retur_penjualan'], function () use ($router) {
        $router->get('/', 'DetailReturPenjualanController@index');
        $router->get('/{id}/detail', 'DetailReturPenjualanController@detail');
        $router->post('/', 'DetailReturPenjualanController@store');
        $router->get('/{id}', 'DetailReturPenjualanController@show');
        $router->put('/{id}', 'DetailReturPenjualanController@update');
        $router->delete('/{id}', 'DetailReturPenjualanController@destroy');

        $router->get('/list/barang', 'DetailReturPenjualanController@list_barang'); //parameter: id_retur_penjualan
    });

    $router->group(['prefix' => 'monitoring'], function () use ($router) {
        $router->get('/pareto', 'MonitoringController@monitoring_pareto');
        $router->get('/ota', 'MonitoringController@monitoring_ota');
        $router->get('/pro', 'MonitoringController@monitoring_pro');
        $router->get('/pro/detail', 'MonitoringController@monitoring_pro_detail');
        $router->get('/query', 'MonitoringController@monitoring_query');
        $router->get('/stock_tmp', 'MonitoringController@monitoring_stock_tmp');
        $router->get('/quick_search/table', 'MonitoringController@quick_search_table');
        $router->get('/quick_search/column', 'MonitoringController@quick_search_column');
        $router->get('/quick_search/row', 'MonitoringController@quick_search_row');
    });

    $router->group(['prefix' => 'retur_segmen'], function () use ($router) {
        $router->get('/', 'ReturSegmenController@index');
    });

    $router->group(['prefix' => 'omset_toko'], function () use ($router) {
        $router->get('/', 'OmsetTokoController@index');
        $router->get('/detail', 'OmsetTokoController@detail');
    });

    $router->group(['prefix' => 'ranking_barang'], function () use ($router) {
        $router->get('/', 'RankingBarangController@index');
    });

    $router->group(['prefix' => 'ranking_piutang'], function () use ($router) {
        $router->get('/', 'RankingPiutangController@index');
    });

    $router->group(['prefix' => 'kpi'], function () use ($router) {
        $router->get('/', 'KpiController@index');
    });

    $router->group(['prefix' => 'service_level'], function () use ($router) {
        $router->get('/', 'ServiceLevelController@index');
    });

    // =========================================== LOGISTIK & DELIVERY ===========================================================

    $router->group(['prefix' => 'driver'], function () use ($router) {
        $router->get('/', 'DriverController@index');
        $router->get('/distribusi', 'DriverController@driver_distribusi'); //Berfungsi untuk get data driver yang memilki jadwal hari ini
        $router->put('/distribusi/{id}', 'DriverController@get_driver_by_id_depo_penjualan'); // Berfungsi get data driver sesuai depo penjualan
        $router->get('/{id}', 'DriverController@show');
        $router->get('/list_by_depo/{id}', 'DriverController@get_driver_by_id_depo');
        // $router->put('/{id}', 'DriverController@update');
    });

    $router->group(['prefix' => 'checker'], function () use ($router) {
        $router->get('/', 'CheckerController@index');
        $router->put('/distribusi/{id}', 'CheckerController@get_checker_by_id_gudang_penjualan');
    });

    $router->group(['prefix' => 'pengiriman'], function () use ($router) {
        $router->get('/', 'PengirimanController@index');
        $router->get('/{id}', 'PengirimanController@show');
        $router->post('/', 'PengirimanController@store');
        $router->put('/{id}', 'PengirimanController@update');
        $router->delete('/{id}', 'PengirimanController@destroy');
        $router->post('/{id}/restore', 'PengirimanController@restore');
        $router->get('/list/penjualan_belum/{id_pengiriman}', 'PengirimanController@get_list_penjualan_belum');
        $router->get('/list/penjualan_sudah/{id_pengiriman}', 'PengirimanController@get_list_penjualan_sudah');
        $router->post('/set/{id_pengiriman}/{id_penjualan}', 'PengirimanController@set_penjualan');
        $router->post('/unset/{id_pengiriman}/{id_penjualan}', 'PengirimanController@unset_penjualan');
        $router->get('/list/tanggal', 'PengirimanController@list_tanggal');
    });

    $router->group(['prefix' => 'canvass'], function () use ($router) {
        $router->get('/', 'CanvassController@index');
        $router->get('/{id}', 'CanvassController@show');
        $router->put('/{id}', 'CanvassController@update');
    });


    // =========================================== REPORT ===========================================================

    $router->group(['prefix' => 'report'], function () use ($router) {
        // all, daily, weekly, mothly, quarterly, yearly
        // by toko, salesman, depo
        // by brand, segmen

        $router->get('/', 'ReportController@all'); //all
        $router->get('/weekly', 'ReportController@weekly'); // /week/{year}/{week_num}

        $router->get('/stt', 'ReportController@stt2');
        $router->get('/stt_all', 'ReportController@stt3');
        $router->get('/std', 'ReportController@std2');
        $router->get('/posisi_stock', 'ReportController@posisi_stock');
        $router->get('/laporan_penjualan', 'ReportController@laporan_penjualan');
        $router->get('/laporan_penjualan/by_customer_by_stock', 'ReportController@penjualan_toko_barang');
        $router->get('/retur_penjualan', 'ReportController@retur_penjualan');
        $router->get('/laporan_actual', 'ReportController@laporan_actual2');
        // $router->get('/saldo_retur_toko', 'ReportController@saldo_retur_toko');
        $router->get('/realisasi_program', 'ReportController@realisasi_program');
        $router->get('/effective_call/item', 'ReportController@ec_per_item');
        $router->get('/laporan_penjualan_salesman', 'ReportController@report_by_salesman_global');
        $router->get('/posisi_stock_gudang', 'ReportController@posisi_stock_gudang_2');
        $router->post('/posisi_stock_gudang/print', 'ReportController@print_posisi_stock_gudang');
        //Cetak Delivery Report Distribution Plan
        $router->get('/get_report/delivery', 'ReportController@get_report_delivery');
        $router->get('/report_jadwal_pengiriman', 'ReportController@report_jadwal_pengiriman');
        $router->get('/mutasi_barang', 'ReportController@mutasi_barang');
        $router->post('/mutasi_barang/excel', 'ReportController@mutasi_barang_excel');
        $router->get('/rekapitulasi_do', 'ReportController@rekapitulasi_do');
        $router->get('/report_outlet', 'ReportController@report_outlet');
        $router->get('/report_klaim_retur', 'ReportController@report_klaim_retur');
    });

    // =========================================== JURNAL ====================================================================
    $router->group(['prefix' => 'jurnal'], function () use ($router) {
        $router->get('penjualan', 'JurnalController@penjualan');
    });

    // =========================================== Faktur Pembelian ===========================================================
    $router->group(['prefix' => 'faktur_pembelian'], function () use ($router) {
        $router->get('/', 'FakturPembelianController@index');
        $router->post('/', 'FakturPembelianController@store');
        $router->get('/{id}', 'FakturPembelianController@edit');
        $router->put('/{id}', 'FakturPembelianController@update');
        $router->put('/{id}/approve', 'FakturPembelianController@approve');
        $router->put('/{id}/unapprove', 'FakturPembelianController@unapprove');
        $router->delete('/{id}', 'FakturPembelianController@destroy');
    });

    // ========================================================================================
    // ===================================== ACCURATE =========================================
    // ========================================================================================
    // 1. login melalui web browser, untuk mendapatkan code
    //      https://accurate.id/oauth/authorize?client_id=7acfce81-9e14-48ea-82bb-c865cb7551e1&response_type=code&redirect_uri=http://kpm-api.webku.org/&scope=branch_view branch_save customer_view customer_save item_view item_save payment_term_view payment_term_save purchase_invoice_view purchase_invoice_save purchase_payment_view purchase_payment_save sales_invoice_view sales_invoice_save sales_receipt_view sales_receipt_save vendor_view vendor_save
    // 2. otorisasi oauth, untuk mendapatkan Authorization bearer token
    //      https://accurate.id/oauth/token?code=JKp3u75RiOLp5GK4dJDJ&grant_type=authorization_code&redirect_uri=http://kpm-api.webku.org/
    //      header: Authorization => Basic N2FjZmNlODEtOWUxNC00OGVhLTgyYmItYzg2NWNiNzU1MWUxOjUyMDNmNGJiZTkwYjFlZWRiYjViMzMyYjU3MGZmYThl
    // 3. get list db, untuk mendapatkann id db
    //      localhost:8000/accurate_post/get_db
    // 4. open db, untuk mendapatkan session
    //      localhost:8000/accurate_post/open_db?id=118944

    $router->get('/clear-cache', function () {
        // Artisan::call('cache:clear');
        Cache::flush();
        return "All cache cleared";
    });

    $router->get('/tz', function () use ($router) {
        return gmdate('Y-m-d\TH:i:s\Z');
    });
    $router->group(['prefix' => 'sign'], function () use ($router) {
        $router->get('/', 'AccurateSignController@index');
        $router->get('/get_db', 'AccurateSignController@get_db');
        $router->get('/open_db', 'AccurateSignController@open_db');
    });

    $router->group(['prefix' => 'accurate_post'], function () use ($router) {
        $router->post('/get_db', 'AccuratePostController@get_db');
        $router->post('/open_db', 'AccuratePostController@open_db');

        // $router->post('/test_post', 'AccuratePostController@test_post');

        $router->post('/branch_list', 'AccuratePostController@branch_list');
        $router->post('/branch_save', 'AccuratePostController@branch_save');

        $router->post('/customer_list', 'AccuratePostController@customer_list');
        $router->post('/customer_detail', 'AccuratePostController@customer_detail');

        $router->post('/item_list', 'AccuratePostController@item_list');
        $router->post('/item_detail', 'AccuratePostController@item_detail');
        $router->post('/item_save', 'AccuratePostController@item_save');

        $router->post('/sales_invoice_save', 'AccuratePostController@sales_invoice_save');
        $router->post('/sales_invoice_detail', 'AccuratePostController@sales_invoice_detail');
        $router->post('/sales_invoice_list', 'AccuratePostController@sales_invoice_list');

        $router->post('/employee_list', 'AccuratePostController@employee_list');
        $router->post('/employee_detail', 'AccuratePostController@employee_detail');

        $router->post('/tax_list', 'AccuratePostController@tax_list');
        $router->post('/tax_detail', 'AccuratePostController@tax_detail');

        $router->post('/payment_term_list', 'AccuratePostController@payment_term_list');
        $router->post('/payment_term_detail', 'AccuratePostController@payment_term_detail');
    });

    // API CUSTOMER
    $router->group(['prefix' => 'customer-api'], function () use ($router) {
        $router->get('/my_outlet', 'CustomerController@my_outlet');
        $router->get('/riwayat_penjualan', 'CustomerController@riwayat_penjualan');
        $router->get('/laporan_penjualan_item', 'CustomerController@laporan_penjualan_item');
        $router->get('/laporan_penjualan_value', 'CustomerController@laporan_penjualan_value');

        $router->group(['prefix' => 'account'], function () use ($router) {
            $router->post('/create', 'CustomerController@create_customer_account');
            $router->post('/update', 'CustomerController@update_customer_account');
            $router->post('/change_password', 'CustomerController@change_password_customer_account');
        });
    });

    $router->group(['prefix' => 'bridging'], function () use ($router) {
        $router->get('/principal', 'BridgingController@principal');
    });

    // IMPORT
    $router->group(['prefix' => 'import'], function () use ($router) {
        $router->post('/kino', 'KinoBridgingController@import');
        $router->post('/bridging', 'SttImportController@import');
        $router->post('/pajak', 'PenjualanController@importNoPajak');
        $router->post('/toko', 'ImportController@toko');
        $router->post('/ketentuan_toko', 'ImportController@ketentuan_toko');
        $router->post('/barang', 'ImportController@barang');
        $router->post('/harga_barang', 'ImportController@harga_barang');
        $router->post('/harga_barang_aktif', 'ImportController@harga_barang_aktif');
        $router->post('/update_toko', 'ImportController@update_toko');
        $router->post('/update_harga_barang', 'ImportController@update_harga_barang');
        $router->post('/update_barang', 'ImportController@update_barang');

        $router->get('/penjualan_kino', 'ImportController@penjualan_kino');
        $router->post('/penjualan_sosro', 'ImportController@penjualan_sosro');
        $router->post('/whitelist_outlet', 'ImportController@whitelist_outlet');
    });

    //IMPORT NPWP EXTERNAL
    $router->group(['prefix' => 'npwp_external'], function () use ($router) {
        $router->get('/', 'NpwpExternalController@index');
        $router->post('/', 'NpwpExternalController@store');
        $router->put('/{id}', 'NpwpExternalController@update');
        $router->delete('/{id}', 'NpwpExternalController@destroy');
        $router->post('/import', 'NpwpExternalController@import');
    });

    //KONVERSI PAJAK
    $router->group(['prefix' => 'koversi_pajak'], function () use ($router) {
        $router->post('/convert', 'KonversiPajakController@convert');
    });

    // EXPORT
    $router->group(['prefix' => 'export'], function () use ($router) {
        $router->post('/stt/bridging', 'SttExportController@export');
        $router->get('/kino', 'KinoBridgingController@export');
    });
    // ROLE
    $router->group(['prefix' => 'role'], function () use ($router) {
        $router->get('/', 'RoleController@index');
        $router->post('/', 'RoleController@store');
        $router->get('/{id}', 'RoleController@edit');
        $router->put('/{id}', 'RoleController@update');
        $router->delete('/{id}', 'RoleController@destroy');
    });

    // PERMISSION
    $router->group(['prefix' => 'permission'], function () use ($router) {
        $router->get('/', 'PermissionController@index');
        $router->post('/', 'PermissionController@store');
        $router->get('/{id}', 'PermissionController@edit');
        $router->put('/{id}', 'PermissionController@update');
        $router->delete('/{id}', 'PermissionController@destroy');
    });

    // SELECT OPTION
    $router->group(['prefix' => '/options'], function () use ($router) {
        $router->get('/', 'SelectOptionController@index');
        $router->post('/', 'SelectOptionController@store');
        $router->get('/{id}', 'SelectOptionController@edit');
        $router->put('/{id}', 'SelectOptionController@update');
        $router->delete('/{id}', 'SelectOptionController@destroy');

        $router->get('/get/list', 'SelectOptionController@listByCode');
    });

    //STOCK
    $router->group(['prefix' => 'riwayat_barang'], function () use ($router) {
        $router->get('/', 'StockController@riwayatBarang');
    });

    //FIX BUG
    $router->group(['prefix' => 'fix-bug'], function () use ($router) {
        $router->get('/saldo-retur/{id}', 'ReturPenjualanController@fixSaldoRetur');
    });

    $router->get('logs', 'LogController@index');

    $router->group(['prefix' => 'stock_opname'], function () use ($router) {
        $router->get('/', 'StockOpnameController@index');
        $router->get('/{id}/cancel_approval', 'StockOpnameController@cancel_approval');
        $router->post('/', 'StockOpnameController@store');
        $router->put('/{id}', 'StockOpnameController@update');
        $router->delete('/{id}', 'StockOpnameController@destroy');
    });

    $router->group(['prefix' => 'detail_stock_opname'], function () use ($router) {
        $router->get('/{id}/detail', 'DetailStockOpnameController@index');
        $router->post('/{id}', 'DetailStockOpnameController@store');
        $router->post('/update/detail', 'DetailStockOpnameController@update');
    });

    $router->group(['prefix' => '/references'], function () use ($router) {
        $router->get('/', 'ReferenceController@index');
        $router->post('/', 'ReferenceController@store');
        $router->get('/{id}', 'ReferenceController@edit');
        $router->put('/{id}', 'ReferenceController@update');
        $router->delete('/{id}', 'ReferenceController@destroy');

        $router->get('/get/by_code', 'ReferenceController@findByCode');
    });

    // kunjungan sales
    $router->group(['prefix' => '/kunjungan_sales'], function () use ($router) {
        $router->get('/', 'KunjunganSalesController@index');
        $router->post('/', 'KunjunganSalesController@store');
        $router->get('/{id}', 'KunjunganSalesController@show');
        $router->put('/{id}', 'KunjunganSalesController@update');
        $router->delete('/{id}', 'KunjunganSalesController@destroy');
        $router->get('/get/riwayat_kunjungan', 'KunjunganSalesController@riwayat');
        $router->get('/get/report_excel', 'KunjunganSalesController@reportExcel');
    });

    // target sales
    $router->group(['prefix' => '/target_salesman'], function () use ($router) {
        $router->get('/', 'TargetSalesmanController@index');
        $router->post('/', 'TargetSalesmanController@store');
        $router->get('/{id}', 'TargetSalesmanController@edit');
        $router->put('/{id}', 'TargetSalesmanController@update');
        $router->delete('/{id}', 'TargetSalesmanController@destroy');

        $router->get('/get/report', 'TargetSalesmanController@report');
        $router->get('/get/report_excel', 'TargetSalesmanController@reportExcel');
    });

    //mitra
    $router->group(['prefix' => 'mitra'], function () use ($router) {
        $router->get('/list/simple', 'MitraController@list');
    });
    $router->group(['prefix' => 'posisi_stock_mitra'], function () use ($router) {
        $router->get('/', 'PosisiStockMitraController@index');
    });
});
