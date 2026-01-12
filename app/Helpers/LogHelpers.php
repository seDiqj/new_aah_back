<?php 

namespace App\Helpers;

use Illuminate\Support\Collection;

use function PHPUnit\Framework\isArray;

class LogHelpers {

    public static function logArrToConsole (array $arr) 
    {

        $base = "[";
        $end = "]";


        foreach ($arr as &$ar) {

            $ar = $ar;

            if (isArray($ar)) {

                foreach ($ar as $item) {

                    $base .= "$item, ";

                }

                continue;

            }

            $base .= "$ar,";

        }

        $base .= $end;


        error_log($base);

    }

}

