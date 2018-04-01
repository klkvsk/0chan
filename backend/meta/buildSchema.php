<?php
require dirname(__FILE__) . '/../config.inc.php';
require PATH_CLASSES.'Auto'.DIRECTORY_SEPARATOR.'schema.php';

file_put_contents(
	PATH_CLASSES.'Auto'.DIRECTORY_SEPARATOR.'schema.sql',
	$schema->toDialectString(
		DBPool::me()->getLink()->getDialect()
	)
);
