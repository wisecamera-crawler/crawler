<?php

abstract class WebCrawler
{
    private $url;

    abstract public function getIssue(Issue & $issue);
    abstract public function getWiki(Wiki & $wiki);
    abstract public function getWikiPages(array & $wikiPageList);
    abstract public function getRating(Rating & $rating);
    abstract public function getDownload(Download & $download);
}

