<?php

class MyataCaptchaProvider extends CaptchaProvider
{
    public function getCaptcha()
    {
        $img = imagecreate(200, 60);

        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 0, 0, 0);

        // This creates the captcha word
        $len = 7;

        $word = $this->generateCaptchaTextMarkov($len);
        $offsetX = 0;
        for ($i = 0; $i < $len; $i++) {
            $symbol = mb_substr($word, $i, 1);
            $symbol = iconv('UTF-8', 'cp1251', $symbol);
            imagettftext($img, mt_rand(28, 30), mt_rand(-20, 20), $offsetX + 5, mt_rand(32, 34)+10, $black, __DIR__ . '/captcha.ttf', $symbol);
            $offsetX += mt_rand(24, 28);
        }

        // Noise over the word
        foreach ([ $black, $white ] as $noiseColor) {
            imagesetstyle($img, array($noiseColor));
            imagesetthickness($img, 3);
            for ($i = 0; $i < 1; $i++) {
                $arcs = mt_rand(0, 360);
                imagearc($img, mt_rand(40, 160), mt_rand(0, 60), mt_rand(60, 80), mt_rand(10, 40), $arcs, $arcs + mt_rand(60, 270), IMG_COLOR_STYLED);
            }
        }

        /*$captcha_image_lineys = array(mt_rand(0, 29), mt_rand(0, 29), mt_rand(0, 29), mt_rand(0, 29), mt_rand(0, 29), mt_rand(0, 29), mt_rand(0, 29), mt_rand(0, 29), mt_rand(0, 29), mt_rand(0, 29));
        $captcha_image_lineye = array(mt_rand(0, 29), mt_rand(0, 29), mt_rand(0, 29), mt_rand(0, 29), mt_rand(0, 29), mt_rand(0, 29), mt_rand(0, 29), mt_rand(0, 29), mt_rand(0, 29), mt_rand(0, 29));
        for ($i = 0; $i <= 1; $i++) {
            imageline($captcha_image, $i*10+mt_rand(1, 3), $captcha_image_lineys[$i], $i*8+mt_rand(1, 3), $captcha_image_lineye[$i], $captcha_image_lcolor[mt_rand(0, 1)]);
            imageline($captcha_image, $i*10+mt_rand(1, 3), $captcha_image_lineys[$i], $i*8+mt_rand(1, 3), $captcha_image_lineye[$i], $captcha_image_lcolor[mt_rand(0, 1)]);
        }*/

        // now distort it
        $imgDist = imagecreatetruecolor(200, 60);
        imagefilledrectangle($imgDist, 0, 0, imagesx($img), imagesy($img), 0xFFFFFF);
        $freq1 = mt_rand(12, 18);
        $freq2 = mt_rand(60, 200);
        $strength = 4;
        for ($i = 0; $i < imagesx($imgDist); $i++) {
            for ($j = 0; $j < imagesy($imgDist); $j++) {
                $newX = intval($i + (sin($j/$freq1)+sin($i/$freq2)) * $strength);
                $newY = intval($j + (sin($i/$freq1)+sin($j/$freq2)) * $strength);
                if ($newY >= 0 && $newY < 60) {
                    $col_i = imagecolorat($img, $i, $j);
                    $color = imagecolorsforindex($img, $col_i);
                    $newColor = $color['red'] > 150 ? 0xFFFFFF : 0x000000;
                    imagesetpixel($imgDist, $newX, $newY, $newColor);
                }
            }
        }


        ob_start();
        imagepng($imgDist);
        $imgData = ob_get_clean();

        imagedestroy($imgDist);
        imagedestroy($img);

        return new Captcha(
            RandomUtils::makeString(16),
            'data:image/png;base64,' . base64_encode($imgData),
            $word
        );
    }

    public function validateCaptcha(Captcha $captcha, $answer)
    {
        return $captcha->getAnswer() === mb_strtoupper($answer);
    }

    function generateCaptchaTextMarkov($size) {
        $transitionMatrix = json_decode('{' .
            '"Г":["Л","В","А","О"],' .
            '"Л":["А","Ю","У","О"],' .
            '"А":["М","В","Н","Р","Т","Ч","З","Д"],' .
            '"В":["А","О","И"],' .
            '"С":["Е","Т","А","Л","И","У"],' .
            '"Е":["М","Р","П","Д","В","З","Ж","С","Н"],' .
            '"Р":["Ж","Д","Е","И","А","О"],' .
            '"Ж":["А","Н","Е"],' .
            '"Н":["Т","О","А","Е","Ы","И","Н"],' .
            '"Д":["И","А","К"],' .
            '"И":["М","И","Н","С","Р","Л","Е"],' .
            '"О":["Ж","С","Е","К","В","Р","Д","О","Т","П"],' .
            '"Т":["Ы","У","Е","А"],' .
            '"К":["Р","А"],' .
            '"П":["О","У","Р"],' .
            '"Ю":["О"],' .
            '"У":["Г","П","К","Д","Е"],' .
            '"Ч":["Е"],' .
            '"З":["В","Л"],' .
            '"М":["Я"],' .
            '"Я":["Т"]' .
            '}', true);
        $output = '';
        $prev = '';
        while (mb_strlen($output) < $size) {
            if ($prev && isset($transitionMatrix[$prev])) {
                $symbol = $transitionMatrix[$prev][rand(0, count($transitionMatrix[$prev]) - 1)];
            } else {
                $symbol = array_rand($transitionMatrix);
            }

            $output .= $symbol;
            $prev = $symbol;
        }
        return $output;

    }
}