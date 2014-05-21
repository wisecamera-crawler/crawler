<?php
/**
 * A crawler for Sourceforge.net
 *
 * PHP version 5
 *
 * LICENSE : none
 *
 * @category WebCrawler
 * @package  Wisecamera
 * @author   Shannon Chang <shchang@gmail.com>
 * @license  none <none>
 * @version  GIT: <git_id>
 * @link     none
 */


/**
 * A crawler for Sourceforge.net
 *
 * LICENSE : none
 *
 * @category WebCrawler
 * @package  Wisecamera
 * @author   Shannon Chang <shchang@gmail.com>
 * @license  none <none>
 * @version  Release: <package_version> 
 * @link     none
 */

namespace wisecamera;

class SourceForgeCrawler extends WebCrawler
{
    private $id;
    private $baseUrl;
    public function __construct($url)
    {
        $this->baseUrl = $url;
        $arr = explode("/", $url);
        $this->id = $arr[4];
    }

    /**
     * SourceForge issue crawler
     *
     * This function access SourceForge website and get
     * issues info.
     *
     * @param Issue class $issue  {int topic ,int article, int account} 
     *
     * @return none, show the result through echo
     *
     * @author Shannon Chang <shchang@gmail.com>
     * @version 1.0
     */
    public function getIssue(Issue & $issue)
    {
        $url="http://sourceforge.net/p/$this->id/bugs/search/?q=status%3Aopen+or+status%3Aclosed&limit=100&page=0";

        $time_out_count=0;
        while ($time_out_count<3&&($issueMainPage = WebUtility::getHtmlContent($url))===false) {
            $time_out_count++;
        }
        //Need to take care sometimes will get internal server error
        preg_match_all('/results of (\d*) <\/strong><\/p>/', $issueMainPage, $total_array);
        if (sizeof($total_array[1])>0) {
            $comments=0;
            $authors=array();
            $total_issue=$total_array[1][0];
            preg_match_all('/<td><a href="\/p\/.*\/bugs\/\d*\/">(\d*)<\/a><\/td>/', $issueMainPage, $issue_array);
        
            foreach ($issue_array[1] as $current_issue_no) {
                $comments+=$this->traverseIssues(
                    "http://sourceforge.net/p/$this->id/bugs",
                    $current_issue_no,
                    $authors
                );
            }

            $page_counter=floor($total_issue/100);//take the number without float, read issues page by page
            for ($i=1; $i<$page_counter; $i++) {
                $url="http://sourceforge.net/p/$this->id/bugs/search/
                ?q=status%3Aopen+or+status%3Aclosed&limit=100&page=$i";
                $time_out_count=0;
                while ($time_out_count<3&&($issueMainPage = WebUtility::getHtmlContent($url))===false) {
                    $time_out_count++;
                }
                preg_match_all('/<td><a href="\/p\/.*\/bugs\/\d*\/">(\d*)<\/a><\/td>/', $issueMainPage, $issue_array);
                foreach ($issue_array[1] as $current_issue_no) {
                    $comments+=$this->traverseIssues(
                        "http://sourceforge.net/p/$this->id/bugs",
                        $current_issue_no,
                        $authors
                    );
                }
            }
            $issue->topic = $total_issue;
            $issue->article = $comments;
            $issue->account= sizeof($authors);
        }
    }

