<?php

//first, change to working drectory
chdir(__DIR__);

//set up timzone info
date_default_timezone_set('Asia/Taipei');

require_once "third/SplClassLoader.php";
$loader = new \SplClassLoader('wisecamera');
$loader->register();

date_default_timezone_set('Asia/Taipei');

require_once "third/PHPMailer/class.phpmailer.php";

use wisecamera\dispatcher\Mailer;
use wisecamera\dispatcher\ProxyCheck;
use wisecamera\dispatcher\ProxySQLService;
use wisecamera\utility\Config;

try {
    $config = new Config();
} catch (\Exception $e) {
    echo $e->getMessage() . "\n";
    exit();
}

ProxySQLService::$dbname = $config->getValue("dbname");
ProxySQLService::$host = $config->getValue("host");
ProxySQLService::$password = $config->getValue("password");
ProxySQLService::$user = $config->getValue("user");
ProxyCheck::$chkAllTime = $config->getValue("chkAllTime");
ProxyCheck::$chkProxyTime = $config->getValue("chkProxyTime");
ProxyCheck::$chkTime = $config->getValue("chkTime");
ProxyCheck::$chkType = $config->getValue("chkType");
ProxyCheck::$extraProgram = $config->getValue("extraProgram");

exec("ps aux | grep '[p]hp proxyCheck.php' | awk '{print $2}' | xargs", $firstCmd);
$firstRun = explode(" ", $firstCmd[0]);
if (count($firstRun) > 1) {
    echo "Alert: This program is still running." . chr(10);
    exit;
}


$SQL = new ProxySQLService();
$Proxy = new ProxyCheck();
$check = 0;
$thisDatetime = date("Y-m-d H:i");
$logFolder = "log/run";
$logFolder2 = "log/run/server";
$logSaveFolder = "log/save";
if (!is_dir($logFolder)) {
    mkdir($logFolder);
}
if (!is_dir($logFolder2)) {
    mkdir($logFolder2);
}
if (!is_dir($logSaveFolder)) {
    mkdir($logSaveFolder);
}


