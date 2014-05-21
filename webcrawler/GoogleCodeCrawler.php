<?php
/**
 * A crawler for GoogleCode
 *
 * PHP version 5
 *
 * LICENSE : none
 *
 * @category WebCrawler
 * @package  Wisecamera
 * @license  none <none>
 */
namespace wisecamera;

class GoogleCodeCrawler extends WebCrawler
{
    private $projectName = "";
    private $ch= "";
    private $html= "";
    private $baseUrl = 'https://code.google.com/p/';
    private $baseLoginUrl= "";
    private $baseIssueUrl= "";
    private $totalIssues = 0; // 總total issue數
    private $totalIssuesDiscuss = 0; // 所有issue 討論總數
    private $user_agent = 'Mozilla/5.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.0.3705; .NET CLR 1.1.4322)';
    private $blockTagAry = array();
    private $issueReplyAuthor = array();

    /**
    *  初始化所有變數
    *
    *  $this->projectName : 欲爬的專題名稱
    *  $this->baseUrl: 該專題名稱在google code的網址
    *  $this->baseIssueUrl: 該專題的issues網址
    *  $this->baseLoginUrl: 登入google的網址
    *  $this->blockTagAry: 斷行html tag
    *
    **/
    public function __construct($url)
    {
        $arr = explode("/", $url);
        $projectName = $arr[4];
        $this->projectName = $projectName;
        $this->baseUrl = $this->baseUrl.$projectName.'/';
        $this->baseIssueUrl = $this->baseUrl.'issues/list?can=1&q=&colspec=ID+Type+Status+Priority+Milestone+Owner+Summary';
        $this->baseLoginUrl = 'https://accounts.google.com/ServiceLogin?service=code&ltmpl=phosting&continue=https%3A%2F%2Fcode.google.com%2Fp%2F'.$this->projectName.'%2F&followup=https%3A%2F%2Fcode.google.com%2Fp%2F'.$this->projectName.'%2F';
        $this->blockTagAry = array(
            0 => "p",   1 => "li",   2 => "h1",   3 => "h2",  4 => "h3",
            5 => "h4",  6 => "h5",  7 => "h6", 8 => "pre", 9 => "tr",
        );

        $this->ch = curl_init();
        curl_setopt($this->ch, CURLOPT_USERAGENT, $this->user_agent);
        curl_setopt($this->ch, CURLOPT_HEADER, 0);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
    }

    /**
    *   取得登入表格並回傳input 欄位
    *
    *   Argument:
    *   $data: 某個form的html code
    *
    *   Return:
    *   $inputs: 回傳該表單的input欄位
    *
    **/
    private function getFormFields($data)
    {
        if (preg_match('/(<form.*?id=.?gaia_loginform.*?<\/form>)/is', $data, $matches)) {
            $inputs = $this->getInputs($matches[1]);

            return $inputs;
        } else {
            die('didnt find login form');
        }
    }

    /*
    *   從某個表單取得input欄位
    *
    *   Argument:
    *   $form: 某個表單的html code
    *
    *   Return:
    *   $inputs: input欄位, 為一個陣列
    *
    **/
    private function getInputs($form)
    {
        $inputs = array();

        $elements = preg_match_all('/(<input[^>]+>)/is', $form, $matches);

        if ($elements > 0) {
            for ($i = 0; $i < $elements; $i++) {
                $el = preg_replace('/\s{2,}/', ' ', $matches[1][$i]);

                if (preg_match('/name=(?:["\'])?([^"\'\s]*)/i', $el, $name)) {
                    $name  = $name[1];
                    $value = '';

                    if (preg_match('/value=(?:["\'])?([^"\'\s]*)/i', $el, $value)) {
                        $value = $value[1];
                    }

                    $inputs[$name] = $value;
                }
            }
        }

        return $inputs;
    }

    /**
    *   取得wiki page的css clase名稱
    *
    *   Return:
    *   $tblTitleClass: 陣列, wiki page的class 名稱
    *
    **/
    private function curlWikiClassName()
    {
        $wikiUrl = $this->baseUrl . 'w/list?colspec=PageName+Summary+Changed+ChangedBy+RevNum+Stars';
        curl_setopt($this->ch, CURLOPT_URL, $wikiUrl);

        $this->html = '';
        $this->html = curl_exec($this->ch);

        $firstCut = explode('thead', $this->html);
        $tmpTblTitleClass = explode('<th class="', $firstCut[1]);
        $tblTitleClass = array();

        for ($i = 1; $i < count($tmpTblTitleClass); $i++) {
            $tmp = explode('"', $tmpTblTitleClass[$i]);
            $tblTitleClass[] = trim($tmp[0]);
            unset($tmp);
        }

        return $tblTitleClass;
    }

