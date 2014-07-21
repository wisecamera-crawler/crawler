<?php
/**
 * Logger.php A class for write log
 *
 * PHP version 5
 *
 * LICENSE : none
 *
 * @author   Poyu Chen <poyu677@gmail.com>
 */
namespace wisecamera\utility;

/**
 * Logger, a simple class for logging 
 *
 * LICENSE : none
 *
 * @author   Poyu Chen <poyu677@gmail.com>
 */
class Logger
{
    /**
     * A file descripter for log file
     */
    private $fp;

    /**
     * Constructor
     *
     * @param Sting $file   A file to write log
     */
    public function __construct($file)
    {
        $this->fp = fopen($file, "a");
    }

    /**
     * destructor
     *
     * To close file when over
     */
    public function __destruct()
    {
        fclose($this->fp);
    }

    /**
     * append log to the end of file
     *
     * @param String str    String to log
     */
    public function append($str)
    {
        $timeStamp = date("Y-m-d H:i:s");
        $fullString = "[$timeStamp] $str";
        fwrite($this->fp, $fullString);
    }
}
