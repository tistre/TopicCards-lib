<?php

namespace TopicCards\Cypher;

use DateTimeImmutable;
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
        $interval = (new DateTimeImmutable('1970-01-01'))->diff(self::stringToDateTime($dateStr));
        return new Date(intval($interval->format('%a')));
    }


    public static function neo4jDateToString(Date $neo4jDate): string
    {
        return $neo4jDate->toDateTime()->format('Y-m-d');
    }


    public static function stringToNeo4jTime(string $timeStr): Time
    {
        // Time objects need seconds since Unix epoch
        return new Time(self::stringToDateTime($timeStr)->format('U'));
    }


    public static function neo4jTimeToString(Time $neo4jTime): string
    {
        return (new DateTimeImmutable('@' . $neo4jTime->getSeconds()))->format('H:i:s');
    }


    public static function stringToNeo4jDateTime(string $dateTimeStr): DateTime
    {
        $dt = new DateTimeImmutable($dateTimeStr);
        return new DateTime($dt->format('U'), $dt->format('u') * 1000, $dt->format('Z'));
    }


    public static function neo4jDateTimeToString(DateTime $neo4jDateTime): string
    {
        return $neo4jDateTime->toDateTime()->format('c');
    }
}