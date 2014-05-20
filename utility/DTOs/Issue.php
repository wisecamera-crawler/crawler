<?php
/**
 * Wiki.php : DTO of issue 
 *
 * PHP version 5
 *
 * LICENSE : none
 *
 * @author   Poyu Chen <poyu677@gmail.com>
 */

namespace wisecamera;

/**
 * Wiki 
 *
 * Refer to DB `issue` table
 * +----------+------------+-------+------+-------+---------+---------+-----------+
 * | issue_id | project_id | topic | open | close | article | account | timestamp |
 * +----------+------------+-------+------+-------+---------+---------+-----------+
 *                           ^^^^^   ^^^^   ^^^^^   ^^^^^^^   ^^^^^^^
 * topic    Total issues(topics)
 * open     Total open issues
 * close    Total close issues
 * article  Total article in all issue discusssion
 * account  Diffrent accounts in all issue discussion
 *
 * @author   Poyu Chen <poyu677@gmail.com>
 */
class Issue
{
    public $topic = 0;
    public $open = 0;
    public $close = 0;
    public $article = 0;
    public $account = 0;
}
