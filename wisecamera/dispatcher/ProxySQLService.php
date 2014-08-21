<?php
/**
 * SQL service
 *
 * PHP version 5
 *
 * LICENSE: none
 *
 * @category Utility
 * @package  PackageName
 * @author   Patrick Her <zivhsiao@gmail.com>
 * @license  none <none>
 * @version  GIT: <git_id>
 * @link     none
 */

namespace wisecamera\dispatcher;

use \PDO;

/**
 * SQL service
 *
 * PHP version 5
 *
 * LICENSE: none
 *
 * @category Utility
 * @package  PackageName
 * @author   Patrick Her <zivhsiao@gmail.com>
 * @license  none <none>
 * @link     none
 */
class ProxySQLService
{
    public static $conn;

    public static $host;
    public static $user;
    public static $password;
    public static $dbname;

    /**
     * SQL connection
     *
     * PHP version 5
     *
     * LICENSE: none
     *
     * @category  Utility
     * @package   PackageName
     * @author    Patrick Her <zivhsiao@gmail.com>
     * @license   none <none>
     * @version   SVN: $Id$
     * @link      none
     */
    public function __construct()
    {
        $dsn = "mysql:host=" . ProxySQLService::$host . ";dbname=" . ProxySQLService::$dbname;
        $this->conn = new PDO($dsn, ProxySQLService::$user, ProxySQLService::$password);
        $this->conn->query("SET CHARACTER SET utf8 ");
        $this->conn->query("SET NAMES utf8");
    }

    /**
     * Get Proxy's server ip, port
     *
     * @category  Utility
     * @return    proxy's address
     */
    public function getProxyId()
    {
        $proxySvr = array();
        $result = $this->conn->query(
            "SELECT `proxy_ip`, `proxy_port`
               FROM `proxy`
              WHERE `status` IN ('on-line', 'off-line')"
        );

        while ($rows = $result->fetch()) {
            array_push($proxySvr, $rows['proxy_ip'] . ":" . $rows['proxy_port']);
        }

        return $proxySvr;
    }

    /**
     * Proxy's status by address
     *
     * @category  Utility
     * @return    proxy's nice or error
     */
    public function proxyStatus()
    {
        $result = $this->conn->query(
            "SELECT `proxy_ip`,
                    `proxy_port`,
                    `status`
               FROM `proxy`"
        );

        while ($row = $result->fetch()) {
            $status = 'proxy_error';
            if ($row['status'] == 'on-line') {
                $status = 'proxy_nice';
                break;
            }
        }

        if ($status == 'proxy_error') {
            return 'proxy_error';
        } else {
            return 'proxy_nice';
        }

    }
    /**
     * Get Proxy's status
     *
     * @param string $proxy Proxy's address
     *
     * @category  Utility
     * @return    the last status
     */
    public function getProxyStatus($proxy)
    {
        $proxyIP = explode(":", $proxy);

        $result = $this->conn->query(
            "SELECT `status`
               FROM `proxy`
              WHERE `proxy_ip` = '$proxyIP[0]'
                AND `proxy_port` = '$proxyIP[1]'"
        );

        $rows = $result->fetch(PDO::FETCH_ASSOC);
        return $rows['status'];
    }
    /**
     * Get Proxy's last status
     *
     * @param string $proxyIP   Proxy's address
     * @param string $proxyPort Proxy's port
     *
     * @category  Utility
     * @return    the last status
     */
    public function getProxyLastStatus($proxyIP, $proxyPort)
    {
        $result = $this->conn->query(
            "SELECT `last_status`
               FROM `proxy`
              WHERE `proxy_ip` = '$proxyIP'
                AND `proxy_port` = '$proxyPort'"
        );

        $rows = $result->fetch(PDO::FETCH_ASSOC);
        return $rows['last_status'];
    }

    /**
     * Get Proxy's current status
     *
     * @param string $proxyIP    Proxy's address
     * @param string $proxyPort  Proxy's port
     * @param string $lastStatus Proxy's last status
     *
     * @category  Utility
     * @return    none
     */
    public function updateProxyLastStatus($proxyIP, $proxyPort, $lastStatus)
    {
        $this->conn->query(
            "UPDATE `proxy`
                SET `last_status` = '$lastStatus'
              WHERE `proxy_ip` = '$proxyIP'
                AND `proxy_port` = '$proxyPort'"
        );
    }

