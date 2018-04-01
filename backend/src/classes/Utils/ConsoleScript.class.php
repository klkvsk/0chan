<?php

abstract class ConsoleScript {

    const LOGFILE_PREFIX = 'script_';

    protected $args = [];

    abstract public function run();

    public function log($message, $fgColor = null) {
        ConsoleScriptRunner::me()->log($message, $this->getLogFilename(), $fgColor);
    }

    protected function getLogFilename() {
        return PATH_LOGS . static::LOGFILE_PREFIX . str_replace('\\', '_', static::class) . '.' . Date::makeToday()->toDate() . '.log';
    }

    public function getArg($index)
    {
        if (isset($this->args[$index])) {
            return $this->args[$index];
        }
        return null;
    }

    /**
     * @return array
     */
    public function getArgs()
    {
        return $this->args;
    }

    /**
     * @param array $args
     * @return $this
     */
    public function setArgs($args)
    {
        $this->args = $args;
        return $this;
    }
}