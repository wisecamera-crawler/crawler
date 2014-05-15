<?php
namespace wisecamera;

class HGStat extends RepoStat
{
    private $tmpLogArr;

    public function __construct($projectId, $url)
    {
        $this->projectId = $projectId;
        if (is_dir("repo/$this->projectId")) {
            exec("cd repo; cd $this->projectId; hg pull");
        } else {
            exec("cd repo; hg clone $url $this->projectId", $arr);
        }
    }

    public function getSummary(VCS & $vcs)
    {
        exec(
            "cd repo/$this->projectId;
             hg status --all | python ../../third/HGPlus/HGPlus.py",
            $arr
        );
        $fileArr = explode(" ", $arr[3]);
        $lineArr = explode(" ", $arr[4]);
        $sizeArr = explode(" ", $arr[5]);

        $vcs->file = (int) $fileArr[0];
        $vcs->line = (int) $lineArr[0];
        $vcs->size = (double)$sizeArr[0] / 1024;

        exec(
            "cd repo/$this->projectId;
             hg log | python ../../third/HGPlus/HGPlusMore.py",
            $this->tmpLogArr
        );
        $userArr = explode(" ", $this->tmpLogArr[1]);
        $commitArr = explode(" ", $this->tmpLogArr[0]);
        $vcs->user = (int)$userArr[0];
        $vcs->commit = (int)$commitArr[0];
    }

    public function getDataByCommiters(array & $commiters)
    {
        for ($i = 3; $i < sizeof($this->tmpLogArr); $i +=4) {
            $v = new VCSCommiter();
            $v->commiter = substr($this->tmpLogArr[$i], 0, -1);
            $arr = explode(" ", $this->tmpLogArr[$i+3]);
            $v->modify = (int)$arr[0];
            $arr = explode(" ", $this->tmpLogArr[$i+2]);
            $v->delete = (int)$arr[0];
            $arr = explode(" ", $this->tmpLogArr[$i+1]);
            $v->new =  (int)$arr[0];
            $commiters []= $v;
        }
    }
}
