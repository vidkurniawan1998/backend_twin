<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\Resource;

class SalesmanReport extends Resource
{
    // https://github.com/laravel/framework/issues/23826
    /**
     * @var
     */
    // private $foo;


    /**
     * Create a new resource instance.
     *
     * @param  mixed  $resource
     * @return void
     */
    // public function __construct($resource, $foo)
    // {
    //     // Ensure you call the parent constructor
    //     parent::__construct($resource);
    //     $this->resource = $resource;
        
    //     $this->foo = $foo;
    // }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id_salesman' => $this->user_id,
            'nama_salesman' => $this->user->name,
            'tipe_salesman' => $this->tim->tipe,
            'nama_tim' => $this->tim->nama_tim,
            'nama_depo' => $this->tim->depo->nama_depo,
            'nama_gudang' => $this->tim->depo->gudang->nama_gudang,

            'ec' => $this->effective_call,
            'sku' => $this->stock_keeping_unit,
            'ds' => $this->drop_size,
            
        ];
    }
}