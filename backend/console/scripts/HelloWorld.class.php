<?php

/**
 * Example of Console Script
 *
 * Class HelloWorld
 * @package Scripts
 */
class Script_HelloWorld extends ConsoleScript {

    /**
     * [./console]$ php run.php helloWorld
     */
    public function run() {
        $this->log('Hello, world!');
    }

}