    /**
     * update Proxy's status
     *
     * @param string $proxy  Proxy's address
     * @param string $status Proxy's current status
     *
     * @category  Utility
     * @return    none
     */
    public function updateProxyStatus($proxy, $status)
    {
        $proxyIP = explode(":", $proxy);

        $this->conn->query(
            "UPDATE `proxy`
                set `status` = '$status'
              WHERE `proxy_ip` = '$proxyIP[0]'
                AND `proxy_port` = '$proxyIP[1]'"
        );
    }

    /**
     * Get mailer
     *
     * @category  Utility
     * @return    email
     */
    public function getMailer()
    {
        $result = $this->conn->query(
            "SELECT `email` FROM `email`"
        );

        return $result;
    }


    /**
     * Delete schedule
     *
     * @param string $schID main parameter
     *
     * @category  Utility
     * @return    email
     */
    public function deleteSchedule($schID)
    {
        $this->conn->query(
            "DELETE FROM `schedule_group`
             WHERE `schedule_id` = '$schID'"
        );

        $this->conn->query(
            "DELETE FROM `schedule`
             WHERE `schedule_id` = '$schID'"
        );


    }

    /**
     * Get schedule
     *
     * @category  Utility
     * @return    email
     */
    public function getSchedule()
    {
        $res = $this->conn->query(
            "SELECT *
               FROM `schedule`"
        );

        return $res;
    }

    /**
     * Get mailer
     *
     * @param string $param give a param
     *
     * @category  Utility
     * @return    schedule with parameters
     */
    public function getScheduleParam($param)
    {

        $res = $this->conn->query(
            "SELECT *
               FROM `schedule` WHERE " . $param
        );

        return $res;
    }

    /**
     * Get schedule_group for schedule
     *
     * @param string $status     Schedule's status
     * @param string $schID     Schedule's id
     * @param string $start_time Start time
     *
     * @category  Utility
     * @return    sch_type
     */
    public function updateSchedule($status, $schID, $start_time = '')
    {
        $this->conn->query(
            "UPDATE `schedule`
                SET `status` = '$status',
                    `start_time` = '$start_time'
              WHERE `schedule_id` = $schID"
        );

    }

    /**
     * Get schedule_group for schedule
     *
     * @param string $schID Schedule group's id
     *
     * @category  Utility
     * @return    sch_type
     */
    public function getScheduleGroup($schID)
    {
        $result = $this->conn->query(
            "SELECT `schedule_group_id`,
                    `schedule_id`,
                    `member`,
                    `type`
               FROM `schedule_group`
              WHERE `schedule_id` = $schID
           ORDER BY `type`"
        );

        return $result;
    }

    /**
     * Get schedule_group for schedule
     *
     * @param string $projectID Schedule group's id
     * @param string $year       Schedule group's id
     * @param string $type       Schedule group's id
     *
     * @category  Utility
     * @return    sch_type
     */
    public function getProject($projectID = '', $year = '', $type = '')
    {

        $result = $this->conn->query(
            "SELECT `project_id`,
                    `url`
               FROM `project`"
        );

        if ($projectID != '') {
            $result = $this->conn->query(
                "SELECT `project_id`,
                        `url`
                  FROM `project`
                  WHERE `project_id` = '" . $projectID . "'"
            );

        }

        if ($year != "" && $type != "") {
            if ($year == 'all' && $type == 'all') {
                $result = $this->conn->query(
                    "SELECT `project_id`,
                            `url`
                       FROM `project`"
                );
            }

            if ($year == 'all' && $type != 'all') {
                $result = $this->conn->query(
                    "SELECT `project_id`,
                            `url`
                       FROM `project`
                      WHERE `type` = '$type'"
                );
            }

            if ($year != 'all' && $type == 'all') {
                $result = $this->conn->query(
                    "SELECT `project_id`,
                            `url`
                       FROM `project`
                      WHERE `year` = '$year'"
                );
            }

            if ($year != 'all' && $type != 'all') {
                $result = $this->conn->query(
                    "SELECT `project_id`,
                            `url`
                       FROM `project`
                      WHERE `year` = '$year'
                        AND `type` = '$type'"
                );
            }

        }

        return $result->fetch();

    }

