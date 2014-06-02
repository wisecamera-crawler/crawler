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

require_once "config.php";

if ($argc != 2) {
    echo "usage php main.php <project id>\n";
    exit(0);
}

$id = $argv[1];

//first ensure at the working dicretory
//Remember to modify to your own path
chdir($configPath);

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
require_once "repostat/SVNStat.php";
require_once "repostat/HGStat.php";
require_once "repostat/CVSStat.php";
require_once "webcrawler/SourceForgeCrawler.php";

//Set up DB info first
SQLService::$ip = $configDBIp;
SQLService::$user = $configDBUser;
SQLService::$password = $configDBPassword;
SQLService::$db = $configDBName;

//use proxy
WebUtility::useProxy(true);
$proxy = WebUtility::getProxy();

$SQL = new SQLService($id, $proxy);
if ($proxy == "") {
    echo "No Proxy\n";
    return;
}

$url = $SQL->getProjectInfo("url");
if ($url == null) {
    echo "URL is null\n";
    return;
}

//analysis web pages
$webCrawler = WebCrawlerFactory::factory($url);

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

//analysis repos
$webCrawler->getRepoUrl($repoType, $repoUrl);

echo "$repoType : "  . $repoUrl . "\n";
$SQL->insertVCSType($repoType);

if ($repoType == "Git") {
    $repoStat = new GitStat($id, $repoUrl);
} elseif ($repoType == "SVN") {
    $repoStat = new SVNStat($id, $repoUrl);
} elseif ($repoType == "HG") {
    $repoStat = new HGStat($id, $repoUrl);
} elseif ($repoType == "CVS") {
    $repoStat = new CVSStat($id, $repoUrl);
}

$vcs = new VCS();
$repoStat->getSummary($vcs);
print_r($vcs);
$SQL->insertVCS($vcs);

$cList = array();
$repoStat->getDataByCommiters($cList);
print_r($cList);
$SQL->insertVCSCommiters($cList);
