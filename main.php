<?php


if($argc != 2) {
    echo "usage php main.php <project id>\n";
    exit(0);
}

$id = $argv[1];

require_once "utility/DTO.php";
require_once "webcrawler/WebCrawler.php";
require_once "webcrawler/GitHubCrawler.php";
require_once "utility/SQLService.php";
require_once "utility/WebUtility.php";
require_once "repostat/RepoStat.php";
require_once "repostat/GitStat.php";

//WebUtility::useProxy(true);
//$proxy = WebUtility::getProxy();

SQLService::$ip = "127.0.0.1";
SQLService::$user = "root";
SQLService::$password = "openfoundry";

$SQL = new SQLService($id);
$url = $SQL->getProjectInfo("url");
if($url == null)
    return;
echo "$id : $url\n";

//TODO : crawler factory
$webCrawler = new GithubCrawler($url);

$issue = new Issue();
$webCrawler->getIssue($issue);
echo "Issue:\n";
print_r($issue);
$SQL->insertIssue($issue);

$wiki = new Wiki();
$wikiList = array();
$webCrawler->getWiki($wiki, $wikiList);
echo "Wiki:\n";
print_r($wiki);
print_r($wikiList);
$SQL->insertWiki($wiki);
$SQL->insertWikiPages($wikiList);

$rank = new Rating();
$webCrawler->getRating($rank);
echo "rating:\n";
print_r($rank);
$SQL->insertRating($rank);

$dlArray = array();
$webCrawler->getDownload($dlArray);
$SQL->insertDownload($dlArray);

//TODO stat factory
$repoStat = new GitStat($id);

$vcs = new VCS();
$repoStat->getSummary($vcs);
$SQL->insertVCS($vcs);

$cList = array();
$repoStat->getDataByCommiters($cList);
$SQL->insertVCSCommiters($cList);
