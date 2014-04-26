<?php

class GitStat
{
    
    public function __construct($projectId)
    {
        $this->projectId = $projectId;
        exec("cd repo; cd $this->projectId; git pull", $arr);
    }

    public function getSummary(VCS & $vcs)
    {   
        $path = "repostat/gitstat";
        exec("gitstats repo/$this->projectId test1");
        exec("php $path/crawlerFilesAndLines.php", $outputFilesAndLines);

        $vcs->user = $this->getAuthor();
        $vcs->commit = $this->getCommit();

        $file = 'test1/files.html';
        $fileContent = file_get_contents($file);

        $tmp = explode('</dd>',$fileContent);
        $tmpFiles= explode('<dd>',$tmp[0]);
        $tmpLines= explode('<dd>',$tmp[1]);
        $tmpFileSize= explode('<dd>',$tmp[2]);
        //to MB
        $totalFileSize = number_format( ($tmpFileSize[1] / 1000 ) , 4) * $tmpFiles[1] ;
        //$totalFileSize = number_format( $totalFileSize, 2) ;

        $vcs->file = $tmpFiles[1];
        $vcs->line = $tmpLines[1];
        $vcs->size = $totalFileSize;
        exec("rm test1 -rf");
    }

    public function getDataByCommiters(array & $commiters)
    {
        define("CREATE", "create");
        define("DELETE", "delete");
        define("MODIFY", "modify");

        exec("cd repo; cd $this->projectId; 
            git log --stat --summary  > ../../output.txt");
        $file = 'output.txt';
        $fileContent = file_get_contents($file);
        $numOfActions = 0;
        $numOfFileChg = 0;

        $tmp = explode('Author: ',$fileContent);

        for($i = 1; $i < count($tmp); $i++)
        {
            $index = $i - 1;
    
            // Get Author
            $tmpAuthors = explode('<',$tmp[$i]);
            $authors[$index] = $tmpAuthors[0];
    
            // Get the number of file change
            $tmpFileChg = explode(' changed',$tmp[$i]);
            $tmpFileChg2 = explode(' file',$tmpFileChg[0]);
    
            $j = 0;
    
            $str = substr($tmpFileChg2[$j],-1,1);
            $isInt = $this->isInt($str);
    
            while(!$isInt)
            {
                $j++;
                $str = substr($tmpFileChg2[$j],-1,1);
                if($str == '') 
                {
                    $str = 0;
                    break;
                }
                else
                    $isInt = $this->isInt($str);
            }
    
            $aryNumOfFileChg[$index] = $str;
    
            /* Get file change actions */
            $tmp4 = explode(' mode',$tmp[$i]);
            $aryActions[$index]['create'] = 0;
            $aryActions[$index]['delete'] = 0;
            $aryActions[$index]['modify'] = 0;
    
            for($k = 0; $k <= count($tmp4) - 1; $k++)
            {
                $action = substr($tmp4[$k],-6,6);
        
                if($aryNumOfFileChg[$index] !== 0)
                {
                    if( $action !== CREATE AND $action !== DELETE)
                        $action = 'modify';
                    $aryActions[$index][$action]++;
                }
            }
        }

        //group by authors
        $outArray = array();
        for($i = 0; $i < count($authors); ++$i) {
            if(!isset($outArray[$authors[$i]])) {
                $outArray[$authors[$i]] = array();
                $outArray[$authors[$i]]['create'] = $aryActions[$i]['create'];
                $outArray[$authors[$i]]['modify'] = $aryActions[$i]['modify'];
                $outArray[$authors[$i]]['delete'] = $aryActions[$i]['delete'];
            }
            else {
                $outArray[$authors[$i]]['create'] += $aryActions[$i]['create'];
                $outArray[$authors[$i]]['modify'] += $aryActions[$i]['modify'];
                $outArray[$authors[$i]]['delete'] += $aryActions[$i]['delete'];
            }
        }
        
        //dump into vcsList
        foreach($outArray as $name => $c) {
            $v = new VCSCommiter();
            $v->commiter = $name;
            $v->modify = $c["modify"];
            $v->delete = $c["delete"];
            $v->new = $c["create"];
            $commiters []= $v;
        }

        exec("rm output.txt");
    }

    private function getAuthor()
    {
        $file = 'test1/authors.html';
        $fileContent = file_get_contents($file);

        $tmp = explode('</table>',$fileContent);
        $tmp2 = explode('<td>',$tmp[0]);

        $num = 0 ;
        for($i = 1; $i < count($tmp2); $i += 9)
        {
            $num++;
        }
        return $num;
    }

    private function getCommit()
    {
        $file = 'test1/activity.html';
        $fileContent = file_get_contents($file);

        $tmp = explode('<div class="vtable">',$fileContent); 
        $tmp2 = explode('<td>',$tmp[4]);

        $num = 0 ;
        for($i = 1;$i < count($tmp2); $i += 4)
        {
            $commitNum = explode('(',$tmp2[($i+1)]);
            $num += $commitNum[0];
        }
        return $num;
    }
    
    private function isInt($str)
    {
        $ascii = ord($str);
    
        if( $ascii > 57 OR $ascii < 48) 
            return false;
        else
            return true;
    }
}

