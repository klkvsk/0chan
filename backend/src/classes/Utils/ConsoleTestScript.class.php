<?php

abstract class ConsoleTestScript extends ConsoleScript {
    const LOGFILE_PREFIX = 'test_';
    public function setUp() {}
    public function tearDown() {}

    public function api(HttpMethod $method, $path, $getParams = [], $postParams = []) {
        $request = HttpRequest::create()
            ->setUrl( HttpUrl::parse(PATH_WEB . $path) )
            ->setGet($getParams)
            ->setPost($postParams)
            ->setMethod($method)
            ->setHeaderVar('User-Agent', '0chan/test');

        $response = CurlHttpClient::create()
            ->send($request);

        $this->log('[API] Got response ' . $response->getStatus()->getId() . ' ' . $response->getStatus()->getName() . PHP_EOL
            . 'URL: ' . $request->getUrl()->toString() . PHP_EOL
            . 'GET: ' . UrlParamsUtils::toString($getParams). PHP_EOL
            . 'POST: ' . UrlParamsUtils::toString($postParams)
            , ConsoleMode::FG_BLUE);

        $this->log(
            $response->getBody(),
            $response->getStatus()->getId() == HttpStatus::CODE_200 ? ConsoleMode::FG_GREEN : ConsoleMode::FG_RED
        );

        return json_decode($response->getBody(), true);
    }

    public function getTestMethods() {
        $testMethodNames = [];
        $rc = new ReflectionClass($this);
        $methods = $rc->getMethods();
        foreach ($methods as $method) {
            if (substr($method->getName(), 0, 4) == 'test') {
                $testMethodNames []= $method->getName();
            }
        }
        return $testMethodNames;
    }

    public function run() {
        $this->setUp();

        $testMethodNames = $this->getTestMethods();
        foreach ($testMethodNames as $testMethodName) {
            try {
                $this->log('Running test: ' . static::class . '::' . $testMethodName . '()');
                $this->{$testMethodName}();
            } catch (Exception $e) {
                $this->log($e);
                break;
            }
        }

        $this->tearDown();
    }
}