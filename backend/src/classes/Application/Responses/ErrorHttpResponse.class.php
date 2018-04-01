<?php

class ErrorHttpResponse extends BaseHttpResponse {

    protected $error = null;

    protected function __construct($error = null) {
        $this->headers['Content-Type'] = 'text/plain';
        $this->error = $error;
    }

    public static function create($error = null) {
        return new self($error);
    }

    public function getStatus() {
        return new HttpStatus(HttpStatus::CODE_500);
    }

    public function getBody() {
		$body = $this->error ?: 'Infernal server error';
		return $body;
    }


} 