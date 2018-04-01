<?php
$tStart = microtime(true);
require '../config.inc.php';
$tInit = microtime(true);

$errorReporting = null;
if (PRODUCTION) {
    $sentryEndpoint = getenv('SENTRY_BACKEND');
    if ($sentryEndpoint) {
        $errorReporting = new Raven_Client($sentryEndpoint);
        try {
            $errorReporting->install();
        } catch (Raven_Exception $e) {
            error_log($e->getMessage());
        }
    }
}

try {
	$request = HttpRequest::createFromGlobals();
    $response = App::me()->run($request);
} catch (Throwable $e) {
    if ($errorReporting) {
        $errorReporting->captureException($e);
    }

    $error = '';
    while ($e instanceof Throwable) {
        $error .=
            get_class($e) . ': ' . $e->getMessage() . PHP_EOL
            . $e->getTraceAsString() . PHP_EOL
            . PHP_EOL . PHP_EOL
        ;
        $e = $e->getPrevious();
    }

    error_log($error);

    $response = ErrorHttpResponse::create(__LOCAL_DEBUG__ ? $error : '');
}

if (SAVE_DEBUG) {
    $debugId = uniqid();
    $queries = PgSqlX::getQueriesForDebug();
    $totalQueriesTime = 0;
    foreach ($queries as $query) {
        $totalQueriesTime += $query['timeMs'];
    }
    $debugInfo = [
        'totalTime' => (microtime(true) - $tStart) * 1000,
        'totalTimeAfterInit' => (microtime(true) - $tInit) * 1000,
        'totalQueries' => count($queries),
        'totalQueriesTime' => $totalQueriesTime,
        'queries' => $queries
    ];
    Cache::me()->set('debug_' . $debugId, $debugInfo);
    $response->setHeader('X-Debug-ID', $debugId);
}

$response
    ->setHeader('Access-Control-Allow-Origin' , '*')
    ->setHeader('Access-Control-Allow-Methods', 'GET, POST, DELETE')
    ->setHeader('Access-Control-Allow-Headers', 'Origin, X-Requested-With, X-Session, Content-Type, Accept')
    ->setHeader('Access-Control-Expose-Headers', 'X-Session')
    ->output();