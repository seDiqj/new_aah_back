<?php 

namespace App\Helpers;

class LogHelpers {

    public static function logArrToConsole (array $arr) 
    {

        $base = "[";
        $end = "]";


        foreach ($arr as &$ar) {

            $base .= "$ar,";

        }


        $base .= $end;


        error_log($base);

    }


}

