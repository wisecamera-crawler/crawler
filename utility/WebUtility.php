<?php

class WebUtility
{
    static private $isUseProxy = false;
    static private $proxy = "";

    static public function getHtmlContent($url)
    {
        $ch = curl_init();
        $user_agent = "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/535.19 (KHTML, like Gecko) Chrome/18.0.1025.142 Safari/535.19";
        curl_setopt($ch, CURLOPT_URL, $url);				
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);	
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);			
        curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);		
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, True);
        curl_setopt($ch, CURLOPT_CAPATH, "/certificate");
        curl_setopt($ch, CURLOPT_CAINFO, "/certificate/server.crt");
        if(WebUtility::$isUseProxy == true)
            curl_setopt($ch, CURLOPT_PROXY, WebUtility::$proxy);
        $txt = curl_exec($ch);
		curl_close($ch);

        return $txt;
    }

    static public function useProxy($bool)
    {
        WebUtility::$isUseProxy = $bool;
        
        if($bool == true)
            WebUtility::$proxy = WebUtility::chooseProxy();
        else
            WebUtility::$proxy = "";
    }

    static public function getProxy()
    {
        return WebUtility::$proxy;
    }

    static private function chooseProxy()
    {
        //TODO : how to choose?
        return "socks5://60.245.28.93:2001";
    }
}

