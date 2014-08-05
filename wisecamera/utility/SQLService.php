<?php
/**
 * SQLService.php : Provide the APIs for crawler write data to db
 *
 * PHP version 5
 *
 * LICENSE : none
 *
 * @author   Poyu Chen <poyu677@gmail.com>
 */
namespace wisecamera\utility;

use \PDO;
use wisecamera\utility\DTOs\Download;
use wisecamera\utility\DTOs\Issue;
use wisecamera\utility\DTOs\Rating;
use wisecamera\utility\DTOs\VCS;
use wisecamera\utility\DTOs\VCSCommiter;
use wisecamera\utility\DTOs\Wiki;
use wisecamera\utility\DTOs\WikiPage;

/**
 * SQLService provides the APIs for crawler write data to db.
 *
 * On construction, it will initialize the DB connection,
 * and when destructing, it will write the summary to DB for this connection.
 * Before using this class,
 * there are 3 variables you MUST configure:
 *  SQLService::ip          IP of the DB
 *  SQLService::user        User name of DB
 *  SQLService::password    Password of DB
 *
 * LICENSE : none
 *
 * @author   Poyu Chen <poyu677@gmail.com>
 */
class SQLService
{
    /**
     * This 3 variables are DB config,
     *  ip          ip of DB
     *  user        username of DB
     *  password    password of DB
     *  db          DB name
     */
    public static $ip = "";
    public static $user = "";
    public static $password = "";
    public static $db = "";
    /**
     * The DB connection object(PDO)
     */
    private $connection;

    /**
     * The project id which this serviece so with
     */
    private $projectId;

    /**
     * The proxy ip of this connecyion
     */
    public $proxy;

    /**
     * Start time and end time of this job
     */
    private $startTime;
    private $endTime;

    /**
     * States of diffrent target
     * States are presented in string   "no_change"         get data but same as last time
     *                                  "success_update"    get data and same as last time
     *                                  "can_not_resolve"   data in web page can not be resolved
     *                                  "cannot_get_data"   connection error
     * may refer to DB schema for further information
     */
    private $wikiState;
    private $vcsState;
    private $issueState;
    private $downloadState;

    /**
     * lastData, the latest data of this project, used to compared if data changed
     */
    private $lastData;

    /**
     * constructor
     *
     * The construtor, set up states and get lastData
     *
     * @param string $projectId Project's id
     * @param string $proxy     The proxy used this time
     *
     * @return none
     */
    public function __construct($projectId)
    {
        $this->setupDBConnection();
        $this->projectId = $projectId;
        $this->startTime = date("Y-m-d H:i:s");

        $proxy = $this->chooseProxy();
        if ($proxy == "") {
            $this->writeError("proxy");
            return;
        }
        
        $this->wikiState = "no_change";
        $this->vcsState = "no_change";
        $this->issueState = "no_change";
        $this->downloadState = "no_change";
        $result = $this->connection->query(
            "SELECT * FROM `project` WHERE `project_id` = '$projectId'"
        );
        $result->setFetchMode(PDO::FETCH_ASSOC);
        $this->lastData = $result->fetch();
        $this->proxy = $proxy;
        echo $this->proxy . "\n";
    }

    /**
     * destructor
     *
     * To writeback summary when finished
     */
    public function __destruct()
    {
        $this->endTime = date("Y-m-d H:i:s");
        $this->writeSummary();
    }

    /**
     * checkIssue
     *
     * This function check issue object's data is resonable and 
     * comapre to lastData(lasttime's result) and update the state
     *
     * @param Issue $issue  The issue object to check
     *
     * @return boolean  true for reasonable
     */
    public function checkIssue(Issue $issue)
    {
        if ((int)$this->lastData["issue_topic"] > (int)$issue->topic) {
            $this->updateState("issue", "can_not_resolve");
            return false;
        } elseif (
            (int)$this->lastData["issue_topic"] != (int)$issue->topic
            or (int)$this->lastData["issue_post"] != (int)$issue->article
            or (int)$this->lastData["issue_user"] != (int)$issue->account
        ) {
            $this->updateState("issue", "success_update");
        } else {
            $this->updateState("issue", "no_change");
        }
        return true;
    }
    
    /**
     * checkWiki
     *
     * This function check wiki object's data is resonable and 
     * comapre to lastData(lasttime's result) and update the state
     *
     * @param Wiki $wiki  The wiki object to check
     *
     * @return boolean  true for reasonable
     */
    public function checkWiki(Wiki $wiki)
    {
        if ((int)$this->lastData["wiki_pages"] != (int)$wiki->pages or
            (int)$this->lastData["wiki_line"] != (int)$wiki->line or
            (int)$this->lastData["wiki_word"] != (int)$wiki->word or
            (int)$this->lastData["wiki_update"] != (int)$wiki->update) {
             $this->updateState("wiki", "success_update");
        } else {
            $this->updateState("wiki", "no_change");
        }
        return true;
    }

