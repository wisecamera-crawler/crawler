<?
class ParseUtility
{
    static public function intStrToInt($str){
        $value = 0;
        for($i = 0; $i < strlen($str); ++$i) {
            if($str[$i] >= '0' AND $str[$i] <= '9') {
                $value = $value*10 + ($str[$i] - '0');
            }
        }
        return $value;
    }

    static public function resolveHost($fullUrl)
    {
        $arr = preg_split("/[\/\:]/", $fullUrl);
        return $arr[3];
    }
}

