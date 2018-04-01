<?php
require dirname(__FILE__) . '/../config.inc.php';

if ($argc > 1) {
    $scriptName = $argv[1];
} else {
    die('Usage: php run.php <scriptName>' . PHP_EOL);
}

if ($argc > 2) {
    $scriptArgs = array_slice($argv, 2);
} else {
    $scriptArgs = [];
}

ConsoleScriptRunner::me()->execScript($scriptName, $scriptArgs);