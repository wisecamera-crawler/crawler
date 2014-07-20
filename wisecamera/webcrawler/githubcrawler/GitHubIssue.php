<?php
/**
 * GithubIssue.php : The class responsible for github issue analysis
 *
 * PHP version 5
 *
 * LICENSE : none
 *
 * @author   Poyu Chen <poyu677@gmail.com>
 */
namespace wisecamera\webcrawler\githubcrawler;

use wisecamera\utility\Connection;
use wisecamera\utility\ParseUtility;

/**
 * GitHubIssue
 *
 * To analysis issue tracker of assigned Github projects
 *
 * @author   Poyu Chen <poyu677@gmail.com>
 */
class GitHubIssue
{
    /**
     * baseurl      The base url for assigned issue page
     * mainPageHtml The html content of basurl.
     *
     * The baseurl of Github issue should be :
     *  https://github.com/<owner>/<project>/issues
     */
    private $baseurl;
    private $mainPageHtml;

    /**
     * Connection object
     */
    private $conn;

    /**
     * Constructor
     *
     * Input is main page's url of issue tracker, get the html content
     *
     * @param string $url       The base url of assigned issue tracker
     * @param Connection $conn  The Connection object to use
     */
    public function __construct($url, $conn)
    {
        $this->conn = $conn;
        $this->baseurl = $url;
        $this->mainPageHtml = $this->conn->getHtmlContent($url);
    }

    /**
     * getTotalIssue
     *
     * Get total issue counts
     *
     * @return int  Total counts of issue number
     */
    public function getTotalIssue()
    {
        return $this->getOpenIssue() + $this->getCloseIssue();
    }

    /**
     * getOpenIssue
     *
     * Get the open issues counts.
     *
     * @return int  Counts of opened issues
     */
    public function getOpenIssue()
    {
        preg_match('/([0-9],?)+ Open/', $this->mainPageHtml, $matches);
        $arr = explode(' ', $matches[0]);

        return ParseUtility::intStrToInt($arr[0]);
    }

    /**
     * getCloseIssue
     *
     * Get the close issues counts.
     *
     * @return int  Counts of closed issues
     */
    public function getCloseIssue()
    {
        preg_match('/([0-9],?)+ Closed/', $this->mainPageHtml, $matches);
        $arr = explode(' ', $matches[0]);

        return ParseUtility::intStrToInt($arr[0]);
    }

    /**
     * traverseIssues
     *
     * This function traverse all issues of the issue tracker,
     * collect the data of articles and authors in each issue page, 
     * and sum up the result.
     *
     * @param int $totalComments    Pass by reference, used to return the comment counts
     * @param int $totalAuthors     Used to return the authors counts
     */
    public function traverseIssues(&$totalComments, &$totalAuthors)
    {
        $issueCount = $this->getTotalIssue();
        $totalComments = 0;
        $authors = array();
        for ($i = 1; $i <= $issueCount; ++$i) {
            $html = $this->conn->getHtmlContent($this->baseurl . "/" . $i);
            $totalComments += $this->getCommentCountInSingleIssuePage($html);
            $this->getAuthorCountInSingleIssuePage($html, $authors);
        }
        $totalAuthors = sizeof($authors);
    }

    /**
     * getCommentCountInSingleIssuePage
     *
     * This function count the article number in single issue page.
     *
     * @param string $html  Html of the issue page
     *
     * @return int  Number of articles
     */
    private function getCommentCountInSingleIssuePage($html)
    {
        return preg_match_all('/class="timeline-comment-header-text"/', $html, $unsue);
    }

    /**
     * getAuthorCountInSingleIssuePage
     *
     * Get author numbers and author list in single  issue pages
     *
     * @param string $html       Input HTML
     * @param array $authorArr  The array for return author list,
     *                          note that author name will be 'key' of array, and
     *                          won't erase existent data.
     *                          This is a trick to find out diffrent authors.
     *                          
     * @return int  Author count
     */
    private function getAuthorCountInSingleIssuePage($html, &$authorArr = null)
    {
        if ($authorArr == null) {
            $authorArr = array();
        }
        $localAuthorArr = array();

        $htmlArr = explode("\n", $html);
        $i = 0;
        foreach ($htmlArr as $line) {
            if ($line === '        <div class="timeline-comment-header-text">') {
                $author = trim(strip_tags($htmlArr[$i+2] . $htmlArr[$i+3]));
                $authorArr[$author] = 0;
                $localAuthorArr[$author] = 0;
            }
            ++$i;
        }

        return sizeof($localAuthorArr);
    }
}