    /**
    *   計算該專題的issue總數
    **/
    private function curlIssuesTotal()
    {
        curl_setopt($this->ch, CURLOPT_URL, $this->baseIssueUrl);

        $this->html = '';
        $this->html = curl_exec($this->ch);
        $firstCut = explode('1 - 100', $this->html);
        $tmp = explode('of ', $firstCut[1]);
        $tmp2 = explode('<a', $tmp[1]);
        $this->totalIssues = trim($tmp2[0]);
    }

    /**
    *   取得issue page的css clase名稱
    *
    *   Return:
    *   $tblTitleClass: 陣列, issue page的class 名稱
    *
    **/
    private function curlIssuesClassName()
    {
        curl_setopt($this->ch, CURLOPT_URL, $this->baseIssueUrl);

        $this->html = '';
        $this->html = curl_exec($this->ch);

        $firstCut = explode('thead', $this->html);
        $tmpTblTitleClass = explode('<th class="', $firstCut[1]);
        $tblTitleClass = array();

        for ($i = 1; $i < count($tmpTblTitleClass); $i++) {
            $tmp = explode('"', $tmpTblTitleClass[$i]);
            $tblTitleClass[] = trim($tmp[0]);
            unset($tmp);
        }

        return $tblTitleClass;
    }

    /**
    *   取得download page的css clase名稱
    *
    *   Return:
    *   $tblTitleClass: 陣列, download page的class 名稱
    *
    **/
    private function curlDownloadClassName()
    {
        $downloadUrl = $this->baseUrl . 'downloads/list';

        curl_setopt($this->ch, CURLOPT_URL, $downloadUrl);
        $this->html = '';
        $this->html = curl_exec($this->ch);

        $firstCut = explode('thead', $this->html);
        $tmpTblTitleClass = explode('<th class="', $firstCut[1]);
        $tblTitleClass = array();

        for ($i = 1; $i < count($tmpTblTitleClass); $i++) {
            $tmp = explode('"', $tmpTblTitleClass[$i]);
            $tblTitleClass[] = trim($tmp[0]);
            unset($tmp);
        }

        return $tblTitleClass;
    }

    /**
    *   取得source page的css clase名稱
    *
    *   Return:
    *   $dataAry: 為一個二維陣列
    *       -platForm:  該專題存放於哪個平台
    *       -cloneUrl:  clone該專題的網址列
    *
    **/
    private function curlSourceClassName()
    {
        $sourceUrl = $this->baseUrl . 'source/checkout';

        curl_setopt($this->ch, CURLOPT_URL, $sourceUrl);
        $this->html = '';
        $this->html = curl_exec($this->ch);

        $firstCut = explode('<tt id="checkoutcmd">', $this->html);
        $tmp = explode(' ', $firstCut[1]);

        $dataAry = array('platForm' => $tmp[0],'cloneUrl' => $this->baseUrl);

        return $dataAry;
    }

    /**
    *   登入google
    **/
    private function curlAutoLogin()
    {
        $COOKIEFILE = 'cookies.txt';
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($this->ch, CURLOPT_COOKIEJAR, $COOKIEFILE);
        curl_setopt($this->ch, CURLOPT_COOKIEFILE, $COOKIEFILE);
        curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, 120);
        curl_setopt($this->ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($this->ch, CURLOPT_URL, $this->baseLoginUrl);

        $data = curl_exec($this->ch);

        $formFields = $this->getFormFields($data);

        $formFields['Email']  = 'wisecamerademo@gmail.com';
        $formFields['Passwd'] = 'openfoundry';
        unset($formFields['PersistentCookie']);

        $post_string = '';
        foreach ($formFields as $key => $value) {
            $post_string .= $key . '=' . urlencode($value) . '&';
        }

        $post_string = substr($post_string, 0, -1);

        curl_setopt($this->ch, CURLOPT_URL, 'https://accounts.google.com/ServiceLoginAuth');
        curl_setopt($this->ch, CURLOPT_POST, 1);
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $post_string);

        $result = curl_exec($this->ch);
        $finalResult = "";

