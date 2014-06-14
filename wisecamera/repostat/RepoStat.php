<?php
/**
 * RepoStat.php : Interface of RepoStat
 *
 * PHP version 5
 *
 * LICENSE : none
 *
 * @dependency ../utility/DTO.php
 * @author   Poyu Chen <poyu677@gmail.com>
 */
namespace wisecamera\repostat;

use wisecamera\utility\DTOs\VCS;
use wisecamera\utility\DTOs\VCSCommiter;

/**
 * RepoStat
 *
 * The interface of RepoStat
 * To implement new VCS type for this project, 
 * you have to implement this interface.
 * Currently, I have not implement factory for it, you may deal with instatnce generation yourself.
 * May refer to ../main.php
 *
 * @author   Poyu Chen <poyu677@gmail.com>
 */
abstract class RepoStat
{
    /**
     * Project's id in our DB
     */
    protected $projectId;

    /**
     * Constructor
     *
     * @param string $projectId Project id in our DB
     * @param string $url       Repo's clone url
     */
    abstract public function __construct($projectId, $url);

    /**
     * getSummary
     *
     * This function return the summary of the repo.
     * Developers who want to implement new inheritent class need 
     * to fill information to the VCS object in this function.
     * Detail of VCS object please refer to ../utility/DTO/VCS.php
     *
     * @param VCS $vcs  The VCS object to transfer info
     *
     * @return int  Status code, 0 for OK
     */
    abstract public function getSummary(VCS & $vcs);

    /**
     * getDataByCommiters
     *
     * This function return the list of commiter's contribution of this repo.
     * Developers who want to implement new inheritent class need to
     * collect each commiter's contribution and store in VCSCommiter object, and
     * make the list in this function.
     *
     * @param array $commiters  List of VCSCommmiter objects
     *
     * @return int  Status code, 0 for OK
     */
    abstract public function getDataByCommiters(array & $commiters);

    /**
     * getSize
     *
     * Default function of RepoStat for get project's size
     * Use 'du' to get file size
     *
     * @return int  Size of the repo
     */
    protected function getSize()
    {
        exec("du repo/$this->projectId", $output);
        
        $size = 0;
        foreach ($output as $line) {
            $explodeLine = explode("\t", $line);
            
            if ($explodeLine[1] == "repo/$this->projectId") {
                $size += (int)$explodeLine[0];
            } elseif (
                $explodeLine[1] == "repo/$this->projectId/.git" or
                $explodeLine[1] == "repo/$this->projectId/.svn" or
                $explodeLine[1] == "repo/$this->projectId/.hg"
            ) {
                $size -= (int)$explodeLine[0];
            }
        }

        return $size;
    }

    /**
     * getFileCount
     *
     * Default function of RepoStat for get project's files
     * Use 'find' to get filr list, and count the length of file list
     *
     * @return int  File count of the repo
     */
    protected function getFileCount()
    {
        exec(
            "find ./repo/$this->projectId -name \"*\" " .
            "-path \"*.hg\" -prune -o -name \"*\" " .
            "-path \"*.svn\" -prune -o -name \"*\" " .
            "-path \"*.git\" -prune -o -name \"*\"",
            $fileList
        );
        return sizeof($fileList) - 1;
    }

    /**
     * getTotalLine
     *
     * Default function of RepoStat for get project's total lines
     * Use 'find' to get filr list, and count the length of file list
     * And then use wc to get each file's line count
     *
     * @return int  File count of the repo
     */
    protected function getTotalLine()
    {
        exec(
            "find ./repo/$this->projectId -name \"*\" " .
            "-path \"*.hg\" -prune -o -name \"*\" " .
            "-path \"*.svn\" -prune -o -name \"*\" " .
            "-path \"*.git\" -prune -o -name \"*\"",
            $fileList
        );

        $count = 0;
        foreach ($fileList as $file) {
            exec("wc -l $file", $arr);
            $out = explode(" ", $arr[0]);
            $count += (int)$out[0];
            $arr = null;
        }
        return $count;
    }
}
