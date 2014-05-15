<?php
namespace wisecamera;

class CVSStat extends RepoStat
{
    private $logArr;

    public function __construct($projectId, $url)
    {
        //TODO : gen cmd string
        $cmdString = "pserver:anonymous@mx4j.cvs.sourceforge.net:/cvsroot/mx4j";
        $module = "mx4j";
        exec("cd third/cvsPlus; ./perform.sh $cmdString $module", $this->logArr);
    }

    public function getSummary(VCS & $vcs)
    {
        $fileArr = explode(" ", $this->logArr[4]);
        $lineArr = explode(" ", $this->logArr[5]);
        $sizeArr = explode(" ", $this->logArr[6]);
        $userArr = explode(" ", $this->logArr[7]);
        $vcs->file = (int) $fileArr[0];
        $vcs->line = (int) $lineArr[0];
        $vcs->size = (double)$sizeArr[0] / 1024; 
        $vcs->user = (int)$userArr[0];
        
        //TODO
        $vcs->commit = 0;
    }

    public function getDataByCommiters(array & $commiters)
    {
        for ($i = 9; $i < sizeof($this->logArr) - 1; $i +=3) {
            $v = new VCSCommiter();
            $v->commiter = substr($this->logArr[$i], 0, -1);
            $arr = explode(" ", $this->logArr[$i+2]);
            $v->modify = (int)$arr[0];
            //TODO deleted files
            //$arr = explode(" ", $this->logArr[$i+2]);
            $v->delete = 0;
            $arr = explode(" ", $this->logArr[$i+1]);
            $v->new =  (int)$arr[0];
            $commiters []= $v;
        }
    }
}
