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
     * @param string config to read
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
    public function checkLogDir() {

        $logDir = array('log/run/', 'log/run/server/', 'log/save/');

        foreach ($logDir as $directory) {
            if (!is_dir($directory)) {
                mkdir($directory);
            }
        }

        return $logDir;
    }



}
