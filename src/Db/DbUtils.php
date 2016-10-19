<?php

namespace TopicCards\Db;

use TopicCards\Interfaces\iTopicMap;


class DbUtils
{
    public static function labelsString(array $labels)
    {
        $result = '';

        foreach ($labels as $label)
        {
            $result .= sprintf(':`%s`', $label);
        }

        return $result;
    }


    public static function propertiesString(array $properties, &$bind)
    {
        $property_strings = [];

        foreach ($properties as $key => $value)
        {
            if (empty($value))
            {
                continue;
            }

            if (is_array($value))
            {
                $parts = [];

                foreach ($value as $i => $v)
                {
                    $k = $key . $i;
                    $parts[] = sprintf('{%s}', $k);
                    $bind[ $k ] = $v;
                }

                $property_strings[] = sprintf('%s: [ %s ]', $key, implode(', ', $parts));
            }
            else
            {
                $property_strings[] = sprintf('%s: {%s}', $key, $key);
                $bind[ $key ] = $value;
            }
        }

        return implode(', ', $property_strings);
    }


    public static function propertiesUpdateString($node, array $properties, &$bind)
    {
        $set_property_strings = [];
        $remove_property_strings = [];

        foreach ($properties as $key => $value)
        {
            if ((is_array($value) && (count($value) === 0)) || ((! is_array($value)) && (strlen($value) === 0)))
            {
                $remove_property_strings[] = sprintf('%s.%s', $node, $key);
                continue;
            }

            if (is_array($value))
            {
                $parts = [];

                foreach ($value as $i => $v)
                {
                    $k = $key . $i;
                    $parts[] = sprintf('{%s}', $k);
                    $bind[ $k ] = $v;
                }

                $set_property_strings[] = sprintf('%s.%s = [ %s ]', $node, $key, implode(', ', $parts));
            }
            else
            {
                $set_property_strings[] = sprintf('%s.%s = {%s}', $node, $key, $key);
                $bind[ $key ] = $value;
            }
        }

        $result = '';

        if (count($remove_property_strings) > 0)
        {
            $result .= sprintf(' REMOVE %s', implode(', ', $remove_property_strings));
        }

        if (count($set_property_strings) > 0)
        {
            $result .= sprintf(' SET %s', implode(', ', $set_property_strings));
        }

        return $result;
    }


    public static function tmConstructLabelQueries(iTopicMap $topicmap, array $topic_ids, $tm_construct_subject)
    {
        $result = [];

        $tm_construct_id = $topicmap->getTopicIdBySubject($tm_construct_subject);

        if (strlen($tm_construct_id) === 0)
        {
            return $result;
        }

        foreach ($topic_ids as $topic_id)
        {
            // TODO: Skip the ones which the cache knows are already labelled
            
            $result[ ] = 
                [
                    'query' => sprintf
                    (
                        'MATCH (node:Topic { id: {id} }) SET node%s',
                        self::labelsString([ $tm_construct_id ])
                    ),
                    'bind' => [ 'id' => $topic_id ]
                ];
        }
        
        return $result;
    }
}