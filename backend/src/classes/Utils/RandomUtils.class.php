<?php

class RandomUtils {

    const NUMBERS = '0123456789';
    const LETTERS = 'abcdefghijklmnopqrstuvwxyz';
    const HEX = '0123456789abcdef';
    const ALPHANUM = 'abcdefghijklmnopqrstuvwxyz0123456789';

    /**
     * @return RandomSource
     */
    protected static function makeRandomSource() {
        return MtRandomSource::me();
//        return new FileRandomSource('/dev/urandom');
    }

    public static function makeString($length, $alphabet = self::ALPHANUM) {
        $source = self::makeRandomSource();
        $alphabetLength = strlen($alphabet);
        $random = $source->getBytes($length);
        $string = '';
        for ($i = 0; $i < $length; $i++) {
            $string .= $alphabet[ord($random[$i]) % $alphabetLength];
        }
        return $string;
    }
} 