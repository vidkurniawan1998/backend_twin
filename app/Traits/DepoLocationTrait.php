<?php

namespace App\Traits;

trait DepoLocationTrait {
    public function depoLocation(String $wh):string
    {
        $wh         = strtoupper($wh);
        $exclude    = ['BS', 'Heinz', 'TAS', 'FORCE', 'KVS', 'RINA', 'FEBY', 'GRB', 'Bakar', 'Pak', 'Lain-lain', 'Mes', 'SPV', 'DPS2', 'KLK 2', 'NGR 2', 'SGR 2'];
        foreach ($exclude as $exc) {
            $exc = strtoupper($exc);
            if (strpos($wh, $exc) !== false) {
                return '';
            }
        }

        $depo   = '';
        if (strpos($wh, 'DPS') !== false) {
            $depo = 'denpasar';
        }
      
        if (strpos($wh, 'KLUNGKUNG') !== false) {
            $depo = 'klungkung';
        }
        
        if (strpos($wh, 'KLK') !== false) {
            $depo = 'klungkung';
        }
    
        if (strpos($wh, 'SGR') !== false) {
            $depo = 'singaraja';
        }

        if (strpos($wh, 'NGR') !== false) {
            $depo = 'negara';
        }

        return $depo;
    }
}