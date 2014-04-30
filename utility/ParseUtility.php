<?php
namespace wisecamera;

class ParseUtility
{
    public static function intStrToInt($str)
    {
        $value = 0;
        for ($i = 0; $i < strlen($str); ++$i) {
            if ($str[$i] >= '0' and $str[$i] <= '9') {
                $value = $value*10 + ($str[$i] - '0');
            }
        }

        return $value;
    }

    public static function resolveHost($fullUrl)
    {
        $arr = preg_split("/[\/\:]/", $fullUrl);

        return $arr[3];
    }
}