    /**
     * SourceForge Wiki crawler
     *
     * This function access SourceForge website and get
     * Wiki info.
     *
     * @param Wiki class wiki  
     * @param array WikiPageList array ofWikiPage class{string title, string url,
     *  string update, string line} 
     *
     * @return none, show the result through echo
     *
     * @author Shannon Chang <shchang@gmail.com>
     * @version 1.0
     */
    public function getWiki(Wiki & $wiki, array & $wikiPageList)
    {
        $url = "http://sourceforge.net/p/$this->id/wiki/browse_pages/";
        $txt = WebUtility::getHtmlContent($url);
        preg_match_all('/<td><a href="\/p\/(.*)\/wiki\/(.*)\/">(.*)<\/a><\/td>/', $txt, $authRandnumPic);

        $totalUpdate = 0;
        $totalLine = 0;

        foreach ($authRandnumPic[0] as $value) {
            $wikiPage = new WikiPage();
            $_array=explode("/", $value);
            $wiki_page_name_array = explode("\"", $_array[4]);
            $wiki_page_name = $wiki_page_name_array[0];

            $wikiPage->title = $wiki_page_name;
            $wikiPage->url = "http://sourceforge.net/p/$this->id/wiki/$wiki_page_name/";
            $wikiPage->update = $this->fetchSFWikiUpdate($this->id, $wiki_page_name);
            $wikiPage->line = $this->fetchSFWikiLineCount($this->id, $wiki_page_name);
            $wikiPageList []= $wikiPage;
            $totalUpdate += $wikiPage->update;
            $totalLine += $wikiPage->line;
        }

        $wiki->pages = sizeof($authRandnumPic[0]);
        $wiki->line = $totalLine;
        $wiki->update = $totalUpdate;
    }

    /**
     * SourceForge Rating crawler
     *
     * This function access SourceForge website and get
     * rating info.
     *
     * @param Rating class $rating {int fiveStar, int fourStar, int threeStar,
     * int twoStar, int oneStar}
     *
     * @return none, show the result through echo
     *
     * @author Shannon Chang <shchang@gmail.com>
     * @version 1.0
     */
    public function getRating(Rating & $rating)
    {
        $URL = "http://sourceforge.net/projects/$this->id/reviews?source=navbar";
        $txt = WebUtility::getHtmlContent($URL);
        preg_match('/<div class="stars-5" style="border-left-width: \d*px">(.*?)<\/div>/si', $txt, $rank5);
        preg_match('/<div class="stars-4" style="border-left-width: \d*px">(.*?)<\/div>/si', $txt, $rank4);
        preg_match('/<div class="stars-3" style="border-left-width: \d*px">(.*?)<\/div>/si', $txt, $rank3);
        preg_match('/<div class="stars-2" style="border-left-width: \d*px">(.*?)<\/div>/si', $txt, $rank2);
        preg_match('/<div class="stars-1" style="border-left-width: \d*px">(.*?)<\/div>/si', $txt, $rank1);
        $rank5[1]=str_replace(array("\r","\n","\t"," "), '', $rank5[1]);
        $rank4[1]=str_replace(array("\r","\n","\t"," "), '', $rank4[1]);
        $rank3[1]=str_replace(array("\r","\n","\t"," "), '', $rank3[1]);
        $rank2[1]=str_replace(array("\r","\n","\t"," "), '', $rank2[1]);
        $rank1[1]=str_replace(array("\r","\n","\t"," "), '', $rank1[1]);
        
        if (sizeof($rank5[1])>0) {
            $rating->fiveStar = $rank5[1];
            $rating->fourStar = $rank4[1];
            $rating->threeStar = $rank3[1];
            $rating->twoStar = $rank2[1];
            $rating->oneStar = $rank1[1];
        }
    }
    
    /**
     * SourceForge file download count crawler
     *
     * This function access SourceForge website and get
     * file download info. Trace each file tree by calling
     * traverseDL function.
     *
     * @param array $download download class{string name, string url, int count}
     *
     * @return none, show the result through echo
     *
     * @author Shannon Chang <shchang@gmail.com>
     * @version 1.0
     */
    public function getDownload(array & $download)
    {
        $url = "http://sourceforge.net/projects/$this->id/files/";
        $DLMainPage = WebUtility::getHtmlContent($url);

        preg_match_all('/<tr title="(.*)" class="folder ">/', $DLMainPage, $DL_array);
        //Need to add folder/file check, if file, just add dl count into $DL_count_array
        if (sizeof($DL_array[1])>0) {
            $this->traverseDL($url, $DL_array[1], $download);
        }
    }

    /**
     * SourceForge repository url crawler
     *
     * This function access SourceForge website and get
     * repository.
     *
     * @param string $type   Project repository type
     *
     * @return url to download repository
     *
     * @author Shannon Chang <shchang@gmail.com>
     * @version 1.0
     */

