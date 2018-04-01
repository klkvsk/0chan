<?php
// system settings
error_reporting(E_ALL | E_STRICT);
setlocale(LC_CTYPE, "ru_RU.UTF8");
setlocale(LC_TIME, "ru_RU.UTF8");
date_default_timezone_set('Europe/Moscow');

define('PRODUCTION', getenv('PROD') == '1');
define('SALT', getenv('SALT'));
if (empty(SALT)) {
    die('you really should define some random SALT');
}

// paths
define('PATH_BASE', dirname(__FILE__).DIRECTORY_SEPARATOR);
define('PATH_SOURCE', PATH_BASE.'src'.DIRECTORY_SEPARATOR);
if (PRODUCTION) {
    define('PATH_WEB', 'https://0chan.hk/');
    $saveDebug = false;
    if (isset($_SERVER['HTTP_X_DEBUG']) && getenv('SAVE_DEBUG_KEY')
          && ($_SERVER['HTTP_X_DEBUG'] === getenv('SAVE_DEBUG_KEY'))
    ) {
        $saveDebug = true;
    }
    define('SAVE_DEBUG', $saveDebug);
    define('APCU_ENABLED', true);
} else {
//    define('PATH_WEB', 'http://192.168.99.100/');
    define('PATH_WEB', '//localhost/');
    define('SAVE_DEBUG', true);
    define('APCU_ENABLED', false);
}


// shared classes
define('PATH_CLASSES', 		PATH_SOURCE.'classes'.DIRECTORY_SEPARATOR);
define('PATH_CONTROLLERS', 	PATH_SOURCE.'controllers'.DIRECTORY_SEPARATOR);
define('PATH_TEMPLATES', 	PATH_SOURCE.'templates'.DIRECTORY_SEPARATOR);
define('PATH_SCRIPTS', 	    PATH_BASE.'console'.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR);
define('PATH_TESTS', 	    PATH_BASE.'console'.DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR);
define('PATH_LOGS', 	    '/var/www/logs/');

if (!file_exists(PATH_LOGS)) {
    mkdir(PATH_LOGS, 0777, true);
}

// onPHP init
//require PATH_BASE . '../onphp-framework/global.inc.php.tpl';
require PATH_BASE . 'vendor/autoload.php';
require PATH_BASE . 'vendor/onphp/onphp/global.inc.php.tpl';

define('PATH_STORAGE', realpath(PATH_BASE . '../storage') . DIRECTORY_SEPARATOR);

register_shutdown_function( "fatal_handler" );
function fatal_handler() {
    $error = error_get_last();
    if ($error !== NULL) {
        $errno = $error["type"];
        $errfile = $error["file"];
        $errline = $error["line"];
        $errstr = $error["message"];
        throw new ErrorException($errstr, $errno, 1, $errfile, $errline);
    }
}

// everything else
define('DEFAULT_ENCODING', 'UTF-8');
mb_internal_encoding(DEFAULT_ENCODING);
mb_regex_encoding(DEFAULT_ENCODING);

$dbLink = DB::spawn(
    PgSqlX::class,
    getenv('POSTGRES_USER'),
    getenv('POSTGRES_PASSWORD'),
    'db', // hostname
    getenv('POSTGRES_DB')
);

$dbLink->setEncoding(DEFAULT_ENCODING);

DBPool::me()->setDefault($dbLink);
DBPool::me()->addLink('main', $dbLink);


// magic_quotes_gpc must be off
define('__LOCAL_DEBUG__', !PRODUCTION);

Cache::setPeer(
//    PeclMemcached::create('cache')
    RedisCachePeer::create('cache')
);

Cache::setDefaultWorker(CommonDaoWorker::class);
