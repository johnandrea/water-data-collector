#!/bin/bash

here=${0%/*}

bin=/usr/bin

PGPASSFILE=~/owner.pass
export PGPASSFILE

$bin/pg_dumpall -g |
grep -v "CREATE ROLE postgres;" |
grep -v "ALTER ROLE postgres " |
cat > $here/dumpall.dump

mv $here/dumpall.dump.gz   $here/dumpall.dump.1.gz
gzip $here/dumpall.dump

for db in data
do

  $bin/pg_dump --create --inserts -F p $db > $here/$db.dump

  mv $here/$db.dump.gz   $here/$db.dump.1.gz 

  gzip $here/$db.dump

done

