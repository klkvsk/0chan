<?php
require dirname(__FILE__) . '/../config.inc.php';

if ($argc > 1) {
    $testNames = [ $argv[1] ];
} else {
    //die('Usage: php test.php <testName>' . PHP_EOL);
    $testNames = array_map(
        function ($file) { return basename($file, EXT_CLASS); },
        glob(PATH_TESTS . '*' . EXT_CLASS)
    );
}

foreach ($testNames as $testName) {
    ConsoleScriptRunner::me()->execTest($testName);
}
