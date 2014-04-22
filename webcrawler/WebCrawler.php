<?php

abstract class WebCrawler
{
    protected $baseUrl;
    
    abstract public function getIssue(Issue & $issue);
    abstract public function getWiki(Wiki & $wiki);
    abstract public function getWikiPages(array & $wikiPageList);
    abstract public function getRating(Rating & $rating);
    abstract public function getDownload(Download & $download);

    public function __construct($url){
        $this->baseUrl = $url;
    }
}

