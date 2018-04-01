<?php

class YandexCaptchaProvider extends CaptchaProvider {
	const YANDEX_CAPTCHA_KEY = 'cw.1.1.20140821T094621Z.c94676ed43f4f299.898d521fba32eaed6ce76c2658cb790b402247db';
	const LIFETIME = 600;

	/**
	 * @param string $method
	 * @param array $params
	 * @throws WrongArgumentException
	 * @throws WrongStateException
	 * @return SimpleXMLElement
	 */
	protected function makeRequest($method, array $params = []) {
		$params['key'] = self::YANDEX_CAPTCHA_KEY;
		$url = 'http://cleanweb-api.yandex.ru/1.0/' . $method . '?' . http_build_query($params);
		$result = file_get_contents($url);
		if (!$result) {
			throw new WrongArgumentException;
		}

		$resultXml = simplexml_load_string($result);
		if (!$resultXml instanceof SimpleXMLElement) {
			throw new WrongArgumentException;
		}

		if ($resultXml->getName() != ($method . '-result')) {
			throw new WrongStateException($result);
		}

		return $resultXml;
	}

	public function getCaptcha() {
		$resultXml = $this->makeRequest('get-captcha');

		return new Captcha(
			(string)$resultXml->captcha,
			(string)$resultXml->url
		);
	}

	/** @return bool */
	public function validateCaptcha(Captcha $captcha, $answer) {
		$resultXml = $this->makeRequest('check-captcha', ['captcha' => $captcha->getId(), 'value' => $answer]);
		$isValid = (bool)$resultXml->ok;
		return $isValid;
	}

}