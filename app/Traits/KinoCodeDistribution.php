<?php

namespace App\Traits;

trait KinoCodeDistribution {
    public function cdist(String $wh) :string {
        $wh = strtoupper($wh);
        if (strpos($wh, 'DPS') !== false) {
          return 'Bali';
        }
      
        if (strpos($wh, 'KLUNGKUNG') !== false) {
          return 'Klungkung';
        }
        
        if (strpos($wh, 'KLK') !== false) {
          return 'Klungkung';
        }
        
        return '';
    }
}