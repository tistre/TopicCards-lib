<?php

namespace TopicCards\Utils;


class StringUtils
{
    public static function generateUuid()
    {
        // http://stackoverflow.com/questions/2040240/php-function-to-generate-v4-uuid/2040279#2040279

        return sprintf
        (
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',

            // 32 bits for "time_low"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),

            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,

            // 48 bits for "node"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }


    public static function usortByKey(array &$arr, $key)
    {
        return self::sortByKey('usort', $arr, $key);
    }


    protected static function sortByKey($sort_function, array &$arr, $key)
    {
        $collator = new \Collator('en_US');

        $sort_function
        (
            $arr,
            function ($a, $b) use ($key, $collator) {
                $a = $a[$key];
                $b = $b[$key];

                if ($a === $b) {
                    return 0;
                }

                if (is_int($a) && is_int($b)) {
                    return ($a < $b ? -1 : 1);
                }

                return $collator->compare($a, $b);
            }
        );
    }
}
