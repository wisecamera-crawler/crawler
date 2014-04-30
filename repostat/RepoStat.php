<?php
namespace wisecamera;

abstract class RepoStat
{
    protected $projectId;
    abstract public function getSummary(VCS & $vcs);
    abstract public function getDataByCommiters(array & $commiters);
}
