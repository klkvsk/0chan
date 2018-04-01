<?php

class ApiException extends BaseException {
    /** @var mixed */
    protected $details = null;

    public function setDetails($details) {
        $this->details = $details;
        return $this;
    }

    public function getDetails() {
        return $this->details;
    }
} 