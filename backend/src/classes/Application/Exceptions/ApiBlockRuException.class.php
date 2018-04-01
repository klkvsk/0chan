<?php

class ApiBlockRuException extends ApiException {
	public function __construct($message = "not allowed here", $code = 403, Exception $previous = null) {
		parent::__construct($message, $code, $previous);
        $this->setDetails([ 'reason' => 'Нет доступа из РФ' ]);
	}
}