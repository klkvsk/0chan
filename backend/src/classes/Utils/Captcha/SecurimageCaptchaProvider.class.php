<?php

class SecurimageCaptchaProvider extends CaptchaProvider {

	public function getCaptcha() {
        $id = md5(microtime());
        $img = new Securimage([
            'no_session' => true,
            'captchaId' => $id,
            'use_database' => false,
            'send_headers' => false,
            'no_exit' => true,
        ]);
        $img->charset = 'авгежзиклмнопрстуфхчшэюяАБВГДЕЖЗИКЛМНОПРСТУФХЧШЭЮЯ';
        $img->ttf_file = PATH_BASE . 'misc/OpenSans-Light.ttf';
        $img->image_type = Securimage::SI_IMAGE_JPEG;
        $img->num_lines = 5;
        $img->noise_level = 5;
	    ob_start();
	    $img->show();
	    $imgData = ob_get_clean();
		return new Captcha(
		    $id,
            'data:image/jpeg;base64,' . base64_encode($imgData),
            $img->getCode(false, true)
        );
	}

	/** @return bool */
	public function validateCaptcha(Captcha $captcha, $answer) {
        return mb_strtolower($captcha->getAnswer()) == mb_strtolower($answer);
	}

}