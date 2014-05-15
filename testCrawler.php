<?php
namespace wisecamera;

if ($argc != 2) {
    echo "usage php main.php <url>\n";
    exit(0);
}

$url = $argv[1];

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

echo "Repo : " . $webCrawler->getRepoUrl("SVN") . "\n";

$issue = new Issue();
$webCrawler->getIssue($issue);
echo "Issue:\n";
print_r($issue);

$wiki = new Wiki();
$wikiList = array();
$webCrawler->getWiki($wiki, $wikiList);
echo "Wiki:\n";
print_r($wiki);
print_r($wikiList);

$rank = new Rating();
$webCrawler->getRating($rank);
echo "rating:\n";
print_r($rank);

$dlArray = array();
echo "Download:\n";
$webCrawler->getDownload($dlArray);
print_r($dlArray);
