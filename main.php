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

require_once "third/SplClassLoader.php";
$loader = new \SplClassLoader();
$loader->register();

use wisecamera\utility\Config;
use wisecamera\utility\SQLService;
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
use wisecamera\repostat\GitStat;
use wisecamera\repostat\SVNStat;
use wisecamera\repostat\HGStat;
use wisecamera\repostat\RepoStat;
use wisecamera\repostat\CVSStat;

if ($argc != 2) {
    echo "usage php main.php <project id>\n";
    exit(0);
}


$id = $argv[1];

$config = new Config();
//first ensure at the working dicretory
//Remember to modify to your own path
chdir($config->getValue("crawlerPath"));

//Set up DB info first
SQLService::$ip = $config->getValue("host");
SQLService::$user = $config->getValue("user");
SQLService::$password = $config->getValue("password");
SQLService::$db = $config->getValue("dbname");

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
