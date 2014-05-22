<?php
namespace wisecamera;

if ($argc != 4) {
    echo "usage php testStat.php <type> <id> <url>\n";
    exit(0);
}

$repoType = $argv[1];
$repoUrl = $argv[3];
$id = $argv[2];

require_once "utility/DTO.php";
require_once "repostat/RepoStat.php";
require_once "repostat/GitStat.php";
require_once "repostat/SVNStat.php";
require_once "repostat/HGStat.php";
require_once "repostat/CVSStat.php";

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
