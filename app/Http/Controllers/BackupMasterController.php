<?php


namespace App\Http\Controllers;


use App\Helpers\Helper;
use App\Models\Depo;
use App\Models\Mitra;
use App\Models\Penjualan;
use App\Models\Salesman;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class BackupMasterController extends Controller
{
    protected $jwt, $user;

    public function __construct(JWTAuth $jwt)
    {
        $this->jwt = $jwt;
        $this->user = $this->jwt->user();
    }

    public function index(Request $request, $table_request)
    {
        $id_user = $this->user->id;
        $allowed     = [135, 349, 373];
        if (!$this->user->can('Menu Backup Harian')) {
            return $this->Unauthorized();
        }
        if (!in_array($id_user, $allowed)) {
            return $this->Unauthorized();
        }
        $start_date  = $request->has('tanggal') ?
            Carbon::createFromFormat('Y-m-d', $request->tanggal)->subDays(1)->subHours(5) : Carbon::now()->subDays(1)->subHours(5);
        $end_date    = $request->has('tanggal') ?
            Carbon::createFromFormat('Y-m-d', $request->tanggal)->addDays(1) : Carbon::now()->addDays(1);
        $date_filter = [$start_date, $end_date];
        //$tipe        = $request->tipe;
        $tables      = DB::select('SHOW TABLES');
        $DB_name     = DB::connection()->getDatabaseName();
        $DB_src      = 'Tables_in_' . $DB_name;
        $warning_index_array = [];
        $response    = [];
        # WARNING DONT CHANGE THIS!!!!!
        function newLine($set)
        {
            return $set . '
';
        }
        #-------------------GET BACKUP--------------------------#
        $sql_all = '';
        $exclude = [];
        foreach ($tables as $key => $table) {
            $table_nm = $table->{$DB_src};
            if ($table_request != 'all') {
                $table_nm = $table_request;
            }
            $checker  = explode('_', $table_nm);
            $column   = Schema::getColumnListing($table_nm);
            $limit    = in_array('created_at', $column) ? 'created_at' : 'all';
            $limit    = in_array('updated_at', $column) ? 'updated_at' : $limit;
            $limit    = ($table_nm == 'logs'  || $checker[0] == 'v') ? '' : $limit;
            $limit    = ($table_nm == 'log_stock' && $table_request == 'all') ? '' : $limit;

            $test     = implode('`, `', $column);
            $sql_cop  = 'REPLACE INTO ' . '`' . $table_nm . '`' . ' (' . '`' . $test . '`' . ') VALUES';
            $sql_insert = '';
            $sql_is     = false;
            $warning_index  = '';
            if ($limit != '') {
                $data   = DB::table($table_nm);
                if ($limit != 'all') {
                    $data   = $data->whereBetween($limit, $date_filter);
                }
                $data   = $data->get();
                foreach ($data as $sub_key => $sub) {
                    if ($limit != 'all') {
                        if ($sub->id < 500) {
                            $warning_index =  true;
                            $warning_index_array[] = 'id_' . $table_nm . ' = ' . $sub->id;
                        }
                    }
                    $sql_is = true;
                    if ($sub_key % 500 == 0) {
                        if ($sub_key > 0) {
                            $sql_insert = newLine($sql_insert) . $sql_cop;
                        } else {
                            $sql_insert = $sql_insert . $sql_cop;
                        }
                    }
                    #new line
                    $sql_insert = newLine($sql_insert);
                    #sub value
                    $sql_sub    = '#' . implode('#,#', array_values(get_object_vars($sub))) . '#';
                    #insert new value
                    $sql_insert = $sql_insert . ' (' . $sql_sub . ')';
                    #close with coma or semi colum
                    $sql_insert = ($sub_key + 1) % 500 == 0 || ($sub_key + 1) == count($data) ? $sql_insert . ';' : $sql_insert . ',';
                }
            }
            if ($sql_is) {
                $sql_all = $sql_all . newLine($sql_insert);
            }
            if ($table_request != 'all') {
                break;
            }
        }
        #clear sub value
        $sql_all   = str_replace('##', 'NULL', $sql_all);
        $sql_all   = str_replace('"', '', $sql_all);
        $sql_all   = str_replace('#', '"', $sql_all);
        if ($warning_index) {
            print_r($warning_index_array);
        }
        return $warning_index ? 'WARNING INDEX TO SMALL (note : add warning ignore to force get)' : $sql_all;
    }
}
