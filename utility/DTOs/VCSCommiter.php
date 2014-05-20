<?php
/**
 * VCSCommiter.php : DTO of VCS commter
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
 * Refer to DB `vcs_commiter` table
 * +-----------------+----------+------------+--------+--------+-----+-----------+
 * | vcs_commiter_id | commiter | project_id | modify | delete | new | timestamp |
 * +-----------------+----------+------------+--------+--------+-----+-----------+
 *                     ^^^^^^^^                ^^^^^^   ^^^^^^   ^^^
 * commiter     Commiters id/names
 * modify       Files commiters had modifued
 * delete       Files commiters had deleted
 * new          Files commiters had created
 *
 * @author   Poyu Chen <poyu677@gmail.com>
 */
class VCSCommiter
{
    public $commiter = "";
    public $modify = 0;
    public $delete = 0;
    public $new = 0;
}
