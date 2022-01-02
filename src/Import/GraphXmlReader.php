<?php

namespace TopicCards\Import;

use DOMElement;
use Iterator;
use XMLReader;


class GraphXmlReader implements Iterator
{
    protected int $cnt = -1;
    protected string $fileName = '';
    protected GraphXmlImporter $importer;
    protected XMLReader $xmlReader;


    /**
     * GraphXmlReader constructor.
     *
     * @param string $fileName
     */
    public function __construct(string $fileName)
    {
        $this->fileName = $fileName;

        $this->xmlReader = new XMLReader();
        $this->importer = new GraphXmlImporter();
    }


    /**
     * @return void
     */
    public function rewind(): void
    {
        if (!file_exists($this->fileName)) {
            return;
        }

        $ok = $this->xmlReader->open($this->fileName);

        // Go to the root node

        if ($ok) {
            $ok = $this->xmlReader->read();
        }

        if (!$ok) {
            return;
        }

        // Go to the first child node

        while (true) {
            $ok = $this->xmlReader->read();

            if (!$ok) {
                return;
            }

            if ($this->xmlReader->nodeType === XMLReader::ELEMENT) {
                $this->cnt = 0;

                return;
            }
        }
    }


    public function current()
    {
        /** @var DOMElement $domNode */
        $domNode = $this->xmlReader->expand();

        if ($domNode === false) {
            return false;
        }

        if ($domNode->nodeType !== XML_ELEMENT_NODE) {
            return false;
        }

        if ($domNode->tagName === 'node') {
            return $this->importer->getNodeData($domNode);
        } elseif ($domNode->tagName === 'relationship') {
            return $this->importer->getRelationshipData($domNode);
        } else {
            return false;
        }
    }


    /**
     * @return int
     */
    public function key(): int
    {
        return $this->cnt;
    }


    /**
     * @return void
     */
    public function next()
    {
        while (true) {
            $ok = $this->xmlReader->next();

            if (!$ok) {
                $this->cnt = -1;

                return;
            }

            if ($this->xmlReader->nodeType === XMLReader::ELEMENT) {
                $this->cnt++;

                return;
            }
        }
    }


    /**
     * @return bool
     */
    public function valid(): bool
    {
        return ($this->cnt >= 0);
    }
}