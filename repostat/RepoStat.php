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
namespace wisecamera;

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
}
