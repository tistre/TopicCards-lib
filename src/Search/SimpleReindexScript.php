<?php

// Feed it UUIDs from STDIN like this:
// % docker compose exec neo4j cypher-shell -u neo4j -p secret --format plain 'MATCH (n) RETURN n.uuid;'

namespace StrehleDe\TopicCards\Search;

use StrehleDe\TopicCards\Configuration\Configuration;


class SimpleReindexScript
{
    protected Configuration $configuration;


    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }


    public function reindex($stream): void
    {
        $cnt = 0;

        while (($line = fgets($stream, 4096)) !== false) {
            $uuid = trim($line, " \n\r\t\v\x00\"");

            if (empty($uuid)) {
                continue;
            }

            $cnt++;
            printf("%d: Indexing node <%s>\n", $cnt, $uuid);

            IndexUpdate::updateNode($uuid, $this->configuration);
        }
    }
}