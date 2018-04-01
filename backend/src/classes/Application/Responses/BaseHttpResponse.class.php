<?php

abstract class BaseHttpResponse implements HttpResponse {
    protected $headers = array();

    protected function __construct() {}

    public function getHeader($name) {
        if ($this->hasHeader($name)) {
            return $this->headers[$name];
        }
        return null;
    }

    public function getHeaders() {
        return $this->headers;
    }

    public function hasHeader($name) {
        return isset($this->headers[$name]);
    }

    public function setHeader($name, $value) {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * @return HttpStatus
     **/
    public function getStatus() {
        return new HttpStatus(HttpStatus::CODE_200);
    }

    public function getReasonPhrase()
    {
        return $this->getStatus()->getName();
    }

    public function output() {
        header($this->getStatus()->toString());

        foreach ($this->getHeaders() as $header => $value) {
            header($header . ': ' . $value);
        }

        echo $this->getBody();
    }

    public function getBody() {
        return '';
    }


} 