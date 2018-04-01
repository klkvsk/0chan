<?php

class OkHttpResponse extends BaseHttpResponse {

    protected $body;

    protected function __construct($body, $headers) {
        $this->body = $body;
        $this->headers = array_merge($this->headers, $headers);
    }

    public static function create($body, array $headers = array()) {
        return new self($body, $headers);
    }

    public function getStatus() {
        return new HttpStatus(HttpStatus::CODE_200);
    }

    public function getBody() {
        return $this->body;
    }
}