<?php
/**
 * WebCralwer.php : Interface of WebCralwer
 *
 * PHP version 5
 *
 * LICENSE : none
 *
 * @dependency ../utility/DTO.php
 * @author   Poyu Chen <poyu677@gmail.com>
 */
namespace wisecamera;

/**
 * WebCralwer Interface
 *
 * The interface for WebCraweler
 *
 * @author   Poyu Chen <poyu677@gmail.com>
 */
abstract class WebCrawler
{
    abstract public function getIssue(Issue & $issue);
    abstract public function getWiki(Wiki & $wiki, array & $wikiPageList);
    abstract public function getRating(Rating & $rating);
    abstract public function getDownload(array & $download);
    abstract public function getRepoUrl(&$type, &$url);
}
