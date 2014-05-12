<?php
namespace wisecamera;

class OpenFoundryCrawler extends WebCrawler
{
    private $_ofId;
    private $baseUrl;

    public function __construct($url)
    {
        $arr = explode("/", $url);
        $this->_ofId =  $arr[5];
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
        $content = WebUtility::getHtmlContent($this->baseUrl . "/wiki/list");
        $htmlArr = explode("\n", $content);

        $pos = 290;
        while (
            isset($htmlArr[$pos]) and
            $htmlArr[$pos] !== "    <th>修改時間</th>" 
        ) {
            ++$pos;
        }

        $update = 0;
        $line = 0;
        while(
            isset($htmlArr[$pos]) and
            $htmlArr[$pos+2] !== "  </table>"
        ) {
            $pos += 4;
            
            $wikiPage = new WikiPage();
            $wikiPage->title = trim(strip_tags($htmlArr[$pos]));
            $arr = explode("\"", $htmlArr[$pos]);
            $wikiPage->url = "https://www.openfoundry.org" . $arr[1];
            $wikiPage->update = (int)trim(strip_tags($htmlArr[$pos+3]));
            $update += $wikiPage->update;
            $wikiPage->line = $this->_getWikiPageLine($wikiPage->url);
            $line += $wikiPage->line;

            $wikiPageList []= $wikiPage;
            
            $pos += 5;
            //break;
        }
        
        $wiki->pages = sizeof($wikiPageList);
        $wiki->update = $update;
        $wiki->line = $line;
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

    private function _getWikiPageLine($url)
    {
        $content = WebUtility::getHtmlContent($url);
        preg_match("/<hr\/>.*<hr\/>/s", $content, $matches);
        $htmlArr = explode("\n", $matches[0]);
        $lineCount = 0;
        foreach ($htmlArr as $line) {
            $line = trim(strip_tags($line));
            if(strlen($line) != 0) {
                ++$lineCount;
            }
        }
        return $lineCount;
    }

}
