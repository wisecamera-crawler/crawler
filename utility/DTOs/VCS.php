<?php
/**
 * Wiki.php : DTO of vcs
 *
 * PHP version 5
 *
 * LICENSE : none
 *
 * @author   Poyu Chen <poyu677@gmail.com>
 */

namespace wisecamera;

/**
 * VCS 
 *
 * Refer to DB `vcs` table
 * +--------+------------+---------+------+------+------+------+-----------+
 * | vcs_id | project_id | coommit | file | line | size | user | timestamp |
 * +--------+------------+---------+------+------+------+------+-----------+
 *                         ^^^^^^^   ^^^^   ^^^^   ^^^^   ^^^^
 * commit   Commit counts
 * file     File counts
 * line     Total line count
 * size     Size of the files
 * user     Total commiter counts
 *
 * @author   Poyu Chen <poyu677@gmail.com>
 */
class VCS
{
    public $commit = 0;
    public $file = 0;
    public $line = 0;
    public $size = 0;
    public $user = 0;
}
