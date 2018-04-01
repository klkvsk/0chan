<?php

class ApiUnauthorizedException extends ApiException {
	public function __construct($message = "auth required", $code = 403, Exception $previous = null) {
		parent::__construct($message, $code, $previous);
        $this->setDetails([ 'require' => 'auth' ]);
    }
}