<?php
namespace App\Http\Resources;
use Illuminate\Http\Resources\Json\Resource;
use App\Models\Salesman;
use App\Models\Penjualan;
use App\Models\DetailPenjualan;
class WeeklyReport extends Resource
{
    
    /**
     * @var
     */
    private $id_salesman, $start_date, $end_date;
    /**
     * Create a new resource instance.
     *
     * @param  mixed  $resource
     * @return void
     */
    // public function __construct($resource, $start_date, $end_date)
    public function __construct($id_salesman, $start_date, $end_date)
    {
        // Ensure you call the parent constructor
        // parent::__construct($resource);
        // $this->resource = $resource;

        $date = \Carbon\Carbon::createFromFormat('Y-m-d', $start_date);
        $this->id_salesman = $id_salesman;
        $this->week = $date->weekOfYear;
        $this->start_date = $start_date;
        $this->end_date = $end_date;
    }
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $salesman = Salesman::find($this->id_salesman);
        $penjualan = Penjualan::where('id_salesman', $this->id_salesman)->whereBetween('tanggal', [$this->start_date,$this->end_date])->whereNotIn('status', ['waiting','canceled']);
        $list_penjualan = $penjualan->get();
        $count_penjualan = $list_penjualan->count();
        $count_date = $penjualan->distinct('tanggal')->pluck('tanggal')->count();
        $ec = $count_penjualan / $count_date;
        
        $id_penjualan = $penjualan->pluck('id');
        $detail_penjualan = DetailPenjualan::whereIn('id_penjualan', $id_penjualan)->get();
        $sku = $list_penjualan->sum('sku') / $ec;
        $ds = $list_penjualan->sum('sum_carton') / $ec;
        return [
            'id_salesman' => $this->id_salesman,
            'nama_salesman' => $salesman->user->name,
            'nama_tim' => $salesman->tim->nama_tim,
            'nama_depo' => $salesman->tim->depo->nama_depo,
            'week' => $this->week,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'ec' => $ec,
            'sku' => $sku,
            'ds' => $ds,
        ];
    }
} 