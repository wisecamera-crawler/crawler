<?php
/**
 * WikiPage.php : DTO of wiki pages
 *
 * PHP version 5
 *
 * LICENSE : none
 *
 * @author   Poyu Chen <poyu677@gmail.com>
 */

namespace wisecamera;

/**
 * WikiPage
 *
 * Refer to DB `wiki_page` table
 * +--------------+-----+------------+--------------+--------+-----------+
 * | wiki_page_id | url | project_id | title | line | update | timestamp |
 * +--------------+-----+------------+-------+------+--------+-----------+
 *                  ^^^                ^^^^^   ^^^^   ^^^^^^
 * url      Page's url
 * title    Title
 * line     Article lines
 * update   Update times
 *
 * @author   Poyu Chen <poyu677@gmail.com>
 */
class WikiPage
{
    public $url = "";
    public $title = "";
    public $line = 0;
    public $update = 0;
}
