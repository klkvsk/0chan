<?php

class ApiBadRequestException extends ApiException {
	public function __construct($message = "bad request", $code = 400, Exception $previous = null) {
		parent::__construct($message, $code, $previous);
	}
}