<?php

namespace App\Traits;

trait KinoCodeCompany {
    public function ccompany(String $wh):string {
        $wh = strtoupper($wh);
        if (strpos($wh, 'DPS') !== false) {
          return "1202087";
        }

        if (strpos($wh, 'KLUNGKUNG') !== false) {
          return "1202088";
        }

        if (strpos($wh, 'KLK') !== false) {
          return "1202088";
        }

        return '';
    }

    public function ccompany2(String $depo, $id_principal):string {
        $depo = strtoupper($depo);
        if ($depo == 'DENPASAR' && $id_principal == 32) {
            return "1202087";
        }

        if ($depo == 'KLUNGKUNG' && $id_principal == 32) {
            return "1202088";
        }

        if ($depo == 'DENPASAR' && $id_principal == 25) {
            return "248";
        }

        if ($depo == 'KLUNGKUNG' && $id_principal == 25) {
            return "248";
        }

        return '';
    }
}