do {

    // every 30 minutes to run
    $thisDate = array("0" => $thisDatetime,
        "1" => date("Y-m-d H:i", strtotime($thisDatetime) - (30*60)));

    if (date("Y-m-d H:i") == $thisDate[0]) {
        $log = "log/";
        $logPath = scandir($log);

        foreach ($logPath as $checkFile) {
            if ($checkFile != "." && $checkFile != "..") {

                $chkFile = explode(".", $checkFile);
                if (!is_numeric($chkFile[0])) {
                    break;
                }

                $fileTime = date("Y-m-d H:i", filemtime(dirname(__FILE__) ."/" . $log . "/" . $checkFile));

                if ($thisDate[1] <= $fileTime && $thisDate[0] >= $fileTime) {
                    $logLine = fopen($log . "/" . $checkFile, "r");

                    while (!feof($logLine)) {
                        $logToLine = fgets($logLine);
                        $fileForDate = explode(" ", $logToLine);
                        if (!empty($fileForDate[0])) {
                            $chkCheck = $SQL->getProjectStatus(trim($fileForDate[2]));

                            if ($chkCheck['status'] != 'working'
                                && (($thisDate[0] <= $chkCheck['last_update'])
                                    && $thisDate[1] >= $chkCheck['last_update'])) {
                                $SQL->updateProjectStatus('working', $fileForDate[2]);
                                $updateFileLog = fopen('rerun.log', 'a+');
                                fputs($updateFileLog, $fileForDate[2] . " " . date('Y-m-d H:i') . " is rerun.");
                                fclose($updateFileLog);
                                echo $fileForDate[2] . " " . date('Y-m-d H:i') . " is rerun." . chr(10);
                                exec(ProxyCheck::$extraProgram . $fileForDate[2]);
                            }
                        }
                    }
                    fclose($logLine);
                }
            }
        }


        $thisDatetime = date("Y-m-d H:i", strtotime("+30 minutes"));
        $thisDate = array("0" => $thisDatetime,
            "1" => date("Y-m-d H:i", strtotime("-30 minutes")));

    }

    // Proxy Server 檢查
    $proxyServer = $SQL->getProxyId();
    $proxySvr = $Proxy->checkProxy($proxyServer);

    foreach ($proxySvr as $proxy => $status) {
        $fileName = 'log/proxy/proxy::' . $proxy;
        $proxyGet = explode(":", $proxy);

        $currStatus = $status;
        $tempStatus = $SQL->getProxyStatus($proxy);

        if ($currStatus != $tempStatus) {
            if (!empty($currStatus) && !empty($tempStatus)) {
                $msg1 = "偵測Proxy Server恢復連線";
                $msg2 = "偵測Proxy Server中斷連線";
                $message1 = "偵測Proxy Server, " . $proxyGet[0] . ", 於" . date("Y-m-d H:i:s") . "恢復連線";
                $message2 = "偵測Proxy Server, " . $proxyGet[0] . ", 於" . date("Y-m-d H:i:s") . "中斷連線";

                if ($currStatus == 'on-line') {
                    Mailer::$subject = $message1;
                    $SQL->updateLog($proxyGet[0], $msg1);
                } else {
                    Mailer::$subject = $message2;
                    $SQL->updateLog($proxyGet[0], $msg2);
                }
                Mailer::$msg = Mailer::$subject;
                $mail = new Mailer();
                $mail->mailSend();
            }
        }

        $SQL->updateProxyStatus($proxy, $currStatus);
        $fp = fopen($fileName, 'w+');
        fwrite($fp, $currStatus);
        fclose($fp);
    }

    $allProxyStatus = $SQL->proxyStatus();

    if ($allProxyStatus == "proxy_error") {
        $check++;
        if ($check == 1) {
            $msgAll = "偵測所有Proxy Server中斷連線";
            $SQL->updateLog('', $msgAll);
            Mailer::$subject = $msgAll;
            $mail = new Mailer();
            $mail->mailSend();
        }
    } else {
        $check = 0;
    }

    // check proxy ending


    // 檢查排程, 如果proxy都沒有就跳過
    if ($allProxyStatus == 'proxy_nice') {

        $schedule = $SQL->getSchedule();
        $proxyServer = new ProxyCheck();

        while ($rows = $schedule->fetch()) {

            if ($rows['sch_type'] == "one_time") {
                $theDate = date('Y-m-d H:i:00');
                $result = $SQL->getScheduleParam(
                    "`sch_type` = 'one_time'
                    AND `time` = '" . $theDate . "'"
                );
            } elseif ($rows['sch_type'] == "daily") {
                $theDate = date("H:i:00");

                $result = $SQL->getScheduleParam(
                    "`sch_type` = 'daily'
                    AND `time` = '2012-01-01 " . $theDate . "'"
                );
            } elseif ($rows['sch_type'] == "weekly") {
                $theDate = date("H:i:00");

                $result = $SQL->getScheduleParam(
                    " `sch_type` = 'weekly'
                        AND `time` = '2012-01-01 " . $theDate . "'"
                    . " AND `schedule` = " . date('N')
                );
            }

            // 狀態為空的，或者為finish
            while ($arrRow = $result->fetch()) {
                // status is empty or finish
                $arrID = $arrRow['schedule_id'];
                $updFile = "log/run/server/server::" . $arrID;
                $sGroup = $SQL->getScheduleGroup($arrID);

                if (file_exists("log/run/" . $arrID . '.log')) {
                    break;
                }

                // get schedule id, project id

                $logStart = fopen("log/save/" . $arrID . "_run_" . date('Ymd-His') .  ".log", "a+");


                $time = $arrRow['time'];
                $type = array();
                $runVar = array();


                while ($sGRow = $sGroup->fetch()) {
                    if ($sGRow['type'] == 'year' || $sGRow['type'] == 'group') {

                        if ($sGRow['member'] != 'all' && $sGRow['type'] == 'year') {
                            $type[] = 'year';
                            $runVar['year'] = "`year` = " . $sGRow['member'];
                        }

                        if ($sGRow['member'] != 'all' && $sGRow['type'] == 'group') {
                            $type[] = 'group';
                            $runVar['group'] = "`type` = '" . $sGRow['member'] . "'";
                        }
                    }

                    if ($sGRow['type'] == 'project') {
                        $type[] = 'project';
                        $runVar[] = $sGRow['member'];
                    }
                }

                $runPrg = array();
                $runPrg1 = array();
                $runPrg2 = array();
                $runPrg3 = array();
                $runExec = "";

                // all
                if (count($runVar) == 0) {
                    $run = $SQL->getProjectNoParam();
                    while ($runRows = $run->fetch()) {
                        $runPrg1[] = ProxyCheck::$extraProgram .
                            ((ProxyCheck::$chkType == "project") ?
                                $runRows['project_id'] : $runRows['url']);
                    }
                }

                // project
                if (count($runVar) >= 1 && $type[0] == 'project') {
                    for ($i = 0; $i < count($runVar); $i++) {
                        $project = $SQL->getProject($runVar[$i]);
                        $projectID = ((ProxyCheck::$chkType == "project") ?
                            $project['project_id'] : $project['url']);
                        $runPrg2[] = ProxyCheck::$extraProgram . $projectID;
                    }

                }

                // year or group
                if (count($runVar) >= 1) {
                    if ($type[0] == 'year' || $type[0] == 'group') {
                        if ($type[0] == 'year') {
                            $runPrg[] = $runVar['year'];
                        }
                        if ($type[0] == 'group') {
                            $runPrg[] = $runVar['group'];
                        } elseif (count($type) > 1 && $type[1] == 'group') {
                            $runPrg[] = $runVar['group'];
                        }
                        $runExec = implode(" AND ", $runPrg);

                        $prg3Result = $SQL->getProjectParam($runExec);
                        while ($prg3Rows = $prg3Result->fetch()) {
                            $runPrg3[] = ProxyCheck::$extraProgram .
                                ((ProxyCheck::$chkType == "project") ?
                                    $prg3Rows['project_id'] : $prg3Rows['url']);
                        }
                    }
                }

                if (count($runPrg1) == 0 && count($runPrg2) == 0 && count($runPrg3) == 0) {

                    // not schedule project
                    $updateSchedule = fopen("log/run/server/server::" . $arrID, "w+");
                    fwrite($updateSchedule, "not_exist");
                    fclose($updateSchedule);

                    fwrite($logStart, "not_exist");


                } else {
                    $fp = fopen("log/" . $arrID . ".log", "w+");
                    fputs($fp, '');
                    fclose($fp);
                    $fileTime = date("Y-m-d H:i", filemtime(dirname(__FILE__). "/log/" . $arrID . ".log"));

                    // schedule project
                    $fp = fopen("log/" . $arrID . ".log", "w+");
                    if (count($runPrg1) > 0) {
                        for ($i=0; $i < count($runPrg1); $i++) {
                            $out = explode(" ", $runPrg1[$i]);
                            $projectStatus = $SQL->getProjectStatus(trim($out[2]));
                            if ($projectStatus['status'] != 'working') {
                                if ($projectStatus['last_update'] < $fileTime) {
                                    $SQL->updateProjectStatus(trim($out[2]), "working");
                                    exec($runPrg1[$i] . " > /dev/null &");
                                }
                            }
                            fputs($fp, $runPrg1[$i] . chr(10));
                            fputs($logStart, $runPrg1[$i] . chr(10));
                        }
                    }

                    if (count($runPrg2) > 0) {
                        for ($i=0; $i < count($runPrg2); $i++) {
                            $out = explode(" ", $runPrg2[$i]);
                            $projectStatus = $SQL->getProjectStatus(trim($out[2]));
                            if ($projectStatus['status'] != 'working') {
                                if ($projectStatus['last_update'] < $fileTime) {
                                    $SQL->updateProjectStatus(trim($out[2]), "working");
                                    exec($runPrg2[$i] . " > /dev/null &");
                                }
                            }
                            fputs($fp, $runPrg2[$i] . chr(10));
                            fputs($logStart, $runPrg2[$i] . chr(10));
                        }
                    }

                    if (count($runPrg3) > 0) {
                        for ($i=0; $i < count($runPrg3); $i++) {
                            $out = explode(" ", $runPrg3[$i]);
                            $projectStatus = $SQL->getProjectStatus(trim($out[2]));
                            if ($projectStatus['status'] != 'working') {
                                if ($projectStatus['last_update'] < $fileTime) {
                                    $SQL->updateProjectStatus(trim($out[2]), "working");
                                    exec($runPrg3[$i] . " > /dev/null &");
                                }
                            }
                            fputs($fp, $runPrg3[$i] . chr(10));
                            fputs($logStart, $runPrg3[$i] . chr(10));
                        }
                    }

                    fclose($fp);

                    $updateSchedule = fopen("log/run/server/server::" . $arrID, "w+");
                    fwrite($updateSchedule, "work");
                    fclose($updateSchedule);

                    @mkdir("log/server/" . $arrID ."/");

                    copy('log/' . $arrID . ".log", 'log/run/' . $arrID . ".log");
                }


                fclose($logStart);
            }
        }
    }

    // 讀取日誌檔案
    $logDir = "log/run/server/";
    $logDir2 = "log/run/";
    $files = scandir($logDir);

    foreach ($files as $fileName) {
        if ($fileName != "." && $fileName != "..") {
            if (substr($fileName, 0, 6) == "server") {

                $getLog = explode("::", $fileName);

                $fileLog = fopen($logDir . "/" . $fileName, "r");
                $fileLogLine = fgets($fileLog);
                fclose($fileLog);
                if ($fileLogLine == 'work') {
                    $fp = fopen($logDir2 . $getLog[1] . ".log", "r");
                    while (!feof($fp)) {
                        $cmdLine = fgets($fp);

                        if (trim($cmdLine) != '') {
                            $cmdLine = trim($cmdLine);
                            $cmdLine =  substr_replace($cmdLine, "[p]", 0, 1);
                            $cmdRun = explode(" ", $cmdLine);
                            $outLine = array();
                            exec("ps aux | grep '$cmdLine' | awk '{print $2}' | xargs");
                            exec("ps aux | grep '$cmdLine' | awk '{print $9}' | xargs", $outLine);

                            if (trim($outLine[0]) != '') {
                                $logDir2File = $logDir2 . $getLog[1] . ".log";
                                $fileRealTime = filemtime(dirname(__FILE__) . "/" . $logDir2File);
                                $fileTime = date("Y-m-d H:i", $fileRealTime);
                                $timeDiff = $SQL->dateDifference("n", $fileTime, date("Y-m-d H:i"));
                                if (!empty($fileTime) && ($timeDiff >= ProxyCheck::$chkTime)) {
                                    echo $timeDiff . chr(10);
                                    $cmdFile = explode(" ", $cmdLine);
                                    $runProgram = $cmdFile[2];

                                    $SQL->updateProjectStatus($runProgram, 'fail');
                                    $SQL->updateCrawlerTimeOut($runProgram, $fileTime, date('Y-m-d H:i'));

                                    $errLog = fopen("log/error.log", "a+");
                                    $errorMsgFirst = " 執行由" . $fileTime . "~" . date("Y-m-d H:i") . "已超過";
                                    $errorMsg = $cmdFile[2] . $errorMsgFirst . (ProxyCheck::$chkTime / 60) . "小時";
                                    fputs($errLog, $errorMsg . chr(10));
                                    fclose($errLog);

                                    $updateSchedule = fopen("log/server/" . $getLog[1] . "/" . $cmdRun[2], "w+");
                                    fwrite($updateSchedule, "time_out");
                                    fclose($updateSchedule);

                                    exec("ps aux | grep '$cmdLine' | awk '{print $2}' | xargs kill -9");
                                    Mailer::$msg = $errLog;
                                    Mailer::$subject = Mailer::$msg;
                                    $mail = new Mailer();
                                    $mail->mailSend();
                                }
                            } else {
                                $updateSchedule = fopen("log/server/" . $getLog[1] . "/" . $cmdRun[2], "w+");
                                fwrite($updateSchedule, "finish" . chr(10));
                                fclose($updateSchedule);
                            }
                        }
                    }
                    fclose($fp);


                    $updateSchedule = fopen($logDir2 . $getLog[1] . ".log", "r");
                    $checkRun = "finish";
                    while (!feof($updateSchedule)) {
                        $updateLine = fgets($updateSchedule);
                        $checkExplode = explode(" ", $updateLine);
                        @exec("ps aux | grep '$checkExplode[2]' | awk '{print $9}' | xargs", $outLine);
                        if (!empty($outLine[0])) {
                            $checkRun = "work";
                            break;
                        }

                    }
                    fclose($updateSchedule);

                    if ($checkRun == 'finish') {
                        if (file_exists($logDir . $fileName)) {
                            $updateSchedule = fopen($logDir . $fileName, "w+");
                            fwrite($updateSchedule, "finish" . chr(10));
                            fclose($updateSchedule);
                            @unlink($logDir2 . $getLog[1] . ".log");
                        }
                    }


                } else {
                    echo $getLog[1] . " finish" . chr(10);
                    $logRun = fopen("log/save/" .$getLog[1] . "_close_" . date('Ymd-His') .  ".log", "w+");
                    fputs($logRun, "finish");
                    fclose($logRun);

                    @unlink($logDir . $fileName);

                }
            }

        }

    }



    sleep(1);
} while (true);
