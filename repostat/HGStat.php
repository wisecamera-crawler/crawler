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
        $this->getSize();
    }

    /**
     * getSummary
     *
     * This function get the summary of the repo.
     * file, line, size use function inherit from RepoStat
     *
     * @param VCS $vcs The VCS object to transfer info
     *
     * @return int Status code, 0 for OK
     */
    public function getSummary(VCS & $vcs)
    {
        $vcs->file = $this->getFileCount();
        $vcs->line = $this->getTotalLine();
        $vcs->size = $this->getSize();

        exec("cd repo/$this->projectId; hg log", $log);
        $userArr = array();
        $commit = 0;
        foreach ($log as $line) {
            if (substr($line, 0, 4) != "user") {
                continue;
            }
            $user = substr($line, 12);
            $userArr[$user] = 0;
            ++$commit;
        }
        $vcs->user = sizeof($userArr);
        $vcs->commit = $commit;
    }

    /**
     * getDataByCommiters
     *
     * We get the data by parse the hg log and each version's status
     *
     * @param array $commiters List of VCSCommmiter objects
     *
     * @return int Status code, 0 for OK
     */
    public function getDataByCommiters(array & $commiters)
    {   
        exec("cd repo/$this->projectId; hg log", $log);
        
        $userArr = array();
        for ($i = 0; $i < sizeof($log); ++$i) {
            if(substr($log[$i], 0, 9) == "changeset") {
                $ver = substr($log[$i], -12);

                while (substr($log[$i], 0, 4) != "user") {
                    ++$i;
                }
                $user = substr($log[$i], 12);
                if (!isset($userArr[$user])) {
                    $userArr[$user] = array();
                    $userArr[$user]["M"] = 0;
                    $userArr[$user]["A"] = 0;
                    $userArr[$user]["R"] = 0;
                }

                exec(
                    "cd repo/$this->projectId; hg status --change $ver",
                    $change
                );
                foreach ($change as $c) {
                    $userArr[$user][$c[0]] += 1;
                }
            }
        }
    
        foreach ($userArr as $name => $c) {
            $v = new VCSCommiter();
            $v->commiter = $name;
            $v->modify = $c["M"];
            $v->delete = $c["R"];
            $v->new = $c["A"];
            $commiters []= $v;
        }
    }
}
