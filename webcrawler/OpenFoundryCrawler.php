<?php
/**
 * OpenFoundryCrawler.php : Implementation for OF of WebCralwer
 *
 * PHP version 5
 *
 * LICENSE : none
 *
 * @dependency ../utility/DTO.php
 *             WebCralwer.php
 *             openfoundrycrawler/OFIssue.php
 * @author   Poyu Chen <poyu677@gmail.com>
 */
namespace wisecamera;

/**
 * OpenFoundryCralwer
 *
 * WebCralwer implementation for Openfoundry
 *
 * @author   Poyu Chen <poyu677@gmail.com>
 */
class OpenFoundryCrawler extends WebCrawler
{
    /**
     * ofId     The id for that project in openfoundry
     * baseUrl  The main page url of the project
     * Take this project as example:
     *  baseUrl : https://www.openfoundry.org/of/projects/2492
     *  ofId    : 2492
     */
    private $ofId;
    private $baseUrl;

    /**
     * Constructor
     *
     * Record the input url as baseUrl, and parse the project id from URL
     * (this 'project id' refer to project's id on openfoundry)
     *
     * @param string $url The URL
     */
    public function __construct($url)
    {
        $arr = explode("/", $url);
        $this->ofId =  $arr[5];
        $this->baseUrl = $url;
    }

    /**
     * getIssue
     *
     * Function of WebCrawler.
     * The work all done at OFIssue (please refer to openfoundrycrawler/OFIssue.php)
     *
     * @param Issue $issue The issue DTO to fill info
     *
     * @return int Status code, 0 for OK
     */
    public function getIssue(Issue & $issue)
    {
        $ofIssue = new OFIssue($this->ofId);
        $issue->topic = $ofIssue->getTotalIssue();
        $issue->close = $ofIssue->getCloseIssue();
        $issue->open = $ofIssue->getOpenIssue();
        $issue->account = $ofIssue->getTotalAuthors();
        $issue->article = $ofIssue->getTotalArticles();
    }

    /**
     * getWiki
     *
     * Function of WebCrawler
     * This function first get all wiki page from <base url>/wiki/list,
     * then visit each page, record the page info and sum up.
     *
     * @param Wiki  $wiki         The wiki DTO to fill info
     * @param array $wikiPageList Input should be an empty array,
     *                            output should be list of WikiPage objects
     *
     * @return int Status code, 0 for OK
     */
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
        while (
            isset($htmlArr[$pos]) and
            $htmlArr[$pos+2] !== "  </table>"
        ) {
            $pos += 4;

            $wikiPage = new WikiPage();
            $wikiPage->title = trim(strip_tags($htmlArr[$pos]));
            $arr = explode("\"", $htmlArr[$pos]);
            $wikiPage->url = "https://www.openfoundry.org" . $arr[1];
            $wikiPage->update = (int) trim(strip_tags($htmlArr[$pos+3]));
            $update += $wikiPage->update;
            $wikiPage->line = $this->getWikiPageLine($wikiPage->url);
            $line += $wikiPage->line;

            $wikiPageList []= $wikiPage;

            $pos += 5;
            //break;
        }

        $wiki->pages = sizeof($wikiPageList);
        $wiki->update = $update;
        $wiki->line = $line;
    }

    /**
     * getRating
     *
     * Function of WebCrawler, openfoundry do not have rating system, pass
     *
     * @param Issue $issue The issue DTO to fill info
     *
     * @return int Status code, 0 for OK
     */
    public function getRating(Rating & $rating)
    {
    }

    /**
     * getDownload
     *
     * Function of WebCrawler
     * The download page of openfoundry project is <base url>/download
     *
     * @param array $download List of Download data
     *
     * @return int Status code, 0 for OK
     */
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

    /**
     * getRepoUrl
     *
     * Function of WebCrawler.
     * First get title of the project, and check project's VCS type from <base url>/vcs_access.
     * The repo's url is the combination of som rules
     *
     * @param string $type The type of the VCS system (should noly be Git, SVN, HG, CVS)
     * @param string $url  The clone url
     *
     * @return int Status code, 0 for OK
     */
    public function getRepoUrl(&$type, &$url)
    {
        $content = WebUtility::getHtmlContent($this->baseUrl . "/vcs_access");
        $htmlArr = explode("\n", $content);

        $title = "";
        for ($i = 0; $i < sizeof($htmlArr); ++$i) {
            if ($htmlArr[$i] === "      <title>版本控制系統: 存取方式 ") {
                $title = str_replace(" ", "", $htmlArr[$i+1]);
                $title = substr($title, 1);
            }

            if ($htmlArr[$i] === "      <h1>Git</h1>") {
                $type = "Git";
                $url = "http://www.openfoundry.org/git/$title.git";

                return;
            } elseif ($htmlArr[$i] === "      <h1>Subversion</h1>") {
                $type = "SVN";
                $url = "https://www.openfoundry.org/svn/$title";

                return;
            }
        }

        return $this->baseUrl;
    }

    /**
     * getWikiPageLine
     *
     * The function to get wiki page's line count
     *
     * @param string $url Assigned page's url
     *
     * @return int Line count of the page
     */
    private function getWikiPageLine($url)
    {
        $content = WebUtility::getHtmlContent($url);
        preg_match("/<hr\/>.*<hr\/>/s", $content, $matches);
        $htmlArr = explode("\n", $matches[0]);
        $lineCount = 0;
        foreach ($htmlArr as $line) {
            $line = trim(strip_tags($line));
            if (strlen($line) != 0) {
                ++$lineCount;
            }
        }

        return $lineCount;
    }
}
