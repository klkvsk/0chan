<?php

class ApiCaptchaController extends ApiBaseController
{
    /**
     * @return CaptchaProvider
     */
    public function getCaptchaProvider()
    {
        //return YandexCaptchaProvider::me();
//        return SecurimageCaptchaProvider::me();
        return MyataCaptchaProvider::me();
    }

    /**
     * @param null|string $captcha
     * @param null|string $answer
     * @return array
     */
    public function defaultAction($captcha = null, $answer = null)
    {
        $this->limit('captchaRequests', 600, 60, function () {
         //   throw new ApiForbiddenException('too many captcha requests');
        });

        $provider = $this->getCaptchaProvider();

        $captchaId = $captcha;
        /** @var Captcha|null $captcha */
        $captcha = CaptchaStorage::me()->get($captchaId);

        if ($answer || $captchaId) {
            $valid = false;
            if ($captcha instanceof Captcha) {
                if ($captcha->isChecked()) {
                    $valid = $captcha->isValid();
                } else {
                    $valid = $provider->validateCaptcha($captcha, $answer);
                    $captcha
                        ->setChecked(true)
                        ->setValid($valid);
                    CaptchaStorage::me()->put($captcha);
                }
            }

            return [
                'captcha' => $captchaId,
                'ok' => $valid
            ];

        } else {
            if (!$captcha) {
                $captcha = $provider->getCaptcha();
                CaptchaStorage::me()->put($captcha);
            }

            return [
                'captcha' => $captcha->getId(),
                'image' => $captcha->getImage()
            ];
        }
    }
}