        return $result;
    }

    /**
    *   計算特定wiki有幾行
    *
    *   Arguments:
    *   $url:   特定wiki的網址
    *
    *   Return:
    *   $totalLine: 該wiki有幾行
    *
    **/
    private function getWikiLine($url)
    {
        curl_setopt($this->ch, CURLOPT_URL, $url);
        $html = curl_exec($this->ch);
        $tmp = explode("id=\"wikimaincol\"", $html);
        $tmp2 = explode("</div>", $tmp[1]);

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument;
        $dom->loadHTML($tmp2[0]);
        $tags = $dom->getElementsByTagName('*');

        $count_tag = array();
        /*   count tags in this html page and how many times this tag shows up   */
        foreach ($tags as $tag) {
            $isTagExist = array_key_exists($tag->tagName, $count_tag);

            if ($isTagExist) {
                $count_tag[$tag->tagName] += 1;
            } else {
                $count_tag[$tag->tagName] = 1;
            }
        }

        $totalLine = 0;
        /*   compare tags in html is same as block tag   */
        foreach ($this->blockTagAry as $index => $blockTag) {
            foreach ($count_tag as $tag => $num) {
                if (!strcmp($blockTag, $tag)) {
                    $totalLine += $num;
                }
            }
        }

        return $totalLine;
    }

    /**
    *   取得issue討論資訊，如作者、討論篇數
    *
    *   Argument:
    *   $id:某篇issue編號
    *
    **/
    private function getIssueDiscuss($id)
    {
        $url = $this->baseUrl."issues/detail?id=$id&can=1";
        curl_setopt($this->ch, CURLOPT_URL, $url);

        $html = curl_exec($this->ch);

        $tmpReplyAry = explode('<span class="author">', $html);

        $this->totalIssuesDiscuss += count($tmpReplyAry);

        for ($i = 1; $i < count($tmpReplyAry); $i++) {
            $tmpReplyAuthor = explode('</a>', $tmpReplyAry[$i]);
            $replyAuthor = explode('>', $tmpReplyAuthor[1]);

            if (!in_array(substr($replyAuthor[1], 0, -4), $this->issueReplyAuthor)) {
                $this->issueReplyAuthor[] = substr($replyAuthor[1], 0, -4);
            }
        }
    }

    /*
    *   取得該專題所有wiki名稱
    *
    *   Return:
    *   $pageNames: 陣列, 該專題所有wiki名稱
    *
    **/
    public function curlWikiPageName()
    {
        $classNameAry = $this->curlWikiClassName();
        $explodStr = 'vt id '.$classNameAry[0];
        $tmpPageName = explode($explodStr, $this->html);
        $pageNames = array();
        for ($i = 1; $i <  count($tmpPageName); $i++) {
            $tmp = explode('</a>', $tmpPageName[$i]);
            $tmp2 = explode('>', $tmp[0]);
            $pageNames[] = trim($tmp2[2]);
            unset($tmp);
            unset($tmp2);
        }

        return $pageNames;
    }

    /*
    *   取得wiki所有相關資訊pages、status count 和update數
    *
    *   Arguments:
    *   $wiki: wiki class
    *   $wikiList:  wiki class array
    *
    */
    public function getWiki(Wiki &$wiki, array &$wikiList)
    {
        $pageName = $this->curlWikiPageName();
        $classNameAry = $this->curlWikiClassName();
        $explodStr = 'vt '.$classNameAry[4];
        $tmpRevNum = explode($explodStr, $this->html);
        $wikis = array();

        $totalUpdate = 0;
        $totalLine = 0;
        for ($i = 1; $i <  count($tmpRevNum); $i++) {
            $tmp = explode('</a>', $tmpRevNum[$i]);
            $revNum = explode('>', $tmp[0]);

            $idx = $i - 1;

            $url = $this->baseUrl.'wiki/'.$pageName[$idx];
            $line = $this->getWikiLine($url);

            $wikiPage = new WikiPage();
            $wikiPage->url = $url;
            $wikiPage->line = $line;
            $wikiPage->title = $pageName[$idx];
            //TODO update times
            $wikiPage->update = 0;

            $totalUpdate += $wikiPage->update;
            $totalLine += $wikiPage->line;

            $wikiList []= $wikiPage;

            unset($tmp);
            unset($revNum);
        }

        $wiki->pages =  count($tmpRevNum);
        $wiki->line = $totalLine;
        $wiki->update = $totalUpdate;
    }

    /**
    *   取得issue所有相關資訊topic、lines、account數 and article
    *
    *   Arguments:
    *   $issue: issue class
    *
    */
    public function getIssue(Issue &$issue)
    {
        $this->curlIssuesTotal();
        $classNameAry = $this->curlIssuesClassName();
        $idExplodStr = 'vt id '.$classNameAry[0];
        $statusExplodStr = 'vt '.$classNameAry[2];
        $dataInterval = 99;
        $status = array();

        for ($i = 0; $i < $this->totalIssues; $i += $dataInterval) {
            $url = $this->baseIssueUrl ."&num=$dataInterval&start=$i";
            curl_setopt($this->ch, CURLOPT_URL, $url);

            $this->html = '';
            $this->html = curl_exec($this->ch);

            $tmpID = explode($idExplodStr, $this->html);
            $tmpStatus = explode($statusExplodStr, $this->html);

            $open = 0;
            $close = 0;
            for ($j = 1; $j <  count($tmpStatus); $j++) {

                $tmpIDAry = explode('</a>', $tmpID[$j]);
                $tmpIDAry2 = explode('>', $tmpIDAry[0]);

                $this->getIssueDiscuss(trim($tmpIDAry2[2]));

                $tmpStatusAry = explode('</a>', $tmpStatus[$j]);
                $tmpStatusAry2 = explode('>', $tmpStatusAry[0]);
                $idx = count($status);

                if (! strcmp(trim($tmpStatusAry2[2]), "New")) {
                    $statusStr = "open";
                    ++$open;
                } else {
                    $statusStr = "close";
                    ++$close;
                }

                $status[] = trim($tmpStatusAry2[2]);

                unset($tmpIDAry);
                unset($tmpIDAry2);
                unset($tmpStatusAry);
                unset($tmpStatusAry2);
            }
        }

        $issue->topic = $this->totalIssues;
        $issue->open = $open;
        $issue->close = $close;
        //TODO: total account and articles?
        $issue->account = count($this->issueReplyAuthor);
        $issue->article = $this->totalIssuesDiscuss;
    }

    /**
    *   取得該專題所有download名稱
    *
    *   Return:
    *   $downloadName: 陣列, 該專題所有download名稱
    *
    **/
    public function curlDownloadName()
    {
        $classNameAry = $this->curlDownloadClassName();
        $explodStr = 'vt id '.$classNameAry[0];
        $tmpDownloadName = explode($explodStr, $this->html);
        $downloadName = array();

        for ($i = 1; $i <  count($tmpDownloadName); $i++) {
            $tmp = explode('</a>', $tmpDownloadName[$i]);
            $tmp2 = explode('>', $tmp[0]);
            $downloadName[] = trim($tmp2[2]);
            unset($tmp);
            unset($tmp2);
        }

        return $downloadName;
    }

    /**
    *   取得download名稱及位置
    *
    *   Arguments:
    *   $download: A downunit class array
    *
    **/
    public function getDownload(array &$donwload)
    {
        $downloadName = $this->curlDownloadName();
        $classNameAry = $this->curlDownloadClassName();
        $explodStr = 'vt '.$classNameAry[5];
        $tmpDownloadNum = explode($explodStr, $this->html);
        $downloadDetailUrl = $this->baseUrl . 'downloads/detail';
        $downloads = array();

        for ($i = 1; $i <  count($tmpDownloadNum); $i++) {
            $tmp = explode('</a>', $tmpDownloadNum[$i]);
            $tmp2 = explode('>', $tmp[0]);

            $idx = $i - 1;
            $url = $downloadDetailUrl.'name='.$downloadName[$idx];

            $downloadUnit = new Download();
            $downloadUnit->name = $downloadName[$idx];
            $downloadUnit->url = $url;
            $downloadUnit->count = (int) trim($tmp2[2]);
            $donwload []= $downloadUnit;

            unset($tmp);
            unset($tmp2);
        }
    }

    /**
    *   取得使用者評分
    *
    *   Arguments:
    *   $rating: Rating class
    *
    **/
    public function getRating(Rating &$rating)
    {
        $html = $this->curlAutoLogin();
        $tmp = explode('<span id="star_count">', $html);
        $star =explode("<", $tmp[1]);

        $rating->star = (int) $star[0];
    }

    /*
    * Get Source Information
    **/
    public function curlSource()
    {
        $dataAry = $this->curlSourceClassName();

        return $dataAry;
    }

    public function getRepoUrl(&$type, &$url)
    {
        if ($type == 'SVN' || $type == 'Git') {
            $dataAry = $this->curlSourceClassName();

            return $dataAry['cloneUrl'];
        } else {
            return 'none';
        }
    }
}
