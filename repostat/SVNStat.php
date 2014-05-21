<?php
/**
 * class SVNCrawler
 * Abstract:
 * 此類別負責clone svn 專案 並且分析專案資訊
 * 資訊有總作者數, 總commit數, 檔案大小,行數及貢獻值
 * 主要由以下函示負責:
 * 1.crawlAuthorAndCommit()      ---- 抓取作者及commit資訊
 * 2.crawlLine()                            ---- 抓取專案行數資訊
 * 3.crawlFilSize()                        ---- 抓取檔案大小
 * 4.crawlContribute()                  ---- 抓取該專案貢獻資訊
 *
**/

namespace wisecamera;

class SVNStat extends RepoStat
{
    private $cloneUrl;
    private $projectName;
    private $dateTime;
    private $outputFileName;
    private $fileSizeOutput;
    private $logOutput;
    private $reverseFileSizeOutput;
    private $lineOutput;

    /**
     *   當物件被宣告時，初始化各個資訊
     *
    **/
    public function __construct($projectName, $cloneUrl)
    {
        $this->projectName = $projectName;
        $this->dateTime = date('Ymd');
        $this->outputFileName = "repo/" . $this->projectName;
        $this->cloneUrl = $cloneUrl;
        $this->fileSizeOutput = 'fileSize.txt';
        $this->logOutput = 'log.txt';
        $this->reverseFileSizeOutput = 're_'.$this->fileSizeOutput;
        $this->lineOutput = 'line.txt';
        $this->svnCloneProject();
    }

    public function getSummary(VCS & $vcs)
    {
        $authorAndCommit = $this->crawlAuthorAndCommit();
        $vcs->commit = $authorAndCommit["totalAuthors"];
        $vcs->file = $this->getFileCount();
        $vcs->line = $this->crawlLine();
        $vcs->size = $this->crawlFilSize();
        $vcs->user = $authorAndCommit["totalCommit"];
    }

    public function getDataByCommiters(array & $commiters)
    {
        $fileContent = file_get_contents($this->outputFileName.'/'.$this->logOutput);
        $logArr = explode("\n", $fileContent);
        $result = array();
        for ($i = 0; $i < sizeof($logArr); ++$i) {
            $line = $logArr[$i];
            if ($line == "") {
                continue;
            }

            if ($line[0] == 'r') {
                $arr = explode("|", $line);
                $author = trim($arr[1]);
                if (!isset($result[$author])) {
                    $result[$author] = array();
                    $result[$author]['M'] = 0;
                    $result[$author]['A'] = 0;
                    $result[$author]['D'] = 0;
                }

                $i = $i + 2;
                $line = $logArr[$i];
                while ($line != "") {
                    ++$result[$author][$line[3]];
                    ++$i;
                    $line = $logArr[$i];
                }
            }
        }

        foreach ($result as $name => $c) {
            $commiter = new VCSCommiter();
            $commiter->commiter = $name;
            $commiter->modify = $c["M"];
            $commiter->delete = $c["D"];
            $commiter->new = $c["A"];
            $commiters []= $commiter;
        }
    }

    /**
     *  將svn上的專案 clone 下來
     *
    **/
    public function svnCloneProject()
    {
        if (!is_dir($this->outputFileName)) {
            shell_exec('svn checkout '.$this->cloneUrl.' '.$this->outputFileName);
        } else {
            shell_exec("cd $this->outputFileName; svn up");
        }
        shell_exec('du '.$this->outputFileName .' > '.$this->outputFileName .'/'.$this->fileSizeOutput);
        shell_exec('svn log -v '.$this->outputFileName .' > '.$this->outputFileName .'/'.$this->logOutput);
        shell_exec('tac '.$this->outputFileName .'/'.$this->fileSizeOutput.' > '.$this->outputFileName .'/'.$this->reverseFileSizeOutput);
        shell_exec('rm '.$this->outputFileName .'/'.$this->fileSizeOutput);
        shell_exec('find ./'.$this->outputFileName .' -name "*" -and -path "*.svn*" -prune -o -name "*" | xargs wc -l > '.$this->outputFileName .'/'.$this->lineOutput);
    }