    /**
     * checkVCS
     *
     * This function check vcs object's data is resonable and 
     * comapre to lastData(lasttime's result) and update the state
     *
     * @param VCS $vcs  The VCS object to check
     *
     * @return boolean  true for reasonable
     */
    public function checkVCS(VCS $vcs)
    {
        if ((int)$this->lastData["vcs_commit"] != (int)$vcs->commit or
            (string)$this->lastData["vcs_size"] != (string)$vcs->size or
            (int)$this->lastData["vcs_line"] != (int)$vcs->line or
            (int)$this->lastData["vcs_user"] != (int)$vcs->user or
            (int)$this->lastData["vcs_file"] != (int)$vcs->file
        ) {
            $this->updateState("vcs", "success_update");
        } else {
            $this->updateState("vcs", "no_change");
        }
        return true;
    }

    /**
     * checkVCS
     *
     * This function check download object's data is resonable and 
     * comapre to lastData(lasttime's result) and update the state
     *
     * @param array $downloads  The array of Download object to check
     *
     * @return boolean  true for reasonable
     */
    public function checkDownload(array $downloads)
    {
        $dlCount = 0;
        $dlFile = 0;
        foreach ($downloads as $download) {
            $dlFile += 1;
            $dlCount += $download->count;
        }

        if ((int)$this->lastData["dl_file"] != (int)$dlFile or
            (int)$this->lastData["dl_count"] != (int)$dlCount) {
            $this->updateState("download", "success_update");
        } else {
            $this->updateState("download", "no_change");
        }
        return true;
    }
    /**
     * insertIssue
     *
     * Insert the issue data into DB, and checl if data changed
     *
     * @param Issue $issue the inserted Issue object
     *
     * @return none
     */
    public function insertIssue(Issue $issue)
    {
        $this->setupDBConnection();

        $this->connection->query(
            "INSERT INTO `issue`
                (`project_id`, `topic`, `open`, `close`, `article`, `account`)
                VALUES ('$this->projectId' ,'$issue->topic', '$issue->open',
                '$issue->close', '$issue->article', '$issue->account')"
        );

        $this->connection->query(
            "UPDATE `project`
                set `issue_topic` = '$issue->topic',
                    `issue_post` = '$issue->article',
                    `issue_user` = '$issue->account'
            WHERE `project_id` = '$this->projectId'"
        );
    }
    /**
     * insertWiki
     *
     * Insert the wiki data into DB, and checl if data is changed
     *
     * @param Wiki $wiki The inserted Wiki object
     *
     * @return none
     */
    public function insertWiki(Wiki $wiki)
    {
        $this->setupDBConnection();
        $this->checkWiki($wiki);

        $this->connection->query(
            "INSERT INTO `wiki` (`project_id`, `pages`, `line`, `word`, `update`)
                VALUES('$this->projectId', '$wiki->pages', '$wiki->line', 
                       '$wiki->word','$wiki->update')"
        );

        $this->connection->query(
            "UPDATE `project`
                set `wiki_pages` = '$wiki->pages',
                    `wiki_line` = '$wiki->line',
                    `wiki_update` = '$wiki->update',
                    `wiki_word` = '$wiki->word'
            WHERE `project_id` = '$this->projectId'"
        );

    }

    /**
     * insertVCS
     *
     * insert the vcs data into DB, and check if data checnged
     *
     * @param VCS $vcs Inserted VCS object
     *
     * @return none
     */
    public function insertVCS(VCS $vcs)
    {
        $this->setupDBConnection();
        $this->checkVCS($vcs);
       
        $this->connection->query(
            "INSERT INTO `vcs`
                (`project_id`, `commit`, `file`, `line`, `size`, `user`)
                VALUES ('$this->projectId', '$vcs->commit', '$vcs->file', '$vcs->line',
                '$vcs->size', '$vcs->user')"
        );

        $this->connection->query(
            "UPDATE `project`
                set `vcs_commit` = '$vcs->commit',
                    `vcs_size` = '$vcs->size',
                    `vcs_line` = '$vcs->line',
                    `vcs_user` = '$vcs->user',
                    `vcs_file` = '$vcs->file'
            WHERE `project_id` = '$this->projectId'"
        );
    }

