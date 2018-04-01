<?php

class ConsoleScriptRunner extends Singleton {

    /** @return static */
    public static function me() {
        return Singleton::getInstance(__CLASS__);
    }

    public function execScript($scriptName, $args = []) {
        $this->exec($scriptName, PATH_SCRIPTS, 'Script_', $args);
    }

    public function execTest($testName, $args = []) {
        $this->exec($testName, PATH_TESTS, '', $args);
    }

    protected function exec($scriptName, $scriptsPath, $classPrefix, $args = []) {
        try {
            $scriptName = ucfirst($scriptName);
            $fileName = $scriptsPath . $scriptName . EXT_CLASS;
            Assert::isTrue(file_exists($fileName), 'file ' . $fileName . ' does not exist');
            $className = $classPrefix . $scriptName;

            require $fileName;
            Assert::classExists($className, 'class ' . $className . ' was not defined in file ' . $fileName);

            $script = new $className();
            if ($script instanceof ConsoleScript) {
                $script->setArgs($args);
                $this->log('Starting: ' . $className);
                $script->run();
                $this->log('Finished: ' . $className);
            } else {
                throw new WrongArgumentException('class ' . $className . ' is not instance of ConsoleScript');
            }

        } catch (Exception $e) {
            $this->log($e);
            $this->log('Stopped due to error:' . $className);
        }
    }

    public function log($message, $filename = null, $fgColor = null) {
        if ($message instanceof Exception) {
            $e = $message;
            $message = '';
            while ($e instanceof Exception) {
                $message .= get_class($e);
                if ($e->getCode()) $message .= ' [code: ' . $e->getCode() . ']';
                if ($e->getMessage()) $message .= ': ' . $e->getMessage();
                $message .= PHP_EOL . $e->getTraceAsString();
                $e = $e->getPrevious();
            }

        } else if ($message instanceof Stringable) {
            $message = $message->toString();

        } else if (!is_scalar($message)) {
            $message = print_r($message, true);
        }

        $message = '[' . Timestamp::makeNow()->toString() . '] ' . $message . PHP_EOL;
        $message = str_replace(PHP_EOL, "\r\n", $message);

        if ($filename) {
            file_put_contents($filename, $message, FILE_APPEND);
        }
        if ($fgColor) {
            $message =
                chr(0x1B)
                .'['.ConsoleMode::ATTR_RESET_ALL.';'
                .$fgColor.'m'
                .$message
                .chr(0x1B) .'[0m';
        }
        echo $message;
    }

}