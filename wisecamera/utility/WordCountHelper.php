<?php

namespace wisecamera\utility;

class WordCountHelper
{
    private static $UTF8_CHINESE_PATTERN = "/[\x{4e00}-\x{9fff}\x{f900}-\x{faff}]/u";
    private static $UTF8_SYMBOL_PATTERN = "/[\x{ff00}-\x{ffef}\x{2000}-\x{206F}]/u";

    // count only chinese words
    private static function strUtf8ChineseWordCount($str = "")
    {
        $str = preg_replace(WordCountHelper::$UTF8_SYMBOL_PATTERN, "", $str);

        return preg_match_all(WordCountHelper::$UTF8_CHINESE_PATTERN, $str, $textrr);
    }
    
    // count both chinese and english
    private static function strUtf8MixWordCount($str = "")
    {
        $str = preg_replace(WordCountHelper::$UTF8_SYMBOL_PATTERN, "", $str);

        return WordCountHelper::strUtf8ChineseWordCount($str) +
            str_word_count(preg_replace(WordCountHelper::$UTF8_CHINESE_PATTERN, "", $str));
    }

    private static function filterTag($str = "")
    {
        $str = preg_replace('#(<.[^>]*>)#i', "", $str);

        return $str;
    }

    private static function countCharByte($str = "")
    {
        $cwordCount = WordCountHelper::strUtf8ChineseWordCount($str);

        preg_match_all('#([A-Za-z0-9]+)#i', $str, $match);

        $ewordCount;
        foreach ($match[0] as $val) {
            $ewordCount += strlen($val);
        }

        return $ewordCount + $cwordCount*2;
    }

    public static function lineCount($str)
    {
        $filterdStr = WordCountHelper::filterTag($str);
        $strlength = WordCountHelper::countCharByte($filterdStr);

        return ceil($strlength /100) ;
    }

    public static function utf8WordsCount($str)
    {
        $filteredStr = WordCountHelper::filterTag($str);
        $cwordCount = WordCountHelper::strUtf8ChineseWordCount($filteredStr);

        // handle of 1234.123434
        preg_match_all('#([0-9]+[.][0-9]+)#i', $filteredStr, $match);
        $count1 = sizeof($match[0]);
        $str2 = preg_replace('#([0-9]+[.][0-9]+)#i', "", $filteredStr);

        // handle of  123,123,123
        preg_match_all('#([0-9]+([,][0-9]+)+)#i', $str2, $match);
        $count2 = sizeof($match[0]);
        $str3 = preg_replace('#([0-9]+([,][0-9]+)+)#i', "", $str2);

        // handle of english words, pure digital, and english mix with digitals
        preg_match_all('#([A-Za-z0-9]+)#i', $str3, $match);
        $count3 = sizeof($match[0]);

        return $count3 + $count2 + $count1 + $cwordCount;
    }
}
