<?php

require_once("utility/WebUtility.php");
require_once("utility/ParseUtility.php");

class GitHubIssue
{
    private $baseurl;
    private $mainPageHtml;

    public function __construct($url)
    {
        $this->baseurl = $url;
        $this->mainPageHtml = WebUtility::getHtmlContent($url);
    }

    public function getTotalIssue()
    {
        return $this->getOpenIssue() + $this->getCloseIssue();
    }

    public function getOpenIssue()
    {
        preg_match('/([0-9],?)+ Open/', $this->mainPageHtml, $matches);
        $arr = explode(' ', $matches[0]);
        return ParseUtility::intStrToInt($arr[0]);
    }

    public function getCloseIssue(){
        preg_match('/([0-9],?)+ Closed/', $this->mainPageHtml, $matches);
        $arr = explode(' ', $matches[0]);
        return ParseUtility::intStrToInt($arr[0]);
    }

    public function traverseIssues(&$totalComments, &$totalAuthors){
        $issueCount = $this->getTotalIssue();
        $totalComments = 0;
        $authors = array();
        for($i = 1; $i <= $issueCount; ++$i) {
            //TODO : getHTMLContent need to be able for redirection
            //$html = WebUtility::getHtmlContent($this->baseurl . "/" . $i);
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

}

