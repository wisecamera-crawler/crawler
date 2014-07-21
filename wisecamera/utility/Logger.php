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

class Logger
{
    private $fp;

    public function __construct($file)
    {
        $this->fp = fopen($file, "a");
    }

    public function __destruct()
    {
        fclose($this->fp);
    }

    public function append($str)
    {
        $timeStamp = date("Y-m-d H:i:s");
        $fullString = "[$timeStamp] $str";
        fwrite($this->fp, $fullString);
    }
}