    public function getRepoUrl(&$type, &$url)
    {
        $Code_url = "http://sourceforge.net/p/$this->id/code/";
        $CodePage = WebUtility::getHtmlContent($Code_url);
        preg_match_all('/<a href="#" class="btn" data-url="(.*)" title="Read Only">/', $CodePage, $Code_array);
        if (sizeof($Code_array[1])>0) {
            $url_array=explode(" ", $Code_array[1][0]);
            $url=$url_array[2]." ".$url_array[3];
            $Code_type=$url_array[0];
            switch ($Code_type) {
                case "git":
                    $type="Git";
                    break;

                case "svn":
                    $type="SVN";
                    break;

                case "hg":
                    $type="HG";
                    break;
            }
        }
    }


    /**
     * SourceForge Wiki update counter
     *
     * This function access SourceForge website and get
     * Wiki update counter.
     *
     * @param string $PROJECT   Project name
     * @param string $WIKINAME Project Wiki title
     *
     * @return size of updateTimes array
     *
     * @author Shannon Chang <shchang@gmail.com>
     * @version 1.0
     */
    private function fetchSFWikiUpdate($PROJECT, $WIKINAME)
    {
        $URL = "http://sourceforge.net/p/$PROJECT/wiki/$WIKINAME/history";
        $txt = WebUtility::getHtmlContent($URL);
        preg_match_all('/<span title/', $txt, $updateTimes);
        if (sizeof($updateTimes[0])>0) {
            return sizeof($updateTimes[0]);
        } else {
            return -2;
        }
    }

    /**
     * SourceForge Wiki Line counter
     *
     * This function access SourceForge website and get
     * Line count in Wiki content.
     *
     * @param string $PROJECT   Project name
     * @param string $WIKINAME Project Wiki title
     *
     * @return size of lines_array
     *
     * @author Shannon Chang <shchang@gmail.com>
     * @version 1.0
     */
    private function fetchSFWikiLineCount($PROJECT, $WIKINAME)
    {
        $wikipage = WebUtility::getHtmlContent("http://sourceforge.net/p/$PROJECT/wiki/$WIKINAME/");
        //$wikipage = file_get_contents("http://sourceforge.net/p/$PROJECT/wiki/$WIKINAME/");
        $wikipage = str_replace(array("\r\n","\r","\n"), "", $wikipage);
        $wikipage = str_replace(array("\r\n","\r","\n"), "", $wikipage);
        preg_match('/<div class="editbox">(.*)<div id="comment">/', $wikipage, $wiki_array);
        if (sizeof($wiki_array[1])>0) {
            $wiki_array[1]=str_replace("<br />", "<p>", $wiki_array[1]);
            //make <br> as <p>,so that we can count lines easier
            $lines_array=explode("<p>", $wiki_array[1]);
            return sizeof($lines_array);
        } else {
            return -2;
        }
    }

    /**
     * SourceForge file download counter crawler subfunction
     *
     * This function access SourceForge website and get
     * file download info, which called by getDL funciton.
     *
     * @param string $baseUrl specify where this function to read
     * @param array $previous_DL_array file/folder name array
     * @param array $dlList array of download class{string name,
     * string url, int count}
     *
     * @return none, show the result through echo
     *
     * @author Shannon Chang <shchang@gmail.com>
     * @version 1.0
     */
    private function traverseDL($baseUrl, &$previous_DL_array, &$dlList)
    {
        foreach ($previous_DL_array as $DL_item) {
            $url=$baseUrl.$DL_item."/";
            $DLMainPage = WebUtility::getHtmlContent($url);
            //$DLMainPage = file_get_contents($url);

            preg_match_all('/<tr title="(.*)" class="folder ">/', $DLMainPage, $DL_array);
            if (sizeof($DL_array[1])>0) {
                $this->traverseDL($url, $DL_array[1], $dlList);//keep trace file if it is a folder
            }
            preg_match_all('/<tr title="(.*)" class="file ">/', $DLMainPage, $file_array);
            preg_match_all(
                '/ \S*<a href="(\S*\/stats\/timeline)" class="fs-stats ui-corner-all file">/',
                $DLMainPage,
                $URL_array
            );
            //URL_array[1] contains each file's url without http://sourceforge.net
            preg_match_all('/<a href="(http\S*download)"/', $DLMainPage, $Link_array);
            //Link_array contains the link to file
            if (sizeof($file_array[1])>0) {
                for ($i=0; $i<sizeof($file_array[1]); $i++) {
                    //Output file name, download count, file link
                    if (!(array_key_exists($i, $URL_array[1]))) {
                        //Some file don't have download count
                        $URL_array[1][$i]='';
                    }
                    $download = new Download();
                    $download->name = $file_array[1][$i];
                    $download->url = $Link_array[1][$i];
                    $download->count =  $this->getSingleFileDLcountsPage($URL_array[1][$i]);
                    $dlList []= $download;
                }
            }
        }
    }

