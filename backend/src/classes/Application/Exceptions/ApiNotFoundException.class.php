<?php

class ApiNotFoundException extends ApiException {
	public function __construct($message = "not found", $code = 404, Exception $previous = null) {
		parent::__construct($message, $code, $previous);
	}
}