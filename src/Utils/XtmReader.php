<?php

namespace TopicCards\Utils;

use TopicCards\Interfaces\TopicMapInterface;


class XtmReader implements \Iterator
{
    /** @var TopicMapInterface */
    protected $topicMap;

    protected $fileName;

    /** @var \XMLReader */
    protected $xmlReader;

    protected $importer;
    protected $cnt;


    public function __construct($fileName, TopicMapInterface $topicMap)
    {
        $this->fileName = $fileName;
        $this->topicMap = $topicMap;

        $this->xmlReader = new \XMLReader();
        $this->importer = new XtmImport($topicMap);

        $this->cnt = -1;
    }


    public function rewind()
    {
        if (! file_exists($this->fileName)) {
            return;
        }

        $ok = $this->xmlReader->open($this->fileName);

        // Go to the root node

        if ($ok) {
            $ok = $this->xmlReader->read();
        }

        if (! $ok) {
            return;
        }

        // Go to the first child node

        while (true) {
            $ok = $this->xmlReader->read();

            if (! $ok) {
                return;
            }

            if ($this->xmlReader->nodeType === \XMLReader::ELEMENT) {
                $this->cnt = 0;

                return;
            }
        }
    }


    public function current()
    {
        /** @var \DOMElement $node */

        $node = $this->xmlReader->expand();

        if ($node === false) {
            return false;
        }

        if ($node->nodeType !== XML_ELEMENT_NODE) {
            return false;
        }

        if ($node->tagName === 'topic') {
            return $this->importer->importTopic($node);
        } elseif ($node->tagName === 'association') {
            return $this->importer->importAssociation($node);
        } else {
            return false;
        }
    }


    public function key()
    {
        return $this->cnt;
    }


    public function next()
    {
        while (true) {
            $ok = $this->xmlReader->next();

            if (! $ok) {
                $this->cnt = -1;

                return;
            }

            if ($this->xmlReader->nodeType === \XMLReader::ELEMENT) {
                $this->cnt++;

                return;
            }
        }

        $this->cnt = -1;
    }


    public function valid()
    {
        return ($this->cnt >= 0);
    }
}
