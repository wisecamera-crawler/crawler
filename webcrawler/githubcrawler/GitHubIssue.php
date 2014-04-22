<?php

class GitHubIssue
{
    private $baseurl;
    private $mainPageHtml;

    public function __construct($url)
    {
        $this->baseurl = $url;
        $this->mainPageHtml = $this->getHtmlContent($url);
    }

    public function getTotalIssue()
    {
        return $this->getOpenIssue() + $this->getCloseIssue();
    }

    public function getOpenIssue()
    {
        preg_match('/([0-9],?)+ Open/', $this->mainPageHtml, $matches);
        $arr = explode(' ', $matches[0]);
        return $this->intStrToInt($arr[0]);
    }

    public function getCloseIssue(){
        preg_match('/([0-9],?)+ Closed/', $this->mainPageHtml, $matches);
        $arr = explode(' ', $matches[0]);
        return $this->intStrToInt($arr[0]);
    }

    private function intStrToInt($str){
        $value = 0;
        for($i = 0; $i < strlen($str); ++$i) {
            if($str[$i] >= '0' AND $str[$i] <= '9') {
                $value = $value*10 + ($str[$i] - '0');
            }
        }
        return $value;
    }

    public function traverseIssues(&$totalComments, &$totalAuthors){
        $issueCount = $this->getTotalIssue();
        $totalComments = 0;
        $authors = array();
        for($i = 1; $i <= $issueCount; ++$i) {
            //TODO : getHTMLContent need to be able for redirection
            //$html = $this->getHtmlContent($this->baseurl . "/" . $i);
            $html = file_get_contents($this->baseurl . "/" . $i);  
            $totalComments += $this->getCommentCountInSingleIssuePage($html);
            $this->getAuthorCountInSingleIssuePage($html, $authors);
        }
        $totalAuthors = sizeof($authors);
    }

    private function getCommentCountInSingleIssuePage($html){
        return preg_match_all('/class="timeline-comment-header-text"/', $html, $unsue);
    }

    private function getAuthorCountInSingleIssuePage($html, &$authorArr = null){
        if($authorArr == null)
            $authorArr = array();
        $localAuthorArr = array();

        $htmlArr = explode("\n", $html);
        $i = 0;
        foreach($htmlArr as $line) {
            if($line === '        <div class="timeline-comment-header-text">'){
                $author = trim(strip_tags($htmlArr[$i+2] . $htmlArr[$i+3]));
                $authorArr[$author] = 0;
                $localAuthorArr[$author] = 0;
            }
            ++$i;
        }
        return sizeof($localAuthorArr);
    }

    private function getHtmlContent($url)
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

