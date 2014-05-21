<?php
/**
 * WebCralwerFactory.php : Factory of WebCralwer
 *
 * PHP version 5
 *
 * LICENSE : none
 *
 * @dependency GithubCrawler.php
 *             OpenfoundryCrawler.php
 *             SourceForgeCrawler.php
 *             GoogleCodeCrawler.php
 *
 * @author   Poyu Chen <poyu677@gmail.com>
 */
namespace wisecamera;

/**
 * WebCrawlerFactory
 *
 * Factory for WebCrawlers
 * If implementing new WebCralwer,
 * please add the class to the factory.
 */
class WebCrawlerFactory
{
    /**
     * factory
     *
     * The factory gen the WebCrawler instance
     *
     * @param string $url   URL of the project
     *
     * @return WebCrawler   The WebCrawler instatnce
     */
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
