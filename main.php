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

//set up timzone info
date_default_timezone_set('Asia/Taipei');

require_once "third/SplClassLoader.php";
$loader = new \SplClassLoader('wisecamera');
$loader->register();

use wisecamera\utility\Config;
use wisecamera\utility\SQLService;
use wisecamera\utility\Conneciotn;
use wisecamera\utility\WebUtility;
use wisecamera\utility\ParseUtility;
use wisecamera\utility\Logger;
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
} catch (\Exception $e) {
    echo $e->getMessage() . "\n";
    exit();
}

//Set up DB info
SQLService::$ip = $config->getValue("host");
SQLService::$user = $config->getValue("user");
SQLService::$password = $config->getValue("password");
SQLService::$db = $config->getValue("dbname");

$SQL = new SQLService($id);
if ($SQL->proxy == "") {
    echo "No Available proxy, abort execution\n";
    return;
}

$url = $SQL->getProjectInfo("url");

if ($url == null) {
    echo "URL is null, the project id may be worng\n";
    return;
}

$webCrawler = WebCrawlerFactory::factory($url);

//create DTOs
$issue = new Issue();
$wiki = new Wiki();
$wikiList = array();
$rank = new Rating();
$dlArray = array();
$vcs = new VCS();
$cList = array();
$repoType = "";

$executionCounter = 3;
$logger =  new Logger("retry.log");
work();

//insert into DB
$SQL->insertVCSType($repoType);
$SQL->insertIssue($issue);
$SQL->insertWiki($wiki);
$SQL->insertWikiPages($wikiList);
$SQL->insertDownload($dlArray);
$SQL->insertRating($rank);
$SQL->insertVCS($vcs);
$SQL->insertVCSCommiters($cList);

/**
 * function work
 *
 * This function call crawlers to work
 */
function work()
{
    global $executionCounter;
    global $issue, $wiki, $wikiList, $rank, $dlArray, $vcs, $cList, $repoType;
    global $webCrawler, $id, $SQL, $url;

    -- $executionCounter;
    //exceed retry limits, abort
    if ($executionCounter == 0) {
        exit();
    }

    echo "Execution tries left $executionCounter times ...\n";

    try {
        $webCrawler->getIssue($issue);
        echo "Issue:\n";
        print_r($issue);

        $webCrawler->getWiki($wiki, $wikiList);
        echo "Wiki:\n";
        print_r($wiki);
        print_r($wikiList);

        $webCrawler->getRating($rank);
        echo "rating:\n";
        print_r($rank);

        $webCrawler->getDownload($dlArray);
        echo "download : \n";
        print_r($dlArray);

        $webCrawler->getRepoUrl($repoType, $repoUrl);

        echo "$repoType : "  . $repoUrl . "\n";

        if ($repoType == "Git") {
            $repoStat = new GitStat($id, $repoUrl);
        } elseif ($repoType == "SVN") {
            $repoStat = new SVNStat($id, $repoUrl);
        } elseif ($repoType == "HG") {
            $repoStat = new HGStat($id, $repoUrl);
        } elseif ($repoType == "CVS") {
            $repoStat = new CVSStat($id, $repoUrl);
        }

        $repoStat->getSummary($vcs);
        print_r($vcs);
        $repoStat->getDataByCommiters($cList);
        print_r($cList);
    } catch (\Exception $e) {
        //When exception occurs, ther may be 2 situtations
        //  1. Proxy error
        //  2. Cralwer is blocked/target web site is down
        //So we have to test proxy's connectivity
        if (WebUtility::testConnection($SQL->proxy) === true) {
            $SQL->updateState("issue", "cannot_get_data");
            $SQL->updateState("wiki", "cannot_get_data");
            $SQL->updateState("download", "cannot_get_data");
            $SQL->updateState("vcs", "cannot_get_data");
        } else {
            $SQL->updateState("issue", "proxy_error");
            $SQL->updateState("wiki", "proxy_error");
            $SQL->updateState("download", "proxy_error");
            $SQL->updateState("vcs", "proxy_error");
        }

        echo $e->getMessage();
        retry();
    }

    if ($SQL->checkIssue($issue) === false or
        $SQL->checkWiki($wiki) === false or
        $SQL->checkVCS($vcs) === false or
        $SQL->checkDownload($dlArray) === false
    ) {
        retry();
   } 
}

function retry()
{
    global $logger, $id, $url, $webCrawler;
    $webCrawler = WebCrawlerFactory::factory($url);
    $logger->append("$id : $url\n");
    work();
}
