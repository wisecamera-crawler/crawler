<?php
namespace wisecamera;

class OpenFoundryCrawler extends WebCrawler
{
    private $_ofId;

    public function __construct($url)
    {
        $arr = explode("/", $url);
        $this->_ofId =  $arr[5];
        echo $this->_ofId;
        $this->baseUrl = $url;
    }

    public function getIssue(Issue & $issue)
    {
        $ofIssue = new OFIssue($this->_ofId);
        $issue->topic = $ofIssue->getTotalIssue();
        $issue->close = $ofIssue->getCloseIssue();
        $issue->open = $ofIssue->getOpenIssue();
        $issue->account = $ofIssue->getTotalAuthors();
        $issue->article = $ofIssue->getTotalArticles();
    }
    
    public function getWiki(Wiki & $wiki, array & $wikiPageList)
    {
    }

    public function getRating(Rating & $rating)
    {
    }

    public function getDownload(array & $download)
    {
        $content = WebUtility::getHtmlContent($this->baseUrl . "/download");
        $htmlArr = explode("\n", $content);

        for ($i = 0; $i < sizeof($htmlArr); ++$i) {
            if ($htmlArr[$i] === "            顯示/隱藏詳細資訊") {
                $downloadUnit = new Download();

                $i += 3;
                $downloadUnit->name = trim(strip_tags($htmlArr[$i]));
                $arr = explode("\"", $htmlArr[$i]);
                $downloadUnit->url = $arr[1];

                $i += 2;
                $pos = strpos($htmlArr[$i], "下載次數");
                $downloadUnit->count = (int) substr($htmlArr[$i], $pos+12, -5);

                $download []= $downloadUnit;
            }
        }

    }
}
