<?php
namespace wisecamera;

class WebCrawlerFactory
{
    static public function factory($url)
    {
        if (strpos($url, "github") !== false) {
            return new GithubCrawler($url);
        }
        
        if (strpos($url, "openfoundry") !== false) {
            return new OpenfoundryCrawler($url);
        }
    }
}
