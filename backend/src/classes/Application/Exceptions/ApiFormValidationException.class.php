<?php

class ApiFormValidationException extends ApiException {
	public function __construct(Form $form, $message = "bad request", $code = 400, Exception $previous = null) {
		parent::__construct($message, $code, $previous);
        $formErrors = [];
        foreach ($form->getErrors() as $formField => $errorNumber) {
            $errorText = $form->getTextualErrorFor($formField);
            if (!$errorText) {
                switch ($errorNumber) {
                    case Form::MISSING: $errorText = 'missing'; break;
                    case Form::WRONG:   $errorText = 'wrong';   break;
                    default: $errorText = 'error #' . $errorNumber; break;
                }
            }
            $formErrors[$formField] = $errorText;
        }
        $this->setDetails($formErrors);
	}
}