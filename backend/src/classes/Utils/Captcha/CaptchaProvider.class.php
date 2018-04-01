<?php

abstract class CaptchaProvider extends Singleton {

	public static function me() {
		return Singleton::getInstance(get_called_class());
	}

	/** @return Captcha */
	abstract public function getCaptcha();

	/** @return bool */
	abstract public function validateCaptcha(Captcha $captcha, $answer);
	
} 