    /**
     *  分析專案Clone下來後檔案資料
     *
     *  @return $fileSize 該專案檔案大小
    **/
    public function crawlFilSize()
    {
        $handleSize = fopen($this->outputFileName.'/'.$this->reverseFileSizeOutput, "r");
        if ($handleSize) {
            if (($str = fgets($handleSize, 4096)) !== false) {

                $tmpFileSize =  explode(" ", $str);
                $fileSize = round(($tmpFileSize[0] / 1000), 2);

                fclose($handleSize);

                return $fileSize;
            }
        }
    }

    /**
     *  分析專案Clone下來後作者及commit資料
     *
     *  @return $dataAry 該專案共有幾位作者及總commit次數
    **/
    public function crawlAuthorAndCommit()
    {
        $fileContent = file_get_contents($this->outputFileName.'/'.$this->logOutput);
        $maxCommit = 0;
        $authorAry = array();
        $totalAuthor = 0;
        $dataAry = array();

        $logAry = explode('------------------------------------------------------------------------', $fileContent);

        for ($i  = 1; $i < count($logAry); $i++) {
            $isFindAuthor = false;
            $commitAuthorAry = explode('|', $logAry[$i]);

            if (count($commitAuthorAry) > 2) {
                for ($j = 0; $j < count($authorAry); $j++) {
                    if (!strcmp($commitAuthorAry[1], $authorAry[$j])) {
                        $isFindAuthor = true;
                        break;
                    }
                }

                if (!$isFindAuthor) {
                    $authorAry[] = $commitAuthorAry[1];
                }
            }

            $commit = substr(trim($commitAuthorAry[0]), 1);

            if ($commit > $maxCommit) {
                $maxCommit = $commit;
            }
        }

        $totalAuthor = count($authorAry);
        $dataAry  = array('totalAuthors' => $totalAuthor, 'totalCommit' => $maxCommit);

        return $dataAry;
    }

    /**
     *  分析專案Clone下來後檔案資料
     *
     *  @return $finalLine 該專案行數
    **/
    public function crawlLine()
    {
        $handleLine = fopen($this->outputFileName.'/'.$this->lineOutput, "r");
        $contents = '';
        if ($handleLine) {
            $totalLine = 0;
            $svnLine = 0;
            while (!feof($handleLine)) {
                $svnStr = '.svn';
                $contents = fgets($handleLine, 1024);
                $subStr = substr(trim($contents), -4);

                if (!strcmp($svnStr, $subStr)) {
                    preg_match('/\d+ ./', trim($contents), $matches);
                    if (count($matches) > 0) {
                        $line = substr(trim($matches[0]), 0, -2);
                        $svnLine += $line;
                    }
                } else {
                    preg_match('/\d+ total/', trim($contents), $matches);

                    if (count($matches) > 0) {
                        $line = substr(trim($matches[0]), 0, -6);
                        $totalLine += $line;
                    }
                }
            }

            fclose($handleLine);
            $finalLine = $totalLine - $svnLine;

            return $finalLine;
        }
    }

    /**
     *  取得專案貢獻狀況資料
     *
     *
     * @return $changeAry 總create delete and modify數量
    **/
    public function crawlContribute()
    {
        $fileContent = file_get_contents($this->outputFileName.'/'.$this->logOutput);
        $logAry = explode('------------------------------------------------------------------------', $fileContent);
        $changeAry = array('A' => 0, 'D' => 0, 'M' => 0);

        for ($i = 1; $i < count($logAry); $i++) {
            preg_match_all('/[ADM] \//', $logAry[$i], $matches);

            for ($j = 0; $j < count($matches[0]); $j++) {
                $chageStr = trim(substr($matches[0][$j], 0, 1));
                $changeAry[$chageStr]++;
            }
        }

        return $changeAry;

    }

    private function getFileCount()
    {
        $result = exec("wc $this->outputFileName/$this->lineOutput");
        $arr = explode(" ", trim($result));

        return (int) $arr[0] - 6;
    }
}
