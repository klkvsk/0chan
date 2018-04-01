<?php

class RedirectHttpResponse extends BaseHttpResponse {

    protected function __construct($url) {
        $this->headers['Location'] = $url;
    }

    public static function create($url) {
        return new self($url);
    }

    public function getStatus() {
        return new HttpStatus(HttpStatus::CODE_307);
    }
}