    /**
     * SourceForge file download count crawler subfunction
     *
     * This function access SourceForge website and get
     * single file download info, which called by traverseDL
     * function.
     *
     * @param string $FileURL Specify the file we want to get 
     * download counter.
     *
     * @return DLcounts_array[1], which is total download count
     *
     * @author Shannon Chang <shchang@gmail.com>
     * @version 1.0
     */
    private function getSingleFileDLcountsPage($FileURL)
    {
        if ($FileURL=='') {
            return 0;
        }
        $URL='http://sourceforge.net'.$FileURL;
        $Last_day=date("Y-m-d");
        $URL=$URL."?dates=1999-1-1+to+".$Last_day;
        //$DLcountsPage = file_get_contents($URL);
        $DLcountsPage = WebUtility::getHtmlContent($URL);
        if (preg_match('/<td headers="files_downloads_h">(\S*)<\/td>/', $DLcountsPage, $DLcounts_array)) {
            return ParseUtility::intStrToInt($DLcounts_array[1]);
        } else {
            return 0;
        }
    }

    /**
     * SourceForge Issue crawler
     *
     * This function access SourceForge website and get
     * issues info.
     *
     * @param string $baseUrl Specify the project 
     * @param string $issue_no issue number
     * @param array $authors array to keep authors info
     *
     * @return totalComment, summary of all comments count
     *
     * @author Shannon Chang <shchang@gmail.com>
     * @version 1.0
     */
    private function traverseIssues($baseUrl, $issue_no, &$authors)
    {
        $totalComments = 0;
        //$html = file_get_contents($baseUrl . "/" . $issue_no);
        $html = WebUtility::getHtmlContent($baseUrl . "/" . $issue_no."/");
        $totalComments += $this->getCommentCountInSingleIssuePage($html);
        $this->getAuthorCountInSingleIssuePage($html, $authors);
        return $totalComments;
    }

    /**
     * SourceForge single issue comment counter
     *
     * This function access SourceForge website and get
     * single issues comment counter info, which called
     * by traverseIssues function.
     *
     * @param string $html Issue page content
     *
     * @return count of comments of this issue
     *
     * @author Shannon Chang <shchang@gmail.com>
     * @version 1.0
     */
    private function getCommentCountInSingleIssuePage($html)
    {
        return preg_match_all('/<div class="display_post">/', $html, $unsue);
    }

    /**
     * SourceForge Issue comment author counter 
     *
     * This function access SourceForge website and get
     * single issues author count info.
     *
     * @param string $html Issue page content
     * @param array authorArr array keep author info
     *
     * @return size of author info array
     *
     * @author Shannon Chang <shchang@gmail.com>
     * @version 1.0
     */
    private function getAuthorCountInSingleIssuePage($html, &$authorArr = null)
    {
        if ($authorArr == null) {
            $authorArr = array();
        }
        $localAuthorArr = array();

        $htmlArr = explode("\n", $html);
        $i = 0;
        foreach ($htmlArr as $line) {
            if ($line === '       <p class="gravatar">') {
                $author = trim(strip_tags($htmlArr[$i+14]));
                $authorArr[$author] = 0;
                $localAuthorArr[$author] = 0;
            }
            ++$i;
        }
        return sizeof($localAuthorArr);
    }
}
