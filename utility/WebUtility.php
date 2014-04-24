<?php

class WebUtility
{
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
				
        $txt = curl_exec($ch);
		curl_close($ch);

        return $txt;
    }

}

