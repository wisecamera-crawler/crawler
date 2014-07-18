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
 * Connection class is a wrapper of curl actions,
 * it provides connection utility for crawlers
 *
 * There is a public functions for crawler developers:
 *  getHtmlContent($url)
 *      This function will return html content of assigned url.
 * 
 * User may asssign proxy on construction.
 *
 * LICENSE : none
 *
 * @author   Poyu Chen <poyu677@gmail.com>
 */
class Connection
{
    /**
     * The curl object
     */
    private $ch = null;

    /**
     * A string to record the proxy
     */
    private $proxy = null;
    
    /**
     * Constructor
     *
     * @param string $proxy Assign the proxy to rhis connection,
     *                      direct connection if the paramter is not passed
     */
    public function __construct($proxy = null)
    {
        $this->ch = curl_init();
        $user_agent = "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/535.19 " .
            "(KHTML, like Gecko) Chrome/18.0.1025.142 Safari/535.19";
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($this->ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($this->ch, CURLOPT_USERAGENT, $user_agent);

        //May check proxy?
        $this->proxy = $proxy;
        if ($proxy !== null) {
            curl_setopt($this->ch, CURLOPT_PROXY, $proxy);
        }
    }

    /**
     * getHtmlContent
     *
     * This function will return html content of assigned url
     *
     * @param string $url   The assigned url
     *
     * @return string $txt  The html content
     */
    public function getHtmlContent($url)
    {
        $txt = $this->connect($url);
        return $txt;
    }

    /**
     * connect
     *
     * A function really do connection.
     * Initailize the curl object and connect.
     * If connection error, it will update the errCode.
     *
     * @param string $url   The assigned url
     *
     * @return string $txt  The html content
    */
    private function connect($url)
    {
        curl_setopt($this->ch, CURLOPT_URL, $url);
        $txt = curl_exec($this->ch);
        if (curl_errno($this->ch)) {
            echo curl_error($this->ch);
            return null;
        }
        return $txt;
    }

}
