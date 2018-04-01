<?php

class ApiHttpResponse extends BaseHttpResponse {
    protected $httpStatus;
    protected $body;

    public static function create($body, array $headers = array(), $httpStatus = HttpStatus::CODE_200) {
        $self = new static;
        $self->body = $body;
        $self->headers = $headers;
        $self->httpStatus = $httpStatus instanceof HttpStatus ? $httpStatus : new HttpStatus($httpStatus);

        return $self;
    }

    public function getStatus() {
        return $this->httpStatus;
    }

    public function getBody() {
        return $this->body;
    }

}