    /**
     * Get schedule_group for schedule
     *
     * @param string $param Schedule group's id
     *
     * @category  Utility
     * @return    sch_type
     */
    public function getProjectParam($param = "")
    {

        $result = $this->conn->query(
            "SELECT `project_id`,
                    `url`
               FROM `project`
              WHERE " . $param
        );

        return $result;
    }

    /**
     * Get schedule_group for schedule
     *
     * @category  Utility
     * @return    sch_type
     */
    public function getProjectNoParam()
    {
        $result = $this->conn->query(
            "SELECT `project_id`,
                    `url`
               FROM `project`"
        );

        return $result;
    }

    /**
     * Update project log
     *
     * @param string $projectID Project's id
     * @param string $status    Project's Status
     *
     * @category  Utility
     * @return    sch_type
     */
    public function updateProjectStatus($projectID, $status)
    {
        $result = $this->conn->query(
            "UPDATE `project`
                SET `status` = '$status'
              WHERE `project_id` = '$projectID'"
        );


    }

    /**
     * Update log
     *
     * @param string $ip  log ip
     * @param string $msg log msg
     *
     * @category  Utility
     * @return    sch_type
     */
    public function updateLog($ip, $msg)
    {
        $thisTime = date("Y-m-d H:i:s");
        $result = $this->conn->query(
            "INSERT INTO `log`
                (`ip`, `type`, `action`, `timestamp`)
             VALUES ('$ip', 'server','$msg', '$thisTime')"
        );

        if (!$result) {
            return $result->errorInfo();
        } else {
            return $result->fetch();
        }
    }

    /**
     * Project's status
     *
     * @param string $projectID project's id
     *
     * @category  Utility
     * @return    sch_type
     */
    public function getProjectStatus($projectID)
    {
        $result = $this->conn->query(
            "SELECT `status`,
                    `last_update`
               FROM `project`
              WHERE `project_id` = '$projectID'
            "
        );

        return $result->fetch();
    }

    /**
     * Project's status
     *
     * @param string $projectID project's id
     * @param string $startTime project's start time
     * @param string $endTime   project's end time
     * @category  Utility
     * @return    sch_type
     */
    public function updateCrawlerTimeOut($projectID, $startTime, $endTime)
    {
        $timeOut = 'time_out';

        $this->conn->query(
            "INSERT INTO `crawl_status`
                SET `status` = '$timeOut',
                    `wiki` = '$timeOut',
                    `vcs` = '$timeOut',
                    `issue` = '$timeOut',
                    `download` = '$timeOut',
                    `starttime` = '$startTime',
                    `endtime` = '$endTime',
                    `project_id` = '$projectID'
            "
        );
    }

    /**
     * Project's status
     *
     * @param string $projectID project's id
     *
     * @category  Utility
     * @return    sch_type
     */
    public function getCrawlerStatus($projectID)
    {
        $result = $this->conn->query(
            "SELECT `endtime`
               FROM `crawler_status`
              WHERE `project_id` = '$projectID'
            "
        );

        return $result->fetch();
    }

    /**
     * Date difference
     *
     * @param string $interval interval
     * @param string $date1    from date
     * @param string $date2    to date
     *
     * @category  Utility
     * @return    sch_type
     */
    public function dateDifference($interval, $date1, $date2)
    {
        // 得到两日期之间间隔的秒
        $timeDifference = strtotime($date2) - strtotime($date1);

        switch($interval) {
            case "w":
                $result = bcdiv($timeDifference, 604800);
                break;

            case "d":
                $result = bcdiv($timeDifference, 86400);
                break;

            case "h":
                $result = bcdiv($timeDifference, 3600);
                break;

            case "n":
                $result = bcdiv($timeDifference, 60);
                break;

            case "s":
                $result = $timeDifference;
                break;

        }

        return $result;
    }
}
