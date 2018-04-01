<?php

class CaptchaStorage extends Singleton {

	const LIFETIME_BEFORE_CHECK = 180;
	const LIFETIME_AFTER_VALID = 60;

	/**
	 * @return self
	 */
	public static function me() {
		return Singleton::getInstance(__CLASS__);
	}

	protected function makeCacheKey($captchaId) {
		return '__captcha_storage__' . md5($captchaId);
	}

	public function put(Captcha $captcha) {
		$cacheKey = $this->makeCacheKey($captcha->getId());
		$ttl = 0;
		if ($captcha->isChecked() == false) {
			$ttl = self::LIFETIME_BEFORE_CHECK;
		} else if ($captcha->isValid()) {
			$ttl = self::LIFETIME_AFTER_VALID;
		}
		if ($ttl > 0) {
			Cache::me()->set($cacheKey, $captcha, $ttl);
		}
	}

	public function get($captchaId) {
		$cacheKey = $this->makeCacheKey($captchaId);

		return Cache::me()->get($cacheKey);
	}

	public function useCaptcha($captchaId) {
		$isValid = false;
		$captcha = self::get($captchaId);
		if ($captcha instanceof Captcha) {
			$isValid = $captcha->isValid();
			Cache::me()->delete($this->makeCacheKey($captchaId));
		}

		return $isValid;
	}

}