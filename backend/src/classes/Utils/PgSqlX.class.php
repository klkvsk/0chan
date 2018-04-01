<?php

class PgSqlX extends PgSQL
{
    protected static $debugQueries = [];

    public static function getQueriesForDebug()
    {
        return self::$debugQueries;
    }

    public function queryRaw($queryString)
    {
        if (SAVE_DEBUG) {
            $tStart = microtime(true);
        }

        $result = parent::queryRaw($queryString);

        if (SAVE_DEBUG) {
            $time = (microtime(true) - $tStart) * 1000;
            self::$debugQueries []= [
                'query' => $queryString,
                'timeMs' => $time,
                //  'stack' => debug_backtrace()
            ];
            if (php_sapi_name() == 'cli') {
                //error_log('SQL> ' . $queryString);
            }
        }

        return $result;
    }

}