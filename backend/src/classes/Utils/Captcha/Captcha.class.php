<?php

class Captcha {
	protected $id;
	protected $image;
	protected $answer;
	protected $checked = false;
	protected $valid = false;

	public function __construct($id, $image, $answer = null) {
		$this->id = $id;
		$this->image = $image;
		$this->answer = $answer;
	}

	public function getId() {
		return $this->id;
	}

	public function getImage() {
		return $this->image;
	}

	public function getAnswer() {
		return $this->answer;
	}

	public function isChecked() {
		return $this->checked;
	}

	public function isValid() {
		return $this->valid;
	}

	public function setChecked($isChecked) {
		$this->checked = (bool)$isChecked;
		return $this;
	}

	public function setValid($isValid) {
		$this->valid = (bool)$isValid;
		return $this;
	}

} 