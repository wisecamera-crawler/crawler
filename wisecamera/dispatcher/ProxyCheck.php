<?php
/**
 * Proxy's check, a Proxy server class
 *
 * PHP version 5
 *
 * LICENSE: none
 *
 * @category  Utility
 * @package   PackageName
 * @author    Patrick Her <zivhsiao@gmail.com>
 * @copyright 1997-2005 The PHP Group
 * @license   none <none>
 * @version   GIT: <id>
 * @link      none
 */

namespace wisecamera\dispatcher;

/**
 * Proxy's check
 *
 * PHP version 5
 *
 * LICENSE: none
 *
 * @category  Utility
 * @package   PackageName
 * @author    Patrick Her <zivhsiao@gmail.com>
 * @copyright 1997-2005 The PHP Group
 * @license   none <none>
 * @link      none
 */

class ProxyCheck
{

    public static $chkAllTime;
    public static $extraProgram;
    public static $chkType;
    public static $chkTime;
    public static $chkProxyTime;
    /**
     * Proxy's check
     *
     * PHP version 5
     *
     * LICENSE: none
     *
     * @param string $proxy Proxy's address
     *
     * @category  Utility
     * @return    none
     */
    public function check($proxy)
    {
        $urlArray = array(
            'http://www.google.com',
            'http://tw.yahoo.com',
            'http://www.pchome.com.tw'
        );

        $url = $urlArray[rand(0, count($urlArray) - 1)];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, ProxyCheck::$chkProxyTime);
        curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
        curl_setopt($ch, CURLOPT_PROXY, $proxy);

        $response = curl_exec($ch);

        if ($response === false) {
            return "connection error!";
        } else {
            return "worked";
        }
    }

    /**
     * ps making
     *
     * PHP version 5
     *
     * LICENSE: none
     *
     * @param string $param  1st param
     * @param string $param2 2nd param
     *
     * @category  Utility
     * @return    none
     */
    public function makePs($param, $param2)
    {
        $cmd = "ps aux | grep '$param' | awk '{print $param2}' | xargs";

        return $cmd;
    }

    /**
     * Proxy multi check
     *
     * PHP version 5
     *
     * LICENSE: none
     *
     * @param array $nodes param nodes is array
     *
     * @category  Utility
     * @return    none
     */
    public function checkProxy($nodes)
    {
        $mh = curl_multi_init();
        $curl_array = array();
        foreach ($nodes as $i => $url) {
            $curl_array[$i] = curl_init("http://tw.yahoo.com");
            curl_setopt($curl_array[$i], CURLOPT_RETURNTRANSFER, 1); // return don't print
            curl_setopt($curl_array[$i], CURLOPT_TIMEOUT, ProxyCheck::$chkProxyTime);
            curl_setopt($curl_array[$i], CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
            curl_setopt($curl_array[$i], CURLOPT_PROXY, $url);
            curl_multi_add_handle($mh, $curl_array[$i]);
        }

        // 防止CPU負荷過載
        $running = null;
        do {
            usleep(10000);
            curl_multi_exec($mh, $running);
        } while ($running > 0);

        do {
            $mrc = curl_multi_exec($mh, $active);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);

        while ($active and $mrc == CURLM_OK) {
            if (curl_multi_select($mh) != -1) {
                do {
                    $mrc = curl_multi_exec($mh, $active);
                } while ($mrc == CURLM_CALL_MULTI_PERFORM);
            }
        }

        $res = array();
        foreach ($nodes as $i => $url) {

            if (trim(curl_multi_getcontent($curl_array[$i])) == '') {
                $res[$url] = "off-line";
            } else {
                $res[$url] = "on-line";
            }
        }

        foreach ($nodes as $i => $url) {
            curl_multi_remove_handle($mh, $curl_array[$i]);
        }
        curl_multi_close($mh);
        return $res;
    }
}
