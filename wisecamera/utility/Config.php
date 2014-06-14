<?php
/**
 * Config
 *
 * PHP version 5
 *
 * LICENSE: none
 *
 * @author    Poyu Chen <poyu677@gmail.com>
 */
namespace wisecamera\utility;

/**
 * Config
 *
 * The class handle the config file.
 * On construction, it will read config file from assigned path.
 * (if not assigned, defult is ~/crawler_onf)
 * If config file is not exist, it will create one with default value.
 * After constrution, you can get the config value through 'getValue' function
 *
 * LICENSE: none
 *
 * @author  Poyu Chen <poyu677@gmail.com>
 */
class Config
{
    /**
     * An array to store config key-value pair
     */
    private $configData;
    
    /**
     * construtor
     *
     * Read the config file and store in $configData
     *
     * @param $confPath string  Path to config file, if not passed, use default path
     */
    public function __construct($confPath = null)
    {
        $path = $confPath;
        if ($confPath == null) {
            exec("env | grep 'HOME='", $homeOutput);
            for ($i = 0; $i < count($homeOutput); $i++) {
                $selfHome = explode("=", $homeOutput[$i]);
                if ($selfHome[0] == 'HOME') {
                    break;
                }
            }
            $path = $selfHome[1] . "/crawler_conf";
        }

        $this->readConfig($path);
    }

    /**
     * getValue
     *
     * Get config value.
     * The config data will store in $configData, this function provide interface to access
     *
     * @param $key string   Config value want to access
     *
     * @return string   The string of config value
     */
    public function getValue($key)
    {
        return $this->configData[$key];
    }

    /**
     * readConfig
     *
     * Read the config file and store in $configData
     * The pattern of config file should be:
     *  $<value> = <key>
     * Ex. "$user = Poyu". ($ sign is necessary)
     *
     * @param $path string  Path to file
     */
    private function readConfig($path)
    {
        if (!file_exists($path)) {
            $this->makeDefualtConfig($path);
        }

        $fp = fopen($path, "r");

        $this->configData = array();
        while (($line = fgets($fp)) !== false) {
            $arr = explode("=", $line);
            $key = str_replace("$", "", trim($arr[0]));
            $value = str_replace("'", "", trim($arr[1]));
            
            $this->configData[$key] =  $value;
        }

        fclose($fp);
    }
    
    /**
     * makeDefualtConfig
     *
     * To generate config file with default value
     *
     * @param $defHome string   Path to config file
     */
    private function makeDefualtConfig($defHome)
    {
        $dataWrite[0] = "\$host = '127.0.0.1'";
        $dataWrite[1] = "\$dbname = 'NSC'";
        $dataWrite[2] = "\$user = 'root'";
        $dataWrite[3] = "\$password = 'openfoundry'";
        $dataWrite[4] = "\$chkAllTime = 10";
        $dataWrite[5] = "\$chkProxyTime = 10";
        $dataWrite[6] = "\$extraProgram = 'php ./crawler/main.php '";
        $dataWrite[7] = "\$chkType = 'project'";
        $dataWrite[8] = "\$chkTime = 240";

        $fileOpen = fopen($defHome, "w");
        fwrite($fileOpen, implode(chr(10), $dataWrite));
        fclose($fileOpen);
    }
}
