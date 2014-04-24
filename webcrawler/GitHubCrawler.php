<?php

require_once("githubcrawler/GitHubIssue.php");
require_once("utility/ParseUtility.php");
require_once("utility/WebUtility.php");

class GithubCrawler extends WebCrawler
{
    public function getIssue(Issue & $issue)
    {
        $gi = new GitHubIssue($this->baseUrl . "/issues");
        $issue->open = $gi->getOpenIssue();
        $issue->close =  $gi->getCloseIssue();
        $issue->topic = $gi->getTotalIssue();
        $gi->traverseIssues($issue->article, $issue->account);
    }

    public function getWiki(Wiki & $wiki, array & $wikiPageList)
    {
        $url = $this->baseUrl . "/wiki/_pages";
        $txt = WebUtility::getHtmlContent($url);
        $tmp = explode("/", $this->baseUrl);
        $name = $tmp[3];
        $project = $tmp[4];
        preg_match_all('#<strong><a href="/'. $name. '/' . $project .'/wiki/.[^>]*>#i', 
            $txt, $authRandnumPic);

        $totalUpdate = 0;
        $totalLine = 0;
        
        foreach($authRandnumPic[0] as $value) {
            $wikiPage = new WikiPage();
            
            $_array = explode("/", $value);
            $wiki_page_name_array = explode("\"", $_array[4]);
            $wikiPageName = $wiki_page_name_array[0];
            
            $update = $this->fetchGitHubWikiUpdate($wikiPageName);
            $line = $this->fetchGitHubWikiLineCount($wikiPageName);

            $totalUpdate += $update;
            $totalLine += $line;

            $wikiPage->url = $this->baseUrl . "/wiki/$wikiPageName";
            $wikiPage->line = $line;
            $wikiPage->update = $update;
            $wikiPage->title = $wikiPageName;
            $wikiPageList []= $wikiPage;
        }

        $wiki->pages = sizeof($authRandnumPic[0]);
        $wiki->line = $totalLine;
        $wiki->update = $totalUpdate;
    }
    

    public function getRating(Rating & $rating)
    {
        $tmp = explode("/", $this->baseUrl);
        $name = $tmp[3];
        $project = $tmp[4];


        $url = "https://github.com/login";
        $cookie_file = tempnam('./temp','cookie');
        $ch = curl_init();
        $user_agent = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/535.19 (KHTML, like Gecko) Chrome/18.0.1025.142 Safari/535.19';
        curl_setopt($ch, CURLOPT_URL, $url );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT,         30);
        curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file); //¦scookie

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, True);
        curl_setopt($ch, CURLOPT_CAPATH, "/certificate");
        curl_setopt($ch, CURLOPT_CAINFO, "/certificate/server.crt");

        $txt = curl_exec($ch);
        curl_close($ch);

        preg_match_all('#<input name="authenticity_token" type="hidden" value=.[^>]*>#i', $txt, $authenticity_token);
                       
        $authenticity_token_array=explode("\"",$authenticity_token[0][0]);                      
        $authvalue = $authenticity_token_array[5];
        $url = "https://github.com/session";

        $ch = curl_init();
        $user_agent = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/535.19 (KHTML, like Gecko) Chrome/18.0.1025.142 Safari/535.19';
        curl_setopt($ch, CURLOPT_URL, $url );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT,         30);
        curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file); //¦scookie
                                            
        $_POSTDATA["authenticity_token"]=$authvalue;
        $_POSTDATA["login"]="wisecamera777@gmail.com";
        $_POSTDATA["password"]="qazwsxedc123";
        $_POSTDATA["commit"]="Sign in";
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($_POSTDATA));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, True);
        curl_setopt($ch, CURLOPT_CAPATH, "/certificate");
        curl_setopt($ch, CURLOPT_CAINFO, "/certificate/server.crt");
        $txt = curl_exec($ch);
        curl_close($ch);
                        
        $url = "https://github.com/$name/$project/";
        $ch = curl_init();
        $user_agent = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/535.19 (KHTML, like Gecko) Chrome/18.0.1025.142 Safari/535.19';
        curl_setopt($ch, CURLOPT_URL, $url );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT,         30);
        curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file); //¦scookie
                        
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, True);
        curl_setopt($ch, CURLOPT_CAPATH, "/certificate");
        curl_setopt($ch, CURLOPT_CAINFO, "/certificate/server.crt");

        $txt = curl_exec($ch);
        curl_close($ch);
        
        $txt=str_replace(array("\r","\n","\t","\s"), ' ', $txt);
        preg_match('/<a[^>]*href="\/'.$name.'\/'.$project.'\/stargazers"[^>]*>(.*?) <\/a>/si', $txt, $stargazers);          
        preg_match('/<a[^>]*href="\/'.$name.'\/'.$project.'\/watchers"[^>]*>(.*?) <\/a>/si', $txt, $watchers); 
        preg_match('#<a href="/'.$name.'\/'.$project.'/network" class="social-count">.[^>]*>#i', $txt, $network);  
                        
        $stargazers_array=explode(" ",$stargazers[0]);
        $watchers_array=explode(" ",$watchers[0]);
        $tmp=explode(">",$network[0]);
                                   
        $network_array=explode("<",$tmp[1]);
                        
       
        $rating->star = ParseUtility::intStrToInt($stargazers_array[12]);
        $rating->watch = ParseUtility::intStrToInt($watchers_array[12]);
        $rating->fork = ParseUtility::intStrToInt($network_array[0]);
    }

    public function getDownload(array & $downloads)
    {
        $download = null;
    }

    private function fetchGitHubWikiUpdate($wikiName)
    {
        $url = $this->baseUrl . "/wiki/$wikiName/_history";	
        $txt = WebUtility::getHtmlContent($url);
        preg_match_all('/<td[^>]*class="commit-name"[^>]*>(.*?) <\/td>/si', $txt, $updateTimes); 	
        return sizeof($updateTimes[0]);
    }

    private function fetchGitHubWikiLineCount($wikiName){
        $commad = "python third/getWikiLine.py $this->baseUrl/wiki/$wikiName";	
        $output = shell_exec($commad);				
        return $output;
    }
}

