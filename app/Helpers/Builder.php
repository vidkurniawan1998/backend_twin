<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

class Builder
{

    public function request($request)
    {
        $parameter  = array_key_exists('parameter', $request) ? $request['parameter'] : [];
        $select     = $request['select'];
        $fungsi     = $request['using'];
        $alias      = $request['as'];
        $continue   = array_key_exists('continue', $request) ? $request['continue'] : false;
        $query      = '';
        $select     = strtoupper($select);
        $fungsi     = strtolower($fungsi);
        switch ($fungsi) {
            case 'subtotal':
                $query = $this->subtotal($parameter);
                break;
            case 'grand_total':
                $query = $this->grand_total($parameter);
                break;
            case 'grand_total_retur':
                $query = $this->grand_total_retur_penjualan($parameter);
                break;
            case 'qty':
                $query = $this->qty($parameter);
                break;
            case 'qty_pcs':
                $query = $this->qty_pcs($parameter);
                break;
            default:
                return response()->json(['error' => 'error'], 400);
                break;
        }
        $query = $this->bracket($query);
        $query = $select . $query . ' AS ' . $alias;
        return $continue ? $query . ',' : $query;
    }

    public function bracket($query)
    {
        return '( ' . $query . ' )';
    }

    public function set_param($parameter = [], $checker = [])
    {
        $default = [
            'qty'         => 'qty',
            'qty_pcs'     => 'qty_pcs',
            'isi'         => 'barang.isi',
            'harga_jual'  => 'harga_jual',
            'disc_rupiah' => 'disc_rupiah',
            'disc_persen' => 'disc_persen',
            'pajak'       => 10,
            'faktur_pajak_pembelian' => 'faktur_pajak_pembelian',
            'potongan' => 'potongan',
            'is_harga_jual_include_ppn'  => true,
        ];
        foreach ($checker as $row) {
            $parameter[$row] = array_key_exists($row, $parameter) ? $parameter[$row] : $default[$row];
        }
        return $parameter;
    }

    public function paramDefault($parameter)
    {
        $paramDefault = [
            'retur_penjualan' => [
                'qty' => 'qty_dus',
                'harga_jual' => 'harga',
                'disc_rupiah' => 'disc_nominal',
                'is_harga_jual_include_ppn'  => false,
            ],
        ];
        return $paramDefault[$parameter];
    }

    public function subtotal($parameter = [])
    {
        $qty        = $this->qty($parameter);
        $harga_jual = $this->harga_jual($parameter);
        $query      = $qty . ' * ' . $harga_jual;
        return $this->bracket($query);
    }

    public function pajak($parameter = [])
    {
        $checker   = ['pajak'];
        $parameter = $this->set_param($parameter, $checker);
        $query     = (100 + floatval($parameter['pajak'])) / 100;
        return $query;
    }

    public function harga_jual($parameter = [])
    {
        $checker   = ['harga_jual','is_harga_jual_include_ppn'];
        $pajak     = $this->pajak($parameter);
        $parameter = $this->set_param($parameter, $checker);
        $query     =  $parameter['is_harga_jual_include_ppn'] ? $parameter['harga_jual'] . ' / ' . $pajak : $parameter['harga_jual'];
        return $this->bracket($query);
    }

    public function qty($parameter = [])
    {
        $checker   = ['qty', 'qty_pcs', 'isi'];
        $parameter = $this->set_param($parameter, $checker);
        $query     = $parameter['qty'] . ' + ' . '(' . $parameter['qty_pcs'] . ' / ' . $parameter['isi'] . ')';
        return $this->bracket($query);
    }


    public function qty_pcs($parameter = [])
    {
        $checker   = ['qty', 'qty_pcs', 'isi'];
        $parameter = $this->set_param($parameter, $checker);
        $query     = $parameter['qty_pcs'] . ' + ' . '(' . $parameter['qty'] . ' * ' . $parameter['isi'] . ')';
        return $this->bracket($query);
    }

    public function diskon_rupiah($parameter = [])
    {
        $qty       = $this->qty($parameter);
        $pajak     = $this->pajak($parameter);
        $checker   = ['disc_rupiah'];
        $parameter = $this->set_param($parameter, $checker);
        $query     = $qty . ' * ' . $parameter['disc_rupiah'];
        $query     = $this->bracket($query);
        $query     = $query . ' / ' . $pajak;
        return $this->bracket($query);
    }

    public function diskon_persen($parameter = [])
    {
        $subtotal  = $this->subtotal($parameter);
        $checker   = ['disc_persen'];
        $parameter = $this->set_param($parameter, $checker);
        $query     = $subtotal . ' * ' . $parameter['disc_persen'] . ' / ' . '100';
        return $this->bracket($query);
    }

    public function diskon($parameter = [])
    {
        $diskon_persen = $this->diskon_persen($parameter);
        $diskon_rupiah = $this->diskon_rupiah($parameter);
        $query         = $diskon_persen . ' + ' . $diskon_rupiah;
        return $this->bracket($query);
    }

    public function dpp($parameter = [])
    {
        $subtotal = $this->subtotal($parameter);
        $diskon   = $this->diskon($parameter);
        $query    = $subtotal . ' - ' . $diskon;
        return $this->bracket($query);
    }

    public function ppn($parameter = [])
    {
        $dpp       = $this->dpp($parameter);
        $checker   = ['pajak'];
        $parameter = $this->set_param($parameter, $checker);
        $query     = $dpp . ' * ' . $parameter['pajak'] . ' / ' . '100';
        return $this->bracket($query);
    }

    public function grand_total($parameter = [])
    {
        $dpp       = $this->dpp($parameter);
        $pajak     = $this->pajak($parameter);
        $query     = $dpp . ' * ' . $pajak;
        return $this->bracket($query);
    }

    public function grand_total_retur_penjualan($parameter)
    {
        $query = "CASE
                    WHEN faktur_pajak_pembelian > 0 THEN ".$this->grand_total_retur($parameter)."
                    ELSE ".$this->dpp_retur($parameter)."
                   END";
        return $query;
    }

    public function grand_total_retur($parameter = [])
    {
        return $this->after_potongan($this->grand_total($parameter),$parameter);
    }

    public function dpp_retur($parameter = [])
    {
        return $this->after_potongan($this->dpp($parameter),$parameter);
    }

    public function after_potongan($query, $parameter = [])
    {
        $checker   = ['potongan'];
        $parameter = $this->set_param($parameter, $checker);
        return $this->bracket($query.'*(100-'.$parameter['potongan'].')/100');
    }

    public function table($table)
    {
        return DB::table($table)->whereNull($table.'.deleted_at');
    }
}
