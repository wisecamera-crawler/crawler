<?php
namespace wisecamera;

abstract class WebCrawler
{
    abstract public function getIssue(Issue & $issue);
    abstract public function getWiki(Wiki & $wiki, array & $wikiPageList);
    abstract public function getRating(Rating & $rating);
    abstract public function getDownload(array & $download);
}
