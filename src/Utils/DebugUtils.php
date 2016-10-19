<?php

namespace TopicCards\Utils;


class DebugUtils
{
    public static function logBacktraceShort()
    {
        static $base_path = false;
        
        if (! $base_path)
        {
            $base_path = dirname(dirname(dirname(__DIR__)));
        }
        
        $backtrace = array_reverse(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), true);

        $items = array();
        $cnt = count($backtrace);

        foreach ($backtrace as $i => $call)
        {
            $items[ ] = sprintf
            (
                "\n%s%s:%s %s%s",
                str_repeat(' ', ($cnt - $i - 1)),
                ((! empty($call[ 'file' ])) ? str_replace($base_path . '/', '', $call[ 'file' ]) : ''),
                ((! empty($call[ 'line' ])) ? $call[ 'line' ] : ''),
                (($i > 0) && (! empty($call[ 'class' ])) ? $call[ 'class' ] . $call[ 'type' ] : ''),
                ($i > 0 ? $call[ 'function' ] . '()' : '')
            );
        }

        error_log(implode(' => ', $items));
    }
}
