#!/bin/sh
DBNAME=nully
#sudo -u postgres createdb $DBNAME
#PSQLCMD=sudo -u postgres psql -d $DBNAME
#PSQLCMD=php psql.php
/bin/grep -v REFERENCES ../src/classes/Auto/schema.sql | php psql.php
sh ./build.sh --no-color  2> /dev/null | /bin/grep -P "^(ALTER TABLE|CREATE INDEX)" | php psql.php
echo "Database '$DBNAME' created!"
