<?php
/**
 * HGStat.php : Implementation of RepoStat of HG(Mercurial)
 *
 * PHP version 5
 *
 * LICENSE : none
 *
 * @dependency ../utility/DTO.php
 *             RepoStat.php
 * @author   Poyu Chen <poyu677@gmail.com>
 */
namespace wisecamera;

/**
 * HGStat
 *
 * Implemenation of RepoStat of HG
 *
 * @author   Poyu Chen <poyu677@gmail.com>
 */
class HGStat extends RepoStat
{
    /**
     * logArr, to store the third party utility log
     */
    private $tmpLogArr;

    /**
     * Constructor
     *
     * Constructor first check if this repo has been checled out or not.
     * If the repo has been checked out, than update the source,
     * else clone the repo to local.
     *
     * @param string $projectId Project id in our DB
     * @param string $url       Repo's clone url
     */
    public function __construct($projectId, $url)
    {
        $this->projectId = $projectId;
        if (is_dir("repo/$this->projectId")) {
            exec("cd repo; cd $this->projectId; hg pull");
        } else {
            exec("cd repo; hg clone $url $this->projectId", $arr);
        }
    }

    /**
     * getSummary
     *
     * We use third party utility (refer to third/HGPlus) to help analysis information.
     * In this function, we use HGPlus to gen report and parse out info we want
     *
     * @param VCS $vcs The VCS object to transfer info
     *
     * @return int Status code, 0 for OK
     */
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
        $vcs->size = (double) $sizeArr[0] / 1024;

        exec(
            "cd repo/$this->projectId;
             hg log | python ../../third/HGPlus/HGPlusMore.py",
            $this->tmpLogArr
        );
        $userArr = explode(" ", $this->tmpLogArr[1]);
        $commitArr = explode(" ", $this->tmpLogArr[0]);
        $vcs->user = (int) $userArr[0];
        $vcs->commit = (int) $commitArr[0];
    }

    /**
     * getDataByCommiters
     * The report of HGPlus is stored in tmpLogArr.
     * In this function, we parse the info we wnt.
     *
     * @param array $commiters List of VCSCommmiter objects
     *
     * @return int Status code, 0 for OK
     */
    public function getDataByCommiters(array & $commiters)
    {
        for ($i = 3; $i < sizeof($this->tmpLogArr); $i +=4) {
            $v = new VCSCommiter();
            $v->commiter = substr($this->tmpLogArr[$i], 0, -1);
            $arr = explode(" ", $this->tmpLogArr[$i+3]);
            $v->modify = (int) $arr[0];
            $arr = explode(" ", $this->tmpLogArr[$i+2]);
            $v->delete = (int) $arr[0];
            $arr = explode(" ", $this->tmpLogArr[$i+1]);
            $v->new =  (int) $arr[0];
            $commiters []= $v;
        }
    }
}
