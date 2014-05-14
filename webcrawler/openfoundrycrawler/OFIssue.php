<?php
namespace wisecamera;

//require_once "utility/ParseUtility.php";

class OFIssue
{
    private $id;
    private $issueCount = -1;
    private $closeIssue = -1;
    private $articles = -1;
    private $users = -1;

    public function __construct($id)
    {
        $this->id = $id;
    }

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

    public function getOpenIssue()
    {
        return $this->getTotalIssue() - $this->getCloseIssue();
    }

    public function getTotalArticles()
    {
        if ($this->articles == -1) {
            $this->traverseIssues();
        }
        return $this->articles;
    }

    public function getTotalAuthors()
    {
        if ($this->users == -1) {
            $this->traverseIssues();
        }
        return $this->users;
    }

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
            $result []= (int)$temp;
        }

        return $result;
    }

    private function getArticleInOneIssue($html)
    {
        return preg_match_all("/ticket-transaction message/", $html, $matches);
    }

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
