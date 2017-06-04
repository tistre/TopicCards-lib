<?php

use PHPUnit\Framework\TestCase;
use TopicCards\Interfaces\TopicMapInterface;
use TopicCards\Utils\DataTypeUtils;
use TopicCards\Utils\XtmExport;


class XtmExportTest extends TestCase
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
        $topic = self::$topicMap->newTopic();

        $xtmExport = new XtmExport();

        $xml = $xtmExport->exportObjects([$topic]);

        $expectedXml = <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<topicMap xmlns="http://www.topicmaps.org/xtm/" version="2.1">
  <topic id="">
  </topic>
</topicMap>

EOT;

        $this->assertEquals($expectedXml, $xml);
    }


    public function testAllTopicProperties()
    {
        $topic = self::$topicMap->newTopic();

        $topic->setTypes(['http://schema.org/City']);
        
        $topic->setSubjectIdentifiers
        (
            [
                'https://en.wikipedia.org/wiki/Munich',
                'https://de.wikipedia.org/wiki/München'
            ]
        );

        $name = $topic->newName();
        $name->setValue('München');
        $name->setType('http://schema.org/name');

        $name = $topic->newName();
        $name->setLanguage('en');
        $name->setValue('Munich');
        $name->setDataType(DataTypeUtils::DATATYPE_XHTML);
        $name->setType('http://schema.org/name');

        $name = $topic->newName();
        $name->setLanguage('jp');
        $name->setValue('ミュンヘン');
        $name->setDataType(DataTypeUtils::DATATYPE_STRING);
        $name->setType('http://schema.org/name');

        $occurrence = $topic->newOccurrence();
        $occurrence->setLanguage('de');
        $occurrence->setType('http://schema.org/description');
        $occurrence->setValue('München?/i [ˈmʏnçn̩] (bairisch Audio-Datei / Hörbeispiel Minga?/i) ist die Landeshauptstadt des Freistaates Bayern.');
        $occurrence->setDataType(DataTypeUtils::DATATYPE_STRING);

        $occurrence = $topic->newOccurrence();
        $occurrence->setType('http://schema.org/description');
        $occurrence->setValue('<b>Munich</b> (/ˈmjuːnɪk/; German: <i>München</i>, pronounced [ˈmʏnçn̩] ( listen),[2] Bavarian: <i>Minga</i> [ˈmɪŋ(ː)ɐ]) is the capital and largest city of the German state of Bavaria, on the banks of River Isar north of the Bavarian Alps.');
        $occurrence->setDataType(DataTypeUtils::DATATYPE_XHTML);

        $xtmExport = new XtmExport();

        $xml = $xtmExport->exportObjects([$topic]);

        $expectedXml = <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<topicMap xmlns="http://www.topicmaps.org/xtm/" version="2.1">
  <topic id="">
    <subjectIdentifier href="https://en.wikipedia.org/wiki/Munich"/>
    <subjectIdentifier href="https://de.wikipedia.org/wiki/München"/>
    <instanceOf>
      <subjectIdentifierRef href="http://schema.org/City"/>
    </instanceOf>
    <name>
      <type>
        <subjectIdentifierRef href="http://schema.org/name"/>
      </type>
      <value datatype="http://www.w3.org/2001/XMLSchema#string">München</value>
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
      <value datatype="http://www.w3.org/2001/XMLSchema#string" xml:lang="jp">ミュンヘン</value>
    </name>
    <occurrence>
      <type>
        <subjectIdentifierRef href="http://schema.org/description"/>
      </type>
      <resourceData datatype="http://www.w3.org/2001/XMLSchema#string">München?/i [ˈmʏnçn̩] (bairisch Audio-Datei / Hörbeispiel Minga?/i) ist die Landeshauptstadt des Freistaates Bayern.</resourceData>
    </occurrence>
    <occurrence>
      <type>
        <subjectIdentifierRef href="http://schema.org/description"/>
      </type>
      <resourceData datatype="http://www.w3.org/1999/xhtml"><div xmlns="http://www.w3.org/1999/xhtml"><b>Munich</b> (/ˈmjuːnɪk/; German: <i>München</i>, pronounced [ˈmʏnçn̩] ( listen),[2] Bavarian: <i>Minga</i> [ˈmɪŋ(ː)ɐ]) is the capital and largest city of the German state of Bavaria, on the banks of River Isar north of the Bavarian Alps.</div></resourceData>
    </occurrence>
  </topic>
</topicMap>

EOT;

        $this->assertEquals($expectedXml, $xml);
    }
}
