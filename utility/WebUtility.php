<?php
namespace wisecamera;

class WebUtility
{
    private static $isUseProxy = false;
    private static $proxy = "";

    public static function getHtmlContent($url)
    {
        $ch = curl_init();
        $user_agent = "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/535.19 " .
            "(KHTML, like Gecko) Chrome/18.0.1025.142 Safari/535.19";
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, true);
        curl_setopt($ch, CURLOPT_CAPATH, "/certificate");
        curl_setopt($ch, CURLOPT_CAINFO, "/certificate/server.crt");
        if (WebUtility::$isUseProxy == true) {
            curl_setopt($ch, CURLOPT_PROXY, WebUtility::$proxy);
        }
        $txt = curl_exec($ch);
        curl_close($ch);

        return $txt;
    }

    public static function useProxy($bool)
    {
        WebUtility::$isUseProxy = $bool;

        if ($bool == true) {
            WebUtility::$proxy = WebUtility::chooseProxy();
        } else {
            WebUtility::$proxy = "";
        }
    }

    public static function getProxy()
    {
        return WebUtility::$proxy;
    }

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
        $key = array_rand($data);
        return "socks5://" . $data[$key]["proxy_ip"] .  ":" . $data[$key]["proxy_port"];
    }
}
