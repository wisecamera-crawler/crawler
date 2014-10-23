<?php
/**
 * Config
 *
 * PHP version 5
 *
 * LICENSE: none
 *
 * @category  Utility
 * @package   PackageName
 * @author    Patrick Her <zivhsiao@gmail.com>
 * @copyright 1997-2005 The PHP Group
 * @license   none <none>
 * @version   GIT: <id>
 * @link      none
 */

namespace wisecamera\dispatcher;

/**
 * Config
 *
 * PHP version 5
 *
 * LICENSE: none
 *
 * @category  Utility
 * @package   PackageName
 * @author    Patrick Her <zivhsiao@gmail.com>
 * @copyright 1997-2005 The PHP Group
 * @license   none <none>
 * @link      none
 */
class ProxySystem
{
    public static $msg;
    public static $subject;

    /**
     * mailSend function, mail to send administrator
     *
     * @param string $config config to read
     *
     * @category  Utility
     * @return    none
     */
    public function initializeDB($config)
    {
        ProxySQLService::$dbname = $config->getValue("dbname");
        ProxySQLService::$host = $config->getValue("host");
        ProxySQLService::$password = $config->getValue("password");
        ProxySQLService::$user = $config->getValue("user");
        ProxyCheck::$chkAllTime = $config->getValue("chkAllTime");
        ProxyCheck::$chkProxyTime = $config->getValue("chkProxyTime");
        ProxyCheck::$chkTime = $config->getValue("chkTime");
        ProxyCheck::$chkType = $config->getValue("chkType");
        ProxyCheck::$extraProgram = $config->getValue("extraProgram");
    }

    /**
     * check the folder
     *
     * @category  Utility
     * @return    none
     */
    public function checkLogDir()
    {
        $logDir = array('log/run/', 'log/run/server/', 'log/save/');

        foreach ($logDir as $directory) {
            if (!is_dir($directory)) {
                mkdir($directory);
            }
        }

        return $logDir;
    }

    /**
     * current status log
     *
     * @param string $fileName   save the filename
     * @param string $currStatus current status
     *
     * @category  Utility
     * @return    none
     */
    public function currStatusLog($fileName, $currStatus)
    {
        $fp = fopen($fileName, 'w+');
        fwrite($fp, $currStatus);
        fclose($fp);
    }

    /**
     * update schedule log
     *
     * @param string $arrID     save the filename
     * @param string $messenger messenger
     *
     * @category  Utility
     * @return    none
     */
    public function updateScheduleLog($arrID, $messenger)
    {
        $updateSchedule = fopen("log/run/server/server::" . $arrID, "w+");
        fwrite($updateSchedule, $messenger);
        fclose($updateSchedule);
    }

    /**
     * update schedule log
     *
     * @param string $param1    path name
     * @param string $param2    file name
     * @param string $messenger messenger
     *
     * @category  Utility
     * @return    none
     */
    public function updateLog($param1, $param2, $messenger)
    {
        $updateSchedule = fopen("log/server/" . $param1 . "/" . $param2, "w+");
        fwrite($updateSchedule, $messenger);
        fclose($updateSchedule);
    }

    /**
     * check error log
     *
     * @param string $messenger messenger
     *
     * @category  Utility
     * @return    none
     */
    public function chkErrorLog($messenger)
    {
        $updateSchedule = fopen("log/error.log", "a+");
        fwrite($updateSchedule, $messenger);
        fclose($updateSchedule);
    }

    /**
     * check finish log
     *
     * @param string $fileName  filename
     * @param string $messenger messenger
     *
     * @category  Utility
     * @return    none
     */
    public function checkFinialLog($fileName, $messenger)
    {
        $logRun = fopen($fileName, "w+");
        fputs($logRun, $messenger);
        fclose($logRun);
    }

    /**
     * check finish log
     *
     * @param string $fileName  filename
     * @param string $messenger messenger
     *
     * @category  Utility
     * @return    none
     */
    public function checkFinishLog($fileName, $messenger)
    {
        $logRun = fopen("log/save/" .$fileName . "_close_" . date('Ymd-His') .  ".log", "w+");
        fputs($logRun, $messenger);
        fclose($logRun);
    }
}
