<?php
require dirname(__FILE__) . '/../config.inc.php';

$crontab = [
    // script => run every X minutes
    'cleanUnpublished'      => 1,
    'cleanStorageTrash'     => 1,
    'cleanBoards'           => 1,
    'countRatings'          => 60,
    'updateBoardStats'      => 1,
];

$minutesNow = floor(time() / 60);
foreach ($crontab as $command => $everyMinutes) {
    if ($minutesNow % $everyMinutes === 0) {
        passthru('php run.php ' . $command);
    }
}
