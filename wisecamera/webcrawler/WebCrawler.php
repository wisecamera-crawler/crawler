<?php
/**
 * WebCralwer.php : Interface of WebCralwer
 *
 * PHP version 5
 *
 * LICENSE : none
 *
 * @dependency ../utility/DTO.php
 * @author   Poyu Chen <poyu677@gmail.com>
 */
namespace wisecamera\webcrawler;

use wisecamera\utility\DTOs\Download;
use wisecamera\utility\DTOs\Issue;
use wisecamera\utility\DTOs\Rating;
use wisecamera\utility\DTOs\Wiki;
use wisecamera\utility\DTOs\WikiPage;

/**
 * WebCralwer Interface
 *
 * The interface for WebCraweler
 * To implement new target repo host website for this project, tou have to:
 *  1. Implement the class extends from WebCrawler
 *  2. Modify the WebCrawlerFactory (add this class to factory)
 *  3. Remeber to include file
 *
 * @author   Poyu Chen <poyu677@gmail.com>
 */
abstract class WebCrawler
{
    /**
     * Constructor
     *
     * The constructor of WebCrawler class should take the url as parameter
     * This url is the project's main page on the repo host website
     *
     * @param string $url The URL
     */
    abstract public function __construct($url);

    /**
     * getIssue
     *
     * Get the issue information of assigned projects.
     * For developers who want to implement new WebCrawler,
     * you should fill information of the Issue object in this function.
     * Detail of Issue object, please refer to ../utility/DTO/Issue.php
     *
     * @param Issue $issue The issue DTO to fill info
     *
     * @return int Status code, 0 for OK
     */
    abstract public function getIssue(Issue & $issue);

    /**
     * getWiki
     *
     * Get the wiki information of assigned projects.
     * For developers who want to implement new WebCrawler,
     * you should fill in wiki information in the Wiki object and
     * append WikiPage object to $wikiPageList for each wikipages.
     * Detail of Wiki/WikiPage object, please refer to
     * ../utility/DTO/Wiki.php, ../utility/DTO/WikiPage.php
     *
     * @param Wiki  $wiki         The wiki DTO to fill info
     * @param array $wikiPageList Input should be an empty array,
                                    output should be list of WikiPage objects
     *
     * @return int Status code, 0 for OK
     */
    abstract public function getWiki(Wiki & $wiki, array & $wikiPageList);

    /**
     * getRating
     *
     * Get the rating information of assigned projects.
     * For developers who want to implement new WebCrawler,
     * you should fill information of the Rating object in this function.
     * Detail of Issue object, please refer to ../utility/DTO/Rating.php
     * Because there are diffrent rating systems for diffrent website,
     * some field may be ignore.
     *
     * @param Rating $rating The rating DTO to fill info
     *
     * @return int Status code, 0 for OK
     */
    abstract public function getRating(Rating & $rating);

    /**
     * getDownload
     *
     * Get the download information of assigned projects.
     * For developers who want to implement new WebCrawler,
     * you should append Download object to $download list in this function.
     * Detail of Issue object, please refer to ../utility/DTO/Download.php
     *
     * @param array $download List of Download data
     *
     * @return int Status code, 0 for OK
     */
    abstract public function getDownload(array & $download);

    /**
     * getRepoUrl
     *
     * Get the repo's url and type.
     * This function should find out:
     *  1. the repo's type(Git, SVN, HG, or CVS) of this project
     *  2. the url to clone the repo
     *
     * @param string $type The type of the VCS system (should noly be Git, SVN, HG, CVS)
     * @param string $url  The clone url
     *
     * @return int Status code, 0 for OK
     */
     abstract public function getRepoUrl(&$type, &$url);
}
