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

try {
    $config = new Config();
} catch (\Exception $e){
    echo $e->getMessage() . "\n";
    exit();
}

//Set up DB info
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

if (WebUtility::getErrCode() == 0) {
    $issue = new Issue();
    $webCrawler->getIssue($issue);
    
    if (WebUtility::getErrCode() != 0) {
        $SQL->updateState("issue", "proxy_error");
        $SQL->updateState("wiki", "proxy_error");
        $SQL->updateState("download", "proxy_error");
        $SQL->updateState("vcs", "proxy_error");
        echo "Connection seems had error when getting issue.\n";
    } else {
        echo "Issue:\n";
        print_r($issue);
        $SQL->insertIssue($issue);
    }
}

if (WebUtility::getErrCode() == 0) {
    $wiki = new Wiki();
    $wikiList = array();
    $webCrawler->getWiki($wiki, $wikiList);
    
    if (WebUtility::getErrCode() != 0) {
        $SQL->updateState("wiki", "proxy_error");
        $SQL->updateState("download", "proxy_error");
        $SQL->updateState("vcs", "proxy_error");
        echo "Connection seems had error when getting wiki.\n";
    } else {
        echo "Wiki:\n";
        print_r($wiki);
        print_r($wikiList);
        $SQL->insertWiki($wiki);
        $SQL->insertWikiPages($wikiList);
    }
}

if (WebUtility::getErrCode() == 0) {
    $rank = new Rating();
    $webCrawler->getRating($rank);
    
    echo "rating:\n";
    print_r($rank);
    $SQL->insertRating($rank);
}

if (WebUtility::getErrCode() == 0) {
    $dlArray = array();
    $webCrawler->getDownload($dlArray);
    
    if (WebUtility::getErrCode() != 0) {
        $SQL->updateState("download", "proxy_error");
        $SQL->updateState("vcs", "proxy_error");
        echo "Connection seems had error when getting download.\n";
    } else {
        echo "download : \n";
        print_r($dlArray);
        $SQL->insertDownload($dlArray);
    }
}

//analysis repos
if (WebUtility::getErrCode() == 0) {
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
}
