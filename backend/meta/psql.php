<?php
$sql = file_get_contents('php://stdin');

require '../config.inc.php';

//$sql = preg_replace('/CREATE INDEX "([^"]+)" ON "([^"]+)"/', 'CREATE INDEX "$2_$1" ON "$2"', $sql);
echo "\n--\n\n";

$qs = explode(';', $sql);
foreach ($qs as $q) {
    $q = trim($q);
    $q = str_replace('CREATE TABLE', 'CREATE TABLE IF NOT EXISTS', $q);
    $q = str_replace('CREATE INDEX', 'CREATE INDEX IF NOT EXISTS', $q);
    $q = str_replace('CREATE SEQUENCE', 'CREATE SEQUENCE IF NOT EXISTS', $q);
    echo "$q -\n";
    if (empty($q)) continue;
    try {
        $res = DBPool::me()->getLink()->queryRaw($q);
    } catch (Exception $e) {
        echo $e->getMessage() . "\n";
    }
}
//$data = pg_fetch_all_columns($res);
//var_dump($data);