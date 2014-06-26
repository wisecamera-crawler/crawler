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
use wisecamera\utility\DTOs\VCS;
use wisecamera\utility\DTOs\VCSCommiter;
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
    echo "usage php testStat.php <url>\n";
    exit(0);
}

$url = $argv[1];

$webCrawler = WebCrawlerFactory::factory($url);
$webCrawler->getRepoUrl($repoType, $repoUrl);

echo "$repoType : "  . $repoUrl . "\n";
$id = "test";
exec("rm repo/test -rf");
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

$cList = array();
$repoStat->getDataByCommiters($cList);
print_r($cList);
