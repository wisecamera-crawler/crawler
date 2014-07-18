<?php
/**
 * WebUtility.php : Provide the APIs for crawler to get html content
 *
 * PHP version 5
 *
 * LICENSE : none
 *
 * @author   Poyu Chen <poyu677@gmail.com>
 */
namespace wisecamera\utility;

/**
 * WebUtility provides the API for crawler to get html content.
 *
 * Ther are 2 public functions for crawler developers:
 *  getHtmlContent($url, $proxy)
 *      This function will return html content of assigned url.
 *      User may assign proxy to this connection, too.
 *      If not assigned, this connection will use default setting
 *  useProxy($bool)
 *      Set use proxy or not, 
 *      if true, this class will auto select proxy for connection
 *
 * LICENSE : none
 *
 * @author   Poyu Chen <poyu677@gmail.com>
 */
class WebUtility
{
    /**
     * Is use proxy currently?
     */
    private static $isUseProxy = false;
    
    /**
     * If using proxy, proxy url?
     * This url will auto gen with chooseProxy function
     */
    private static $proxy = "";

    /**
     * Error condition code
     * 0 : OK
     * 1 : connection error
     */
    private static $error = 0;

    /**
     * getHtmlContent
     *
     * This function will return html content of assigned url
     *
     * @param string $url   The assigned url
     * @param string $proxy The assigned proxy, default is null.
     *                      If not set, this class will use default setting.
     *                      (depends on $isUseProxy variable)
     *
     * @return string $txt  The html content
     */
    public static function getHtmlContent($url, $proxy = null)
    {
        if (WebUtility::$error !== 0) {
            return "";
        }
        $txt = WebUtility::connect($url, $proxy);
        
        if ($txt === null) {
            if (WebUtility::testConnection($proxy) === false) {
                WebUtility::$error = 1;
            }
        }
        return $txt;
    }

    /**
     * getErrCode
     *
     * @return int  errCode
     */
    public static function getErrCode()
    {
        return WebUtility::$error;
    }

    /**
     * useProxy
     *
     * User may set if using proxy through this function
     * If set to true, this class will select procy from DB
     *
     * @param bool $bool use proxy or not
     *
     * @return none
     */
    public static function useProxy($bool)
    {
        WebUtility::$isUseProxy = $bool;

        if ($bool == true) {
            WebUtility::$proxy = WebUtility::chooseProxy();
            if (WebUtility::$proxy == "") {
                WebUtility::$isUseProxy = false;
            }
        } else {
            WebUtility::$proxy = "";
        }
    }

    /**
     * getProxy
     *
     * Return the current defulat proxy
     *
     * @return string   Url of proxy
     */
    public static function getProxy()
    {
        return WebUtility::$proxy;
    }

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
        if (WebUtility::$isUseProxy == true) {
            curl_setopt($ch, CURLOPT_PROXY, WebUtility::$proxy);
        }
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
     * chhoseProxy
     *
     * This function will choose availabe proxy in DB
     * First, it will get proxy information from DB.
     * Then randomly choose one to test its availibility.
     * If the selected proxy is avalable, return the url.
     * If not, update the DB (set off-line in DB), choose next proxy.
     *
     * @return string   Proxy url
     */
    private static function chooseProxy()
    {
        $dsn = "mysql:host=" . SQLService::$ip . ";dbname=NSC";
        $conn = new \PDO($dsn, SQLService::$user, SQLService::$password);
        $result = $conn->query(
            "SELECT `proxy_ip`, `proxy_port`
                FROM `proxy`
                WHERE `status` = 'on-line'"
        );

        $data = $result->fetchAll(\PDO::FETCH_ASSOC);
        if (!$data) {
            return "";
        }
        $key = array_rand($data);
        $proxy = "socks5://" . $data[$key]["proxy_ip"] .  ":" . $data[$key]["proxy_port"];
        while (!WebUtility::testConnection($proxy) and $data) {
            $result = $conn->query(
                "UPDATE `proxy`
                    set `status` = 'off-line'
                WHERE `proxy_ip` = '" . $data[$key]["proxy_ip"] . "'"
            );

            unset($data[$key]);
            
            if (!$data) {
                return "";
            }
 
            $key = array_rand($data);
            $proxy = "socks5://" . $data[$key]["proxy_ip"] .  ":" . $data[$key]["proxy_port"];
        }

        return $proxy;
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
