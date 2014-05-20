<?php
namespace wisecamera;

class GitHubCrawler extends WebCrawler
{
    private $baseUrl;

    public function __construct($url)
    {
        $this->baseUrl = $url;
    }

    public function getIssue(Issue & $issue)
    {
        $gi = new GitHubIssue($this->baseUrl . "/issues");
        $issue->open = $gi->getOpenIssue();
        $issue->close =  $gi->getCloseIssue();
        $issue->topic = $gi->getTotalIssue();
        $gi->traverseIssues($issue->article, $issue->account);
    }

    public function getWiki(Wiki & $wiki, array & $wikiPageList)
    {
        $url = $this->baseUrl . "/wiki/_pages";
        $txt = WebUtility::getHtmlContent($url);
        $tmp = explode("/", $this->baseUrl);
        $name = $tmp[3];
        $project = $tmp[4];
        preg_match_all(
            '/<td class="content">.*<\/td>/',
            $txt,
            $matches
        );
        $totalUpdate = 0;
        $totalLine = 0;

        foreach ($matches[0] as $match) {
            $wikiPage = new WikiPage();

            $title = trim(strip_tags($match));
            $pageUrl = str_replace(" ", "-", $title);
            
            $update = $this->fetchGitHubWikiUpdate($pageUrl);
            $line = $this->fetchGitHubWikiLineCount($pageUrl);

            $totalUpdate += $update;
            $totalLine += $line;

            $wikiPage->url = $this->baseUrl . "/wiki/$pageUrl";
            $wikiPage->line = $line;
            $wikiPage->update = $update;
            $wikiPage->title = $title;
            $wikiPageList []= $wikiPage;
        }

        $wiki->pages = sizeof($matches[0]);
        $wiki->line = $totalLine;
        $wiki->update = $totalUpdate;

    }

    public function getRating(Rating & $rating)
    {
        $tmp = explode("/", $this->baseUrl);
        $name = $tmp[3];
        $project = $tmp[4];

        $url = "https://github.com/login";
        $cookie_file = tempnam('./temp', 'cookie');
        $ch = curl_init();
        $user_agent = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/535.19'
            . ' (KHTML, like Gecko) Chrome/18.0.1025.142 Safari/535.19';
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file); //¦scookie

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, true);
        curl_setopt($ch, CURLOPT_CAPATH, "/certificate");
        curl_setopt($ch, CURLOPT_CAINFO, "/certificate/server.crt");

        $txt = curl_exec($ch);
        curl_close($ch);

        preg_match_all(
            '#<input name="authenticity_token" type="hidden" value=.[^>]*>#i',
            $txt,
            $authenticity_token
        );

        $authenticity_token_array=explode("\"", $authenticity_token[0][0]);
        $authvalue = $authenticity_token_array[5];
        $url = "https://github.com/session";

        $ch = curl_init();
        $user_agent = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/535.19'
            . ' (KHTML, like Gecko) Chrome/18.0.1025.142 Safari/535.19';
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file); //¦scookie

        $_POSTDATA["authenticity_token"] = $authvalue;
        $_POSTDATA["login"]="wisecamera777@gmail.com";
        $_POSTDATA["password"]="qazwsxedc123";
        $_POSTDATA["commit"]="Sign in";
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($_POSTDATA));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, true);
        curl_setopt($ch, CURLOPT_CAPATH, "/certificate");
        curl_setopt($ch, CURLOPT_CAINFO, "/certificate/server.crt");
        $txt = curl_exec($ch);
        curl_close($ch);

        $url = "https://github.com/$name/$project/";
        $ch = curl_init();
        $user_agent = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/535.19'
            . ' (KHTML, like Gecko) Chrome/18.0.1025.142 Safari/535.19';
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file); //¦scookie

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, true);
        curl_setopt($ch, CURLOPT_CAPATH, "/certificate");
        curl_setopt($ch, CURLOPT_CAINFO, "/certificate/server.crt");

        $txt = curl_exec($ch);
        curl_close($ch);

        $txt=str_replace(array("\r","\n","\t","\s"), ' ', $txt);
        preg_match('/<a[^>]*href="\/'.$name.'\/'.$project.'\/stargazers"[^>]*>(.*?) <\/a>/si', $txt, $stargazers);
        preg_match('/<a[^>]*href="\/'.$name.'\/'.$project.'\/watchers"[^>]*>(.*?) <\/a>/si', $txt, $watchers);
        preg_match('#<a href="/'.$name.'\/'.$project.'/network" class="social-count">.[^>]*>#i', $txt, $network);

        $stargazers_array=explode(" ", $stargazers[0]);
        $watchers_array=explode(" ", $watchers[0]);
        $tmp=explode(">", $network[0]);

        $network_array=explode("<", $tmp[1]);

        $rating->star = ParseUtility::intStrToInt($stargazers_array[12]);
        $rating->watch = ParseUtility::intStrToInt($watchers_array[12]);
        $rating->fork = ParseUtility::intStrToInt($network_array[0]);
    }

    public function getDownload(array & $downloads)
    {
        $download = null;
    }

    public function getRepoUrl($type)
    {
        return $this->baseUrl;
    }

    private function fetchGitHubWikiUpdate($wikiName)
    {
        $url = $this->baseUrl . "/wiki/$wikiName/_history";
        $txt = WebUtility::getHtmlContent($url);
        preg_match_all('/<tr>/', $txt, $updateTimes);

        return sizeof($updateTimes[0])-1;
    }

    private function fetchGitHubWikiLineCount($wikiName)
    {
        $tmp = explode("/", $this->baseUrl);
        $name = $tmp[3];
        $project = $tmp[4];

        $commad = "python third/getGitHubWikiLine/getGitHubLine.py $name/$project/wiki/$wikiName";
        $output = shell_exec($commad);

        return $output;
    }
}
