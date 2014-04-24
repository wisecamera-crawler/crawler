<?

require_once "DTO.php";

class SQLService
{
    static public $ip = "";
    static public $user = "";
    static public $password = "";

    private $connection;
    private $projectId;

    private $startTime, $endTime;
    private $wikiState, $vcsState, $issueState, $downloadState;
    private $lastData;

    public function __construct($projectId)
    {
        $dsn = "mysql:host=" . SQLService::$ip . ";dbname=nsc";
        $this->connection = new PDO($dsn, SQLService::$user, SQLService::$password);
        $this->projectId = $projectId;
        $this->wikiState = "no_change";
        $this->vcsState = "no_change";
        $this->issueState = "no_change";
        $this->downloadState = "no_change";
        $result = $this->connection->query("SELECT * FROM `project` 
            WHERE `project_id` = '$projectId'");
        $result->setFetchMode(PDO::FETCH_ASSOC);
        $this->lastData = $result->fetch();
        $this->startTime = date("Y-m-d H:i:s");
    }

    public function __destruct()
    {
        $this->endTime = date("Y-m-d H:i:s");
        $this->writeSummaray();
    }

    public function insertIssue(Issue $issue)
    {
        if($this->lastData["issue_topic"] != $issue->topic OR
            $this->lastData["issue_post"] != $issue->article OR
            $this->lastData["issue_user"] != $issue->account) {
            $this->issueState = "success_update";
        }

        $this->connection->query("INSERT INTO `issue` 
            (`project_id`, `topic`, `open`, `close`, `article`, `account`) 
            VALUES ('$this->projectId' ,'$issue->topic', '$issue->open', 
            '$issue->close', '$issue->article', '$issue->account')");

        $this->connection->query("UPDATE `project`
            set `issue_topic` = '$issue->topic',
                `issue_post` = '$issue->article',
                `issue_user` = '$issue->account'
            WHERE `project_id` = '$this->projectId'");
    }

    public function insertWiki(Wiki $wiki)
    {
        if($this->lastData["wiki_pages"] != $wiki->pages OR
            $this->lastData["wiki_line"] != $wiki->line OR
            $this->lastData["wiki_update"] != $wiki->update) {
            $this->wikiState = "success_update";
        }

        $this->connection->query("INSERT INTO `wiki` (`project_id`, `pages`, `line`, `update`)
            VALUES('$this->projectId', '$wiki->pages', '$wiki->line', '$wiki->update')");

        $this->connection->query("UPDATE `project`
            set `wiki_pages` = '$wiki->pages',
                `wiki_line` = '$wiki->line',
                `wiki_update` = '$wiki->update'
            WHERE `project_id` = '$this->projectId'");

    }

    public function insertVCS(VCS $vcs)
    {
        if($this->lastData["vcs_commit"] != $vcs->commit OR
            $this->lastData["vcs_size"] != $vcs->size OR
            $this->lastData["vcs_line"] != $vcs->line OR
            $this->lastData["vcs_user"] != $vcs->user) {
                $this->vcsState = "success_update";
        }

        $this->connection->query("INSERT INTO `vcs` 
            (`project_id`, `commit`, `file`, `line`, `size`, `user`)
            VALUES ('$this->projectId', '$vcs->commit', '$vcs->file', '$vcs->line',
            '$vcs->size', '$vcs->user')");

        $this->connection->query("UPDATE `project`
            set `vcs_commit` = '$vcs->commit',
                `vcs_size` = '$vcs->size',
                `vcs_line` = '$vcs->line',
                `vcs_user` = '$vcs->user'
            WHERE `project_id` = '$this->projectId'");

    }

    public function insertDownload(array $downloads)
    {
        $valueString = "VALUES";
        $dlCount = 0;
        $dlFile = 0;
        foreach($downloads as $download) {
            $valueString .= "('$download->url', '$this->projectId', 
                '$download->name', '$download->count'),";
            $dlFile += 1;
            $dlCount += $download->count;
        }
        $valueString = substr($valueString, 0, -1);
        
        if($this->lastData["dl_file"] != $dlFile OR
            $this->lastData["dl_count"] != $dlCount) {
            $this->downloadState = "success_update";
        }

        $this->connection->query("INSERT INTO `download` 
            (`url`, `project_id`, `name`, `count`) $valueString");

        $this->connection->query("UPDATE `project` 
            set `dl_file` = '$dlFile',
                `dl_count` = '$dlCount'
            WHERE `project_id` = '$this->projectId'");
    }


    public function insertVCSCommiters(array $commiters)
    {
        $valueString = "VALUES";
        foreach($commiters as $commiter) {
            $valueString .= "('$commiter->commiter', '$this->projectId', 
                '$commiter->modify', '$commiter->delete', '$commiter->new'),";
        }
        $valueString = substr($valueString, 0, -1);
        
        $this->connection->query("INSERT INTO `vcs_commiter` 
            (`commiter`, `project_id`, `modify`, `delete`, `new`) $valueString");
    }

    public function insertWikiPages(array $pages)
    {
        $valueString = "VALUES";
        foreach($pages as $page) {
            $valueString .= "('$page->url', '$this->projectId', '$page->title',
                '$page->line', '$page->update'),";
        }
        $valueString = substr($valueString, 0, -1);

        $this->connection->query("INSERT INTO `wiki_page` 
            (`url`, `project_id`, `title`, `line`, `update`) $valueString");
    }

    public function insertRating(Rating $ranking)
    {
        $this->connection->query("UPDATE `project` 
            SET `star` = '$ranking->star', 
                `fork` = '$ranking->fork', 
                `watch` = '$ranking->watch', 
                `1-star` = '$ranking->oneStar',
                `2-star` = '$ranking->twoStar',
                `3-star` = '$ranking->threeStar',
                `4-star` = '$ranking->fourStar',
                `5-star` = '$ranking->fiveStar'
            WHERE `project_id` = '$this->projectId'"); 
    }

    public function getProjectInfo($type)
    {
        return $this->lastData[$type];
    }

    private function writeSummaray()
    {
        if($this->lastData == null)
            return;

        $arr = array($this->wikiState, $this->vcsState, 
            $this->issueState, $this->downloadState);
        if(in_array("cannot_get_data", $arr) OR in_array("can_not_resolve", $arr))
            $status = "fail";
        elseif(in_array("success_update", $arr))
            $status = "success_update";
        else
            $status = "no_change";
        
        //TODO : proxy ip
        $ip = "127.0.0.1";

        $this->connection->query("INSERT INTO `crawl_status`
            (`project_id`, `status`, `wiki`, `vcs`, `issue`, 
            `download`, `ip`, `starttime`, `endtime`)
            VALUES('$this->projectId', '$status', '$this->wikiState', '$this->vcsState',
            '$this->issueState', '$this->downloadState', '$ip',
            '$this->startTime', '$this->endTime')");
    }
}

