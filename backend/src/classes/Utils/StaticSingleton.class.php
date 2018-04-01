<?php

class StaticSingleton extends Singleton {

    /**
     * @return static
     */
    public static function me() {
        return Singleton::getInstance(static::class);
    }

}