#!/usr/bin/env bash

set -e

MAX_COUNTER=45
COUNTER=1

echo "Waiting for Elasticsearch server…"
while ! docker-compose exec elasticsearch curl -s -k --fail "http://elasticsearch:9200/_cluster/health?wait_for_status=yellow&timeout=1s" > /dev/null; do
    COUNTER=$((${COUNTER} + 1))
    if [ ${COUNTER} -gt ${MAX_COUNTER} ]; then
        echo "We have been waiting for Elasticsearch too long already; failing." >&2
        exit 1
    fi;
    sleep 1
done

echo "Elasticsearch server is running!"
