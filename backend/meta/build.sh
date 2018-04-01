#!/bin/sh
DIR=`dirname $0`
DIR=`readlink -f $DIR`
$DIR/../vendor/onphp/onphp/meta/bin/build.php $@ $DIR/../config.inc.php $DIR/main.xml
php $DIR/buildSchema.php
#sudo chmod -R 777 $DIR/../src
chmod -R 777 $DIR/../src
