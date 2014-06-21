<?php
/**
 * main.php : the entry of cralwers
 *
 * usage php main.php <id>
 * id : Project id in database
 *
 * PHP version 5
 *
 * LICENSE : none
 *
 * @author   Poyu Chen <poyu677@gmail.com>
 */
namespace wisecamera;

//first, change to working drectory
chdir(__DIR__);

require_once "third/SplClassLoader.php";
$loader = new \SplClassLoader();
$loader->register();

use wisecamera\utility\WebUtility;
use wisecamera\utility\ParseUtility;
use wisecamera\utility\DTOs\Download;
use wisecamera\utility\DTOs\Issue;
use wisecamera\utility\DTOs\Rating;
use wisecamera\utility\DTOs\VCS;
use wisecamera\utility\DTOs\VCSCommiter;
use wisecamera\utility\DTOs\Wiki;
use wisecamera\utility\DTOs\WikiPage;
use wisecamera\webcrawler\WebCrawler;
use wisecamera\webcrawler\GitHubCrawler;
use wisecamera\webcrawler\OpenFoundryCrawler;
use wisecamera\webcrawler\WebCrawlerFactory;
use wisecamera\webcrawler\GoogleCodeCrawler;
use wisecamera\webcrawler\SourceForgeCrawler;
use wisecamera\webcrawler\githubcrawler\GitHubIssue;
use wisecamera\webcrawler\openfoundrycrawler\OFIssue;

if ($argc != 2) {
    echo "usage php main.php <url>\n";
    exit(0);
}

$webCrawler = WebCrawlerFactory::factory($argv[1]);

if (WebUtility::getErrCode() == 0) {
    $issue = new Issue();
    $webCrawler->getIssue($issue);
    
    echo "Issue:\n";
    print_r($issue);
}

if (WebUtility::getErrCode() == 0) {
    $wiki = new Wiki();
    $wikiList = array();
    $webCrawler->getWiki($wiki, $wikiList);
    
    echo "Wiki:\n";
    print_r($wiki);
    print_r($wikiList);
}

if (WebUtility::getErrCode() == 0) {
    $rank = new Rating();
    $webCrawler->getRating($rank);
    
    echo "rating:\n";
    print_r($rank);
}

if (WebUtility::getErrCode() == 0) {
    $dlArray = array();
    $webCrawler->getDownload($dlArray);
    
    echo "download : \n";
    print_r($dlArray);
}
