<?php

require_once("githubcrawler/GitHubIssue.php");

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

    public function getWiki(Wiki & $wiki)
    {
    }

    public function getWikiPages(array & $wikiPageList)
    {
    }

    public function getRating(Rating & $rating)
    {
    }

    public function getDownload(Download & $download)
    {
    }
}

