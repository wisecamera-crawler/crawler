<?php
namespace wisecamera;

class WebCrawlerFactory
{
    public static function factory($url)
    {
        if (strpos($url, "github") !== false) {
            return new GithubCrawler($url);
        }
        
        if (strpos($url, "openfoundry") !== false) {
            return new OpenfoundryCrawler($url);
        }

        if (strpos($url, "sourceforge") !== false) {
            return new SourceForgeCrawler($url);
        }

        if (strpos($url, "google") !== false) {
            return new GoogleCodeCrawler($url);
        }
    }
}
