#!/usr/bin/env bash
cd /src/meta
sh createdb.sh

cd /src/console/
while [ true ]
do
    php daemon.php
    sleep 60
done