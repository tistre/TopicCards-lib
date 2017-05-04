<?php

use PHPUnit\Framework\TestCase;
use TopicCards\Interfaces\TopicMapInterface;
use TopicCards\Utils\DataTypeUtils;
use TopicCards\Utils\XtmImport;


class XtmImportTest extends TestCase
{
    /** @var TopicMapInterface */
    protected static $topicMap;


    public static function setUpBeforeClass()
    {
        global $topicMap;

        self::$topicMap = $topicMap;
    }


    public function testEmptyTopic()
    {
        $xml = <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<topicMap xmlns="http://www.topicmaps.org/xtm/" version="2.1">
  <topic>
  </topic>
</topicMap>

EOT;

        $xtmImport = new XtmImport(self::$topicMap);
        
        $objects = $xtmImport->importObjects($xml);
        
        $this->assertEquals(1, count($objects), 'XTM import failed');
        
        $topic = $objects[0];

        $expected =
            [
                'created' => false,
                'id' => '',
                'names' => [],
                'occurrences' => [],
                'reifies_id' => '',
                'reifies_what' => '',
                'subject_identifiers' => [],
                'subject_locators' => [],
                'types' => [],
                'updated' => false,
                'version' => 0
            ];

        $topicData = $topic->getAll();

        $this->assertEquals($expected, $topicData);

        $topic->delete();
    }


    public function testAllTopicProperties()
    {
        $xml = <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<topicMap xmlns="http://www.topicmaps.org/xtm/" version="2.1">
  <topic>
    <subjectIdentifier href="https://en.wikipedia.org/wiki/Munich"/>
    <subjectIdentifier href="https://de.wikipedia.org/wiki/München"/>
    <instanceOf>
      <subjectIdentifierRef href="http://schema.org/City"/>
    </instanceOf>
    <name>
      <type>
        <subjectIdentifierRef href="http://schema.org/name"/>
      </type>
      <value xml:lang="de">München</value>
    </name>
    <name>
      <type>
        <subjectIdentifierRef href="http://schema.org/name"/>
      </type>
      <value datatype="http://www.w3.org/1999/xhtml" xml:lang="en"><div xmlns="http://www.w3.org/1999/xhtml">Munich</div></value>
    </name>
    <name>
      <type>
        <subjectIdentifierRef href="http://schema.org/name"/>
      </type>
      <value xml:lang="jp">ミュンヘン</value>
    </name>
    <occurrence>
      <type>
        <subjectIdentifierRef href="http://schema.org/description"/>
      </type>
      <resourceData xml:lang="de">München?/i [ˈmʏnçn̩] (bairisch Audio-Datei / Hörbeispiel Minga?/i) ist die Landeshauptstadt des Freistaates Bayern.</resourceData>
    </occurrence>
    <occurrence>
      <type>
        <subjectIdentifierRef href="http://schema.org/description"/>
      </type>
      <resourceData datatype="http://www.w3.org/1999/xhtml" xml:lang="en">
        <div xmlns="http://www.w3.org/1999/xhtml"><b>Munich</b> (/ˈmjuːnɪk/; German: <i>München</i>, pronounced [ˈmʏnçn̩] ( listen),[2] Bavarian: <i>Minga</i> [ˈmɪŋ(ː)ɐ]) is the capital and largest city of the German state of Bavaria, on the banks of River Isar north of the Bavarian Alps.</div>
      </resourceData>
    </occurrence>
  </topic>
</topicMap>

EOT;

        $xtmImport = new XtmImport(self::$topicMap);

        $objects = $xtmImport->importObjects($xml);

        $this->assertEquals(1, count($objects), 'XTM import failed');

        $topic = $objects[0];

        $expected =
            [
                'created' => false,
                'id' => '',
                'names' => 
                    [
                        [
                            'datatype' => self::$topicMap->getTopicIdBySubject(DataTypeUtils::DATATYPE_STRING),
                            'id' => '',
                            'language' => 'de',
                            'reifier' => false,
                            'scope' => [],
                            'type' => self::$topicMap->getTopicIdBySubject('http://schema.org/name'),
                            'value' => 'München'
                        ],
                        [
                            'datatype' => self::$topicMap->getTopicIdBySubject(DataTypeUtils::DATATYPE_XHTML),
                            'id' => '',
                            'language' => 'en',
                            'reifier' => false,
                            'scope' => [],
                            'type' => self::$topicMap->getTopicIdBySubject('http://schema.org/name'),
                            'value' => 'Munich'
                        ],
                        [
                            'datatype' => self::$topicMap->getTopicIdBySubject(DataTypeUtils::DATATYPE_STRING),
                            'id' => '',
                            'language' => 'jp',
                            'reifier' => false,
                            'scope' => [],
                            'type' => self::$topicMap->getTopicIdBySubject('http://schema.org/name'),
                            'value' => 'ミュンヘン'
                        ]
                    ],
                'occurrences' => 
                    [
                        [
                            'datatype' => self::$topicMap->getTopicIdBySubject(DataTypeUtils::DATATYPE_STRING),
                            'id' => '',
                            'language' => 'de',
                            'reifier' => false,
                            'scope' => [],
                            'type' => self::$topicMap->getTopicIdBySubject('http://schema.org/description'),
                            'value' => 'München?/i [ˈmʏnçn̩] (bairisch Audio-Datei / Hörbeispiel Minga?/i) ist die Landeshauptstadt des Freistaates Bayern.'
                        ],
                        [
                            'datatype' => self::$topicMap->getTopicIdBySubject(DataTypeUtils::DATATYPE_XHTML),
                            'id' => '',
                            'language' => 'en',
                            'reifier' => false,
                            'scope' => [],
                            'type' => self::$topicMap->getTopicIdBySubject('http://schema.org/description'),
                            'value' => '<b>Munich</b> (/ˈmjuːnɪk/; German: <i>München</i>, pronounced [ˈmʏnçn̩] ( listen),[2] Bavarian: <i>Minga</i> [ˈmɪŋ(ː)ɐ]) is the capital and largest city of the German state of Bavaria, on the banks of River Isar north of the Bavarian Alps.'
                        ]
                    ],
                'reifies_id' => '',
                'reifies_what' => '',
                'subject_identifiers' => 
                    [
                        'https://en.wikipedia.org/wiki/Munich',
                        'https://de.wikipedia.org/wiki/München'
                    ],
                'subject_locators' => [],
                'types' => [self::$topicMap->getTopicIdBySubject('http://schema.org/City')],
                'updated' => false,
                'version' => 0
            ];

        $topicData = $topic->getAll();

        $this->assertEquals($expected, $topicData);

        $topic->delete();
    }
}
