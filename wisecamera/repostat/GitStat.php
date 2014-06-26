<?php
/**
 * GitStat.php : Implementation of RepoStat of git
 *
 * PHP version 5
 *
 * LICENSE : none
 *
 * @dependency ../utility/DTO.php
 *             RepoStat.php
 * @author   Poyu Chen <poyu677@gmail.com>
 */
namespace wisecamera\repostat;

use wisecamera\utility\DTOs\VCS;
use wisecamera\utility\DTOs\VCSCommiter;

/**
 * GitStat
 *
 * Implemenation of RepoStat of git
 *
 * @author   Poyu Chen <poyu677@gmail.com>
 */
class GitStat extends RepoStat
{
    private $logArr;

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
            exec("cd repo; cd $this->projectId; git pull", $arr);
        } else {
            exec("cd repo; git clone $url $this->projectId", $arr);
        }

        exec("cd repo/$this->projectId; git log --stat --summary;", $this->logArr);
    }

    /**
     * getSummary
     *
     * We use gitstats (http://gitstats.sourceforge.net/) to help analysis information.
     * In this function, we use gitstats to gen report and parse out info we want
     *
     * @param VCS $vcs The VCS object to transfer info
     *
     * @return int Status code, 0 for OK
     */
    public function getSummary(VCS & $vcs)
    {
        $vcs->user = $this->getAuthor();
        $vcs->commit = $this->getCommit();
        $vcs->file = $this->getFileCount();
        $vcs->line = $this->getTotalLine();
        $vcs->size = $this->getSize();    
    }

    /**
     * getDataByCommiters
     *
     * This function dump git log into temp file,
     * then parse the information we want.
     *
     * @param array $commiters List of VCSCommmiter objects
     *
     * @return int Status code, 0 for OK
     */
    public function getDataByCommiters(array & $commiters)
    {
        define("CREATE", "create");
        define("DELETE", "delete");
        define("MODIFY", "modify");

        $fileContent = "";
        foreach ($this->logArr as $line) {
            $fileContent .= $line . "\n";
        }
        $numOfActions = 0;
        $numOfFileChg = 0;

        $tmp = explode('Author: ', $fileContent);

        for ($i = 1; $i < count($tmp); $i++) {
            $index = $i - 1;

            // Get Author
            $tmpAuthors = explode('<', $tmp[$i]);
            $authors[$index] = $tmpAuthors[0];

            // Get the number of file change
            $tmpFileChg = explode(' changed', $tmp[$i]);
            $tmpFileChg2 = explode(' file', $tmpFileChg[0]);

            $j = 0;

            $str = substr($tmpFileChg2[$j], -1, 1);
            $isInt = $this->isInt($str);

            while (!$isInt) {
                $j++;
                $str = substr($tmpFileChg2[$j], -1, 1);
                if ($str == '') {
                    $str = 0;
                    break;
                } else {
                    $isInt = $this->isInt($str);
                }
            }

            $aryNumOfFileChg[$index] = $str;

            /* Get file change actions */
            $tmp4 = explode(' mode', $tmp[$i]);
            $aryActions[$index]['create'] = 0;
            $aryActions[$index]['delete'] = 0;
            $aryActions[$index]['modify'] = 0;

            for ($k = 0; $k <= count($tmp4) - 1; $k++) {
                $action = substr($tmp4[$k], -6, 6);

                if ($aryNumOfFileChg[$index] !== 0) {
                    if ($action !== CREATE and $action !== DELETE) {
                        $action = 'modify';
                    }
                    $aryActions[$index][$action]++;
                }
            }
        }

        //group by authors
        $outArray = array();
        for ($i = 0; $i < count($authors); ++$i) {
            if (!isset($outArray[$authors[$i]])) {
                $outArray[$authors[$i]] = array();
                $outArray[$authors[$i]]['create'] = $aryActions[$i]['create'];
                $outArray[$authors[$i]]['modify'] = $aryActions[$i]['modify'];
                $outArray[$authors[$i]]['delete'] = $aryActions[$i]['delete'];
            } else {
                $outArray[$authors[$i]]['create'] += $aryActions[$i]['create'];
                $outArray[$authors[$i]]['modify'] += $aryActions[$i]['modify'];
                $outArray[$authors[$i]]['delete'] += $aryActions[$i]['delete'];
            }
        }

        //dump into vcsList
        foreach ($outArray as $name => $c) {
            $v = new VCSCommiter();
            $v->commiter = $name;
            $v->modify = $c["modify"];
            $v->delete = $c["delete"];
            $v->new = $c["create"];
            $commiters []= $v;
        }
    }

    private function getAuthor()
    {
        $authorArr = array();
        foreach ($this->logArr as $line) {
            if(substr($line, 0, 6) === "Author") {
                $authorArr[substr($line, 8)] = 1;
            }
        }
        return sizeof($authorArr);
    }

    private function getCommit()
    {
        $ans = 0;
        foreach ($this->logArr as $line) {
            if (substr($line, 0, 6) === "commit") {
                ++$ans;
            }
        }
        return $ans;
    }

    private function isInt($str)
    {
        $ascii = ord($str);

        if ($ascii > 57 or $ascii < 48) {
            return false;
        } else {
            return true;
        }
    }
}
