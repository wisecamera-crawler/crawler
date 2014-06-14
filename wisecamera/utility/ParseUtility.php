<?php
/**
 * ParseUtility.php : Some parsing helpers
 *
 * PHP version 5
 *
 * LICENSE : none
 *
 * @author   Poyu Chen <poyu677@gmail.com>
 */
namespace wisecamera\utility;

/**
 * ParseUtility : Some parsing helpers
 *
 * @author   Poyu Chen <poyu677@gmail.com>
 */
class ParseUtility
{
    /**
     * intStrToInt
     *
     * Translate "int string" to pure int
     * ex. 1,234 => 1234
     *
     * @param string $str   the input string
     *
     * @return int  the result integer
     */
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

    /**
     * resolveHost
     *
     * Resove the ip of full url
     * ex. http://127.0.0.1/some/things => 127.0.0.1
     *
     * @param string $fullUrl   Input
     *
     * @return string   The result
     */
    public static function resolveHost($fullUrl)
    {
        $arr = preg_split("/[\/\:]/", $fullUrl);

        return $arr[3];
    }
}
