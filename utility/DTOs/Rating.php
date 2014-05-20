<?php
/**
 * Rating.php : DTO of rating
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
 * Refer to DB `project` table
 *  In DB  | In DTO
 * ========+==========
 *  5-star | fiveStar   #SF rating
 *  4-star | fourStar   #SF rating
 *  3-star | threeStar  #SF rating
 *  2-star | twoStar    #SF rating
 *  1-star | oneStar    #SF rating
 *  star   | star       #Github/GC rating
 *  fork   | fork       #Github rating
 *  watch  | watch      #Github rating
 *
 * @author   Poyu Chen <poyu677@gmail.com>
 */
class Rating
{
    public $oneStar = 0;
    public $twoStar = 0;
    public $threeStar = 0;
    public $fourStar = 0;
    public $fiveStar = 0;

    public $star = 0;
    public $fork = 0;
    public $watch = 0;
}
