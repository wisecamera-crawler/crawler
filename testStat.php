<?php
namespace wisecamera;

if ($argc != 3) {
    echo "usage php main.php <id> <url>\n";
    exit(0);
}

$url = $argv[2];
$id = $argv[1];

require_once "utility/DTO.php";
require_once "repostat/RepoStat.php";
require_once "repostat/GitStat.php";
require_once "repostat/SVNStat.php";
require_once "repostat/HGStat.php";
$repoStat = new HGStat($id, $url);

$vcs = new VCS();
$repoStat->getSummary($vcs);
print_r($vcs);

$cList = array();
$repoStat->getDataByCommiters($cList);
print_r($cList);
