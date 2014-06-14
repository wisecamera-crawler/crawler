<?php
/**
 * Wiki.php : DTO of download 
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
 * Refer to DB `download` table
 * +-------------+-----+------------+------+-------+-----------+
 * | download_id | url | project_id | name | count | timestamp |
 * +-------------+-----+------------+------+-------+-----------+
 *                 ^^^                ^^^^   ^^^^^
 * url      File url
 * name     File name
 * count    Download times
 *
 * @author   Poyu Chen <poyu677@gmail.com>
 */
class Download
{
    public $url = "";
    public $name = "";
    public $count = 0;
}
