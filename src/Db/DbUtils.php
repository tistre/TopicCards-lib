<?php

namespace TopicCards\Db;

use TopicCards\Interfaces\AssociationInterface;
use TopicCards\Interfaces\CoreInterface;
use TopicCards\Interfaces\NameInterface;
use TopicCards\Interfaces\OccurrenceInterface;
use TopicCards\Interfaces\RoleInterface;
use TopicCards\Interfaces\TopicInterface;
use TopicCards\Interfaces\TopicMapInterface;


class DbUtils
{
    public static function labelsString(array $labels)
    {
        $result = '';

        foreach ($labels as $label) {
            $result .= sprintf(':`%s`', $label);
        }

        return $result;
    }


    public static function propertiesString(array $properties, &$bind)
    {
        $propertyStrings = [];

        foreach ($properties as $key => $value) {
            if (empty($value)) {
                continue;
            }

            if (is_array($value)) {
                $parts = [];

                foreach ($value as $i => $v) {
                    $k = $key . $i;
                    $parts[] = sprintf('{%s}', $k);
                    $bind[$k] = $v;
                }

                $propertyStrings[] = sprintf('%s: [ %s ]', $key, implode(', ', $parts));
            } else {
                $propertyStrings[] = sprintf('%s: {%s}', $key, $key);
                $bind[$key] = $value;
            }
        }

        return implode(', ', $propertyStrings);
    }


    public static function propertiesUpdateString($node, array $properties, &$bind)
    {
        $setPropertyStrings = [];
        $removePropertyStrings = [];

        foreach ($properties as $key => $value) {
            if ((is_array($value) && (count($value) === 0)) || ((! is_array($value)) && (strlen($value) === 0))) {
                $removePropertyStrings[] = sprintf('%s.%s', $node, $key);
                continue;
            }

            if (is_array($value)) {
                $parts = [];

                foreach ($value as $i => $v) {
                    $k = $key . $i;
                    $parts[] = sprintf('{%s}', $k);
                    $bind[$k] = $v;
                }

                $setPropertyStrings[] = sprintf('%s.%s = [ %s ]', $node, $key, implode(', ', $parts));
            } else {
                $setPropertyStrings[] = sprintf('%s.%s = {%s}', $node, $key, $key);
                $bind[$key] = $value;
            }
        }

        $result = '';

        if (count($removePropertyStrings) > 0) {
            $result .= sprintf(' REMOVE %s', implode(', ', $removePropertyStrings));
        }

        if (count($setPropertyStrings) > 0) {
            $result .= sprintf(' SET %s', implode(', ', $setPropertyStrings));
        }

        return $result;
    }


    public static function tmConstructLabelQueries(TopicMapInterface $topicmap, array $topicIds, $tmConstructSubject)
    {
        $result = [];

        $tmConstructId = $topicmap->getTopicIdBySubject($tmConstructSubject);

        if (strlen($tmConstructId) === 0) {
            return $result;
        }

        foreach ($topicIds as $topicId) {
            if (strlen($topicId) === 0) {
                continue;
            }

            // TODO: Skip the ones which the cache knows are already labelled

            $result[] =
                [
                    'query' => sprintf
                    (
                        'MATCH (node:Topic { id: {id} }) SET node%s',
                        self::labelsString([$tmConstructId])
                    ),
                    'bind' => ['id' => $topicId]
                ];
        }

        return $result;
    }


    public static function tmConstructLinkReifierQueries($reifiesWhat, $reifiesId, $reifierTopicId)
    {
        $result = [];

        $propertyData =
            [
                'reifies_what' => $reifiesWhat,
                'reifies_id' => $reifiesId
            ];

        $bind = ['id' => $reifierTopicId];
        $propertyQuery = self::propertiesUpdateString('node', $propertyData, $bind);

        $result[] =
            [
                'query' => sprintf
                (
                    'MATCH (node:Topic { id: {id} })%s',
                    $propertyQuery
                ),
                'bind' => $bind
            ];

        return $result;
    }


    public static function tmConstructUnlinkReifierQueries($reifiesWhat, $reifiesId, $reifierTopicId)
    {
        $result = [];

        $propertyData =
            [
                'reifies_what' => '',
                'reifies_id' => ''
            ];

        $bind = ['id' => $reifierTopicId];
        $propertyQuery = self::propertiesUpdateString('node', $propertyData, $bind);

        $result[] =
            [
                'query' => sprintf
                (
                    'MATCH (node:Topic { id: {id} })%s',
                    $propertyQuery
                ),
                'bind' => $bind
            ];

        return $result;
    }
}
