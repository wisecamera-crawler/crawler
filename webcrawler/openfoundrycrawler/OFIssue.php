<?php
/**
 * OFIssue.php : The class responsible for  openfoundry issue analysis
 *
 * PHP version 5
 *
 * LICENSE : none
 *
 * @dependency  ../utility/WebUtility.php
 *              ../utility/ParseUtility.php
 * @author   Poyu Chen <poyu677@gmail.com>
 */
namespace wisecamera;

/**
 * OFIssue
 *
 * To analysis issue trackers of openfoundry
 *
 * @author   Poyu Chen <poyu677@gmail.com>
 */
class OFIssue
{
    /**
     * id : The id of project in openfoundry
     */
    private $id;

    /**
     * To cache the result in class.
     * If data is -1, means this target has not been analysis,
     * if not, we may directly return that number.
     * Because some targets may be discovered in same actions,
     * recording data at the same time may give better performance and better function interface
     */
    private $issueCount = -1;
    private $closeIssue = -1;
    private $articles = -1;
    private $users = -1;

    /**
     * Constructor
     *
     * @param string $id Id of openfoundry project
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * getTotalIssue
     *
     * Get total issue counts
     *
     * @return int Total counts of issue number
     */
    public function getTotalIssue()
    {
        if ($this->issueCount != -1) {
            return $this->issueCount;
        }

        $content = WebUtility::getHtmlContent(
            "https://www.openfoundry.org/rt/Search/Results.html?"
            . "Query=Queue%20=%20%27" . $this->id . "%27"
        );
        preg_match('/<title>.*<\/title>/', $content, $matches);
        $match = explode(" ", $matches[0]);

        $this->issueCount = ParseUtility::intStrToInt($match[1]);

        return $this->issueCount;
    }

    /**
     * getCloseIssue
     *
     * Get the close issues counts.
     *
     * @return int Counts of closed issues
     */
    public function getCloseIssue()
    {
        if ($this->closeIssue != -1) {
            return $this->closeIssue;
        }

        $issueCount = $this->getTotalIssue();
        $content = WebUtility::getHtmlContent(
            "https://www.openfoundry.org/rt/Search/Results.html?"
            . "Query=Queue%20=%20%27" . $this->id . "%27&Rows="
            . $issueCount
        );

        $this->closeIssue = preg_match_all(
            '/<td class="collection-as-table" align="center" '
            . 'style="padding: 7px 0px 5px 0px;">resolved<\/td>/',
            $content,
            $matches
        );

        return $this->closeIssue;
    }

    /**
     * getOpenIssue
     *
     * Get the open issues counts.
     *
     * @return int Counts of opened issues
     */
    public function getOpenIssue()
    {
        return $this->getTotalIssue() - $this->getCloseIssue();
    }

    /**
     * getTotalArticles
     *
     * Get total articles for assigned projects.
     * The data is collected through traverseIssues function
     *
     * @return int Articles counts
     */
    public function getTotalArticles()
    {
        if ($this->articles == -1) {
            $this->traverseIssues();
        }

        return $this->articles;
    }

    /**
     * getTotalAuthors
     *
     * Get total authors for assigned projects.
     * The data is collected through traverseIssues function
     *
     * @return int Authors counts
     */
    public function getTotalAuthors()
    {
        if ($this->users == -1) {
            $this->traverseIssues();
        }

        return $this->users;
    }

    /**
     * traverseIssues
     *
     * This function traverse all issues of the projects,
     * collect the data of articles and authors in each issue page,
     * and sum up the result.
     * The results are $this->articles and $this->users.
     *
     */
    private function traverseIssues()
    {
        $content = WebUtility::getHtmlContent(
            "https://www.openfoundry.org/rt/Search/Results.html?"
            . "Query=Queue%20=%20%27" . $this->id . "%27&Rows="
            . $this->getTotalIssue()
        );

        $issueIds = $this->resolveIssueIds($content);
        $authors = array();
        $articles = 0;
        foreach ($issueIds as $id) {
            $issuePage = WebUtility::getHTMLContent(
                "https://www.openfoundry.org/rt/Ticket/Display.html?id=$id"
            );
            $articles += $this->getArticleInOneIssue($issuePage);
            $this->getAuthorsInOneIssue($issuePage, $authors);
        }

        $this->articles = $articles;
        $this->users = sizeof($authors);
        if (isset($authors["RT_System"])) {
            $this->users -= 1;
        }
    }

    /**
     * resolveIssueIds
     *
     * Because in openfoundry, all issues share same issue tracker system,
     * we need to find out issue id for assigned project.
     * This function parse the html of issue list page (for assigned project),
     * and find out all issue id
     *
     * @param string $html The input HTML
     *
     * @return array Array of issue id
     */
    private function resolveIssueIds($html)
    {
        $result = array();
        preg_match_all(
            '/a href="\/rt\/Ticket\/Display.html\?id=[0-9]*"/',
            $html,
            $matches
        );

        for ($i = 0; $i < sizeof($matches[0]); $i += 2) {
            $temp = explode("=", $matches[0][$i]);
            $temp = substr($temp[2], 0, -1);
            $result []= (int) $temp;
        }

        return $result;
    }

    /**
     * getArticleInOneIssue
     *
     * This function count the article number in single issue page.
     *
     * @param string $html Html of the issue page
     *
     * @return int Number of articles
     */
    private function getArticleInOneIssue($html)
    {
        return preg_match_all("/ticket-transaction message/", $html, $matches);
    }

    /**
     * getAuthorsInOneIssue
     *
     * Get author numbers and author list in single issue pages
     *
     * @param string $html    Input HTML
     * @param array  $authors The array for return author list,
     *                        note that author name will be 'key' of array, and
     *                        won't erase existent data.
     *                        This is a trick to find out diffrent authors.
     *
     * @return int Author count
     */
    private function getAuthorsInOneIssue($html, &$authors)
    {
        if ($authors == null) {
            $suthors = array();
        }
        $localAuthors = array();

        $htmlArr = explode("\n", $html);
        for ($i = 0; $i < sizeof($htmlArr); ++$i) {
            $line = $htmlArr[$i];
            if ($line === '    <td class="description">') {
                $nextLine = $htmlArr[$i+1];
                $temp = explode(" ", $nextLine);
                $authors[$temp[6]] = 0;
                $localAuthors[$temp[6]] = 0;
            }
        }

        return sizeof($localAuthors);
    }
}