    /**
     * insertDownload
     *
     * Insert the download data into DB,
     * and sum up the total download files, counts, and check if data changed
     *
     * @param Array $downloads Array of Download object
     *
     * @return none
     */
    public function insertDownload(array $downloads)
    {
        $this->setupDBConnection();

        $valueString = "VALUES";
        $dlCount = 0;
        $dlFile = 0;
        foreach ($downloads as $download) {
            $valueString .= "('$download->url', '$this->projectId',
                '". str_replace("'", "\'", $download->name) ."', '$download->count'),";
            $dlFile += 1;
            $dlCount += $download->count;
        }
        $valueString = substr($valueString, 0, -1);

        $this->checkDownload($downloads);

        $this->connection->query(
            "INSERT INTO `download`
                (`url`, `project_id`, `name`, `count`) $valueString"
        );

        $this->connection->query(
            "UPDATE `project`
                set `dl_file` = '$dlFile',
                    `dl_count` = '$dlCount'
            WHERE `project_id` = '$this->projectId'"
        );
    }

    /**
     * insertVCSCommiters
     *
     * insert the VCSCommters data into DB
     *
     * @param Array $commiters array of VCSCommiter
     *
     * @return none
     */
    public function insertVCSCommiters(array $commiters)
    {
        $this->setupDBConnection();

        $valueString = "VALUES";
        foreach ($commiters as $commiter) {
            $valueString .= "('". str_replace("'", "\'", $commiter->commiter) .
                "', '$this->projectId', '$commiter->modify',
                '$commiter->delete', '$commiter->new'),";
        }
        $valueString = substr($valueString, 0, -1);

        $this->connection->query(
            "INSERT INTO `vcs_commiter`
                (`commiter`, `project_id`, `modify`, `delete`, `new`) $valueString"
        );
    }

    /**
     * insertWikiPage
     *
     * insert the each wiki page data into DB
     * The diffrent of WikiPage and Wiki is that WikiPage is data of single wiki page,
     * and Wiki is the summary of assigned projects' wiki
     *
     * @param Array $pages array of WikiPage object
     *
     * @return none
     */
    public function insertWikiPages(array $pages)
    {
        $this->setupDBConnection();

        $valueString = "VALUES";
        foreach ($pages as $page) {
            $valueString .= "('$page->url', '$this->projectId', '$page->title',
                '$page->line', '$page->word', '$page->update'),";
        }
        $valueString = substr($valueString, 0, -1);

        $this->connection->query(
            "INSERT INTO `wiki_page`
                (`url`, `project_id`, `title`, `line`, `word`, `update`) $valueString"
        );
    }

    /**
     * insertRating
     *
     * insert the rating data into DB
     *
     * @param Rating $rating
     *
     * @return none
     */
    public function insertRating(Rating $ranking)
    {
        $this->setupDBConnection();

        $this->connection->query(
            "UPDATE `project`
                SET `star` = '$ranking->star',
                    `fork` = '$ranking->fork',
                    `watch` = '$ranking->watch',
                    `1-star` = '$ranking->oneStar',
                    `2-star` = '$ranking->twoStar',
                    `3-star` = '$ranking->threeStar',
                    `4-star` = '$ranking->fourStar',
                    `5-star` = '$ranking->fiveStar'
            WHERE `project_id` = '$this->projectId'"
        );
    }

    /**
     * insertVCSType
     *
     * update vcs type to project table
     *
     * @param string $type  Repo type: Git SVN HG CVS
     */
    public function insertVCSType($type)
    {
        $this->setupDBConnection();

        $this->connection->query(
            "UPDATE `project` 
                SET `vcs_type` = '$type' 
             WHERE `project`.`project_id` = '$this->projectId';"
        );
    }

    /**
     * updateState
     *
     * Provide method for update state outside.
     * (Only use for proxy_error currently, 
     *  but I think update from outside is not good idea, may have better method?)
     * It will check if input is valid, if valid update directly.
     *
     * @param string $target    Which state var do you want to modify
     * @param string $state     The state
     *
     */
    public function updateState($target, $state)
    {
        $validTarget = array("wiki", "vcs", "issue", "download");
        $validState = array(
            "cannot_get_data",
            "can_not_resolve",
            "proxy_error",
            "success_update",
            "no_change"
        );

        if (
            in_array($target, $validTarget) === false or
            in_array($state, $validState) === false
        ) {
            return;
        }

        $varName = $target . "State";
        $this->$varName = $state;
    }

    /**
     * getProjectInfo
     *
     * Get the data of this project
     * The detail may refer to DB schema
     *
     * @param string $type The target info want to get (field name of project table)
     *
     * @return string The assigned information
     */
    public function getProjectInfo($type)
    {
        return $this->lastData[$type];
    }

    /**
     * writeError
     *
     * If there is any error cause the progrm finish, call it
     *
     * @param string $type  Error type
     */
    private function writeError($type)
    {
        $this->endTime = date("Y-m-d H:i:s");
        $this->setupDBConnection();
        
        $ip = $this->proxy;
        if ($type == "proxy") {
            $this->connection->query(
                "INSERT INTO `crawl_status`
                    (`project_id`, `status`, `wiki`, `vcs`, `issue`,
                    `download`, `proxy_ip`, `starttime`, `endtime`)
                    VALUES('$this->projectId', 'no_proxy', 'no_proxy',
                        'no_proxy', 'no_proxy', 'no_proxy',
                        '', '$this->startTime', '$this->endTime')"
            );
            $ip = "";
        }

        $this->connection->query(
            "UPDATE `project`
                SET `status` = 'fail',
                    `fail` = `fail` + 1,
                    `proxy_ip` = ''
            WHERE `project_id` = '$this->projectId'"
        );
    }

    /**
     * writeSummary
     *
     * Write the summary of this crawling result.
     * In other words, write states of each target into DB (table crawl_status)
     * Automatically invoked on destruction
     */
    private function writeSummary()
    {
        if ($this->lastData == null) {
            return;
        }

        $this->setupDBConnection();

        $arr = array($this->wikiState, $this->vcsState,
            $this->issueState, $this->downloadState);
        if (
            in_array("cannot_get_data", $arr) or
            in_array("can_not_resolve", $arr) or
            in_array("proxy_error", $arr)
        ) {
            $status = "fail";
        } elseif (in_array("success_update", $arr)) {
            $status = "success_update";
        } else {
            $status = "no_change";
        }

        if ($this->proxy != "127.0.0.1") {
            $ip = ParseUtility::resolveHost($this->proxy);
        } else {
            $ip = $this->proxy;
        }

        $this->connection->query(
            "INSERT INTO `crawl_status`
                (`project_id`, `status`, `wiki`, `vcs`, `issue`,
                `download`, `proxy_ip`, `starttime`, `endtime`)
                VALUES('$this->projectId', '$status', '$this->wikiState', '$this->vcsState',
                '$this->issueState', '$this->downloadState', '$ip',
                '$this->startTime', '$this->endTime')"
        );

        $result = "success";
        if ($status === "fail") {
            $result = "fail";
        }

        $this->connection->query(
            "UPDATE `project`
                SET `status` = '$result',
                    `success` = `$result` + 1,
                    `proxy_ip` = '$ip'
            WHERE `project_id` = '$this->projectId'"
        );
    }
    
    /**
     * chooseProxy
     *
     * This function will choose availabe proxy in DB
     * First, it will get proxy information from DB.
     * Then randomly choose one to test its availibility.
     * If the selected proxy is avalable, return the url.
     * If not, update the DB (set off-line in DB), choose next proxy.
     *
     * @return string   Proxy url
     */
    private function chooseProxy()
    {
        $this->setupDBConnection();
        $result = $this->connection->query(
            "SELECT `proxy_ip`, `proxy_port`
                FROM `proxy`
                WHERE `status` = 'on-line'"
        );

        $data = $result->fetchAll(PDO::FETCH_ASSOC);
        if (!$data) {
            return "";
        }
        $key = array_rand($data);
        $proxy = "socks5://" . $data[$key]["proxy_ip"] .  ":" . $data[$key]["proxy_port"];
        while (!WebUtility::testConnection($proxy) and $data) {
            $result = $this->connection->query(
                "UPDATE `proxy`
                    set `status` = 'off-line'
                WHERE `proxy_ip` = '" . $data[$key]["proxy_ip"] . "'"
            );

            unset($data[$key]);
            
            if (!$data) {
                return "";
            }
 
            $key = array_rand($data);
            $proxy = "socks5://" . $data[$key]["proxy_ip"] .  ":" . $data[$key]["proxy_port"];
        }

        return $proxy;
    }


    /**
     * setupDBConnection
     *
     * set up the DB connection (due to long crawling time, DB connection may time out)
     */
    private function setupDBConnection()
    {
        $dsn = "mysql:host=" . SQLService::$ip . ";dbname=" . SQLService::$db;
        $this->connection = new PDO($dsn, SQLService::$user, SQLService::$password);
        $this->connection->query("SET CHARACTER SET utf8 ");
        $this->connection->query("SET NAMES utf8");
    }
}
