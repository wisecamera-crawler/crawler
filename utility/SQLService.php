<?

require_once "DTO.php";

class SQLService
{
    static public $ip = "";
    static public $user = "";
    static public $password = "";

    private $connection;
    private $projectId;

    public function __construct($projectId)
    {
        $dsn = "mysql:host=" . SQLService::$ip . ";dbname=nsc";
        $this->connection = new PDO($dsn, SQLService::$user, SQLService::$password);
        $this->projectId = $projectId;
    }

    public function insertIssue(Issue $issue)
    {
        $this->connection->query("INSERT INTO `issue` 
            (`project_id`, `topic`, `open`, `close`, `article`, `account`) 
            VALUES ('$this->projectId' ,'$issue->topic', '$issue->open', 
            '$issue->close', '$issue->article', '$issue->account')");
    }

    public function insertWiki(Wiki $wiki)
    {
         $this->connection->query("INSERT INTO `wiki` (`project_id`, `pages`, `line`, `update`)
            VALUES('$this->projectId', '$wiki->pages', '$wiki->line', '$wiki->update')");
    }

    public function insertDownload(Download $download)
    {
        $this->connection->query("INSERT INTO `download` (`url`, `project_id`, `name`, `count`)
            VALUES('$download->url', '$this->projectId',
            '$download->name', '$download->count')");
    }

    public function insertVCS(VCS $vcs)
    {
        $this->connection->query("INSERT INTO `vcs` 
            (`project_id`, `commit`, `file`, `line`, `size`, `user`)
            VALUES ('$this->projectId', '$vcs->commit', '$vcs->file', '$vcs->commit',
            '$vcs->size', '$vcs->user')");
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
                `3-star` = '$ranking->threStar',
                `4-star` = '$ranking->fourStar',
                `5-star` = '$ranking->fiveStar'
            WHERE `project_id` = '$this->projectId'"); 
    }

    private function writeSummaray()
    {
    }
}
