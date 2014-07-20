<?php
/**
 * WebUtility.php : Provide functions for test connection
 *
 * PHP version 5
 *
 * LICENSE : none
 *
 * @author   Poyu Chen <poyu677@gmail.com>
 */
namespace wisecamera\utility;

/**
 * WebUtility provides functions for test connection of proxy
 *
 * LICENSE : none
 *
 * @author   Poyu Chen <poyu677@gmail.com>
 */
class WebUtility
{
    /**
     * connect
     *
     * A function really do connection.
     * Initailize the curl object and connect.
     * If connection error, it will update the errCode.
     *
     * @param string $url   The assigned url
     * @param string $proxy The assigned proxy, default is null.
     *                      If not set, this class will use default setting.
     *                      (depends on $isUseProxy variable)
     *
     * @return string $txt  The html content
    */
    private static function connect($url, $proxy = null)
    {
        $ch = curl_init();
        $user_agent = "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/535.19 " .
            "(KHTML, like Gecko) Chrome/18.0.1025.142 Safari/535.19";
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
        if ($proxy !== null) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy);
        }

        $txt = curl_exec($ch);
        if (curl_errno($ch)) {
            return null;
        }
        curl_close($ch);

        return $txt;
    }

    /**
     * testConnection
     *
     * This function test connection's (proxy's) availability through connect to Google
     *
     * @param string $proxy Proxy url, or default is not to use proxy
     *
     * @return bool True for OK, false for bad connection
     */
    public static function testConnection($proxy = null)
    {
        $html = WebUtility::connect("www.google.com", $proxy);
        if ($html === null) {
            return false;
        }
        return true;
    }
}
