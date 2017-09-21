<?php

namespace TopicCards\Utils;

use TopicCards\Interfaces\TopicMapInterface;


class LanguageUtils
{
    /**
     * @param string $languageCode
     * @return string
     */
    public static function codeToSubject($languageCode)
    {
        return 'http://id.loc.gov/vocabulary/iso639-1/' . urlencode($languageCode);
    }


    /**
     * @param TopicMapInterface $topicMap
     * @param string $languageCode
     * @param bool $createTopic
     * @return string
     */
    public static function codeToLabel(TopicMapInterface $topicMap, $languageCode, $createTopic = false)
    {
        $label = $languageCode;

        $topicId = $topicMap->getTopicIdBySubject(self::codeToSubject($languageCode), $createTopic);
        
        if (strlen($topicId) > 0) {
            $label = $topicMap->getTopicLabel($topicId);
        }

        return $label;
    }
}
