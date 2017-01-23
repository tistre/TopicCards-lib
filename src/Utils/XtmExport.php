<?php

namespace TopicCards\Utils;

use TopicCards\Interfaces\AssociationInterface;
use TopicCards\Interfaces\TopicInterface;
use TopicCards\Interfaces\TopicMapInterface;


class XtmExport
{
    /** @var TopicMapInterface */
    protected $topicMap;


    public function exportObjects(array $objects)
    {
        $result =
            '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<topicMap xmlns="http://www.topicmaps.org/xtm/" version="2.1">' . "\n";

        foreach ($objects as $object) {
            if ($object instanceOf TopicInterface) {
                $result .= $this->exportTopic($object, 1);
            } elseif ($object instanceOf AssociationInterface) {
                $result .= $this->exportAssociation($object, 1);
            }
        }

        $result .= "</topicMap>\n";

        return $result;
    }


    protected function exportTopic(TopicInterface $topic, $indent)
    {
        $this->topicMap = $topic->getTopicMap();

        $result = sprintf
        (
            '%s<topic id="%s">' . "\n",
            str_repeat('  ', $indent),
            htmlspecialchars($topic->getId())
        );

        $result .= $this->exportSubjectIdentifiers($topic->getSubjectIdentifiers(), ($indent + 1));
        $result .= $this->exportSubjectLocators($topic->getSubjectLocators(), ($indent + 1));
        $result .= $this->exportTypes($topic->getTypeIds(), ($indent + 1));
        $result .= $this->exportNames($topic->getNames([]), ($indent + 1));
        $result .= $this->exportOccurrences($topic->getOccurrences([]), ($indent + 1));

        $result .= sprintf
        (
            "%s</topic>\n",
            str_repeat('  ', $indent)
        );

        return $result;
    }


    protected function exportAssociation(AssociationInterface $association, $indent)
    {
        $this->topicMap = $association->getTopicMap();

        $result = sprintf
        (
            '%s<association id="%s">' . "\n",
            str_repeat('  ', $indent),
            htmlspecialchars($association->getId())
        );

        $result .= $this->exportReifier($association->getReifierId(), ($indent + 1));
        $result .= $this->exportType($association->getTypeId(), ($indent + 1));
        $result .= $this->exportScope($association->getScopeIds(), ($indent + 1));
        $result .= $this->exportRoles($association->getRoles([]), ($indent + 1));

        $result .= sprintf
        (
            "%s</association>\n",
            str_repeat('  ', $indent)
        );

        return $result;
    }


    protected function exportTopicRef($topicId, $indent)
    {
        $value = $this->topicMap->getTopicSubjectIdentifier($topicId);

        if (strlen($value) > 0) {
            $tag = 'subjectIdentifierRef';
        } else {
            $value = $this->topicMap->getTopicSubjectLocator($topicId);

            if (strlen($value) > 0) {
                $tag = 'subjectLocatorRef';
            } else {
                $value = '#' . $topicId;
                $tag = 'topicRef';
            }
        }

        return sprintf
        (
            '%s<%s href="%s"/>' . "\n",
            str_repeat('  ', $indent),
            $tag,
            htmlspecialchars($value)
        );
    }


    protected function exportSubjectLocators(array $subjectLocators, $indent)
    {
        return $this->exportSubjects('subjectLocator', $subjectLocators, $indent);
    }


    protected function exportSubjectIdentifiers(array $subjectIdentifiers, $indent)
    {
        return $this->exportSubjects('subjectIdentifier', $subjectIdentifiers, $indent);
    }


    protected function exportSubjects($tag, array $urls, $indent)
    {
        $result = '';

        foreach ($urls as $url) {
            $result .= sprintf
            (
                '%s<%s href="%s"/>' . "\n",
                str_repeat('  ', $indent),
                $tag,
                htmlspecialchars($url)
            );
        }

        return $result;
    }


