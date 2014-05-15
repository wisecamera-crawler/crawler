<?php
namespace wisecamera;

if ($argc < 2 or $argc > 3) {
    echo "usage php main.php <url> [option]\n";
    exit(0);
}

$url = $argv[1];
if ($argc == 3) {
    $qString = strtoupper($argv[2]);
} else {
    $qString = "IWRD";
}
require_once "utility/DTO.php";
require_once "webcrawler/WebCrawler.php";
require_once "webcrawler/githubcrawler/GitHubIssue.php";
require_once "webcrawler/openfoundrycrawler/OFIssue.php";
require_once "webcrawler/GitHubCrawler.php";
require_once "webcrawler/OpenFoundryCrawler.php";
require_once "webcrawler/WebCrawlerFactory.php";
require_once "webcrawler/GoogleCodeCrawler.php";
require_once "utility/SQLService.php";
require_once "utility/WebUtility.php";
require_once "utility/ParseUtility.php";
require_once "repostat/RepoStat.php";
require_once "repostat/GitStat.php";
require_once "webcrawler/SourceForgeCrawler.php";

$webCrawler = WebCrawlerFactory::factory($url);

if (strpos($qString, "I") !== false) {
    $issue = new Issue();
    $webCrawler->getIssue($issue);
    echo "Issue:\n";
    print_r($issue);
}
if (strpos($qString, "W") !== false) {
    $wiki = new Wiki();
    $wikiList = array();
    $webCrawler->getWiki($wiki, $wikiList);
    echo "Wiki:\n";
    print_r($wiki);
    print_r($wikiList);
}
if (strpos($qString, "R") !== false) {
    $rank = new Rating();
    $webCrawler->getRating($rank);
    echo "rating:\n";
    print_r($rank);
}
if (strpos($qString, "D") !== false) {
    $dlArray = array();
    echo "Download:\n";
    $webCrawler->getDownload($dlArray);
    print_r($dlArray);
}
