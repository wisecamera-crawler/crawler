<?php
/**
 * Wiki.php : DTO of wiki
 *
 * PHP version 5
 *
 * LICENSE : none
 *
 * @author   Poyu Chen <poyu677@gmail.com>
 */
namespace wisecamera\utility\DTOs;

/**
 * Wiki 
 *
 * Refer to DB `wiki` table
 * +---------+------------+-------+------+--------+-----------+
 * | wiki_id | project_id | pages | line | update | timestamp |
 * +---------+------------+-------+------+--------+-----------+
 *                          ^^^^^   ^^^^   ^^^^^^
 * pages    Total pages
 * line     Total lines
 * update   Total updates
 *
 * @author   Poyu Chen <poyu677@gmail.com>
 */
class Wiki
{
    public $pages = 0;
    public $line = 0;
    public $update = 0;
}
