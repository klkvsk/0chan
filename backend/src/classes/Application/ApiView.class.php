<?php

class ApiView extends JsonView {
    protected $httpCode = HttpStatus::CODE_200;
    protected $headers = [
        'Content-Type' => 'application/json'
    ];

	public static function create() {
		$self = new self;
		$self->options = $self->options | JSON_UNESCAPED_UNICODE;
		return $self;
	}

    public function getHeaders()
    {
        return $this->headers;
	}

    public function setHeader($name, $value)
    {
        $this->headers[$name] = $value;
        return $this;
	}

    public function setHttpCode($httpCode)
    {
        $this->httpCode = $httpCode;
        return $this;
	}

    public function getHttpCode()
    {
        return $this->httpCode;
	}

} 