<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;
use App\Models\Toko;
use App\Models\KetentuanToko;
use App\Models\Penjualan;
use App\Models\DetailPenjualan;
use App\Models\User;
use App\Http\Resources\Toko as TokoResource;
use App\Http\Resources\Penjualan as PenjualanResource;

class CustomerController extends Controller
{
    protected $jwt;

    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
    }

    public function my_outlet(Request $request){
        $my_outlet = Toko::where('id_user', $this->user->id)->get();

        return TokoResource::collection($my_outlet);
    }

    public function riwayat_penjualan(Request $request){ // parameters: $per_page, $page, $date, $start_date, $end_date

        if ($this->user->role == 'customer'){
            $id_toko = Toko::where('id_user', $this->user->id)->pluck('id');
        }
        else{
            $id_toko = array($request->id_toko);
        }

        $list_penjualan = Penjualan::whereIn('id_toko', $id_toko)->latest();

        if($request->has('keyword')){
            $keyword = $request->keyword;
            $list_penjualan = $list_penjualan->where(function ($query) use ($keyword){
                $query->where('id', 'like', '%' . $keyword . '%');
                // ->orWhere('tanggal', 'like', '%' . $keyword . '%');
            });
        }

        if($request->start_date != '' && $request->end_date != ''){
            $start_date = $request->start_date;
            $end_date = $request->end_date;
        }
        elseif($request->date != ''){
            $start_date = $request->date;
            $end_date = $request->date;
        }
        else{
            $dateTime = new \Carbon\Carbon();
            if($request->year != '' && $request->week != ''){
                $dateTime = $dateTime->setISODate($request->year, $request->week);
                $start_date = $dateTime->copy()->startOfWeek();
                $end_date = $dateTime->copy()->endOfWeek();
            }
            elseif($request->year != '' && $request->month != ''){
                $dateTime = $dateTime->create($request->year, $request->month, 1, 0, 0, 0);
                $start_date = $dateTime->copy()->startOfMonth();
                $end_date = $dateTime->copy()->endOfMonth();
            }
            elseif($request->year != '' && $request->quarter != ''){
                $month = ($request->quarter * 3) -1;
                $dateTime = $dateTime->createFromFormat('Y-m-d', $request->year . '-' . $month .'-01');
                $start_date = $dateTime->copy()->startOfQuarter();
                $end_date = $dateTime->copy()->endOfQuarter();
            }
            elseif($request->year != ''){
                $dateTime = $dateTime->create($request->year, 1, 1, 0, 0, 0);
                $start_date = $dateTime->copy()->startOfYear();
                $end_date = $dateTime->copy()->endOfYear();
            }
            else{
                $today = $dateTime->now();
                $start_date = $today->copy()->startOfYear();
                $end_date = $today->copy()->endOfYear();
            }
        }

        // return 'Start Date : ' . $start_date . ', End Date : ' . $end_date;

        $list_penjualan = $list_penjualan->whereBetween('tanggal', [$start_date, $end_date]);

        // if($request->date != ''){
        //     $list_penjualan = $list_penjualan->where('tanggal',$request->date);
        // }
        // elseif($request->start_date != '' && $request->end_date != ''){
        //     $list_penjualan = $list_penjualan->whereBetween('tanggal', [$request->start_date, $request->end_date]);
        // }

        $perPage = $request->has('per_page') ? $perPage = $request->per_page : $perPage = 5;
        $list_penjualan = $perPage == 'all' ? $list_penjualan->get() : $list_penjualan->paginate((int)$perPage);
    
        if ($list_penjualan) {
            return PenjualanResource::collection($list_penjualan);
        }
    }

    public function laporan_penjualan_item(Request $request){ // parameter : per_page, page, start_date, end_date, keyword

        if ($this->user->role == 'customer'){
            $id_toko = Toko::where('id_user', $this->user->id)->pluck('id');
        }
        else{
            $id_toko = array($request->id_toko);
        }

        $id_penjualan = Penjualan::whereIn('id_toko', $id_toko);

        if($request->start_date != '' && $request->end_date != ''){
            $start_date = $request->start_date;
            $end_date = $request->end_date;
        }
        elseif($request->date != ''){
            $start_date = $request->date;
            $end_date = $request->date;
        }
        else{
            $dateTime = new \Carbon\Carbon();
            if($request->year != '' && $request->week != ''){
                $dateTime = $dateTime->setISODate($request->year, $request->week);
                $start_date = $dateTime->copy()->startOfWeek();
                $end_date = $dateTime->copy()->endOfWeek();
            }
            elseif($request->year != '' && $request->month != ''){
                $dateTime = $dateTime->create($request->year, $request->month, 1, 0, 0, 0);
                $start_date = $dateTime->copy()->startOfMonth();
                $end_date = $dateTime->copy()->endOfMonth();
            }
            elseif($request->year != '' && $request->quarter != ''){
                $month = ($request->quarter * 3) -1;
                $dateTime = $dateTime->createFromFormat('Y-m-d', $request->year . '-' . $month .'-01');
                $start_date = $dateTime->copy()->startOfQuarter();
                $end_date = $dateTime->copy()->endOfQuarter();
            }
            elseif($request->year != ''){
                $dateTime = $dateTime->create($request->year, 1, 1, 0, 0, 0);
                $start_date = $dateTime->copy()->startOfYear();
                $end_date = $dateTime->copy()->endOfYear();
            }
            else{
                $today = $dateTime->now();
                $start_date = $today->copy()->startOfYear();
                $end_date = $today->copy()->endOfYear();
            }
        }

        // return 'Start Date : ' . $start_date . ', End Date : ' . $end_date;

        $id_penjualan = $id_penjualan->whereBetween('tanggal', [$start_date, $end_date])->pluck('id');

        $detail_penjualan = DetailPenjualan::whereIn('id_penjualan', $id_penjualan)
                            // ->with('stock.barang')
                            ->join('stock', 'stock.id', '=', 'detail_penjualan.id_stock')
                            ->join('barang', 'barang.id', '=', 'stock.id_barang')
                            ->select(\DB::raw('detail_penjualan.id_stock, sum(detail_penjualan.qty) as sum_qty, sum(detail_penjualan.qty_pcs) as sum_pcs, barang.*'))
                            ->groupBy('detail_penjualan.id_stock')
                            ->orderBy('barang.kode_barang');
                            // ->paginate(2, ['barang.kode_barang']);
        
        if($request->has('keyword')){
            $keyword = $request->keyword;
            $detail_penjualan = $detail_penjualan->where(function ($query) use ($keyword){
                $query->where('barang.kode_barang', 'like', '%' . $keyword . '%')
                ->orWhere('barang.nama_barang', 'like', '%' . $keyword . '%');
            });
        }

        $perPage = $request->has('per_page') ? $perPage = $request->per_page : $perPage = 5;
        $detail_penjualan = $perPage == 'all' ? $detail_penjualan->get() : $detail_penjualan->paginate((int)$perPage, ['barang.kode_barang']);

        return $detail_penjualan;
    }

    // localhost:8000/customer-api/laporan_penjualan_value?start_date=2019-06-10&end_date=2019-12-14&year=2019&week=43&month=11&quarter=4
    // opt filter : - date
    //              - start_date, end_date
    //              - year
    //              - year, week (1-53)
    //              - year, month (1-12)
    //              - year, quarter (1-4)
    public function laporan_penjualan_value(Request $request){
        if ($this->user->role == 'customer'){
            $id_toko = Toko::where('id_user', $this->user->id)->pluck('id');
        }
        else{
            $id_toko = array($request->id_toko);
        }

        $penjualan = Penjualan::whereIn('id_toko', $id_toko);

        if($request->start_date != '' && $request->end_date != ''){
            $start_date = $request->start_date;
            $end_date = $request->end_date;
        }
        elseif($request->date != ''){
            $start_date = $request->date;
            $end_date = $request->date;
        }
        else{
            $dateTime = new \Carbon\Carbon();
            if($request->year != '' && $request->week != ''){
                $dateTime = $dateTime->setISODate($request->year, $request->week);
                $start_date = $dateTime->copy()->startOfWeek();
                $end_date = $dateTime->copy()->endOfWeek();
            }
            elseif($request->year != '' && $request->month != ''){
                $dateTime = $dateTime->create($request->year, $request->month, 1, 0, 0, 0);
                $start_date = $dateTime->copy()->startOfMonth();
                $end_date = $dateTime->copy()->endOfMonth();
            }
            elseif($request->year != '' && $request->quarter != ''){
                $month = ($request->quarter * 3) -1;
                $dateTime = $dateTime->createFromFormat('Y-m-d', $request->year . '-' . $month .'-01');
                $start_date = $dateTime->copy()->startOfQuarter();
                $end_date = $dateTime->copy()->endOfQuarter();
            }
            elseif($request->year != ''){
                $dateTime = $dateTime->create($request->year, 1, 1, 0, 0, 0);
                $start_date = $dateTime->copy()->startOfYear();
                $end_date = $dateTime->copy()->endOfYear();
            }
            else{
                $today = $dateTime->now();
                $start_date = $today->copy()->startOfYear();
                $end_date = $today->copy()->endOfYear();
            }
        }

        // return 'Start Date : ' . $start_date . ', End Date : ' . $end_date;

        $penjualan = $penjualan->whereBetween('tanggal', [$start_date, $end_date])->get();

        $laporan_penjualan = [
            'count' => $penjualan->count(),
            'sku' => $penjualan->avg('sku'),
            'total_qty' => $penjualan->sum('total_qty'),
            'total_pcs' => $penjualan->sum('total_pcs'),
            'total_qty_order' => $penjualan->sum('total_qty_order'),
            'total_pcs_order' => $penjualan->sum('total_pcs_order'),
            'value' => $penjualan->sum('grand_total'),
            'value_order' => $penjualan->sum('grand_total_order')
        ];

        return $laporan_penjualan;

    }


    // ===================================================== MANAGE ACCOUNT CUSTOMER (by salesman) =====================================================
    public function create_customer_account(Request $request){
        // localhost:8000/customer-api/account/create?id_toko=807&name=Test  Account Customer 2&password=secret&password_confirmation=secret&phone=2134567890&nik=5171017485960001&email=test.kpm@gmail.com
        $toko = KetentuanToko::find($request->id_toko);

        if($toko->toko->id_user != ''){
            return response()->json([
                'message' => 'Sudah ada akun pelanggan yang terdaftar pada toko ini'
            ], 400);
        }

        if ($this->user->role == 'salesman'){
            $id_tim_sales = $this->user->salesman->id_tim;
            if($id_tim_sales != $toko->id_tim){
                return response()->json([
                    'message' => 'Anda tidak berhak untuk mengelola data toko ini'
                ], 403);
            }
        }
        elseif($this->user->role != 'admin' && $this->user->role != 'accounting' && $this->user->role != 'pimpinan'){
            return response()->json([
                'message' => 'Anda tidak berhak untuk mengelola data toko ini'
            ], 403);
        }   

        $this->validate($request, [
            'name' => 'required|max:255|min:2',
            'email' => 'nullable|email|unique:users|max:190',
            'password' => 'required|confirmed|min:6|max:30',
            'phone' => 'required|unique:users|max:15',
            'nik' => 'max:16|nullable',
        ]);

        $input = $request->except(['password_confirmation']);
        $input['status'] = 'active';
        $input['role'] = 'customer';
        $input['password'] = app('hash')->make($input['password']);
        $input['created_by'] = $this->user->id;

        try {
            $user = User::create($input);
            Toko::find($request->id_toko)->update(['id_user' => $user->id]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

        return response()->json([
            'message' => 'Akun pelanggan berhasil dibuat.',
            'customer' => $user
        ], 201);
    }

    public function update_customer_account(Request $request){
        // localhost:8000/customer-api/account/update?id_toko=807&name=TEST Account Customer 2&phone=2134567890&nik=5171017485960002
        $toko = KetentuanToko::find($request->id_toko);

        if($toko->toko->id_user == ''){
            return response()->json([
                'message' => 'Belum ada akun pelanggan yang terdaftar pada toko ini'
            ], 400);
        }     

        if ($this->user->role == 'salesman'){
            $id_tim_sales = $this->user->salesman->id_tim;
            if($id_tim_sales != $toko->id_tim){
                return response()->json([
                    'message' => 'Anda tidak berhak untuk mengelola data toko ini'
                ], 403);
            }
        }
        elseif($this->user->role != 'admin' && $this->user->role != 'accounting' && $this->user->role != 'pimpinan'){
            return response()->json([
                'message' => 'Anda tidak berhak untuk mengelola data toko ini'
            ], 403);
        }

        $user = User::find($toko->toko->id_user);

        $this->validate($request, [
            'name' => 'required|max:255|min:2',
            'email' => 'nullable|email|max:190|unique:users,email,' . $user->id,
            // 'password' => 'required|confirmed|min:6|max:30',
            'phone' => 'required|max:15|unique:users,phone,' . $user->id,
            'nik' => 'max:16|nullable',
        ]);

        $input = $request->except(['password', 'password_confirmation']);
        $input['status'] = 'active';
        $input['role'] = 'customer';
        // $input['password'] = app('hash')->make($input['password']);
        $input['updated_by'] = $this->user->id;
        
        try {
            $user->update($input);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

        return response()->json([
            'message' => 'Akun pelanggan berhasil diubah.',
            'customer' => $user
        ], 201);
    }

    public function change_password_customer_account(Request $request){
        // localhost:8000/customer-api/account/change_password?id_toko=807&password=secret&password_confirmation=secret
        $toko = KetentuanToko::find($request->id_toko);

        if($toko->toko->id_user == ''){
            return response()->json([
                'message' => 'Belum ada akun pelanggan yang terdaftar pada toko ini'
            ], 400);
        }     

        if ($this->user->role == 'salesman'){
            $id_tim_sales = $this->user->salesman->id_tim;
            if($id_tim_sales != $toko->id_tim){
                return response()->json([
                    'message' => 'Anda tidak berhak untuk mengelola data toko ini'
                ], 403);
            }
        }
        elseif($this->user->role != 'admin' && $this->user->role != 'accounting' && $this->user->role != 'pimpinan'){
            return response()->json([
                'message' => 'Anda tidak berhak untuk mengelola data toko ini'
            ], 403);
        }

        $user = User::find($toko->toko->id_user);

        $this->validate($request, [
            'password' => 'required|confirmed|min:6|max:30'
        ]);

        $input = $request->except(['password_confirmation']);
        $input['password'] = app('hash')->make($input['password']);
        $input['updated_by'] = $this->user->id;
        
        try {
            $user->update($input);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

        return response()->json([
            'message' => 'Password akun pelanggan berhasil diubah.',
            'customer' => $user
        ], 201);
    }


}