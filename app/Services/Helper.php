<?php

namespace App\Services;

class Helper
{
    public function hideNumb($phone)
    {
        $middleString = '';
        if ($phone != '') {
            $length = strlen($phone);
            if ($length > 8) {
                if( $length < 3 ) {
                    echo $length == 1 ? "*" : "*". substr($phone,  - 1);            
                } else{
                    $partSize = floor( $length / 3 ) ; 
                    $middlePartSize = $length - ( $partSize * 2 );
                    for( $i=0; $i < $middlePartSize ; $i ++ ){
                        $middleString .= "*";
                    }
                    $phone = substr($phone, 0, $partSize ) . $middleString  . substr($phone,  - $partSize );
                }
            }
        }
        return $phone;
    }
}