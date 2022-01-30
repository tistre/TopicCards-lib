<?php

namespace StrehleDe\TopicCards\Cypher;

use DateTimeImmutable;
use Laudis\Neo4j\Types\CypherList;
use Laudis\Neo4j\Types\Date;
use Laudis\Neo4j\Types\DateTime;
use Laudis\Neo4j\Types\Time;


class Converter
{
    public static function stringToDateTime(string $str): DateTimeImmutable
    {
        return new DateTimeImmutable($str);
    }


    public static function stringToNeo4jDate(string $dateStr): Date
    {
        // Date objects need days since Unix epoch
        $interval = self::stringToDateTime('1970-01-01')->diff(self::stringToDateTime($dateStr));
        return new Date(intval($interval->format('%a')));
    }


    public static function neo4jDateToDateTime(Date $neo4jDate): DateTimeImmutable
    {
        return $neo4jDate->toDateTime();
    }


    public static function neo4jDateToString(Date $neo4jDate): string
    {
        return self::neo4jDateToDateTime($neo4jDate)->format('Y-m-d');
    }


    public static function stringToNeo4jTime(string $timeStr): Time
    {
        // Time objects need seconds since Unix epoch
        return new Time(self::stringToDateTime($timeStr)->format('U'));
    }


    public static function neo4jTimeToDateTime(Time $neo4jTime): DateTimeImmutable
    {
        return new DateTimeImmutable('@' . $neo4jTime->getSeconds());
    }


    public static function neo4jTimeToString(Time $neo4jTime): string
    {
        return self::neo4jTimeToDateTime($neo4jTime)->format('H:i:s');
    }


    public static function stringToNeo4jDateTime(string $dateTimeStr): DateTime
    {
        $dt = self::stringToDateTime($dateTimeStr);
        return new DateTime($dt->format('U'), $dt->format('u') * 1000, $dt->format('Z'));
    }


    public static function neo4jDateTimeToDateTime(DateTime $neo4jDateTime): DateTimeImmutable
    {
        return $neo4jDateTime->toDateTime();
    }


    public static function neo4jDateTimeToString(DateTime $neo4jDateTime): string
    {
        return self::neo4jDateTimeToDateTime($neo4jDateTime)->format('c');
    }


    /**
     * @param mixed $value
     * @return mixed
     */
    public static function neo4jToScalar($value)
    {
        if (!is_object($value)) {
            return $value;
        }

        if ($value instanceof Date) {
            return self::neo4jDateToString($value);
        } elseif ($value instanceof Time) {
            return self::neo4jTimeToString($value);
        } elseif ($value instanceof DateTime) {
            return self::neo4jDateTimeToString($value);
        } elseif ($value instanceof CypherList) {
            return $value->toArray();
        }

        return (string)$value;
    }
}