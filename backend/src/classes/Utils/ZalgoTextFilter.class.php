<?php

class ZalgoTextFilter extends BaseFilter
{
    public static function me()
    {
        return Singleton::getInstance(__CLASS__);
    }

    public function apply($value)
    {
        return preg_replace("~(?:[\p{M}]{1})([\p{M}])+?~uis","", $value);
    }
}