    protected function exportNames(array $names, $indent)
    {
        $result = '';

        foreach ($names as $name) {
            $result .= sprintf
            (
                "%s<name>\n",
                str_repeat('  ', $indent)
            );

            $result .= $this->exportReifier($name->getReifierId(), ($indent + 1));
            $result .= $this->exportType($name->getTypeId(), ($indent + 1));
            $result .= $this->exportScope($name->getScopeIds(), ($indent + 1));

            $result .= sprintf
            (
                "%s<value>%s</value>\n",
                str_repeat('  ', ($indent + 1)),
                htmlspecialchars($name->getValue())
            );

            $result .= sprintf
            (
                "%s</name>\n",
                str_repeat('  ', $indent)
            );
        }

        return $result;
    }


    protected function exportRoles(array $roles, $indent)
    {
        $result = '';

        foreach ($roles as $role) {
            $result .= sprintf
            (
                "%s<role>\n",
                str_repeat('  ', $indent)
            );

            $result .= $this->exportReifier($role->getReifierId(), ($indent + 1));
            $result .= $this->exportType($role->getTypeId(), ($indent + 1));
            $result .= $this->exportTopicRef($role->getPlayerId(), ($indent + 1));

            $result .= sprintf
            (
                "%s</role>\n",
                str_repeat('  ', $indent)
            );
        }

        return $result;
    }


    protected function exportOccurrences(array $occurrences, $indent)
    {
        $result = '';

        foreach ($occurrences as $occurrence) {
            $result .= sprintf
            (
                "%s<occurrence>\n",
                str_repeat('  ', $indent)
            );

            $result .= $this->exportReifier($occurrence->getReifierId(), ($indent + 1));
            $result .= $this->exportType($occurrence->getTypeId(), ($indent + 1));
            $result .= $this->exportScope($occurrence->getScopeIds(), ($indent + 1));

            $dataType = $occurrence->getDataType();

            if (strlen($dataType) === 0) {
                $dataType = '#' . $occurrence->getDataTypeId();
            }

            $result .= sprintf
            (
                '%s<resourceData datatype="%s">%s</resourceData>' . "\n",
                str_repeat('  ', ($indent + 1)),
                htmlspecialchars($dataType),
                DataTypeUtils::valueToXml($occurrence->getValue(), $dataType)
            );

            $result .= sprintf
            (
                "%s</occurrence>\n",
                str_repeat('  ', $indent)
            );
        }

        return $result;
    }


    protected function exportReifier($reifier, $indent)
    {
        if (strlen($reifier) === 0) {
            return '';
        }

        return sprintf
        (
            "%s<reifier>\n%s%s</reifier>\n",
            str_repeat('  ', $indent),
            $this->exportTopicRef($reifier, ($indent + 1)),
            str_repeat('  ', $indent)
        );
    }


    protected function exportTypes(array $types, $indent)
    {
        if (count($types) === 0) {
            return '';
        }

        $result = '';

        foreach ($types as $topicId) {
            $result .= sprintf
            (
                "%s<instanceOf>\n%s%s</instanceOf>\n",
                str_repeat('  ', $indent),
                $this->exportTopicRef($topicId, ($indent + 1)),
                str_repeat('  ', $indent)
            );
        }

        return $result;
    }


    protected function exportType($type, $indent)
    {
        if (strlen($type) === 0) {
            return '';
        }

        return sprintf
        (
            "%s<type>\n%s%s</type>\n",
            str_repeat('  ', $indent),
            $this->exportTopicRef($type, ($indent + 1)),
            str_repeat('  ', $indent)
        );
    }


    protected function exportScope(array $scope, $indent)
    {
        $result = '';

        foreach ($scope as $topicId) {
            $result .= sprintf
            (
                "%s<scope>\n%s%s</scope>\n",
                str_repeat('  ', $indent),
                $this->exportTopicRef($topicId, ($indent + 1)),
                str_repeat('  ', $indent)
            );
        }

        return $result;
    }
}
