<?php
/**
 * GithubCrawler.php : Implementation for Github of WebCralwer
 *
 * PHP version 5
 *
 * LICENSE : none
 *
 * @dependency ../utility/DTO.php
 *             WebCralwer.php
 *             githubcrawler/GithubIssue.php
 * @author   Poyu Chen <poyu677@gmail.com>
 */
namespace wisecamera\webcrawler;

use wisecamera\utility\DTOs\Download;
use wisecamera\utility\DTOs\Issue;
use wisecamera\utility\DTOs\Rating;
use wisecamera\utility\DTOs\Wiki;
use wisecamera\utility\DTOs\WikiPage;
use wisecamera\utility\Connection;
use wisecamera\utility\ParseUtility;
use wisecamera\webcrawler\githubcrawler\GitHubIssue;
use wisecamera\utility\WordCountHelper;

/**
 * GithubCralwer
 *
 * WebCralwer implementation for Github
 *
 * @author   Poyu Chen <poyu677@gmail.com>
 */
class GitHubAPICrawler extends WebCrawler
{
    /**
     * baseUrl  The main page url of the project
     */
    private $baseUrl;

    /**
     * Constructor
     *
     * Record the input url as baseUrl
     *
     * @param string $url   The URL
     * @param string $proxy Proxy to use
     */
    public function __construct($url, $proxy = null)
    {
        $this->conn = new Connection($proxy);
        $this->baseUrl = $url;
    }

    /**
     * getIssue
     *
     * Function of WebCrawler.
     * The work all done at GithubIssue (please refer to githubcrawler/GithubIssue.php)
     *
     * @param Issue $issue The issue DTO to fill info
     *
     * @return int Status code, 0 for OK
     */
    public function getIssue(Issue & $issue)
    {
        $gi = new GitHubIssue($this->baseUrl, $this->conn);
        $issue->open = $gi->getOpenIssue();
        $issue->close =  $gi->getCloseIssue();
        $issue->topic = $gi->getTotalIssue();
        $gi->traverseIssues($issue->article, $issue->account);
    }

    /**
     * getWiki
     *
     * Function of WebCrawler
     * This function first get all wiki page from <base url>/wiki/_pages,
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
        $url = $this->baseUrl . "/wiki/_pages";
        $txt = $this->conn->getHtmlContent($url);
        $tmp = explode("/", $this->baseUrl);
        $name = $tmp[3];
        $project = $tmp[4];
        preg_match_all(
            '/<td class="content">.*<\/td>/',
            $txt,
            $matches
        );

        $totalUpdate = 0;
        $totalLine = 0;
        $totalWord = 0;
        foreach ($matches[0] as $match) {
            $wikiPage = new WikiPage();

            $title = trim(strip_tags($match));
            $pageUrl = str_replace(" ", "-", $title);

            $update = $this->fetchGitHubWikiUpdate($pageUrl);
            $content = $this->getWikiContent($this->baseUrl . "/wiki/" . $pageUrl);
            $wikiPage->line = WordCountHelper::lineCount($content);
            $wikiPage->word = WordCountHelper::utf8WordsCount($content);

            $totalUpdate += $update;
            $totalLine += $wikiPage->line;
            $totalWord += $wikiPage->word;

            $wikiPage->url = $this->baseUrl . "/wiki/$pageUrl";
            $wikiPage->update = $update;
            $wikiPage->title = $title;
            $wikiPageList []= $wikiPage;
        }

        $wiki->pages = sizeof($matches[0]);
        $wiki->line = $totalLine;
        $wiki->update = $totalUpdate;
        $wiki->word = $totalWord;
    }

    /**
     * getRating
     *
     * Function of WebCrawler.
     * Because to get star info, we need to login.
     * First part is to login to Github, then get star, fork, and watch
     *
     * @param Rating $rating The rating DTO to fill info
     *
     * @return int Status code, 0 for OK
     */
    public function getRating(Rating & $rating)
    {
        $tmp = explode("/", $this->baseUrl);
        $owner = $tmp[3];
        $repo = $tmp[4];
        $URL = "https://api.github.com/repos";
        $username= "wisecamera777@gmail.com";
        $password="qazwsxedc123";

        $ch = curl_init();
        $user_agent = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/535.19 (KHTML, like Gecko) Chrome/18.0.1025.142 Safari/535.19';
        curl_setopt($ch, CURLOPT_URL, $URL."/$owner/$repo");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
        curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);


        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, true);
        curl_setopt($ch, CURLOPT_CAPATH, "/certificate");
        curl_setopt($ch, CURLOPT_CAINFO, "/certificate/server.crt");

        $txt = curl_exec($ch);
        curl_close($ch);

        //var_dump($txt);
        $decode = json_decode($txt, true);
        // echo "Fork:".$decode["network_count"]."<br>";
        // echo "Watch:".$decode["subscribers_count"]."<br>";
        // echo "Star:".$decode["stargazers_count"]."<br>";

        $rating->star = $decode["stargazers_count"];
        $rating->watch = $decode["subscribers_count"];
        $rating->fork = $decode["network_count"];


    }

    /**
     * getDownload
     *
     * Function of WebCrawler, Github has no such target.
     *
     * @param array $download List of Download data
     *
     * @return int Status code, 0 for OK
     */
    public function getDownload(array & $downloads)
    {
        $download = null;
    }

    /**
     * getRepoUrl
     *
     * Function of WebCrawler.
     * Github only have git, and its url is baseurl
     *
     * @param string $type The type of the VCS system (should noly be Git, SVN, HG, CVS)
     * @param string $url  The clone url
     *
     * @return int Status code, 0 for OK
     */
    public function getRepoUrl(&$type, &$url)
    {
        $type = "Git";
        $url =  $this->baseUrl;
    }

    /**
     * fetchGitHubWikiUpdate
     *
     * This function get update times of assigned wikipage
     *
     * @param string $wikiName The title of wiki page
     *
     * @return int Update times
     */
    private function fetchGitHubWikiUpdate($wikiName)
    {
        $url = $this->baseUrl . "/wiki/$wikiName/_history";
        $txt = $this->conn->getHtmlContent($url);
        preg_match_all('/<tr>/', $txt, $updateTimes);

        return sizeof($updateTimes[0])-1;
    }

    /**
     * getWikiContent
     *
     * The function to extract wiki's content from wiki page
     *
     * @param string $url Assigned page's url
     *
     * @return string Wiki's content
     */
    private function getWikiContent($url)
    {
        $content = $this->conn->getHtmlContent($url);
        $start = strpos($content, "<div class=\"markdown-body\">");
        $end = strpos($content, "<div class=\"modal-backdrop\"></div>");
        return substr($content, $start, $end - $start);
    }
}
